<?php

namespace App\Http\Controllers\Admin;

use Throwable;
use App\Models\User;
use App\Models\TenancyRole;
use Illuminate\Http\Request;
use App\Models\GroupModerator;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Lang;
use Illuminate\Auth\Events\Registered;
use App\Http\Resources\Admin\AdminResource;
use App\Http\Resources\Admin\AdminEditResource;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Resources\Admin\AdminSingleResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminController extends Controller
{

    /**
     * Get all users.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('view user');

        $query = $request->query('query');
        $sortBy = $request->query('sortBy');
        $direction = $request->query('direction');
        $per_page = $request->query('per_page', 10);
        $roles = TenancyRole::whereIn('name', ['admin', 'super-admin'])->get();
        $users = User::with('roles')
            ->role($roles)
            ->latest();
        if ($query) {
            $users = User::search($query);
        }
        if ($sortBy) {
            $users = User::with('roles')
                ->role($roles)
                ->orderBy($sortBy, $direction);
        }
        if (!auth()->user()->hasRole('super-admin')) {
            $users = $users->role(TenancyRole::whereNotIn('name', ['super-admin'])->get());
        }
        if ($per_page === '-1') {
            $results = $users->get();
            $users = new LengthAwarePaginator($results, $results->count(), -1);
        } else {
            $users = $users->paginate($per_page);
        }
        return AdminResource::collection($users);
    }

    /**
     * Store new user into database.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create user');

        // validate request
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:App\Models\User',
            'username' => 'required|unique:App\Models\User',
            'password' => 'required|min:8',
            'role' => 'required'
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
            // check if request has email verified option
            if ($request->get('email_verified_at')) {
                $request->merge([
                    'email_verified_at' => now()
                ]);
            }
            // create user
            $user = User::create($request->all());

            if ($request->get('role') === 'group-moderator' || $request->get('role') === 'subgroup-moderator') {
                GroupModerator::query()->where('user_id', '=', $user->id)->delete();
                $groups = [];
                foreach (collect($request->get('subgroups'))->unique()->values() as $groupId) {
                    $groups[] = [
                        'user_id' => $user->id,
                        'group_id' => $groupId,
                    ];
                }
                $user->groupModerators()->createMany($groups);
            } else {
                GroupModerator::query()->where('user_id', '=', $user->id)->delete();
            }

            // assign role to user
            $user->assignRole($request->get('role'));
            // fire register event to send email verification link
            if (!$request->get('email_verified_at')) {
                event(new Registered($user));
            }

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.create')
            ], 201);
        } catch (Throwable $exception) {
            // log exception
            report($exception);
            // rollback database
            DB::rollBack();
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error'),
                'error' => $exception->getMessage()
            ], 400);
        }
    }

    /**
     * Get single user.
     *
     * @param User $user
     * @return AdminSingleResource
     */
    public function show(User $user): AdminSingleResource
    {
        $user->load('roles');
        return new AdminSingleResource($user);
    }

    /**
     * Edit user.
     *
     * @param User $user
     * @return AdminEditResource
     * @throws AuthorizationException
     */
    public function edit(User $user): AdminEditResource
    {
        $this->authorize('update user');

        $user->load('role');
        return new AdminEditResource($user);
    }

    /**
     * Update record into database.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorize('update user');

        // validate request
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'username' => ['nullable', Rule::unique('App\Models\User')->ignore($user->id)],
            'email' => ['required', 'email', Rule::unique('App\Models\User')->ignore($user->id)],
            'password' => 'nullable|min:8',
            'role' => 'required'
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
            // check if request has email verified option
            if ($request->get('email_verified_at')) {
                $request->merge([
                    'email_verified_at' => now()
                ]);
            }
            // update user
            $user->update($request->all());

            if ($request->get('role') === 'group-moderator' || $request->get('role') === 'subgroup-moderator') {
                GroupModerator::query()->where('user_id', '=', $user->id)->delete();
                $groups = [];
                foreach (collect($request->get('subgroups'))->unique()->values() as $groupId) {
                    $groups[] = [
                        'user_id' => $user->id,
                        'group_id' => $groupId,
                    ];
                }
                $user->groupModerators()->createMany($groups);
            } else {
                GroupModerator::query()->where('user_id', '=', $user->id)->delete();
            }


            // sync role to user
            $user->syncRoles($request->get('role'));
            // fire register event to send email verification link
            if (!$request->get('email_verified_at')) {
                event(new Registered($user));
            }
            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.update')
            ]);
        } catch (Throwable $exception) {
            // log exception
            report($exception);
            // rollback database
            DB::rollBack();
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error'),
                'error' => $exception->getMessage()
            ], 400);
        }
    }

    /**
     * Delete user from database.
     *
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete user');
        // begin database transaction
        DB::beginTransaction();
        try {
            // delete user
            $user->delete();
            // delete roles associated with this user.
            $user->syncRoles([]);
            // commit changes
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.delete')
            ]);
        } catch (Throwable $exception) {
            // log exception
            report($exception);
            // rollback changes
            DB::rollBack();
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error'),
                'error' => $exception->getMessage()
            ], 400);
        }
    }

    /**
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function updateAdminInfo(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'username' => 'required|unique:App\Models\User,username,' . $user->id,
            'email' => 'required|email|unique:App\Models\User,email,' . $user->id,
            'phone' => 'nullable|unique:App\Models\User,phone,' . $user->id,
        ]);
        // begin database transaction
        DB::beginTransaction();
        try {
            // update user
            $user->update($request->except('email_verified_at'));
            // commit changes
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.update'),
                'user' => new AdminSingleResource($user)
            ]);
        } catch (Throwable $exception) {
            // log exception
            report($exception);
            // rollback changes
            DB::rollBack();
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error'),
                'error' => $exception->getMessage()
            ], 400);
        }
    }

    /**
     * Update user password.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function updateAdminPassword(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'password' => 'required|min:8|confirmed'
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
            // update user
            $user->update([
                'password' => $request->get('password')
            ]);
            // commit changes
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.update'),
                'user' => new AdminSingleResource($user)
            ]);
        } catch (Throwable $exception) {
            // log exception
            report($exception);
            // rollback changes
            DB::rollBack();
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error'),
                'error' => $exception->getMessage()
            ], 400);
        }
    }

    /**
     * Upload user avatar.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function updateAdminAvatar(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image'
        ]);
        // begin database transaction
        DB::beginTransaction();
        try {
            $image = $request->file('avatar');
            $url = $user->addMedia($image)
                ->toMediaCollection('avatar')
                ->getFullUrl();
            // update user
            $user->update([
                'avatar' => $url
            ]);
            // commit changes
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.update'),
                'user' => new AdminSingleResource($user)
            ]);
        } catch (Throwable $exception) {
            // log exception
            report($exception);
            // rollback changes
            DB::rollBack();
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error'),
                'error' => $exception->getMessage()
            ], 400);
        }
    }
}
