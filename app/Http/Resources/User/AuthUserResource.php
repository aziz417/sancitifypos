<?php

namespace App\Http\Resources\User;

use App\Traits\ConnectionHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    use ConnectionHelper;

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
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
            'image' => $this->image,
            'cover_image' => $this->cover_image,
            'created_at' => $this->created_at,
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at ? true : false,
            'role' => collect($this->roles)->pluck('name')->first(),
        ];
    }
}
