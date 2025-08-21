<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SimulationRule;
use App\Models\SimulationLog;
use App\Models\Api\Webinar;
use App\Models\Bundle;
use App\User;
use App\Services\SimulationService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EnhancedSimulationController extends Controller
{
    protected $simulationService;
    protected $emailService;
    
    public function __construct(SimulationService $simulationService, EmailService $emailService)
    {
        $this->simulationService = $simulationService;
        $this->emailService = $emailService;
    }
    
    /**
     * Enhanced simulation dashboard with bundle detection
     */
    public function index()
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }
        
        $stats = $this->getSimulationStats();
        $recentRules = SimulationRule::latest()->take(5)->get();
        $recentLogs = SimulationLog::with(['user', 'course'])->latest()->take(10)->get();
        
        return view('admin.enhanced-simulation.index', compact('stats', 'recentRules', 'recentLogs'));
    }
    
    /**
     * Test bundle detection functionality
     */
    public function testBundleDetection()
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }
        
        try {
            $bundles = Bundle::with('bundleWebinars.webinar')->where('status', 'active')->get();
            $bundleAnalysis = [];
            
            foreach ($bundles as $bundle) {
                $analysis = $this->analyzeBundle($bundle);
                $bundleAnalysis[] = $analysis;
            }
            
            return response()->json([
                'success' => true,
                'bundles_analyzed' => count($bundleAnalysis),
                'analysis' => $bundleAnalysis
            ]);
            
        } catch (\Exception $e) {
            Log::error('Bundle detection test failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Execute enhanced simulation with bundle detection
     */
    public function executeEnhancedSimulation(Request $request)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }
        
        $request->validate([
            'rule_id' => 'required|exists:simulation_rules,id',
            'enable_bundle_detection' => 'boolean',
            'send_cme_emails' => 'boolean'
        ]);
        
        try {
            $rule = SimulationRule::findOrFail($request->rule_id);
            
            // Execute simulation
            $result = $this->simulationService->executeRule($rule, auth()->id());
            
            if ($result['success'] && $request->send_cme_emails) {
                // Send CME emails to affected users
                $this->sendCmeEmailsForSimulation($rule, $result);
            }
            
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'result' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('Enhanced simulation execution failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get simulation statistics
     */
    private function getSimulationStats()
    {
        $totalRules = SimulationRule::count();
        $activeRules = SimulationRule::where('status', 'active')->count();
        $totalLogs = SimulationLog::count();
        $todayLogs = SimulationLog::whereDate('created_at', today())->count();
        
        // Bundle-specific stats
        $bundlesWithSimulations = DB::table('simulation_logs')
            ->join('webinars', 'simulation_logs.course_id', '=', 'webinars.id')
            ->join('bundle_webinars', 'webinars.id', '=', 'bundle_webinars.webinar_id')
            ->distinct('bundle_webinars.bundle_id')
            ->count('bundle_webinars.bundle_id');
        
        return [
            'total_rules' => $totalRules,
            'active_rules' => $activeRules,
            'total_logs' => $totalLogs,
            'today_logs' => $todayLogs,
            'bundles_with_simulations' => $bundlesWithSimulations
        ];
    }
    
    /**
     * Analyze bundle structure and simulation potential
     */
    private function analyzeBundle(Bundle $bundle)
    {
        $courses = $bundle->bundleWebinars;
        $totalStudents = DB::table('sales')
            ->where('bundle_id', $bundle->id)
            ->where('status', 'completed')
            ->count();
        
        $completionRates = [];
        foreach ($courses as $bundleWebinar) {
            $course = $bundleWebinar->webinar;
            if (!$course) continue;
            
            $enrolled = DB::table('course_learning')
                ->where('webinar_id', $course->id)
                ->count();
            
            $completed = DB::table('course_learning')
                ->where('webinar_id', $course->id)
                ->where('status', 'completed')
                ->count();
            
            $completionRates[] = [
                'course_id' => $course->id,
                'course_title' => $course->title ?? 'Unknown Course',
                'enrolled' => $enrolled,
                'completed' => $completed,
                'completion_rate' => $enrolled > 0 ? round(($completed / $enrolled) * 100, 2) : 0
            ];
        }
        
        return [
            'bundle_id' => $bundle->id,
            'bundle_title' => $bundle->title ?? 'Unknown Bundle',
            'total_courses' => $courses->count(),
            'total_students' => $totalStudents,
            'completion_rates' => $completionRates,
            'average_completion_rate' => collect($completionRates)->avg('completion_rate')
        ];
    }
    
    /**
     * Send CME emails for simulation results
     */
    private function sendCmeEmailsForSimulation(SimulationRule $rule, $result)
    {
        try {
            // Get users affected by this simulation
            $affectedUsers = SimulationLog::where('rule_id', $rule->id)
                ->with('user')
                ->get()
                ->pluck('user')
                ->unique('id');
            
            $emailCount = 0;
            foreach ($affectedUsers as $user) {
                if ($user && $user->email) {
                    $success = $this->emailService->sendCmeInitiatedEmail(
                        $user,
                        'Simulated Course Completion',
                        now()->format('Y-m-d')
                    );
                    
                    if ($success) {
                        $emailCount++;
                    }
                }
            }
            
            Log::info("Sent {$emailCount} CME emails for simulation rule {$rule->id}");
            
        } catch (\Exception $e) {
            Log::error('Failed to send CME emails for simulation: ' . $e->getMessage());
        }
    }
    
    /**
     * Get simulation preview with bundle analysis
     */
    public function getEnhancedPreview(Request $request)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }
        
        $request->validate(['rule_id' => 'required|exists:simulation_rules,id']);
        
        try {
            $rule = SimulationRule::findOrFail($request->rule_id);
            $preview = $this->simulationService->generatePreview($rule);
            
            // Add bundle-specific analysis
            if ($rule->target_type === 'bundle') {
                $preview['bundle_analysis'] = $this->getBundleAnalysisForRule($rule);
            }
            
            return response()->json([
                'success' => true,
                'preview' => $preview
            ]);
            
        } catch (\Exception $e) {
            Log::error('Enhanced preview generation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get bundle analysis for a specific rule
     */
    private function getBundleAnalysisForRule(SimulationRule $rule)
    {
        $bundles = Bundle::where('status', 'active')->get();
        $analysis = [];
        
        foreach ($bundles as $bundle) {
            $bundleAnalysis = $this->analyzeBundle($bundle);
            $analysis[] = $bundleAnalysis;
        }
        
        return $analysis;
    }
}
