<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class CertificateRequestController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('admin_certificate_requests_list');

        // Check if the certificate_requests table exists
        if (!Schema::hasTable('certificate_requests')) {
            return view('admin.certificate_requests.setup_required', [
                'pageTitle' => 'Certificate Requests Setup Required',
                'message' => 'The certificate_requests table has not been created yet. Please run the database setup first.'
            ]);
        }

        try {
            $query = CertificateRequest::query()
                ->with(['user', 'course'])
                ->orderBy('created_at', 'desc');

            // Filter by status
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            // Filter by course type
            if ($request->has('course_type') && $request->course_type !== '') {
                $query->where('course_type', $request->course_type);
            }

            $certificateRequests = $query->paginate(20);

            $data = [
                'pageTitle' => 'Certificate Requests Without Completion',
                'certificateRequests' => $certificateRequests,
                'statuses' => ['pending', 'approved', 'rejected'],
                'courseTypes' => ['webinar', 'bundle'],
            ];

            return view('admin.certificate_requests.index', $data);
        } catch (\Exception $e) {
            Log::error('Error in CertificateRequestController@index: ' . $e->getMessage());

            return view('admin.certificate_requests.error', [
                'pageTitle' => 'Certificate Requests Error',
                'message' => 'An error occurred while loading certificate requests. Please check the logs for details.'
            ]);
        }
    }

    public function show($id)
    {
        $this->authorize('admin_certificate_requests_view');

        // Check if the certificate_requests table exists
        if (!Schema::hasTable('certificate_requests')) {
            return view('admin.certificate_requests.setup_required', [
                'pageTitle' => 'Certificate Requests Setup Required',
                'message' => 'The certificate_requests table has not been created yet. Please run the database setup first.'
            ]);
        }

        try {
            $certificateRequest = CertificateRequest::with(['user', 'course'])->findOrFail($id);

            $data = [
                'pageTitle' => 'Certificate Request Details',
                'certificateRequest' => $certificateRequest,
            ];

            return view('admin.certificate_requests.show', $data);
        } catch (\Exception $e) {
            Log::error('Error in CertificateRequestController@show: ' . $e->getMessage());

            return view('admin.certificate_requests.error', [
                'pageTitle' => 'Certificate Request Error',
                'message' => 'An error occurred while loading the certificate request. Please check the logs for details.'
            ]);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $this->authorize('admin_certificate_requests_edit');

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $certificateRequest = CertificateRequest::findOrFail($id);

        $certificateRequest->update([
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
            'updated_at' => time(),
        ]);

        // Send notification to user about status update
        $this->sendStatusUpdateNotification($certificateRequest);

        return response()->json([
            'status' => 'success',
            'message' => 'Certificate request status updated successfully'
        ]);
    }

    private function sendStatusUpdateNotification($certificateRequest)
    {
        $status = $certificateRequest->status;
        $courseTitle = $certificateRequest->course_title;

        $notifyOptions = [
            '[c.title]' => $courseTitle,
            '[status]' => ucfirst($status),
        ];

        $template = $status === 'approved'
            ? 'certificate_request_approved'
            : 'certificate_request_rejected';

        sendNotification($template, $notifyOptions, $certificateRequest->user_id);
    }

    public function bulkAction(Request $request)
    {
        $this->authorize('admin_certificate_requests_edit');

        $request->validate([
            'action' => 'required|in:approve,reject',
            'ids' => 'required|array',
            'ids.*' => 'exists:certificate_requests,id',
        ]);

        $action = $request->action;
        $ids = $request->ids;

        $status = ($action === 'approve') ? 'approved' : 'rejected';

        CertificateRequest::whereIn('id', $ids)->update([
            'status' => $status,
            'updated_at' => time(),
        ]);

        // Send notifications for all updated requests
        $updatedRequests = CertificateRequest::whereIn('id', $ids)->get();
        foreach ($updatedRequests as $certificateRequest) {
            $this->sendStatusUpdateNotification($certificateRequest);
        }

        return response()->json([
            'status' => 'success',
            'message' => count($ids) . ' certificate request(s) ' . $action . 'd successfully'
        ]);
    }
}
