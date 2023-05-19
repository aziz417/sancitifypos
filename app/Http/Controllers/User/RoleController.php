<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\RoleEditResource;
use App\Http\Resources\User\RoleResource;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\ValidationException;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Throwable;

class RoleController extends Controller
{

    /**
     *
     */
    public function __construct()
    {
        $this->middleware('permission:view role|viewAny role|edit role|update role|delete role', ['only' => ['index']]);
    }


    /**
     * Get all permissions as tree.
     *
     * @return JsonResponse
     */
    public function getPermissions(): JsonResponse
    {
        try {
            $tree = [];

            $permissions = Permission::all();

            foreach ($permissions as $permission) {
                list($action, $model) = explode(' ', $permission->name);

                if (!isset($tree[$model])) {
                    $tree[$model] = [];
                }
                $tree[$model][$permission->id] = $action;
            }

            return response()->json($tree);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(collect());
        }
    }

    /**
     * Get all roles for menu page.
     *
     * @return JsonResponse
     */
    public function getRoles(): JsonResponse
    {
        $roles = Role::query()->get();
        return response()->json($roles);
    }


    public function index(Request $request)
    {
        $query = $request->query('query');
        $sortBy = $request->query('sortBy');
        $direction = $request->query('direction');
        $per_page = (int)$request->query('per_page', 10);

        $roles = Role::query()->latest();
        if ($query) {
            $roles = Role::search($request->query('query'));
        }
        if ($sortBy) {
            $roles = Role::query()->orderBy($sortBy, $direction);
        }
        if ($per_page == '-1') {
            $results = $roles->get();
            $roles = new LengthAwarePaginator($results, $results->count(), -1);
        } else {
            $roles = $roles->paginate($per_page);
        }
        return RoleResource::collection($roles);
    }

    /**
     * Store new role into database.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|Exception
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create role');

        // validate request
        $data = $this->validate($request, [
            'label' => 'required',
            'name' => 'required|unique:roles',
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
            // create role
            Role::create($data);

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
     * Edit role.
     *
     * @param Role $role
     * @return RoleEditResource
     * @throws AuthorizationException
     */
    public function show(Role $role): RoleEditResource
    {
        $this->authorize('update role');

        return RoleEditResource::make($role);
    }

    /**
     * Update record into database.
     *
     * @param Request $request
     * @param Role $role
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $this->authorize('update role');

        // validate request
        $data = $this->validate($request, [
            'name' => 'required|unique:roles,name,' . $role->id,
            'label' => 'required'
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
            // update role
            $role->update($data);

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
     * Delete role from database.
     *
     * @param Role $role
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('delete role');

        try {
            // delete role
            $role->delete();
            // delete permissions associated with this role.
            $role->syncPermissions([]);

            // return success message
            return response()->json([
                'message' => Lang::get('crud.delete')
            ]);
        } catch (Throwable $exception) {
            // log exception
            report($exception);
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error'),
                'error' => $exception->getMessage()
            ], 400);
        }
    }

    /**
     * Add permission to associate role.
     *
     * @param Request $request
     * @param Role $role
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function addPermissions(Request $request, Role $role): JsonResponse
    {
        $this->authorize('update role');

        // validate request
        $this->validate($request, [
            'permissions' => 'required',
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
            // assign permissions to role
            $role->syncPermissions($request->input('permissions'));
            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.update')
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
     * @param $role
     * @return JsonResponse
     */
    public function getPermissionsByRole($role): JsonResponse
    {
        $permissions = DB::table('role_has_permissions')
            ->where('role_id', '=', $role)
            ->pluck('permission_id');
        return response()->json($permissions);
    }
}
