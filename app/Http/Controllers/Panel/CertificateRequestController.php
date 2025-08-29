<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\CertificateRequest;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class CertificateRequestController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();
        $courseId = $request->input('course_id');
        $courseType = $request->input('course_type', 'webinar');

        // Debug logging
        Log::info('Certificate request attempt', [
            'user_id' => $user->id,
            'course_id' => $courseId,
            'course_type' => $courseType,
            'request_data' => $request->all()
        ]);

        // Validate that user has purchased this course
        $sale = Sale::where('buyer_id', $user->id)
            ->where(function ($query) use ($courseId, $courseType) {
                if ($courseType === 'webinar') {
                    $query->where('webinar_id', $courseId);
                } elseif ($courseType === 'bundle') {
                    $query->where('bundle_id', $courseId);
                }
            })
            ->where(function ($query) {
                // Handle both old and new sales table structures
                if (Schema::hasColumn('sales', 'refund_at')) {
                    $query->whereNull('refund_at');
                } else {
                    $query->where('status', '!=', 'refunded');
                }
            })
            ->first();

        // Debug logging for sale query
        Log::info('Sale query result', [
            'sale_found' => $sale ? true : false,
            'sale_id' => $sale ? $sale->id : null,
            'sale_details' => $sale ? [
                'webinar_id' => $sale->webinar_id,
                'bundle_id' => $sale->bundle_id,
                'status' => $sale->status ?? 'N/A',
                'refund_at' => $sale->refund_at ?? 'N/A'
            ] : null
        ]);

        // Additional debugging: check all sales for this user
        $allUserSales = Sale::where('buyer_id', $user->id)->get();
        Log::info('All user sales', [
            'total_sales' => $allUserSales->count(),
            'sales_details' => $allUserSales->map(function($sale) {
                return [
                    'id' => $sale->id,
                    'webinar_id' => $sale->webinar_id,
                    'bundle_id' => $sale->bundle_id,
                    'status' => $sale->status ?? 'N/A',
                    'refund_at' => $sale->refund_at ?? 'N/A'
                ];
            })->toArray()
        ]);

        if (!$sale) {
            // Additional debugging: check if the course exists at all
            $courseExists = false;
            if ($courseType === 'webinar') {
                $courseExists = \App\Models\Webinar::where('id', $courseId)->exists();
            } elseif ($courseType === 'bundle') {
                $courseExists = \App\Models\Bundle::where('id', $courseId)->exists();
            }

            Log::info('Course validation failed', [
                'course_exists' => $courseExists,
                'course_type' => $courseType,
                'course_id' => $courseId
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Course not found or not purchased'
            ], 400);
        }

        // Check if request already exists (any status)
        $existingRequest = CertificateRequest::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->where('course_type', $courseType)
            ->first();

        if ($existingRequest) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already requested a certificate for this course'
            ], 400);
        }

        // Create certificate request
        $certificateRequest = CertificateRequest::create([
            'user_id' => $user->id,
            'course_id' => $courseId,
            'course_type' => $courseType,
            'status' => 'pending',
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        // Send notification to admin
        $this->sendAdminNotification($user, $certificateRequest);

        // Send email to admin
        $this->sendAdminEmail($user, $certificateRequest);

        return response()->json([
            'status' => 'success',
            'message' => 'Request submitted successfully. Admin will review your request.'
        ]);
    }

    private function sendAdminNotification($user, $certificateRequest)
    {
        $notifyOptions = [
            '[u.name]' => $user->full_name,
            '[c.title]' => $certificateRequest->course_title,
            '[c.id]' => $certificateRequest->course_id,
        ];

        $admins = \App\User::where('role_name', 'admin')->get();
        foreach ($admins as $admin) {
            sendNotification('certificate_request_without_completion', $notifyOptions, $admin->id);
        }
    }

    private function sendAdminEmail($user, $certificateRequest)
    {
        $adminEmail = getGeneralSettings('admin_email') ?? config('mail.from.address');

        if ($adminEmail) {
            $data = [
                'user_name' => $user->full_name,
                'user_email' => $user->email,
                'course_name' => $certificateRequest->course_title,
                'course_id' => $certificateRequest->course_id,
                'course_type' => $certificateRequest->course_type,
                'request_date' => date('M d, Y H:i:s', $certificateRequest->created_at),
            ];

            try {
                Mail::send('emails.certificate_request_without_completion', $data, function($message) use ($adminEmail, $user, $certificateRequest) {
                    $message->to($adminEmail)
                            ->subject('Certificate Request Without Course Completion - ' . $certificateRequest->course_title);
                });
            } catch (\Exception $e) {
                // Log error but don't fail the request
                \Illuminate\Support\Facades\Log::error('Failed to send certificate request email: ' . $e->getMessage());
            }
        }
    }
}
