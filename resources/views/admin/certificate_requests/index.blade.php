@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ $pageTitle }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item">{{ $pageTitle }}</div>
            </div>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-header justify-content-between">
                    <div>
                        <h5 class="font-14 mb-0">{{ $pageTitle }}</h5>
                        <p class="font-12 mt-4 mb-0 text-gray-500">Manage certificate requests without course completion</p>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" id="statusFilter">
                                <option value="">All Statuses</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                        {{ ucfirst($status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Course Type</label>
                            <select class="form-control" id="courseTypeFilter">
                                <option value="">All Types</option>
                                @foreach($courseTypes as $type)
                                    <option value="{{ $type }}" {{ request('course_type') == $type ? 'selected' : '' }}>
                                        {{ ucfirst($type) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-primary btn-block" onclick="applyFilters()">
                                Apply Filters
                            </button>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-outline-secondary btn-block" onclick="clearFilters()">
                                Clear Filters
                            </button>
                        </div>
                    </div>

                    <!-- Requests Table -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Course</th>
                                    <th>Course Type</th>
                                    <th>Status</th>
                                    <th>Request Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($certificateRequests as $request)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm mr-2">
                                                    <img src="{{ $request->user->getAvatar() }}" alt="{{ $request->user->full_name }}" class="rounded-circle">
                                                </div>
                                                <div>
                                                    <div class="font-weight-bold">{{ $request->user->full_name }}</div>
                                                    <small class="text-muted">{{ $request->user->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $request->course_title }}</div>
                                            <small class="text-muted">ID: {{ $request->course_id }}</small>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ ucfirst($request->course_type) }}</span>
                                        </td>
                                        <td>
                                            @if($request->status === 'pending')
                                                <span class="badge badge-warning">Pending</span>
                                            @elseif($request->status === 'approved')
                                                <span class="badge badge-success">Approved</span>
                                            @else
                                                <span class="badge badge-danger">Rejected</span>
                                            @endif
                                        </td>
                                        <td>{{ date('M d, Y H:i', $request->created_at) }}</td>
                                        <td>
                                            <a href="{{ getAdminPanelUrl() }}/certificate-requests/{{ $request->id }}"
                                               class="btn btn-sm btn-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($request->status === 'pending')
                                                <button type="button" class="btn btn-sm btn-success"
                                                        onclick="updateStatus({{ $request->id }}, 'approved')" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger"
                                                        onclick="updateStatus({{ $request->id }}, 'rejected')" title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-inbox fa-2x mb-3"></i>
                                                <p>No certificate requests found</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($certificateRequests->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $certificateRequests->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts_bottom')
<script>
    function applyFilters() {
        const status = document.getElementById('statusFilter').value;
        const courseType = document.getElementById('courseTypeFilter').value;

        let url = new URL(window.location);
        if (status) url.searchParams.set('status', status);
        else url.searchParams.delete('status');

        if (courseType) url.searchParams.set('course_type', courseType);
        else url.searchParams.delete('course_type');

        window.location.href = url.toString();
    }

    function clearFilters() {
        window.location.href = window.location.pathname;
    }

    function updateStatus(requestId, status) {
        if (!confirm(`Are you sure you want to ${status} this request?`)) {
            return;
        }

        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        fetch(`{{ getAdminPanelUrl() }}/certificate-requests/${requestId}/update-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ status: status })
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
