<?php

namespace App\Http\Resources\Admin;

use App\Models\TenancyPermission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class AuthAdminResource extends JsonResource
{
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
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'username' => $this->username,
            'avatar' => $this->avatar,
            'role' => collect($this->getRoleNames())->first(),
            'permissions' => $this->getPermissions(),
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at ? true : false,
        ];
    }

    /**
     * @return array
     */
    public function getPermissions(): array
    {
        $permissions = [];
        foreach (TenancyPermission::all() as $permission) {
            if (Auth::user()->can($permission->name)) {
                $permissions[] = $permission->name;
            }
        }
        return $permissions;
    }
}
