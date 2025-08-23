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
                'enrollment_offset_days' => 'required|integer|min:-365|max:365',
                'completion_offset_days' => 'required|integer|min:1|max:365',
                'inter_course_gap_days' => 'required|integer|min:0|max:30',
                'course_order' => 'nullable|array',
                'status' => 'required|in:active,inactive',
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'exists:users,id',
                'course_ids' => 'required_if:target_type,course|array',
                'course_ids.*' => 'exists:webinars,id'
            ]);

            // For now, we'll use the first user/course as target_id for backward compatibility
            // In the future, we can modify the system to handle multiple targets
            switch ($validated['target_type']) {
                case 'course':
                    $validated['target_id'] = $validated['course_ids'][0] ?? null;
                    break;
                case 'student':
                    $validated['target_id'] = $validated['user_ids'][0] ?? null;
                    break;
                case 'bundle':
                    // For bundle simulation, we'll simulate all bundles for selected users
                    $validated['target_id'] = null;
                    break;
            }

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

    public function executeView(SimulationRule $rule)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }

        return view('admin.simulation.execute', compact('rule'));
    }

    public function preview(Request $request, SimulationRule $rule)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }

        try {
            $preview = $this->simulationService->generatePreview($rule);
            return response()->json($preview);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Preview generation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function execute(Request $request, SimulationRule $rule)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }

        if ($rule->status !== 'active') {
            return back()->withErrors(['error' => 'Cannot execute inactive simulation rule.']);
        }

        try {
            $adminId = Auth::id();
            $result = $this->simulationService->executeRule($rule, $adminId);

            if ($result['success']) {
                return redirect()->route('admin.simulation.show', $rule)
                    ->with('success', $result['message']);
            } else {
                return back()->withErrors(['error' => $result['message']]);
            }

        } catch (\Exception $e) {
            Log::error('Simulation execution failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Simulation execution failed: ' . $e->getMessage()]);
        }
    }

    public function executeImmediate(Request $request)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }

        try {
            $validated = $request->validate([
                'target_type' => 'required|in:course,student,bundle',
                'enrollment_offset_days' => 'required|integer|min:-365|max:365',
                'completion_offset_days' => 'required|integer|min:1|max:365',
                'inter_course_gap_days' => 'required|integer|min:0|max:30',
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'exists:users,id',
                'course_ids' => 'required_if:target_type,course|array',
                'course_ids.*' => 'exists:webinars,id',
                'status' => 'required|in:active,inactive'
            ]);

            // Create a temporary simulation rule for immediate execution
            $rule = new SimulationRule([
                'target_type' => $validated['target_type'],
                'enrollment_offset_days' => $validated['enrollment_offset_days'],
                'completion_offset_days' => $validated['completion_offset_days'],
                'inter_course_gap_days' => $validated['inter_course_gap_days'],
                'status' => 'active',
                'created_by' => Auth::id()
            ]);

            // Execute simulation immediately
            $adminId = Auth::id();
            $result = $this->simulationService->executeImmediate($rule, $validated, $adminId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Immediate simulation execution failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Simulation execution failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(SimulationRule $rule)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }

        try {
            $rule->delete();
            return redirect()->route('admin.simulation.index')
                ->with('success', 'Simulation rule deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete simulation rule: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to delete simulation rule: ' . $e->getMessage()]);
        }
    }
}
