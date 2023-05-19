<?php

namespace App\Traits;

use App\Models\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

trait ConnectionHelper
{
    /**
     * @param string $type
     * @return Builder[]|Collection
     */
    protected function getConnectedIds(string $type = 'connected'): Collection|array
    {
        $authId = auth()->id();

        return Connection::query()
            ->selectRaw('case when user_id = ? then friend_id else user_id end as user_id', [$authId])
            ->whereRaw('? in (user_id, friend_id)', [$authId])
            ->where('type', '=', $type)
            ->get();
    }

    /**
     * @param int $userId
     * @return mixed
     */
    protected function getConnectedId(int $userId): mixed
    {
        $authId = auth()->id();

        $connection = Connection::query()
            ->select('id')
            ->where(function ($q) use ($authId, $userId) {
                $q->where('user_id', '=', $authId)
                    ->where('friend_id', '=', $userId);
            })
            ->orWhere(function ($q) use ($authId, $userId) {
                $q->where('user_id', '=', $userId)
                    ->where('friend_id', '=', $authId);
            })
            ->first();

        return $connection?->id;
    }

    /**
     * @return array
     */
    protected function getConnections(): array
    {
        $authId = auth()->id();

        return Connection::query()
            ->where('type', '=', 'connected')
            ->where(function ($q) use ($authId) {
                $q->where('user_id', '=', $authId)
                    ->orWhere('friend_id', '=', $authId);
            })
            ->pluck('id')
            ->toArray();
    }
}
