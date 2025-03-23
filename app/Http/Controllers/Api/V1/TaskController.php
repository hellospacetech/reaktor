<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\EntityStillInUseApiException;
use App\Http\Requests\V1\Task\TaskIndexRequest;
use App\Http\Requests\V1\Task\TaskStoreRequest;
use App\Http\Requests\V1\Task\TaskUpdateRequest;
use App\Http\Requests\V1\Task\TaskUpdateStatusRequest;
use App\Http\Resources\V1\Task\TaskCollection;
use App\Http\Resources\V1\Task\TaskResource;
use App\Models\Organization;
use App\Models\Task;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use App\Enums\TaskStatus;

class TaskController extends Controller
{
    protected function checkPermission(Organization $organization, string $permission, ?Task $task = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($task !== null && $task->organization_id !== $organization->id) {
            throw new AuthorizationException('Task does not belong to organization');
        }
    }

    /**
     * Get tasks
     *
     * @return TaskCollection<TaskResource>
     *
     * @throws AuthorizationException
     *
     * @operationId getTasks
     */
    public function index(Organization $organization, TaskIndexRequest $request): TaskCollection
    {
        $this->checkPermission($organization, 'tasks:view');
        $canViewAllTasks = $this->hasPermission($organization, 'tasks:view:all');
        $user = $this->user();

        $projectId = $request->input('project_id');

        $query = Task::query()
            ->whereBelongsTo($organization, 'organization');

        if ($projectId !== null) {
            $query->where('project_id', '=', $projectId);
        }

        if (! $canViewAllTasks) {
            $query->visibleByEmployee($user);
        }
        $doneFilter = $request->getFilterDone();
        if ($doneFilter === 'true') {
            $query->whereNotNull('done_at');
        } elseif ($doneFilter === 'false') {
            $query->whereNull('done_at');
        }

        $tasks = $query->paginate(config('app.pagination_per_page_default'));

        return new TaskCollection($tasks);
    }

    /**
     * Get task details
     *
     * @throws AuthorizationException
     *
     * @operationId getTask
     */
    public function show(Organization $organization, Task $task): JsonResource
    {
        $hasViewPermission = $this->hasPermission($organization, 'tasks:view');
        $hasViewDetailsPermission = $this->hasPermission($organization, 'tasks:view:details');
        
        if (!$hasViewPermission && !$hasViewDetailsPermission) {
            throw new AuthorizationException('You do not have permission to view task details');
        }
        
        if ($task->organization_id !== $organization->id) {
            throw new AuthorizationException('Task does not belong to organization');
        }
        
        // Eğer detay gösterme izni yoksa, sadece basit görevleri görebilir kullanıcı
        $canViewAllTasks = $this->hasPermission($organization, 'tasks:view:all');
        if (!$canViewAllTasks) {
            $user = $this->user();
            $isVisible = Task::where('id', $task->id)
                ->visibleByEmployee($user)
                ->exists();
            
            if (!$isVisible) {
                throw new AuthorizationException('You do not have permission to view this task');
            }
        }
        
        return new TaskResource($task);
    }

    /**
     * Create task
     *
     * @throws AuthorizationException
     *
     * @operationId createTask
     */
    public function store(Organization $organization, TaskStoreRequest $request): JsonResource
    {
        $this->checkPermission($organization, 'tasks:create');
        $task = new Task;
        $task->name = $request->input('name');
        $task->description = $request->input('description');
        $task->project_id = $request->input('project_id');
        
        // Status değerini ata
        if ($request->has('status')) {
            $task->status = TaskStatus::fromValue($request->getStatus());
        } else {
            $task->status = TaskStatus::Active();
        }
        
        if ($this->canAccessPremiumFeatures($organization) && $request->has('estimated_time')) {
            $task->estimated_time = $request->getEstimatedTime();
        }
        $task->organization()->associate($organization);
        $task->save();

        return new TaskResource($task);
    }

    /**
     * Update task
     *
     * @throws AuthorizationException
     *
     * @operationId updateTask
     */
    public function update(Organization $organization, Task $task, TaskUpdateRequest $request): JsonResource
    {
        $this->checkPermission($organization, 'tasks:update', $task);
        
        // Genel güncelleme işlemleri
        $task->name = $request->input('name');
        $task->description = $request->input('description');
        if ($this->canAccessPremiumFeatures($organization) && $request->has('estimated_time')) {
            $task->estimated_time = $request->getEstimatedTime();
        }
        
        $task->save();

        return new TaskResource($task);
    }
    
    /**
     * Update task status
     * 
     * @throws AuthorizationException
     * 
     * @operationId updateTaskStatus
     */
    public function updateStatus(Organization $organization, Task $task, TaskUpdateStatusRequest $request): JsonResource
    {
        // Organizasyona ait mi kontrolü
        if ($task->organization_id !== $organization->id) {
            throw new AuthorizationException('Task does not belong to organization');
        }
        
        $newStatus = $request->getStatus();
        
        if ($newStatus === TaskStatus::InternalTest && ($task->status->is(TaskStatus::Active) || $task->status->is(TaskStatus::Done))) {
            // Dahili test olarak işaretleme izni
            $this->checkPermission($organization, 'tasks:mark-as-internal-test');
            $task->status = TaskStatus::InternalTest();
            // Eğer task tamamlanmış durumundan geliyorsa, done_at değerini sıfırlayalım
            if ($task->status->is(TaskStatus::Done)) {
                $task->done_at = null;
            }
        } 
        elseif ($newStatus === TaskStatus::Done && $task->status->is(TaskStatus::InternalTest)) {
            // Tamamlandı olarak işaretleme izni
            $this->checkPermission($organization, 'tasks:mark-as-done');
            $task->status = TaskStatus::Done();
            $task->done_at = Carbon::now();
        }
        elseif ($newStatus === TaskStatus::Active) {
            // Active durumuna geçiş için tasks:update VEYA tasks:mark-as-internal-test izinlerinden 
            // herhangi birine sahip olma kontrolü
            $hasUpdatePermission = $this->hasPermission($organization, 'tasks:update');
            $hasMarkAsInternalTestPermission = $this->hasPermission($organization, 'tasks:mark-as-internal-test');
            
            if (!$hasUpdatePermission && !$hasMarkAsInternalTestPermission) {
                throw new AuthorizationException('You do not have permission to change task status to active');
            }
            
            $task->status = TaskStatus::Active();
            $task->done_at = null;
        }
        else {
            throw new AuthorizationException('Invalid status transition');
        }
     
        $task->save();
        
        return new TaskResource($task);
    }

    /**
     * Delete task
     *
     * @throws AuthorizationException|EntityStillInUseApiException
     *
     * @operationId deleteTask
     */
    public function destroy(Organization $organization, Task $task): JsonResponse
    {
        $this->checkPermission($organization, 'tasks:delete', $task);

        if ($task->timeEntries()->exists()) {
            throw new EntityStillInUseApiException('task', 'time_entry');
        }

        $task->delete();

        return response()
            ->json(null, 204);
    }
}
