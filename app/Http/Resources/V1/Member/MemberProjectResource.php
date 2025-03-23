<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Member;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'client' => $this->whenLoaded('client', function () {
                if (!$this->client) {
                    return null;
                }
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                ];
            }),
            'client_id' => $this->client_id,
            'is_archived' => $this->is_archived,
            'billable_rate' => $this->billable_rate,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Üyeye özel proje ilişki bilgileri
            'member_joined_at' => $this->whenPivotLoaded('project_members', function () {
                return $this->pivot->created_at?->toIso8601String();
            }),
            'member_billable_rate' => $this->whenPivotLoaded('project_members', function () {
                return $this->pivot->billable_rate;
            }),
        ];
    }
}
