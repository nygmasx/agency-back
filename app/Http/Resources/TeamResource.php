<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'subdomain' => $this->subdomain,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'settings' => $this->settings,
            'members_count' => $this->whenCounted('members'),
            'my_role' => $this->when(
                $request->user(),
                fn () => $this->getMemberRole($request->user())?->value
            ),
            'onboarding_completed' => (bool) $this->onboarding_completed,
            'business_type' => $this->business_type,
            'client_count' => $this->client_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
