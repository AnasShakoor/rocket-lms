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
                    // Check if this course is part of a bundle
                    $bundleInfo = $this->getCourseBundleInfo($course);

                    if ($bundleInfo) {
                        // If course is part of a bundle, simulate all courses in the bundle
                        $bundleCourses = $this->getBundleCourses($bundleInfo);
                        $processed = $this->processBundleCoursesSequentially(
                            $rule, $student, $bundleInfo, $bundleCourses, $adminId
                        );

                        if ($processed) {
                            $totalProcessed += count($bundleCourses);
                            $totalLogs += count($bundleCourses);
                        }
                    } else {
                        // Single course simulation
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
     * Following the exact requirements:
     * - Course 1: fake enrollment = purchase date - 12 days (enrollment_offset_days), completion = enrollment + 2 days
     * - Course 2: fake enrollment = course 1 completion + 1 day, completion = enrollment + 2 days
     * - Course 3: fake enrollment = course 2 completion + 1 day, completion = enrollment + 2 days
     * - And so on...
     */
    private function processStudentCoursesSequentially(SimulationRule $rule, $student, $courses, $adminId)
    {
        if ($courses->isEmpty()) return false;

        $purchaseDate = $this->getStudentPurchaseDate($student);
        if (!$purchaseDate) return false;

        // First course: fake enrollment = purchase date - 12 days (enrollment_offset_days)
        $firstEnrollmentDate = $purchaseDate->copy()->addDays($rule->enrollment_offset_days);
        $firstCompletionDate = $firstEnrollmentDate->copy()->addDays($rule->completion_offset_days);

        // Create simulation for first course
        $this->createSimulationLog(
            $rule, $student, $courses[0], $firstEnrollmentDate, $firstCompletionDate, $adminId
        );

        // Process remaining courses sequentially
        for ($i = 1; $i < count($courses); $i++) {
            $course = $courses[$i];

            // Previous course completion date + 1 day gap
            $previousCompletionDate = $firstCompletionDate->copy()->addDays(($i - 1) * ($rule->completion_offset_days + $rule->inter_course_gap_days));
            $enrollmentDate = $previousCompletionDate->copy()->addDays($rule->inter_course_gap_days);
            $completionDate = $enrollmentDate->copy()->addDays($rule->completion_offset_days);

            // Create simulation log
            $this->createSimulationLog(
                $rule, $student, $course, $enrollmentDate, $completionDate, $adminId
            );
        }

        return true;
    }

    /**
     * Process bundle courses sequentially for a student
     */
    private function processBundleCoursesSequentially(SimulationRule $rule, $student, $bundle, $courses, $adminId)
    {
        if ($courses->isEmpty()) return false;

        $purchaseDate = $this->getStudentPurchaseDate($student);
        if (!$purchaseDate) return false;

        // First course: fake enrollment = purchase date - 12 days (enrollment_offset_days)
        $firstEnrollmentDate = $purchaseDate->copy()->addDays($rule->enrollment_offset_days);
        $firstCompletionDate = $firstEnrollmentDate->copy()->addDays($rule->completion_offset_days);

        // Create simulation for first course
        $this->createSimulationLog(
            $rule, $student, $courses[0], $firstEnrollmentDate, $firstCompletionDate, $adminId
        );

        // Process remaining courses sequentially
        for ($i = 1; $i < count($courses); $i++) {
            $course = $courses[$i];

            // Previous course completion date + 1 day gap
            $previousCompletionDate = $firstCompletionDate->copy()->addDays(($i - 1) * ($rule->completion_offset_days + $rule->inter_course_gap_days));
            $enrollmentDate = $previousCompletionDate->copy()->addDays($rule->inter_course_gap_days);
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
                'purchase_date' => $this->getStudentPurchaseDate($student),
                'fake_enroll_date' => $enrollmentDate,
                'fake_completion_date' => $completionDate,
                'status' => 'success',
                'notes' => "Simulated completion for {$rule->target_type} rule #{$rule->id}"
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
        switch ($rule->target_type) {
            case 'course':
                return \App\Models\Api\Webinar::where('id', $rule->target_id)
                    ->where('status', 'active')
                    ->where('type', 'course')
                    ->get();
            case 'student':
                return $this->getStudentCourses(\App\User::find($rule->target_id));
            case 'bundle':
                return $this->getBundleCourses(\App\Models\Bundle::find($rule->target_id));
            default:
                return collect();
        }
    }

    /**
     * Get students for simulation based on rule
     */
    private function getStudentsForSimulation(SimulationRule $rule)
    {
        switch ($rule->target_type) {
            case 'course':
                return $this->getStudentsWithCoursePurchase($rule->target_id);
            case 'student':
                return \App\User::where('id', $rule->target_id)
                    ->where('role_name', 'user')
                    ->where('status', 'active')
                    ->get();
            case 'bundle':
                return $this->getStudentsWithBundlePurchase($rule->target_id);
            default:
                return collect();
        }
    }

    /**
     * Get students who have purchased a specific course
     */
    private function getStudentsWithCoursePurchase($courseId)
    {
        return \App\User::where('role_name', 'user')
            ->where('status', 'active')
            ->where(function($query) use ($courseId) {
                $query->whereHas('sales', function($q) use ($courseId) {
                    $q->where('webinar_id', $courseId)
                        ->where('status', 'completed')
                        ->whereNull('refund_at');
                })
                ->orWhereHas('sales', function($q) use ($courseId) {
                    $q->whereNotNull('bundle_id')
                        ->where('status', 'completed')
                        ->whereNull('refund_at')
                        ->whereHas('bundle', function($bq) use ($courseId) {
                            $bq->whereHas('bundleWebinars', function($bwq) use ($courseId) {
                                $bwq->where('webinar_id', $courseId);
                            });
                        });
                });
            })
            ->get();
    }

    /**
     * Get students who have purchased a specific bundle
     */
    private function getStudentsWithBundlePurchase($bundleId)
    {
        return \App\User::where('role_name', 'user')
            ->where('status', 'active')
            ->whereHas('sales', function($query) use ($bundleId) {
                $query->where('bundle_id', $bundleId)
                    ->where('status', 'completed')
                    ->whereNull('refund_at');
            })
            ->get();
    }

    /**
     * Get bundles for simulation based on rule
     */
    private function getBundlesForSimulation(SimulationRule $rule)
    {
        if ($rule->target_type === 'bundle') {
            return \App\Models\Bundle::where('id', $rule->target_id)
                ->where('status', 'active')
                ->get();
        }

        return collect();
    }

    /**
     * Get student's purchased courses
     */
    private function getStudentCourses($student)
    {
        if (!$student) return collect();

        $courseIds = \App\Models\Sale::where('buyer_id', $student->id)
            ->where('status', 'completed')
            ->whereNull('refund_at')
            ->where(function($query) {
                $query->whereNotNull('webinar_id')
                    ->orWhereNotNull('bundle_id');
            })
            ->get()
            ->flatMap(function($sale) {
                if ($sale->webinar_id) {
                    return [$sale->webinar_id];
                } elseif ($sale->bundle_id) {
                    return $sale->bundle->bundleWebinars->pluck('webinar_id')->toArray();
                }
                return [];
            })
            ->unique()
            ->toArray();

        return \App\Models\Api\Webinar::whereIn('id', $courseIds)
            ->where('status', 'active')
            ->where('type', 'course')
            ->get();
    }

    /**
     * Get bundle students
     */
    private function getBundleStudents($bundle)
    {
        return \App\Models\Sale::where('bundle_id', $bundle->id)
            ->where('status', 'completed')
            ->whereNull('refund_at')
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
            ->whereNull('refund_at')
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
            ->whereNull('refund_at')
            ->whereHas('bundle', function($query) use ($course) {
                $query->whereHas('bundleWebinars', function($q) use ($course) {
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
     * Get course bundle info
     */
    private function getCourseBundleInfo($course)
    {
        $bundleWebinar = \App\Models\BundleWebinar::where('webinar_id', $course->id)
            ->with('bundle')
            ->first();

        return $bundleWebinar ? $bundleWebinar->bundle : null;
    }

    /**
     * Get bundle courses with proper ordering
     */
    private function getBundleCourses($bundle)
    {
        if (!$bundle) return collect();

        return $bundle->bundleWebinars()
            ->with('webinar')
            ->orderBy('sort_order', 'asc')
            ->get()
            ->pluck('webinar')
            ->filter(function($webinar) {
                return $webinar && $webinar->status === 'active' && $webinar->type === 'course';
            });
    }

    /**
     * Get student purchase date
     */
    private function getStudentPurchaseDate($student)
    {
        // Get the most recent purchase date for the student
        $latestPurchase = \App\Models\Sale::where('buyer_id', $student->id)
            ->where('status', 'completed')
            ->whereNull('refund_at')
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
     * Create or update completion record and trigger certificate generation
     */
    private function createOrUpdateCompletion($student, $course, $completionDate)
    {
        $courseLearning = \App\Models\CourseLearning::updateOrCreate(
            ['user_id' => $student->id, 'webinar_id' => $course->id],
            [
                'status' => 'completed',
                'progress' => 100,
                'completed_at' => $completionDate
            ]
        );

        // Trigger certificate generation if course supports certificates
        if ($course->certificate) {
            $this->generateCertificate($student, $course, $completionDate);
        }

        return $courseLearning;
    }

    /**
     * Generate certificate for completed course
     */
    private function generateCertificate($student, $course, $completionDate)
    {
        try {
            // Check if certificate already exists
            $existingCertificate = \App\Models\Certificate::where('student_id', $student->id)
                ->where('webinar_id', $course->id)
                ->where('type', 'course')
                ->first();

            if (!$existingCertificate) {
                // Create certificate using the existing certificate service
                $makeCertificate = new \App\Mixins\Certificate\MakeCertificate();
                $certificate = $makeCertificate->saveCourseCertificate($student, $course);

                if ($certificate) {
                    Log::info("Certificate generated for student {$student->id} in course {$course->id}");
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to generate certificate for student {$student->id} in course {$course->id}: " . $e->getMessage());
        }
    }

    /**
     * Execute simulation immediately without creating a rule
     */
    public function executeImmediate(SimulationRule $rule, array $data, $adminId)
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

            $userIds = $data['user_ids'];
            $users = User::whereIn('id', $userIds)->get();

            switch ($rule->target_type) {
                case 'course':
                    $courseIds = $data['course_ids'] ?? [];
                    $courses = Webinar::whereIn('id', $courseIds)->get();
                    $result = $this->executeImmediateCourseSimulation($rule, $users, $courses, $adminId);
                    break;
                case 'student':
                    $result = $this->executeImmediateStudentSimulation($rule, $users, $adminId);
                    break;
                case 'bundle':
                    $result = $this->executeImmediateBundleSimulation($rule, $users, $adminId);
                    break;
                default:
                    throw new \Exception('Invalid target type');
            }

            DB::commit();
            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Immediate simulation execution failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Simulation failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Execute immediate course simulation
     */
    private function executeImmediateCourseSimulation(SimulationRule $rule, $users, $courses, $adminId)
    {
        $totalProcessed = 0;
        $totalLogs = 0;

        foreach ($users as $user) {
            foreach ($courses as $course) {
                // Check if user purchased this course individually or as part of a bundle
                $purchaseInfo = $this->getStudentPurchaseInfo($user, $course);

                if ($purchaseInfo) {
                    // Check if this course is part of a bundle
                    $bundleInfo = $this->getCourseBundleInfo($course);

                    if ($bundleInfo) {
                        // If course is part of a bundle, simulate all courses in the bundle
                        $bundleCourses = $this->getBundleCourses($bundleInfo);
                        $processed = $this->processBundleCoursesSequentially(
                            $rule, $user, $bundleInfo, $bundleCourses, $adminId
                        );

                        if ($processed) {
                            $totalProcessed += count($bundleCourses);
                            $totalLogs += count($bundleCourses);
                        }
                    } else {
                        // Single course simulation
                        $processed = $this->processCourseSimulation(
                            $rule, $user, $course, $purchaseInfo, $adminId
                        );

                        if ($processed) {
                            $totalProcessed++;
                            $totalLogs++;
                        }
                    }
                }
            }
        }

        return [
            'success' => true,
            'message' => "Immediate course simulation completed. Processed: {$totalProcessed} enrollments, Created: {$totalLogs} logs",
            'courses_processed' => $totalProcessed,
            'students_processed' => count($users),
            'logs_created' => $totalLogs
        ];
    }

    /**
     * Execute immediate student simulation
     */
    private function executeImmediateStudentSimulation(SimulationRule $rule, $users, $adminId)
    {
        $totalProcessed = 0;
        $totalLogs = 0;

        foreach ($users as $user) {
            $userCourses = $this->getStudentCourses($user);
            $processed = $this->processStudentCoursesSequentially(
                $rule, $user, $userCourses, $adminId
            );

            if ($processed) {
                $totalProcessed++;
                $totalLogs += count($userCourses);
            }
        }

        return [
            'success' => true,
            'message' => "Immediate student simulation completed. Processed: {$totalProcessed} students, Created: {$totalLogs} logs",
            'courses_processed' => $totalProcessed,
            'students_processed' => count($users),
            'logs_created' => $totalLogs
        ];
    }

    /**
     * Execute immediate bundle simulation
     */
    private function executeImmediateBundleSimulation(SimulationRule $rule, $users, $adminId)
    {
        $totalProcessed = 0;
        $totalLogs = 0;

        foreach ($users as $user) {
            // Get all bundles the user has purchased
            $userBundles = $this->getUserBundles($user);

            foreach ($userBundles as $bundle) {
                $bundleCourses = $this->getBundleCourses($bundle);
                $processed = $this->processBundleCoursesSequentially(
                    $rule, $user, $bundle, $bundleCourses, $adminId
                );

                if ($processed) {
                    $totalProcessed++;
                    $totalLogs += count($bundleCourses);
                }
            }
        }

        return [
            'success' => true,
            'message' => "Immediate bundle simulation completed. Processed: {$totalProcessed} enrollments, Created: {$totalLogs} logs",
            'courses_processed' => $totalProcessed,
            'students_processed' => count($users),
            'logs_created' => $totalLogs
        ];
    }

    /**
     * Get user's purchased bundles
     */
    private function getUserBundles($user)
    {
        return Bundle::whereHas('sales', function($query) use ($user) {
            $query->where('buyer_id', $user->id)
                  ->where('status', 'completed')
                  ->whereNull('refund_at');
        })->where('status', 'active')->get();
    }

    /**
     * Generate preview of simulation results
     */
    public function generatePreview(SimulationRule $rule)
    {
        try {
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

            // Get courses and students for preview
            $courses = $this->getCoursesForSimulation($rule);
            $students = $this->getStudentsForSimulation($rule);

            $preview['estimated_impact']['courses_affected'] = $courses->count();
            $preview['estimated_impact']['students_affected'] = $students->count();
            $preview['estimated_impact']['simulations_created'] = $courses->count() * $students->count();

            // Generate sample timeline for first student and first few courses
            if ($students->count() > 0 && $courses->count() > 0) {
                $sampleStudent = $students->first();
                $sampleCourses = $courses->take(3);

                $purchaseDate = $this->getStudentPurchaseDate($sampleStudent);
                $currentDate = $purchaseDate->copy()->addDays($rule->enrollment_offset_days);

                foreach ($sampleCourses as $index => $course) {
                    if ($index === 0) {
                        $enrollmentDate = $currentDate;
                    } else {
                        $enrollmentDate = $currentDate->copy()->addDays($index * ($rule->completion_offset_days + $rule->inter_course_gap_days));
                    }

                    $completionDate = $enrollmentDate->copy()->addDays($rule->completion_offset_days);

                    $preview['sample_timeline'][] = [
                        'course' => $course->title ?? "Course #{$course->id}",
                        'enrollment_date' => $enrollmentDate->format('Y-m-d'),
                        'completion_date' => $completionDate->format('Y-m-d')
                    ];
                }
            }

            return $preview;

        } catch (\Exception $e) {
            Log::error('Preview generation failed: ' . $e->getMessage());
            return [
                'error' => 'Preview generation failed: ' . $e->getMessage()
            ];
        }
    }
}
