<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\AuthAdminResource;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Traits\HasRoles;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class AuthController extends Controller
{
       /**
     * Login a user with email and get token
     *
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function login(Request $request): mixed
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8'
        ]);
        $user = User::query()->where('email', $request->get('email'))->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ["We can't find a user with that email address."],
            ]);
        }
        if ($user instanceof MustVerifyEmail && !$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Your email address is not verified.',
            ], 403);
        }
        if ($user->hasRole('subscriber')) {
            return response()->json([
                'message' => 'You are not allowed to login!',
            ], 403);
        }
        if (!Hash::check($request->get('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        return $user->createToken('admin')->plainTextToken;
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
        // $this->authorize('create user');

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



            // assign role to user
            $user->assignRole($request->get('role'));
           // $user->assignRole($request->input('roles'));
            // fire register event to send email verification link
            // if (!$request->get('email_verified_at')) {
            //     event(new Registered($user));
            // }

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
                'message' => 'done',
                'error' => $exception->getMessage()
            ], 400);
        }
    }

    /**
     * Check is email or mobile unique in database.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkIsEmailMobileExist(Request $request): JsonResponse
    {
        $user_id = $request->query('user_id');
        $field = $request->query('username') ?? $request->query('email') ?? $request->query('mobile');
        $queries = collect([]);
        foreach ($request->query() as $key => $value) {
            $queries->push($key);
        }
        $name = $queries[1];
        $user = User::find($user_id);
        if ($user) {
            if ($user->$name === $field) {
                return response()->json([
                    'message' => "The $name is available.",
                    'valid' => true
                ]);
            } else {
                if (User::where($name, '=', $field)->exists()) {
                    return response()->json([
                        'message' => "The $name has already taken.",
                        'valid' => false
                    ]);
                } else {
                    return response()->json([
                        'message' => "The $name is available.",
                        'valid' => true
                    ]);
                }
            }
        } else {
            if (User::where($name, '=', $field)->exists()) {
                return response()->json([
                    'message' => "The $name has already taken.",
                    'valid' => false
                ]);
            } else {
                return response()->json([
                    'message' => "The $name is available.",
                    'valid' => true
                ]);
            }
        }
    }

    /**
     * Get auth user response.
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        return response()->json([
            'user' => new AuthAdminResource(Auth::user())
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );
            return $status === Password::RESET_LINK_SENT
                ? response()->json([
                    'message' => __($status)
                ])
                : response()->json([
                    'message' => __($status)
                ], 400);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('auth.error'),
                'error' => $exception->getMessage()
            ], 400);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);
        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) use ($request) {
                    $user->forceFill([
                        'password' => $password
                    ])->setRememberToken(Str::random(60));
                    $user->save();
                    event(new PasswordReset($user));
                }
            );
            return $status === Password::PASSWORD_RESET
                ? response()->json([
                    'message' => __($status)
                ])
                : response()->json([
                    'message' => __($status)
                ], 400);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('auth.error'),
                'error' => $exception->getMessage()
            ], 400);
        }
    }

    /**
     * @param $name
     * @return string|null
     */
    public function checkUsername($name): ?string
    {
        try {
            $slug = Str::slug($name, '-', app()->getLocale());
            # slug repeat check
            $latest = User::query()->where('username', '=', $slug)
                ->latest('id')
                ->value('username');

            if ($latest) {
                $pieces = explode('-', $latest);
                $number = intval(end($pieces));
                $slug .= '-' . ($number + 1);
            }
            return $slug;
        } catch (Throwable $exception) {
            // return failed message
            return null;
        }
    }
}
