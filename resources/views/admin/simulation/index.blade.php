@extends('admin.layouts.app')

@section('title', 'Simulation Rules')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Simulation Rules</h4>
                    <a href="{{ route('admin.simulation.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Rule
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Target Type</th>
                                    <th>Enrollment Offset</th>
                                    <th>Completion Offset</th>
                                    <th>Inter-Course Gap</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rules as $rule)
                                    <tr>
                                        <td>{{ $rule->id }}</td>
                                        <td>
                                            <span class="badge badge-{{ $rule->target_type === 'course' ? 'primary' : ($rule->target_type === 'student' ? 'success' : 'info') }}">
                                                {{ ucfirst($rule->target_type) }}
                                            </span>
                                        </td>
                                        <td>{{ $rule->enrollment_offset_days }} days</td>
                                        <td>{{ $rule->completion_offset_days }} days</td>
                                        <td>{{ $rule->inter_course_gap_days }} days</td>
                                        <td>
                                            <span class="badge badge-{{ $rule->status === 'active' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($rule->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $rule->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.simulation.show', $rule) }}" 
                                                   class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-warning preview-btn" 
                                                        data-rule-id="{{ $rule->id }}"
                                                        title="Preview">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-success execute-btn" 
                                                        data-rule-id="{{ $rule->id }}"
                                                        title="Execute">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                                <form action="{{ route('admin.simulation.destroy', $rule) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this rule?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No simulation rules found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $rules->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Simulation Preview</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="previewContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Execute Modal -->
<div class="modal fade" id="executeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Execute Simulation</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to execute this simulation rule? This action cannot be undone.</p>
                <p><strong>Note:</strong> This will create fake enrollment and completion dates for the affected courses.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmExecute">Execute Simulation</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let currentRuleId = null;

    // Preview button click
    $('.preview-btn').click(function() {
        currentRuleId = $(this).data('rule-id');
        $('#previewModal').modal('show');
        
        // Load preview data
        $.post(`/admin/simulation/${currentRuleId}/preview`)
            .done(function(data) {
                displayPreview(data);
            })
            .fail(function(xhr) {
                $('#previewContent').html('<div class="alert alert-danger">Error loading preview: ' + xhr.responseText + '</div>');
            });
    });

    // Execute button click
    $('.execute-btn').click(function() {
        currentRuleId = $(this).data('rule-id');
        $('#executeModal').modal('show');
    });

    // Confirm execute
    $('#confirmExecute').click(function() {
        if (!currentRuleId) return;
        
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Executing...');
        
        $.post(`/admin/simulation/${currentRuleId}/execute`)
            .done(function(response) {
                if (response.success) {
                    $('#executeModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            })
            .fail(function(xhr) {
                alert('Error executing simulation: ' + xhr.responseText);
            })
            .always(function() {
                $('#confirmExecute').prop('disabled', false).html('Execute Simulation');
            });
    });

    function displayPreview(data) {
        let html = '<div class="table-responsive"><table class="table table-striped">';
        html += '<thead><tr><th>Course</th><th>Purchase Date</th><th>Fake Enroll Date</th><th>Fake Complete Date</th><th>Status</th><th>Notes</th></tr></thead><tbody>';
        
        data.forEach(function(item) {
            let statusClass = item.status === 'ready_to_simulate' ? 'success' : 
                             item.status === 'already_completed' ? 'warning' : 'danger';
            
            html += `<tr>
                <td>${item.course_title}</td>
                <td>${item.purchase_date || 'N/A'}</td>
                <td>${item.fake_enroll_date || 'N/A'}</td>
                <td>${item.fake_completion_date || 'N/A'}</td>
                <td><span class="badge badge-${statusClass}">${item.status.replace(/_/g, ' ')}</span></td>
                <td>${item.notes || '-'}</td>
            </tr>`;
        });
        
        html += '</tbody></table></div>';
        $('#previewContent').html(html);
    }
});
</script>
@endpush
