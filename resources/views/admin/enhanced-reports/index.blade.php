@extends('admin.layouts.app')

@section('title', 'Enhanced Reports')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Course Completion Reports</h4>
                </div>
                <div class="card-body">
                    <!-- Filters Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Filters</h5>
                                </div>
                                <div class="card-body">
                                    <form id="reportFiltersForm" method="GET">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="course_id">Course</label>
                                                    <select name="course_id" id="course_id" class="form-control">
                                                        <option value="">All Courses</option>
                                                        @foreach($filterOptions['courses'] as $course)
                                                            <option value="{{ $course->id }}" {{ $filters['course_id'] == $course->id ? 'selected' : '' }}>
                                                                {{ $course->title }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="bundle_id">Bundle</label>
                                                    <select name="bundle_id" id="bundle_id" class="form-control">
                                                        <option value="">All Bundles</option>
                                                        @foreach($filterOptions['bundles'] as $bundle)
                                                            <option value="{{ $bundle->id }}" {{ $filters['bundle_id'] == $bundle->id ? 'selected' : '' }}>
                                                                {{ $bundle->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="completion_status">Completion Status</label>
                                                    <select name="completion_status" id="completion_status" class="form-control">
                                                        <option value="">All Statuses</option>
                                                        @foreach($filterOptions['completion_statuses'] as $status)
                                                            <option value="{{ $status }}" {{ $filters['completion_status'] == $status ? 'selected' : '' }}>
                                                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="search">Search</label>
                                                    <input type="text" name="search" id="search" class="form-control" 
                                                           value="{{ $filters['search'] }}" placeholder="Search users, courses...">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mt-3">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="date_from">Date From</label>
                                                    <input type="date" name="date_from" id="date_from" class="form-control" 
                                                           value="{{ $filters['date_from'] }}">
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="date_to">Date To</label>
                                                    <input type="date" name="date_to" id="date_to" class="form-control" 
                                                           value="{{ $filters['date_to'] }}">
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="per_page">Per Page</label>
                                                    <select name="per_page" id="per_page" class="form-control">
                                                        <option value="20" {{ $filters['per_page'] == 20 ? 'selected' : '' }}>20</option>
                                                        <option value="50" {{ $filters['per_page'] == 50 ? 'selected' : '' }}>50</option>
                                                        <option value="100" {{ $filters['per_page'] == 100 ? 'selected' : '' }}>100</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>&nbsp;</label>
                                                    <div>
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="fas fa-search"></i> Apply Filters
                                                        </button>
                                                        <a href="{{ route('admin.enhanced-reports.index') }}" class="btn btn-secondary">
                                                            <i class="fas fa-times"></i> Clear
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-success" id="exportAndEmailBtn">
                                    <i class="fas fa-file-excel"></i> Export + Email + Archive
                                </button>
                                <button type="button" class="btn btn-info" id="exportOnlyBtn">
                                    <i class="fas fa-download"></i> Export Only
                                </button>
                                <button type="button" class="btn btn-warning" id="archiveOnlyBtn">
                                    <i class="fas fa-archive"></i> Archive Only
                                </button>
                            </div>
                            
                            <div class="float-right">
                                <a href="{{ route('admin.enhanced-reports.archived') }}" class="btn btn-secondary">
                                    <i class="fas fa-archive"></i> View Archived
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Results Table -->
                    <div class="table-responsive">
                        <table class="table table-striped" id="reportsTable">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAll">
                                    </th>
                                    <th>User</th>
                                    <th>Course</th>
                                    <th>Status</th>
                                    <th>Enrollment Date</th>
                                    <th>Completion Date</th>
                                    <th>Simulated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data as $row)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="user-checkbox" value="{{ $row->user_id }}" 
                                                   data-course-id="{{ $row->course_id }}">
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ $row->user_name }}</strong><br>
                                                <small class="text-muted">{{ $row->user_email }}</small><br>
                                                <small class="text-muted">{{ $row->user_profession ?? 'N/A' }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ $row->course_title }}</strong><br>
                                                <small class="text-muted">ID: {{ $row->course_id }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $row->completion_status === 'completed' ? 'success' : ($row->completion_status === 'in_progress' ? 'warning' : 'secondary') }}">
                                                {{ ucfirst(str_replace('_', ' ', $row->completion_status ?? 'pending')) }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $row->enrollment_date ? \Carbon\Carbon::parse($row->enrollment_date)->format('Y-m-d') : 'N/A' }}
                                        </td>
                                        <td>
                                            {{ $row->completion_date ? \Carbon\Carbon::parse($row->completion_date)->format('Y-m-d') : 'N/A' }}
                                        </td>
                                        <td>
                                            @if($row->simulation_id)
                                                <span class="badge badge-info">Yes</span>
                                                <br><small class="text-muted">
                                                    {{ $row->simulated_enrollment ? \Carbon\Carbon::parse($row->simulated_enrollment)->format('Y-m-d') : 'N/A' }}
                                                </small>
                                            @else
                                                <span class="badge badge-secondary">No</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-info" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-warning" title="Send Email">
                                                    <i class="fas fa-envelope"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No records found matching your criteria.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $data->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Report</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Selected Users: <span id="selectedCount">0</span></label>
                    <div id="selectedUsersList" class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                        <p class="text-muted">No users selected</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="exportFormat">Export Format</label>
                    <select id="exportFormat" class="form-control">
                        <option value="excel">Excel (.xlsx)</option>
                        <option value="csv">CSV (.csv)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="emailTemplate">Email Template</label>
                    <select id="emailTemplate" class="form-control">
                        <option value="cme_initiated">CME Initiated Successfully</option>
                        <option value="completion_certificate">Completion Certificate</option>
                        <option value="custom">Custom Message</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="emailSubject">Email Subject</label>
                    <input type="text" id="emailSubject" class="form-control" value="Your CME has been initiated successfully">
                </div>
                
                <div class="form-group">
                    <label for="archiveReason">Archive Reason (if archiving)</label>
                    <input type="text" id="archiveReason" class="form-control" placeholder="Reason for archiving...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmExportBtn">Confirm Export</button>
            </div>
        </div>
    </div>
</div>

<!-- Archive Modal -->
<div class="modal fade" id="archiveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Archive Records</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Selected Users: <span id="archiveSelectedCount">0</span></label>
                    <div id="archiveSelectedUsersList" class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                        <p class="text-muted">No users selected</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="archiveReasonRequired">Archive Reason <span class="text-danger">*</span></label>
                    <textarea id="archiveReasonRequired" class="form-control" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirmArchiveBtn">Confirm Archive</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let currentAction = '';
    
    // Select all functionality
    $('#selectAll').change(function() {
        $('.user-checkbox').prop('checked', $(this).is(':checked'));
        updateSelectedCount();
    });
    
    // Individual checkbox change
    $(document).on('change', '.user-checkbox', function() {
        updateSelectedCount();
        
        // Update select all checkbox
        const totalCheckboxes = $('.user-checkbox').length;
        const checkedCheckboxes = $('.user-checkbox:checked').length;
        
        if (checkedCheckboxes === 0) {
            $('#selectAll').prop('indeterminate', false).prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $('#selectAll').prop('indeterminate', false).prop('checked', true);
        } else {
            $('#selectAll').prop('indeterminate', true);
        }
    });
    
    // Update selected count
    function updateSelectedCount() {
        const selectedCount = $('.user-checkbox:checked').length;
        $('#selectedCount, #archiveSelectedCount').text(selectedCount);
        
        // Update selected users list
        const selectedUsers = [];
        $('.user-checkbox:checked').each(function() {
            const row = $(this).closest('tr');
            const userName = row.find('td:eq(1) strong').text();
            const userEmail = row.find('td:eq(1) small:first').text();
            selectedUsers.push(`${userName} (${userEmail})`);
        });
        
        if (selectedUsers.length > 0) {
            $('#selectedUsersList, #archiveSelectedUsersList').html(
                selectedUsers.map(user => `<div class="mb-1">${user}</div>`).join('')
            );
        } else {
            $('#selectedUsersList, #archiveSelectedUsersList').html('<p class="text-muted">No users selected</p>');
        }
    }
    
    // Export + Email + Archive button
    $('#exportAndEmailBtn').click(function() {
        if (validateSelection()) {
            currentAction = 'export_email_archive';
            $('#exportModal').modal('show');
        }
    });
    
    // Export only button
    $('#exportOnlyBtn').click(function() {
        if (validateSelection()) {
            currentAction = 'export_only';
            $('#exportModal').modal('show');
        }
    });
    
    // Archive only button
    $('#archiveOnlyBtn').click(function() {
        if (validateSelection()) {
            $('#archiveModal').modal('show');
        }
    });
    
    // Validate selection
    function validateSelection() {
        const selectedCount = $('.user-checkbox:checked').length;
        if (selectedCount === 0) {
            alert('Please select at least one user to proceed.');
            return false;
        }
        return true;
    }
    
    // Confirm export
    $('#confirmExportBtn').click(function() {
        const selectedUsers = getSelectedUserIds();
        const exportFormat = $('#exportFormat').val();
        const emailTemplate = $('#emailTemplate').val();
        const emailSubject = $('#emailSubject').val();
        const archiveReason = $('#archiveReason').val();
        
        if (currentAction === 'export_email_archive' && !archiveReason) {
            alert('Please provide an archive reason.');
            return;
        }
        
        // Perform export
        performExport(selectedUsers, exportFormat, emailTemplate, emailSubject, archiveReason);
    });
    
    // Confirm archive
    $('#confirmArchiveBtn').click(function() {
        const selectedUsers = getSelectedUserIds();
        const archiveReason = $('#archiveReasonRequired').val();
        
        if (!archiveReason.trim()) {
            alert('Please provide an archive reason.');
            return;
        }
        
        // Perform archive
        performArchive(selectedUsers, archiveReason);
    });
    
    // Get selected user IDs
    function getSelectedUserIds() {
        const userIds = [];
        $('.user-checkbox:checked').each(function() {
            userIds.push($(this).val());
        });
        return userIds;
    }
    
    // Perform export
    function performExport(userIds, format, emailTemplate, emailSubject, archiveReason) {
        const data = {
            user_ids: userIds,
            format: format,
            email_template: emailTemplate,
            subject: emailSubject,
            archive_reason: archiveReason,
            action: currentAction
        };
        
        $.ajax({
            url: '{{ route("admin.enhanced-reports.export") }}',
            method: 'POST',
            data: data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Download file
                    if (response.download_url) {
                        window.open(response.download_url, '_blank');
                    }
                    
                    // Send emails if needed
                    if (currentAction === 'export_email_archive' || currentAction === 'export_email') {
                        sendEmails(userIds, emailTemplate, emailSubject);
                    }
                    
                    // Archive if needed
                    if (currentAction === 'export_email_archive') {
                        performArchive(userIds, archiveReason);
                    }
                    
                    $('#exportModal').modal('hide');
                    showSuccessMessage(response.message);
                }
            },
            error: function(xhr) {
                showErrorMessage('Export failed: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    }
    
    // Send emails
    function sendEmails(userIds, emailTemplate, emailSubject) {
        $.ajax({
            url: '{{ route("admin.enhanced-reports.send-email") }}',
            method: 'POST',
            data: {
                user_ids: userIds,
                email_template: emailTemplate,
                subject: emailSubject
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.message);
                }
            },
            error: function(xhr) {
                showErrorMessage('Email sending failed: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    }
    
    // Perform archive
    function performArchive(userIds, archiveReason) {
        $.ajax({
            url: '{{ route("admin.enhanced-reports.archive") }}',
            method: 'POST',
            data: {
                user_ids: userIds,
                archive_reason: archiveReason
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#archiveModal').modal('hide');
                    showSuccessMessage(response.message);
                    
                    // Reload page to reflect changes
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                }
            },
            error: function(xhr) {
                showErrorMessage('Archive failed: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    }
    
    // Show success message
    function showSuccessMessage(message) {
        // You can implement your preferred notification system here
        alert('Success: ' + message);
    }
    
    // Show error message
    function showErrorMessage(message) {
        // You can implement your preferred notification system here
        alert('Error: ' + message);
    }
});
</script>
@endpush

