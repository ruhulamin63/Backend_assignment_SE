<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'type' => $this->type,
            'phone' => $this->phone,
            'role' => $this->roles ? $this->roles->pluck('name') : '',
            'role_id' => $this->roles ? $this->roles->pluck('id') : '',
            'permissions' => $this->roles ? $this->getAllPermissions()->pluck('name') : '',
            'photo_url' => $this->photo_url,
        ];
    }
}
