<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SimulationRule;
use App\Models\SimulationLog;
use App\Services\SimulationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SimulationController extends Controller
{
    protected $simulationService;

    public function __construct(SimulationService $simulationService)
    {
        $this->simulationService = $simulationService;
    }

    public function index()
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }
        
        $rules = SimulationRule::with('creator')->latest()->paginate(20);
        return view('admin.simulation.index', compact('rules'));
    }

    public function create()
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }
        
        // Get active courses
        $courses = \App\Models\Api\Webinar::where('status', 'active')
            ->where('type', 'course')
            ->get();
            
        // Get active bundles
        $bundles = \App\Models\Bundle::where('status', 'active')
            ->get();
            
        // Get students (users with role 'user')
        $students = \App\User::where('role_name', 'user')
            ->where('status', 'active')
            ->get();
        
        return view('admin.simulation.create', compact('courses', 'bundles', 'students'));
    }

    public function store(Request $request)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }
        
        try {
            $validated = $request->validate([
                'target_type' => 'required|in:course,student,bundle',
                'enrollment_offset_days' => 'required|integer',
                'completion_offset_days' => 'required|integer',
                'inter_course_gap_days' => 'required|integer|min:0',
                'course_order' => 'nullable|array',
                'status' => 'required|in:active,inactive'
            ]);

            $validated['created_by'] = Auth::id();
            
            // Debug: Log the data being saved
            Log::info('Creating simulation rule with data:', $validated);
            
            $rule = SimulationRule::create($validated);
            
            Log::info('Simulation rule created successfully with ID: ' . $rule->id);
            
            return redirect()->route('admin.simulation.index')
                ->with('success', 'Simulation rule created successfully.');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed for simulation rule:', $e->errors());
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error creating simulation rule: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create simulation rule: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(SimulationRule $rule)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }
        
        $logs = $rule->logs()->with(['user', 'course'])->latest()->paginate(20);
        return view('admin.simulation.show', compact('rule', 'logs'));
    }

    public function preview(Request $request, SimulationRule $rule)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }
        
        $preview = $this->simulationService->generatePreview($rule);
        return response()->json($preview);
    }

    public function execute(Request $request, SimulationRule $rule)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }
        
        $result = $this->simulationService->executeRule($rule, Auth::id());
        return response()->json([
            'success' => true,
            'message' => 'Simulation executed successfully',
            'data' => $result
        ]);
    }

    public function destroy(SimulationRule $rule)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }
        
        $rule->delete();
        return redirect()->route('admin.simulation.index')
            ->with('success', 'Simulation rule deleted successfully.');
    }
}
