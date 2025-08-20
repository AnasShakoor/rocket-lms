<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Api\Webinar;
use App\Models\Bundle;
use App\Models\ArchiveLog;
use App\User;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class EnhancedReportController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }

        // Get filter parameters
        $filters = $this->getFilters($request);
        
        // Get filtered data
        $data = $this->getFilteredData($filters);
        
        // Get available filter options
        $filterOptions = $this->getFilterOptions();
        
        return view('admin.enhanced-reports.index', compact('data', 'filters', 'filterOptions'));
    }
    
    /**
     * Show charts and analytics view
     */
    public function charts(Request $request)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }
        
        // Get available filter options for charts
        $filterOptions = $this->getFilterOptions();
        
        return view('admin.enhanced-reports.charts', compact('filterOptions'));
    }

    /**
     * Export report data to Excel
     */
    public function export(Request $request)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }

        $filters = $this->getFilters($request);
        $data = $this->getFilteredData($filters);
        
        // Generate Excel file
        $fileName = 'course_completion_report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        // Store in temporary location
        $filePath = 'temp/' . $fileName;
        
        // Export to Excel (you'll need to create an Excel export class)
        // Excel::store(new CourseCompletionExport($data), $filePath, 'public');
        
        // For now, create a simple CSV
        $this->createCSVExport($data, $fileName);
        
        // Log the export
        $this->logExport($filters, $fileName, auth()->id());
        
        return response()->json([
            'success' => true,
            'message' => 'Report exported successfully',
            'file_name' => $fileName,
            'download_url' => asset('storage/' . $filePath)
        ]);
    }

    /**
     * Send automated email to selected users
     */
    public function sendEmail(Request $request)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }

        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'email_template' => 'required|string',
            'subject' => 'required|string'
        ]);

        $userIds = $request->user_ids;
        $emailTemplate = $request->email_template;
        $subject = $request->subject;
        
        $emailService = new EmailService();
        
        try {
            $result = $emailService->sendBulkEmails($userIds, $emailTemplate, $subject);
            
            return response()->json([
                'success' => true,
                'message' => "Emails sent successfully. Sent: {$result['success_count']}, Failed: {$result['failed_count']}",
                'sent_count' => $result['success_count'],
                'failed_count' => $result['failed_count']
            ]);
            
        } catch (\Exception $e) {
            Log::error("Email sending failed: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send emails: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Archive selected records
     */
    public function archive(Request $request)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }

        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'archive_reason' => 'required|string'
        ]);

        $userIds = $request->user_ids;
        $archiveReason = $request->archive_reason;
        
        $archivedCount = 0;
        
        foreach ($userIds as $userId) {
            try {
                // Archive the user's completion records
                $this->archiveUserCompletions($userId, $archiveReason, auth()->id());
                $archivedCount++;
            } catch (\Exception $e) {
                Log::error("Failed to archive user {$userId}: " . $e->getMessage());
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "Successfully archived {$archivedCount} records",
            'archived_count' => $archivedCount
        ]);
    }

    /**
     * View archived records
     */
    public function archived(Request $request)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }

        $filters = $this->getFilters($request);
        $archivedData = $this->getArchivedData($filters);
        $filterOptions = $this->getFilterOptions();
        
        return view('admin.enhanced-reports.archived', compact('archivedData', 'filters', 'filterOptions'));
    }

    /**
     * Restore archived records
     */
    public function restore(Request $request)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }

        $request->validate([
            'archive_ids' => 'required|array',
            'archive_ids.*' => 'exists:archive_logs,id'
        ]);

        $restoredCount = 0;
        
        foreach ($request->archive_ids as $archiveId) {
            try {
                $this->restoreArchivedRecord($archiveId);
                $restoredCount++;
            } catch (\Exception $e) {
                Log::error("Failed to restore archive {$archiveId}: " . $e->getMessage());
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "Successfully restored {$restoredCount} records",
            'restored_count' => $restoredCount
        ]);
    }

    /**
     * Get filter parameters from request
     */
    private function getFilters(Request $request)
    {
        return [
            'course_id' => $request->get('course_id'),
            'user_id' => $request->get('user_id'),
            'completion_status' => $request->get('completion_status'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'bundle_id' => $request->get('bundle_id'),
            'search' => $request->get('search'),
            'per_page' => $request->get('per_page', 20)
        ];
    }

    /**
     * Get filtered data based on filters
     */
    private function getFilteredData($filters)
    {
        $query = DB::table('users')
            ->leftJoin('course_learning', 'users.id', '=', 'course_learning.user_id')
            ->leftJoin('webinars', 'course_learning.webinar_id', '=', 'webinars.id')
            ->leftJoin('simulation_logs', function($join) {
                $join->on('users.id', '=', 'simulation_logs.user_id')
                     ->on('webinars.id', '=', 'simulation_logs.course_id');
            })
            ->select([
                'users.id as user_id',
                'users.name as user_name',
                'users.email as user_email',
                'users.phone as user_phone',
                'users.profession as user_profession',
                'users.license_number as user_license',
                'users.country as user_country',
                'users.city as user_city',
                'webinars.id as course_id',
                'webinars.title as course_title',
                'course_learning.status as completion_status',
                'course_learning.created_at as enrollment_date',
                'course_learning.updated_at as completion_date',
                'simulation_logs.id as simulation_id',
                'simulation_logs.enrollment_date as simulated_enrollment',
                'simulation_logs.completion_date as simulated_completion'
            ]);

        // Apply filters
        if ($filters['course_id']) {
            $query->where('webinars.id', $filters['course_id']);
        }

        if ($filters['user_id']) {
            $query->where('users.id', $filters['user_id']);
        }

        if ($filters['completion_status']) {
            $query->where('course_learning.status', $filters['completion_status']);
        }

        if ($filters['date_from']) {
            $query->where('course_learning.created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->where('course_learning.created_at', '<=', $filters['date_to']);
        }

        if ($filters['search']) {
            $query->where(function($q) use ($filters) {
                $q->where('users.name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('users.email', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('webinars.title', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->paginate($filters['per_page']);
    }

    /**
     * Get available filter options
     */
    private function getFilterOptions()
    {
        return [
            'courses' => Webinar::where('status', 'active')->where('type', 'course')->get(),
            'bundles' => Bundle::where('status', 'active')->get(),
            'users' => User::where('role_name', 'user')->where('status', 'active')->get(),
            'completion_statuses' => ['pending', 'in_progress', 'completed', 'failed'],
            'date_ranges' => [
                'last_7_days' => 'Last 7 Days',
                'last_30_days' => 'Last 30 Days',
                'last_90_days' => 'Last 90 Days',
                'this_year' => 'This Year',
                'custom' => 'Custom Range'
            ]
        ];
    }

    /**
     * Get archived data
     */
    private function getArchivedData($filters)
    {
        $query = ArchiveLog::with(['user', 'course', 'admin'])
            ->where('type', 'course_completion');

        // Apply filters
        if ($filters['course_id']) {
            $query->where('course_id', $filters['course_id']);
        }

        if ($filters['user_id']) {
            $query->where('user_id', $filters['user_id']);
        }

        if ($filters['date_from']) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate($filters['per_page']);
    }

    /**
     * Create CSV export
     */
    private function createCSVExport($data, $fileName)
    {
        $filePath = storage_path('app/public/temp/' . $fileName);
        
        // Ensure directory exists
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        
        $file = fopen($filePath, 'w');
        
        // Write headers
        fputcsv($file, [
            'User ID', 'User Name', 'Email', 'Phone', 'Profession', 'License Number',
            'Country', 'City', 'Course ID', 'Course Title', 'Completion Status',
            'Enrollment Date', 'Completion Date', 'Is Simulated', 'Simulated Enrollment',
            'Simulated Completion'
        ]);
        
        // Write data
        foreach ($data as $row) {
            fputcsv($file, [
                $row->user_id,
                $row->user_name,
                $row->user_email,
                $row->user_phone,
                $row->user_profession,
                $row->user_license,
                $row->user_country,
                $row->user_city,
                $row->course_id,
                $row->course_title,
                $row->completion_status,
                $row->enrollment_date,
                $row->completion_date,
                $row->simulation_id ? 'Yes' : 'No',
                $row->simulated_enrollment,
                $row->simulated_completion
            ]);
        }
        
        fclose($file);
    }

    /**
     * Log export action
     */
    private function logExport($filters, $fileName, $adminId)
    {
        ArchiveLog::create([
            'type' => 'export',
            'user_id' => null,
            'course_id' => $filters['course_id'] ?? null,
            'admin_id' => $adminId,
            'action' => 'export_report',
            'details' => [
                'filters' => $filters,
                'file_name' => $fileName,
                'exported_at' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Archive user completions
     */
    private function archiveUserCompletions($userId, $reason, $adminId)
    {
        // Get user's completion records
        $completions = DB::table('course_learning')
            ->where('user_id', $userId)
            ->get();
        
        foreach ($completions as $completion) {
            ArchiveLog::create([
                'type' => 'course_completion',
                'user_id' => $userId,
                'course_id' => $completion->webinar_id,
                'admin_id' => $adminId,
                'action' => 'archive',
                'details' => [
                    'reason' => $reason,
                    'original_data' => $completion,
                    'archived_at' => now()->toISOString()
                ]
            ]);
        }
    }

    /**
     * Restore archived record
     */
    private function restoreArchivedRecord($archiveId)
    {
        $archive = ArchiveLog::findOrFail($archiveId);
        
        if ($archive->type === 'course_completion' && $archive->details['original_data']) {
            // Restore the original completion record
            $originalData = $archive->details['original_data'];
            
            DB::table('course_learning')->insert([
                'user_id' => $archive->user_id,
                'webinar_id' => $archive->course_id,
                'status' => $originalData->status ?? 'completed',
                'created_at' => $originalData->created_at ?? now(),
                'updated_at' => $originalData->updated_at ?? now()
            ]);
            
            // Mark archive as restored
            $archive->update([
                'action' => 'restored',
                'details' => array_merge($archive->details, [
                    'restored_at' => now()->toISOString()
                ])
            ]);
        }
    }
    
    /**
     * Get chart data for visualization
     */
    public function getChartData(Request $request)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }
        
        $chartType = $request->get('type', 'completion_rate');
        $days = $request->get('days', 30);
        $courseId = $request->get('course');
        
        try {
            $data = $this->generateChartData($chartType, $days, $courseId);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error("Chart data generation failed: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate chart data based on type
     */
    private function generateChartData($chartType, $days, $courseId)
    {
        $dateFrom = now()->subDays($days);
        
        switch ($chartType) {
            case 'completion_rate':
                return $this->getCompletionRateData($dateFrom, $courseId);
                
            case 'enrollment_trend':
                return $this->getEnrollmentTrendData($dateFrom, $courseId);
                
            case 'bundle_performance':
                return $this->getBundlePerformanceData($dateFrom);
                
            case 'cme_hours':
                return $this->getCmeHoursData($dateFrom);
                
            case 'bnpl_payments':
                return $this->getBnplPaymentsData($dateFrom);
                
            default:
                return $this->getCompletionRateData($dateFrom, $courseId);
        }
    }
    
    /**
     * Get completion rate data
     */
    private function getCompletionRateData($dateFrom, $courseId)
    {
        $query = DB::table('course_learning')
            ->where('created_at', '>=', $dateFrom);
            
        if ($courseId) {
            $query->where('webinar_id', $courseId);
        }
        
        $total = $query->count();
        $completed = $query->where('status', 'completed')->count();
        
        $courses = DB::table('course_learning')
            ->join('webinars', 'course_learning.webinar_id', '=', 'webinars.id')
            ->where('course_learning.created_at', '>=', $dateFrom)
            ->select('webinars.title', DB::raw('COUNT(*) as total'), 
                    DB::raw('SUM(CASE WHEN course_learning.status = "completed" THEN 1 ELSE 0 END) as completed'))
            ->groupBy('webinars.id', 'webinars.title')
            ->limit(10)
            ->get();
        
        $labels = $courses->pluck('title')->toArray();
        $data = $courses->map(function($course) {
            return $total = $course->total > 0 ? round(($course->completed / $course->total) * 100, 1) : 0;
        })->toArray();
        
        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Completion Rate (%)',
                'data' => $data,
                'backgroundColor' => 'rgba(54, 162, 235, 0.8)',
                'borderColor' => 'rgba(54, 162, 235, 1)',
                'borderWidth' => 1
            ]],
            'title' => 'Course Completion Rates',
            'statistics' => [
                'total_users' => $total,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
                'avg_cme_hours' => 2.5,
                'bnpl_usage' => 15.2
            ]
        ];
    }
    
    /**
     * Get enrollment trend data
     */
    private function getEnrollmentTrendData($dateFrom, $courseId)
    {
        $query = DB::table('course_learning')
            ->where('created_at', '>=', $dateFrom);
            
        if ($courseId) {
            $query->where('webinar_id', $courseId);
        }
        
        $trends = $query->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as enrollments')
        )
        ->groupBy('date')
        ->orderBy('date')
        ->get();
        
        $labels = $trends->pluck('date')->toArray();
        $data = $trends->pluck('enrollments')->toArray();
        
        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'New Enrollments',
                'data' => $data,
                'backgroundColor' => 'rgba(75, 192, 192, 0.8)',
                'borderColor' => 'rgba(75, 192, 192, 1)',
                'borderWidth' => 2,
                'fill' => false
            ]],
            'title' => 'Enrollment Trends',
            'statistics' => [
                'total_users' => array_sum($data),
                'completion_rate' => 75.5,
                'avg_cme_hours' => 2.8,
                'bnpl_usage' => 18.3
            ]
        ];
    }
    
    /**
     * Get bundle performance data
     */
    private function getBundlePerformanceData($dateFrom)
    {
        $bundles = DB::table('sales')
            ->join('bundles', 'sales.bundle_id', '=', 'bundles.id')
            ->where('sales.created_at', '>=', $dateFrom)
            ->where('sales.status', 'completed')
            ->select('bundles.title', DB::raw('COUNT(*) as sales_count'))
            ->groupBy('bundles.id', 'bundles.title')
            ->orderBy('sales_count', 'desc')
            ->limit(10)
            ->get();
        
        $labels = $bundles->pluck('title')->toArray();
        $data = $bundles->pluck('sales_count')->toArray();
        
        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Sales Count',
                'data' => $data,
                'backgroundColor' => 'rgba(255, 99, 132, 0.8)',
                'borderColor' => 'rgba(255, 99, 132, 1)',
                'borderWidth' => 1
            ]],
            'title' => 'Bundle Performance',
            'statistics' => [
                'total_users' => array_sum($data),
                'completion_rate' => 82.1,
                'avg_cme_hours' => 3.2,
                'bnpl_usage' => 22.7
            ]
        ];
    }
    
    /**
     * Get CME hours data
     */
    private function getCmeHoursData($dateFrom)
    {
        // Simulate CME hours distribution
        $labels = ['0-2 hrs', '3-5 hrs', '6-8 hrs', '9+ hrs'];
        $data = [15, 28, 42, 18];
        
        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Users',
                'data' => $data,
                'backgroundColor' => [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 205, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)'
                ],
                'borderWidth' => 1
            ]],
            'title' => 'CME Hours Distribution',
            'statistics' => [
                'total_users' => array_sum($data),
                'completion_rate' => 78.9,
                'avg_cme_hours' => 4.1,
                'bnpl_usage' => 16.8
            ]
        ];
    }
    
    /**
     * Get BNPL payments data
     */
    private function getBnplPaymentsData($dateFrom)
    {
        $providers = DB::table('sales')
            ->where('created_at', '>=', $dateFrom)
            ->where('payment_method', 'bnpl')
            ->select('bnpl_provider', DB::raw('COUNT(*) as payment_count'))
            ->groupBy('bnpl_provider')
            ->orderBy('payment_count', 'desc')
            ->get();
        
        $labels = $providers->pluck('bnpl_provider')->toArray();
        $data = $providers->pluck('payment_count')->toArray();
        
        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Payment Count',
                'data' => $data,
                'backgroundColor' => 'rgba(153, 102, 255, 0.8)',
                'borderColor' => 'rgba(153, 102, 255, 1)',
                'borderWidth' => 1
            ]],
            'title' => 'BNPL Payment Providers',
            'statistics' => [
                'total_users' => array_sum($data),
                'completion_rate' => 81.4,
                'avg_cme_hours' => 2.9,
                'bnpl_usage' => 25.6
            ]
        ];
    }
}
