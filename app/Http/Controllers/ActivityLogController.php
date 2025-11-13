<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $perPage = 10;

        $logs = ActivityLog::with('admin')
                    ->latest()
                    ->paginate($perPage);

        return response()->json($logs);
    }

    public function clean(Request $request)
    {
        $filter = $request->input('filter', 'all'); // default = all
        $query = ActivityLog::query();

        if(Auth::user() && !Auth::user()->super_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);

        }

        switch ($filter) {
            case 'today':
                $query->whereDate('created_at', Carbon::today());
                break;
            case 'last_week':
                $query->whereBetween('created_at', [
                    Carbon::now()->subWeek()->startOfWeek(),
                    Carbon::now()->subWeek()->endOfWeek()
                ]);
                break;
            case 'last_month':
                $query->whereBetween('created_at', [
                    Carbon::now()->subMonth()->startOfMonth(),
                    Carbon::now()->subMonth()->endOfMonth()
                ]);
                break;
            case 'all':
            default:
                // no filter, delete all
                break;
        }

        $deletedCount = $query->delete();

        return response()->json([
            'message' => "Deleted $deletedCount activity log(s)",
        ]);
    }
}
