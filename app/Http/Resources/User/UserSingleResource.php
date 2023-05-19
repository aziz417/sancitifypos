<?php

namespace App\Http\Resources\User;

use App\Models\UserFollow;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSingleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'username' => $this->username,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->name,
            'bio' => $this->bio,
            'image' => $this->image,
            'cover_image' => $this->cover_image,
            'created_at' => $this->created_at,
        ];
    }
}
