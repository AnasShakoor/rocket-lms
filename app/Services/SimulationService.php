<?php

namespace App\Services;

use App\Models\SimulationRule;
use App\Models\SimulationLog;
use App\Models\Api\Webinar;
use App\Models\Bundle;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SimulationService
{
    /**
     * Execute a simulation rule with bundle detection and sequential logic
     */
    public function executeRule(SimulationRule $rule, $adminId)
    {
        try {
            DB::beginTransaction();
            
            $result = [
                'success' => true,
                'message' => 'Simulation executed successfully',
                'courses_processed' => 0,
                'students_processed' => 0,
                'logs_created' => 0
            ];

            switch ($rule->target_type) {
                case 'course':
                    $result = $this->executeCourseSimulation($rule, $adminId);
                    break;
                case 'student':
                    $result = $this->executeStudentSimulation($rule, $adminId);
                    break;
                case 'bundle':
                    $result = $this->executeBundleSimulation($rule, $adminId);
                    break;
                default:
                    throw new \Exception('Invalid target type');
            }

            DB::commit();
            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Simulation execution failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Simulation failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Execute simulation for a single course with bundle detection
     */
    private function executeCourseSimulation(SimulationRule $rule, $adminId)
    {
        $courses = $this->getCoursesForSimulation($rule);
        $students = $this->getStudentsForSimulation($rule);
        
        $totalProcessed = 0;
        $totalLogs = 0;

        foreach ($students as $student) {
            foreach ($courses as $course) {
                // Check if student purchased this course individually or as part of a bundle
                $purchaseInfo = $this->getStudentPurchaseInfo($student, $course);
                
                if ($purchaseInfo) {
                    $processed = $this->processCourseSimulation(
                        $rule, $student, $course, $purchaseInfo, $adminId
                    );
                    
                    if ($processed) {
                        $totalProcessed++;
                        $totalLogs++;
                    }
                }
            }
        }

        return [
            'success' => true,
            'message' => "Course simulation completed. Processed: {$totalProcessed} enrollments, Created: {$totalLogs} logs",
            'courses_processed' => $totalProcessed,
            'students_processed' => count($students),
            'logs_created' => $totalLogs
        ];
    }

    /**
     * Execute simulation for a student across all their courses
     */
    private function executeStudentSimulation(SimulationRule $rule, $adminId)
    {
        $students = $this->getStudentsForSimulation($rule);
        $totalProcessed = 0;
        $totalLogs = 0;

        foreach ($students as $student) {
            $studentCourses = $this->getStudentCourses($student);
            $processed = $this->processStudentCoursesSequentially(
                $rule, $student, $studentCourses, $adminId
            );
            
            if ($processed) {
                $totalProcessed++;
                $totalLogs += count($studentCourses);
            }
        }

        return [
            'success' => true,
            'message' => "Student simulation completed. Processed: {$totalProcessed} students, Created: {$totalLogs} logs",
            'courses_processed' => $totalProcessed,
            'students_processed' => count($students),
            'logs_created' => $totalLogs
        ];
    }

    /**
     * Execute simulation for a bundle with sequential course logic
     */
    private function executeBundleSimulation(SimulationRule $rule, $adminId)
    {
        $bundles = $this->getBundlesForSimulation($rule);
        $totalProcessed = 0;
        $totalLogs = 0;

        foreach ($bundles as $bundle) {
            $bundleStudents = $this->getBundleStudents($bundle);
            $bundleCourses = $this->getBundleCourses($bundle);
            
            foreach ($bundleStudents as $student) {
                $processed = $this->processBundleCoursesSequentially(
                    $rule, $student, $bundle, $bundleCourses, $adminId
                );
                
                if ($processed) {
                    $totalProcessed++;
                    $totalLogs += count($bundleCourses);
                }
            }
        }

        return [
            'success' => true,
            'message' => "Bundle simulation completed. Processed: {$totalProcessed} enrollments, Created: {$totalLogs} logs",
            'courses_processed' => $totalProcessed,
            'students_processed' => count($bundles),
            'logs_created' => $totalLogs
        ];
    }

    /**
     * Process courses sequentially for a student with realistic date logic
     */
    private function processStudentCoursesSequentially(SimulationRule $rule, $student, $courses, $adminId)
    {
        $purchaseDate = $this->getStudentPurchaseDate($student);
        if (!$purchaseDate) return false;

        $currentDate = $purchaseDate->copy()->addDays($rule->enrollment_offset_days);
        
        foreach ($courses as $index => $course) {
            $coursePurchaseInfo = $this->getStudentPurchaseInfo($student, $course);
            if (!$coursePurchaseInfo) continue;

            // Calculate dates based on sequence
            if ($index === 0) {
                // First course: use purchase date + offset
                $enrollmentDate = $currentDate;
            } else {
                // Subsequent courses: use previous course completion + gap
                $previousCourse = $courses[$index - 1];
                $previousCompletion = $this->getLastCompletionDate($student, $previousCourse);
                $enrollmentDate = $previousCompletion->addDays($rule->inter_course_gap_days);
            }

            // Ensure enrollment date is not before purchase date
            $enrollmentDate = max($enrollmentDate, $currentDate);
            
            $completionDate = $enrollmentDate->copy()->addDays($rule->completion_offset_days);

            // Create simulation log
            $this->createSimulationLog(
                $rule, $student, $course, $enrollmentDate, $completionDate, $adminId
            );
        }

        return true;
    }

    /**
     * Process bundle courses sequentially with realistic date logic
     */
    private function processBundleCoursesSequentially(SimulationRule $rule, $student, $bundle, $courses, $adminId)
    {
        $purchaseDate = $this->getStudentPurchaseDate($student);
        if (!$purchaseDate) return false;

        $currentDate = $purchaseDate->copy()->addDays($rule->enrollment_offset_days);
        
        foreach ($courses as $index => $course) {
            // Calculate dates based on sequence
            if ($index === 0) {
                // First course: use purchase date + offset
                $enrollmentDate = $currentDate;
            } else {
                // Subsequent courses: use previous course completion + gap
                $previousCourse = $courses[$index - 1];
                $previousCompletion = $this->getLastCompletionDate($student, $previousCourse);
                $enrollmentDate = $previousCompletion->addDays($rule->inter_course_gap_days);
            }

            // Ensure enrollment date is not before purchase date
            $enrollmentDate = max($enrollmentDate, $currentDate);
            
            $completionDate = $enrollmentDate->copy()->addDays($rule->completion_offset_days);

            // Create simulation log
            $this->createSimulationLog(
                $rule, $student, $course, $enrollmentDate, $completionDate, $adminId
            );
        }

        return true;
    }

    /**
     * Process single course simulation
     */
    private function processCourseSimulation(SimulationRule $rule, $student, $course, $purchaseInfo, $adminId)
    {
        $purchaseDate = Carbon::parse($purchaseInfo->purchase_date);
        $enrollmentDate = $purchaseDate->copy()->addDays($rule->enrollment_offset_days);
        $completionDate = $enrollmentDate->copy()->addDays($rule->completion_offset_days);

        return $this->createSimulationLog(
            $rule, $student, $course, $enrollmentDate, $completionDate, $adminId
        );
    }

    /**
     * Create simulation log entry
     */
    private function createSimulationLog($rule, $student, $course, $enrollmentDate, $completionDate, $adminId)
    {
        try {
            // Create or update enrollment record
            $this->createOrUpdateEnrollment($student, $course, $enrollmentDate);
            
            // Create or update completion record
            $this->createOrUpdateCompletion($student, $course, $completionDate);
            
            // Create simulation log
            SimulationLog::create([
                'rule_id' => $rule->id,
                'user_id' => $student->id,
                'course_id' => $course->id,
                'triggered_by_admin_id' => $adminId,
                'enrollment_date' => $enrollmentDate,
                'completion_date' => $completionDate,
                'simulation_data' => [
                    'rule_type' => $rule->target_type,
                    'enrollment_offset' => $rule->enrollment_offset_days,
                    'completion_offset' => $rule->completion_offset_days,
                    'inter_course_gap' => $rule->inter_course_gap_days,
                    'original_purchase_date' => $this->getStudentPurchaseDate($student)->toDateString()
                ]
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to create simulation log: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get courses for simulation based on rule
     */
    private function getCoursesForSimulation(SimulationRule $rule)
    {
        if ($rule->course_order && is_array($rule->course_order)) {
            return Webinar::whereIn('id', $rule->course_order)
                         ->where('status', 'active')
                         ->where('type', 'course')
                         ->get();
        }
        
        return Webinar::where('status', 'active')
                     ->where('type', 'course')
                     ->get();
    }

    /**
     * Get students for simulation
     */
    private function getStudentsForSimulation(SimulationRule $rule)
    {
        return User::where('role_name', 'user')
                   ->where('status', 'active')
                   ->get();
    }

    /**
     * Get bundles for simulation
     */
    private function getBundlesForSimulation(SimulationRule $rule)
    {
        return Bundle::where('status', 'active')->get();
    }

    /**
     * Get student courses (purchased courses)
     */
    private function getStudentCourses($student)
    {
        return \App\Models\CourseLearning::where('user_id', $student->id)
                     ->with('course')
                     ->get()
                     ->pluck('course')
                     ->filter();
    }

    /**
     * Get bundle courses
     */
    private function getBundleCourses($bundle)
    {
        return DB::table('bundle_webinars')
            ->join('webinars', 'bundle_webinars.webinar_id', '=', 'webinars.id')
            ->where('bundle_webinars.bundle_id', $bundle->id)
            ->where('webinars.status', 'active')
            ->where('webinars.type', 'course')
            ->orderBy('bundle_webinars.sort_order')
            ->select('webinars.*')
            ->get();
    }

    /**
     * Get bundle students
     */
    private function getBundleStudents($bundle)
    {
        return \App\Models\Sale::where('bundle_id', $bundle->id)
            ->where('status', 'completed')
            ->with('buyer')
            ->get()
            ->pluck('buyer')
            ->filter();
    }

    /**
     * Get student purchase info for a course
     */
    private function getStudentPurchaseInfo($student, $course)
    {
        // Check if student purchased this course individually
        $individualPurchase = \App\Models\Sale::where('buyer_id', $student->id)
            ->where('webinar_id', $course->id)
            ->where('status', 'completed')
            ->first();
            
        if ($individualPurchase) {
            return (object) [
                'purchase_date' => $individualPurchase->purchased_at ?? $individualPurchase->created_at,
                'amount' => $individualPurchase->amount,
                'status' => $individualPurchase->status,
                'type' => 'individual'
            ];
        }
        
        // Check if student purchased this course as part of a bundle
        $bundlePurchase = \App\Models\Sale::where('buyer_id', $student->id)
            ->whereNotNull('bundle_id')
            ->where('status', 'completed')
            ->whereHas('bundle', function($query) use ($course) {
                $query->whereHas('courses', function($q) use ($course) {
                    $q->where('webinar_id', $course->id);
                });
            })
            ->first();
            
        if ($bundlePurchase) {
            return (object) [
                'purchase_date' => $bundlePurchase->purchased_at ?? $bundlePurchase->created_at,
                'amount' => $bundlePurchase->amount,
                'status' => $bundlePurchase->status,
                'type' => 'bundle',
                'bundle_id' => $bundlePurchase->bundle_id
            ];
        }
        
        return null;
    }

    /**
     * Get student purchase date
     */
    private function getStudentPurchaseDate($student)
    {
        // Get the most recent purchase date for the student
        $latestPurchase = \App\Models\Sale::where('buyer_id', $student->id)
            ->where('status', 'completed')
            ->latest('purchased_at')
            ->first();
            
        return $latestPurchase ? ($latestPurchase->purchased_at ?? $latestPurchase->created_at) : now()->subDays(30);
    }

    /**
     * Get last completion date for a course
     */
    private function getLastCompletionDate($student, $course)
    {
        $completion = \App\Models\CourseLearning::where('user_id', $student->id)
            ->where('webinar_id', $course->id)
            ->where('status', 'completed')
            ->first();
            
        return $completion ? ($completion->completed_at ?? $completion->updated_at) : now()->subDays(25);
    }

    /**
     * Create or update enrollment record
     */
    private function createOrUpdateEnrollment($student, $course, $enrollmentDate)
    {
        \App\Models\CourseLearning::updateOrCreate(
            ['user_id' => $student->id, 'webinar_id' => $course->id],
            [
                'status' => 'completed',
                'progress' => 100,
                'enrolled_at' => $enrollmentDate,
                'started_at' => $enrollmentDate,
                'completed_at' => $enrollmentDate
            ]
        );
    }

    /**
     * Create or update completion record
     */
    private function createOrUpdateCompletion($student, $course, $completionDate)
    {
        \App\Models\CourseLearning::updateOrCreate(
            ['user_id' => $student->id, 'webinar_id' => $course->id],
            [
                'status' => 'completed',
                'progress' => 100,
                'completed_at' => $completionDate
            ]
        );
    }

    /**
     * Generate preview of simulation results
     */
    public function generatePreview(SimulationRule $rule)
    {
        $preview = [
            'rule_info' => [
                'target_type' => $rule->target_type,
                'enrollment_offset' => $rule->enrollment_offset_days,
                'completion_offset' => $rule->completion_offset_days,
                'inter_course_gap' => $rule->inter_course_gap_days
            ],
            'estimated_impact' => [
                'courses_affected' => 0,
                'students_affected' => 0,
                'simulations_created' => 0
            ],
            'sample_timeline' => []
        ];

        // Generate sample timeline based on rule type
        switch ($rule->target_type) {
            case 'course':
                $preview = $this->generateCoursePreview($rule, $preview);
                break;
            case 'student':
                $preview = $this->generateStudentPreview($rule, $preview);
                break;
            case 'bundle':
                $preview = $this->generateBundlePreview($rule, $preview);
                break;
        }

        return $preview;
    }

    private function generateCoursePreview(SimulationRule $rule, $preview)
    {
        $courses = $this->getCoursesForSimulation($rule);
        $students = $this->getStudentsForSimulation($rule);
        
        $preview['estimated_impact']['courses_affected'] = $courses->count();
        $preview['estimated_impact']['students_affected'] = $students->count();
        $preview['estimated_impact']['simulations_created'] = $courses->count() * $students->count();
        
        // Generate sample timeline for first course
        if ($courses->count() > 0) {
            $course = $courses->first();
            $purchaseDate = now()->subDays(30);
            $enrollmentDate = $purchaseDate->copy()->addDays($rule->enrollment_offset_days);
            $completionDate = $enrollmentDate->copy()->addDays($rule->completion_offset_days);
            
            $preview['sample_timeline'] = [
                [
                    'course' => $course->title ?? 'Sample Course',
                    'purchase_date' => $purchaseDate->format('Y-m-d'),
                    'enrollment_date' => $enrollmentDate->format('Y-m-d'),
                    'completion_date' => $completionDate->format('Y-m-d')
                ]
            ];
        }
        
        return $preview;
    }

    private function generateStudentPreview(SimulationRule $rule, $preview)
    {
        $students = $this->getStudentsForSimulation($rule);
        $sampleStudent = $students->first();
        
        if ($sampleStudent) {
            $courses = $this->getStudentCourses($sampleStudent);
            $purchaseDate = now()->subDays(30);
            $currentDate = $purchaseDate->copy()->addDays($rule->enrollment_offset_days);
            
            $preview['estimated_impact']['students_affected'] = $students->count();
            $preview['estimated_impact']['simulations_created'] = $students->count() * $courses->count();
            
            // Generate sequential timeline
            foreach ($courses->take(3) as $index => $course) {
                if ($index === 0) {
                    $enrollmentDate = $currentDate;
                } else {
                    $enrollmentDate = $currentDate->copy()->addDays($index * ($rule->completion_offset_days + $rule->inter_course_gap_days));
                }
                
                $completionDate = $enrollmentDate->copy()->addDays($rule->completion_offset_days);
                
                $preview['sample_timeline'][] = [
                    'course' => $course->title ?? "Course " . ($index + 1),
                    'enrollment_date' => $enrollmentDate->format('Y-m-d'),
                    'completion_date' => $completionDate->format('Y-m-d')
                ];
            }
        }
        
        return $preview;
    }

    private function generateBundlePreview(SimulationRule $rule, $preview)
    {
        $bundles = $this->getBundlesForSimulation($rule);
        $students = $this->getStudentsForSimulation($rule);
        
        $preview['estimated_impact']['courses_affected'] = $bundles->count();
        $preview['estimated_impact']['students_affected'] = $students->count();
        $preview['estimated_impact']['simulations_created'] = $bundles->count() * $students->count();
        
        // Generate bundle timeline
        if ($bundles->count() > 0) {
            $bundle = $bundles->first();
            $purchaseDate = now()->subDays(30);
            $currentDate = $purchaseDate->copy()->addDays($rule->enrollment_offset_days);
            
            // Simulate 4 courses in bundle
            for ($i = 0; $i < 4; $i++) {
                if ($i === 0) {
                    $enrollmentDate = $currentDate;
                } else {
                    $enrollmentDate = $currentDate->copy()->addDays($i * ($rule->completion_offset_days + $rule->inter_course_gap_days));
                }
                
                $completionDate = $enrollmentDate->copy()->addDays($rule->completion_offset_days);
                
                $preview['sample_timeline'][] = [
                    'course' => "Bundle Course " . ($i + 1),
                    'enrollment_date' => $enrollmentDate->format('Y-m-d'),
                    'completion_date' => $completionDate->format('Y-m-d')
                ];
            }
        }
        
        return $preview;
    }
}
