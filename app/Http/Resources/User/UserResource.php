<?php

namespace App\Http\Resources\User;

use App\Models\UserFollow;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'username' => $this->username,
            'name' => $this->name,
            'tagline' => $this->tagline,
            'bio' => $this->bio,
            'image' => $this->image,
            'cover_image' => $this->cover_image,
            'followers' => $this->followers,
            'follows' => $this->follows,
            'created_at' => $this->created_at,
            'isFollowing' => $this->isFollowing(),
            'connectionStatus' => $this->connectionStatus(),
            'remainingTime' => $this->getRemainingDay(),
        ];
    }

    /**
     * @return bool
     */
    private function isFollowing(): bool
    {
        $followers = collect(auth()->user()->followings);
        if ($followers->count() > 0) {
            return $followers->contains('id', $this->id);
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    private function connectionStatus(): string
    {
        $connection = $this->getConnection();

        if ($connection?->type === 'connected') {
            $status = 'connected';
        } elseif ($connection?->withdraw_at) {
            $status = 'blocked';
        } elseif ($connection?->type === 'pending') {
            $status = 'pending';
        } elseif ($connection?->type === 'requested') {
            $status = 'requested';
        } else {
            $status = 'notConnected';
        }
        return $status;
    }

    /**
     * @return mixed
     */
    private function getRemainingDay(): mixed
    {
        $connection = $this->getConnection();

        if ($connection?->withdraw_at) {
            return $connection->withdraw_at;
        } else {
            return null;
        }
    }

    /**
     * @return mixed
     */
    private function getConnection(): mixed
    {
        $collections1 = collect($this->connection1)->merge($this->connection2);
        $collections2 = collect($this->connection3)->merge($this->connection4);
        $auth_id = auth()->id();
        $user_id = $this->id;

        $connection = $collections1->first(function ($item) use ($auth_id, $user_id) {
            return ($item->user_id === $auth_id && $item->friend_id === $user_id) || ($item->friend_id === $auth_id && $item->user_id === $user_id);
        });
        if (empty($connection)) {
            $connection = $collections2->first(function ($item) use ($auth_id, $user_id) {
                return $item->user_id === $auth_id && $item->friend_id === $user_id;
            });
        }

        return $connection;
    }
}
