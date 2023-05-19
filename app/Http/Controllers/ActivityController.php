<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Common\ActivityResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Spatie\Activitylog\Models\Activity;
use Throwable;

class ActivityController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        $this->middleware('permission:view activity|viewAny activity|edit activity|update activity|delete activity', ['only' => ['index']]);
    }

    /**
     * Get all activity logs.
     *
     * @param Request $request
     * @return JsonResponse|AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        try {
            $per_page = (int)$request->query('per_page');
            $query = $request->query('query');
            if (!$query) {
                $activities = Activity::with('causer')->latest();

            } else {
                $activities = Activity::search($query);
            }
            if ($per_page == '-1') {
                $results = $activities->get();
                $activities = new LengthAwarePaginator($results, $results->count(), -1);
            } else {
                $activities = $activities->paginate($per_page);
            }
            return ActivityResource::collection($activities);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error'),
                'error' => $exception->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete activity log.
     *
     * @param Activity $activity
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(Activity $activity): JsonResponse
    {
        $this->authorize('delete activity');
        DB::beginTransaction();
        try {
            // delete activity log
            $activity->delete();
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.delete')
            ]);
        } catch (Throwable $exception) {
            report($exception);
            DB::rollBack();
            return response()->json([
                'message' => Lang::get('crud.error'),
                'error' => $exception->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete all activities log.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroyAll(Request $request): JsonResponse
    {
        $this->authorize('delete activity');
        DB::beginTransaction();
        try {
            // delete all activity log
            $ids = explode(',', $request->query('ids'));
            DB::table(config('activitylog.table_name'))
                ->whereIn('id', $ids)
                ->delete();

            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.delete')
            ]);
        } catch (Throwable $exception) {
            report($exception);
            DB::rollBack();
            return response()->json([
                'message' => Lang::get('crud.error'),
                'error' => $exception->getMessage(),
            ], 400);
        }
    }
}
