<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\Role;
use App\Events\MemberMadeToPlaceholder;
use App\Exceptions\Api\CanNotRemoveOwnerFromOrganization;
use App\Exceptions\Api\ChangingRoleOfPlaceholderIsNotAllowed;
use App\Exceptions\Api\ChangingRoleToPlaceholderIsNotAllowed;
use App\Exceptions\Api\EntityStillInUseApiException;
use App\Exceptions\Api\OnlyOwnerCanChangeOwnership;
use App\Exceptions\Api\OnlyPlaceholdersCanBeMergedIntoAnotherMember;
use App\Exceptions\Api\OrganizationNeedsAtLeastOneOwner;
use App\Exceptions\Api\ThisPlaceholderCanNotBeInvitedUseTheMergeToolInsteadException;
use App\Exceptions\Api\UserIsAlreadyMemberOfOrganizationApiException;
use App\Exceptions\Api\UserNotPlaceholderApiException;
use App\Http\Requests\V1\Member\MemberIndexRequest;
use App\Http\Requests\V1\Member\MemberMergeIntoRequest;
use App\Http\Requests\V1\Member\MemberUpdateRequest;
use App\Http\Resources\V1\Member\MemberCollection;
use App\Http\Resources\V1\Member\MemberResource;
use App\Models\Member;
use App\Models\Organization;
use App\Service\BillableRateService;
use App\Service\InvitationService;
use App\Service\MemberService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\TimeEntry;
use App\Http\Resources\V1\TimeEntry\TimeEntryCollection;
use App\Models\Project;
use App\Http\Resources\V1\Project\ProjectCollection;
use App\Http\Resources\V1\Member\MemberProjectCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MemberController extends Controller
{
    protected function checkPermission(Organization $organization, string $permission, ?Member $member = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($member !== null && $member->organization_id !== $organization->id) {
            throw new AuthorizationException('Member does not belong to organization');
        }
    }

    /**
     * List all members of an organization
     *
     * @return MemberCollection<MemberResource>
     *
     * @throws AuthorizationException
     *
     * @operationId getMembers
     */
    public function index(Organization $organization, MemberIndexRequest $request): MemberCollection
    {
        $this->checkPermission($organization, 'members:view');

        $members = Member::query()
            ->whereBelongsTo($organization, 'organization')
            ->with(['user'])
            ->paginate(config('app.pagination_per_page_default'));

        return MemberCollection::make($members);
    }

    /**
     * Update a member of the organization
     *
     * @throws AuthorizationException
     * @throws OrganizationNeedsAtLeastOneOwner
     * @throws OnlyOwnerCanChangeOwnership
     * @throws ChangingRoleToPlaceholderIsNotAllowed
     * @throws ChangingRoleOfPlaceholderIsNotAllowed
     *
     * @operationId updateMember
     */
    public function update(Organization $organization, Member $member, MemberUpdateRequest $request, BillableRateService $billableRateService, MemberService $memberService): JsonResource
    {
        $this->checkPermission($organization, 'members:update', $member);

        if ($request->has('billable_rate') && $member->billable_rate !== $request->getBillableRate()) {
            $member->billable_rate = $request->getBillableRate();

            $billableRateService->updateTimeEntriesBillableRateForMember($member);
        }
        if ($request->has('role') && $member->role !== $request->getRole()->value) {
            $newRole = $request->getRole();
            $allowOwnerChange = $this->hasPermission($organization, 'members:change-ownership');
            $memberService->changeRole($member, $organization, $newRole, $allowOwnerChange);
        }
        $member->save();

        return new MemberResource($member);
    }

    /**
     * Remove a member of the organization.
     *
     * @throws AuthorizationException|EntityStillInUseApiException|CanNotRemoveOwnerFromOrganization
     *
     * @operationId removeMember
     */
    public function destroy(Organization $organization, Member $member, MemberService $memberService): JsonResponse
    {
        $this->checkPermission($organization, 'members:delete', $member);

        $memberService->removeMember($member, $organization);

        return response()
            ->json(null, 204);
    }

    /**
     * Make a member a placeholder member
     *
     * @throws AuthorizationException|CanNotRemoveOwnerFromOrganization|ChangingRoleOfPlaceholderIsNotAllowed
     *
     * @operationId makePlaceholder
     */
    public function makePlaceholder(Organization $organization, Member $member, MemberService $memberService): JsonResponse
    {
        $this->checkPermission($organization, 'members:make-placeholder', $member);

        if ($member->role === Role::Owner->value) {
            throw new CanNotRemoveOwnerFromOrganization;
        }
        if ($member->role === Role::Placeholder->value) {
            throw new ChangingRoleOfPlaceholderIsNotAllowed;
        }

        $memberService->makeMemberToPlaceholder($member);

        MemberMadeToPlaceholder::dispatch($member, $organization);

        return response()->json(null, 204);
    }

    /**
     * @throws AuthorizationException
     * @throws OnlyPlaceholdersCanBeMergedIntoAnotherMember
     * @throws \Throwable
     *
     * @operationId mergeMember
     */
    public function mergeInto(Organization $organization, Member $member, MemberMergeIntoRequest $request, MemberService $memberService): JsonResponse
    {
        $this->checkPermission($organization, 'members:merge-into', $member);

        $user = $member->user;
        if ($member->role !== Role::Placeholder->value || ! $user->is_placeholder) {
            throw new OnlyPlaceholdersCanBeMergedIntoAnotherMember;
        }
        $memberTo = Member::findOrFail($request->getMemberId());

        DB::transaction(function () use ($organization, $member, $user, $memberTo, $memberService): void {
            $memberService->assignOrganizationEntitiesToDifferentMember($organization, $member, $memberTo);
            $member->delete();
            $user->delete();
        });

        return response()->json(null, 204);
    }

    /**
     * Invite a placeholder member to become a real member of the organization
     *
     * @throws AuthorizationException
     * @throws UserNotPlaceholderApiException
     * @throws UserIsAlreadyMemberOfOrganizationApiException
     * @throws ThisPlaceholderCanNotBeInvitedUseTheMergeToolInsteadException
     *
     * @operationId invitePlaceholder
     */
    public function invitePlaceholder(Organization $organization, Member $member, InvitationService $invitationService): JsonResponse
    {
        $this->checkPermission($organization, 'members:invite-placeholder', $member);
        $user = $member->user;

        if (! $user->is_placeholder) {
            throw new UserNotPlaceholderApiException;
        }

        if (Str::endsWith($user->email, '@solidtime-import.test')) {
            throw new ThisPlaceholderCanNotBeInvitedUseTheMergeToolInsteadException;
        }

        $invitationService->inviteUser($organization, $user->email, Role::Employee);

        return response()->json(null, 204);
    }

    /**
     * Belirli bir üyenin detaylarını gösterir
     * 
     * @param Organization $organization
     * @param Member $member
     * @return JsonResource
     * 
     * @throws AuthorizationException
     * 
     * @operationId getMemberDetails
     */
    public function showDetails(Organization $organization, Member $member): JsonResource
    {
        $this->checkPermission($organization, 'members:view:detailed', $member);
        
        $memberData = $member->load(['user', 'user.projectMembers.project']);
        
        return new MemberResource($memberData);
    }

    /**
     * Belirli bir üyenin zaman kayıtlarını listeler
     * 
     * @param Organization $organization
     * @param Member $member
     * @param Request $request
     * @return JsonResource
     * 
     * @throws AuthorizationException
     * 
     * @operationId getMemberTimeEntries
     */
    public function memberTimeEntries(Organization $organization, Member $member, Request $request): JsonResource
    {
        $this->checkPermission($organization, 'members:view:reports', $member);
        
        // Filtreleme parametreleri
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $query = TimeEntry::query()
            ->where('user_id', $member->user_id)
            ->whereBelongsTo($organization, 'organization')
            ->with(['project', 'task', 'tags'])
            ->orderBy('created_at', 'desc');
        
        if ($startDate) {
            $query->whereDate('start_time', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('start_time', '<=', $endDate);
        }
        
        $timeEntries = $query->paginate(config('app.pagination_per_page_default'));
        
        return new TimeEntryCollection($timeEntries);
    }

    /**
     * Belirli bir üyenin banka hesaplarını listeler
     * 
     * @param Organization $organization
     * @param Member $member
     * @return ResourceCollection
     * 
     * @throws AuthorizationException
     * 
     * @operationId getMemberBankAccounts
     */
    public function memberBankAccounts(Organization $organization, Member $member): ResourceCollection
    {
        $this->checkPermission($organization, 'members:view:detailed', $member);
        
        $bankAccounts = $member->user->bankAccounts()
            ->with('bank')
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return JsonResource::collection($bankAccounts);
    }

    /**
     * Get projects for a member.
     *
     * @param \App\Models\Organization $organization
     * @param \App\Models\Member $member
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\JsonResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     *
     * @operationId getMemberProjects
     */
    public function memberProjects(Organization $organization, Member $member, Request $request): JsonResource
    {
        $this->checkPermission($organization, 'members:view:projects', $member);

        // Pivot bilgilerini de içerecek şekilde projeleri getir
        $projects = $member->projects()
            ->with(['client'])
            ->get();

        return new MemberProjectCollection($projects, true);
    }
}
