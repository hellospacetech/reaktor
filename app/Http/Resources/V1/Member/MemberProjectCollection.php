<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Member;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MemberProjectCollection extends ResourceCollection
{
    /**
     * Faturalama oranlarının gösterilip gösterilmeyeceğini belirler
     *
     * @var bool
     */
    protected bool $showBillableRates;
    
    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @param  bool  $showBillableRates
     * @return void
     */
    public function __construct($resource, bool $showBillableRates = false)
    {
        parent::__construct($resource);
        $this->showBillableRates = $showBillableRates;
        $this->collects = MemberProjectResource::class;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $projects = $this->collection->map(function ($project) use ($request) {
            $projectArray = (new MemberProjectResource($project))->toArray($request);
            
            // Sadece gerekli olduğunda faturalama oranlarını göster
            if (!$this->showBillableRates) {
                unset($projectArray['billable_rate']);
                unset($projectArray['member_billable_rate']);
            }
            
            return $projectArray;
        });
        
        return [
            'data' => $projects,
            'meta' => [
                'show_billable_rates' => $this->showBillableRates,
                'total_count' => $this->collection->count(),
            ],
        ];
    }
}
