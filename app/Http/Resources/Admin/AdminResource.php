<?php

namespace App\Http\Resources\Admin;

use App\Models\UserFollow;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class AdminResource extends JsonResource
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
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'created_at' => $this->created_at,
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at ? true : false,
            'role' => collect($this->roles)->pluck('name')->first(),
        ];
    }
}
