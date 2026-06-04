<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Models\Customer;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;

class AuditLogController extends Controller
{
    public function index()
    {
        return view('audit-logs.index');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $logs = Activity::with(['causer', 'subject'])->latest();

            if ($request->filled('log_name')) {
                $logs->where('log_name', $request->log_name);
            }

            if ($request->filled('causer_type')) {
                $logs->where('causer_type', $request->causer_type);
            }

            if ($request->filled('causer_id')) {
                $logs->where('causer_id', $request->causer_id);
            }

            return DataTables::of($logs)
                ->editColumn('created_at', function ($log) {
                    return Carbon::parse($log->created_at)->format('H:i - d/m/Y');
                })
                ->addColumn('causer_name', function ($log) {
                    return $log->causer?->name;
                })
                ->editColumn('subject_id', function ($log) {
                    if (!$log->subject_type) return 'N/A';

                    $modelName = class_basename($log->subject_type);
                    return '<span class="tw-text-xs tw-font-mono tw-bg-gray-100 tw-px-1.5 tw-py-0.5 tw-rounded-sm">
                            ' . $modelName . ' #' . $log->subject_id . '
                            </span>';
                })
                ->addColumn('ip_address', function ($log) {
                    return $log->getExtraProperty('ip');
                })
                ->rawColumns(['description', 'subject_id', 'ip_address'])
                ->make(true);
        }
    }

    public function getFilterData()
    {
        // Get log names
        $logNames = Activity::select('log_name')
            ->whereNotNull('log_name')
            ->where('log_name', '!=', '')
            ->distinct()
            ->pluck('log_name');

        $logNameData = $logNames->map(function ($name) {
            return [
                'id' => $name,
                'text' => $name
            ];
        })->values();

        // Get user causers
        $userCauserIds = Activity::where('causer_type', User::class)
            ->whereNotNull('causer_id')
            ->distinct()
            ->pluck('causer_id');

        $userCauserData = User::whereIn('id', $userCauserIds)
            ->select('id', 'name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'text' => $user->name
                ];
            })->values();

        // Get customer causers
        $customerCauserIds = Activity::where('causer_type', Customer::class)
            ->whereNotNull('causer_id')
            ->distinct()
            ->pluck('causer_id');

        $customerCauserData = Customer::whereIn('id', $customerCauserIds)
            ->select('id', 'name', 'email')
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'text' => $customer->name ?: $customer->email
                ];
            })->values();

        return response()->json([
            'logNameData' => $logNameData,
            'userCauserData' => $userCauserData,
            'customerCauserData' => $customerCauserData,
        ]);
    }

    public function show($id)
    {
        $activity = Activity::with('causer')->findOrFail($id);

        return view('audit-logs.show', compact('activity'));
    }
}
