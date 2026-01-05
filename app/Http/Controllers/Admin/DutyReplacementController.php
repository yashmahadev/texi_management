<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyDutyLog;
use App\Models\Driver;
use App\Services\DutyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DutyReplacementController extends Controller
{
    protected $dutyService;

    public function __construct(DutyService $dutyService)
    {
        $this->dutyService = $dutyService;
    }

    public function index()
    {
        // History of replacements
        $replacements = \App\Models\DutyReplacement::with(['dailyDutyLog', 'originalDriver', 'replacementDriver', 'assigner'])
            ->latest()
            ->paginate(20);
            
        return view('admin.replacements.index', compact('replacements'));
    }

    public function create(DailyDutyLog $log)
    {
        $drivers = Driver::where('status', 'active')->where('id', '!=', $log->monthlyDuty->primary_driver_id)->get();
        return view('admin.replacements.create', compact('log', 'drivers'));
    }

    public function store(\App\Http\Requests\AssignReplacementRequest $request, DailyDutyLog $log)
    {
        $this->dutyService->assignReplacement(
            $log, 
            $request->replacement_driver_id, 
            $request->reason, 
            Auth::id()
        );

        return redirect()->route('admin.daily-logs.show', $log)->with('success', 'Replacement assigned.');
    }
}
