<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\CustomerResource;
use App\Http\Resources\User\CustomerSingleResource;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Role;
use Throwable;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{

    /**
     *
     */
    public function __construct()
    {
        $this->middleware('permission:view user|viewAny user|edit user|update user|delete user', ['only' => ['index']]);
    }

    /**
     * @param Request $request
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $request->query('query');
        $sortBy = $request->query('sortBy');
        $direction = $request->query('direction');
        $per_page = $request->query('per_page', 10);


        $customers = User::query()->latest();
        if ($query) {
            $customers = User::search($query);
        }
        if ($sortBy) {
            $customers = User::query()->orderBy($sortBy, $direction);
        }
        if ($per_page === '-1') {
            $results = $customers->get();
            $customers = new LengthAwarePaginator($results, $results->count(), -1);
        } else {
            $customers = $customers->paginate($per_page);
        }
        return CustomerResource::collection($customers);
    }

    /**
     * @return JsonResponse
     */
    public function getAuthors(): JsonResponse
    {
        $users = User::query()
            ->where('status', '=', 'active')
            ->latest()
            ->selectRaw('CONCAT(name," (",email,")") as text, id as value')
            ->get();

        return response()->json([
            'data' => $users
        ]);
    }

    /**
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
            'username' => 'nullable|unique:App\Models\User',
            'password' => 'required|min:8',
        ]);
        // begin database transaction
        DB::beginTransaction();
        try {
            // check if request has email verified option
            if ($request->get('email_verified_at')) {
                $request->merge([
                    'email_verified_at' => now()
                ]);
            } else {
                $request->merge([
                    'email_verified_at' => null,
                ]);
            }
            // create user
            $user = User::create($request->all());

            if ($request->get('role')) {
                $user->assignRole($request->get('role'));
            }

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
     * @param User $customer
     * @return CustomerSingleResource
     */
    public function show(User $customer): CustomerSingleResource
    {
        return new CustomerSingleResource($customer);
    }

    /**
     * @param Request $request
     * @param User $customer
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(Request $request, User $customer): JsonResponse
    {
        $this->authorize('update user');

        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => ['required', 'email', Rule::unique('App\Models\User')->ignore($customer->id)],
            'username' => ['nullable', Rule::unique('App\Models\User')->ignore($customer->id)],
            'phone' => ['nullable', Rule::unique('App\Models\User')->ignore($customer->id)],
            'password' => 'nullable|min:8',
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
            // check if request has email verified option
            if ($request->get('email_verified_at')) {
                $request->merge([
                    'email_verified_at' => now()
                ]);
            } else {
                $request->merge([
                    'email_verified_at' => null,
                ]);
            }
            // create user
            $customer->update($request->except('password'));

            if ($request->password) {
                $customer->update(['password']);
            }

            if ($request->get('role')) {
                $customer->syncRoles($request->get('role'));
            }

            // fire register event to send email verification link
            if (!$request->get('email_verified_at')) {
                event(new Registered($customer));
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
     * @param User $customer
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(User $customer): JsonResponse
    {
        $this->authorize('delete user');
        // begin database transaction
        DB::beginTransaction();
        try {
            // delete user
            $customer->delete();
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
}
