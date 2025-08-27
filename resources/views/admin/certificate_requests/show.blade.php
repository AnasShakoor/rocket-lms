@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ $pageTitle }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item"><a href="{{ getAdminPanelUrl() }}/certificate-requests">Certificate Requests</a></div>
                <div class="breadcrumb-item">Details</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4>Request Information</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Request ID</label>
                                        <input type="text" class="form-control" value="#{{ $certificateRequest->id }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Status</label>
                                        <div>
                                            @if($certificateRequest->status === 'pending')
                                                <span class="badge badge-warning">Pending</span>
                                            @elseif($certificateRequest->status === 'approved')
                                                <span class="badge badge-success">Approved</span>
                                            @else
                                                <span class="badge badge-danger">Rejected</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Course Type</label>
                                        <input type="text" class="form-control" value="{{ ucfirst($certificateRequest->course_type) }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Request Date</label>
                                        <input type="text" class="form-control" value="{{ date('M d, Y H:i:s', $certificateRequest->created_at) }}" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Course Information</label>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">{{ $certificateRequest->course_title }}</h6>
                                        <p class="card-text mb-1"><strong>Course ID:</strong> {{ $certificateRequest->course_id }}</p>
                                        <p class="card-text mb-0"><strong>Course Type:</strong> {{ ucfirst($certificateRequest->course_type) }}</p>
                                    </div>
                                </div>
                            </div>

                            @if($certificateRequest->admin_notes)
                                <div class="form-group">
                                    <label class="form-label">Admin Notes</label>
                                    <textarea class="form-control" rows="3" readonly>{{ $certificateRequest->admin_notes }}</textarea>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h4>User Information</h4>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <img src="{{ $certificateRequest->user->getAvatar() }}" 
                                     alt="{{ $certificateRequest->user->full_name }}" 
                                     class="rounded-circle" style="width: 80px; height: 80px;">
                            </div>
                            <div class="text-center">
                                <h6>{{ $certificateRequest->user->full_name }}</h6>
                                <p class="text-muted">{{ $certificateRequest->user->email }}</p>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">User ID</small>
                                    <p class="mb-0">{{ $certificateRequest->user->id }}</p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Role</small>
                                    <p class="mb-0">{{ ucfirst($certificateRequest->user->role_name) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($certificateRequest->status === 'pending')
                        <div class="card">
                            <div class="card-header">
                                <h4>Take Action</h4>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Admin Notes (Optional)</label>
                                    <textarea class="form-control" id="adminNotes" rows="3" placeholder="Add notes about your decision..."></textarea>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-success" onclick="updateStatus('approved')">
                                        <i class="fas fa-check mr-1"></i> Approve Request
                                    </button>
                                    <button type="button" class="btn btn-danger" onclick="updateStatus('rejected')">
                                        <i class="fas fa-times mr-1"></i> Reject Request
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts_bottom')
<script>
    function updateStatus(status) {
        if (!confirm(`Are you sure you want to ${status} this request?`)) {
            return;
        }

        const adminNotes = document.getElementById('adminNotes').value;
        const button = event.target;
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        fetch(`{{ getAdminPanelUrl() }}/certificate-requests/{{ $certificateRequest->id }}/update-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ 
                status: status,
                admin_notes: adminNotes
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }
</script>
@endpush
