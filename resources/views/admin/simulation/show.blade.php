@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>Simulation Rule #{{ $rule->id }}</h1>
            <div class="section-header-breadcrumb">
                <a href="{{ route('admin.simulation.execute.view', $rule) }}" class="btn btn-primary">Execute</a>
                <a href="{{ route('admin.simulation.index') }}" class="btn btn-light">Back</a>
            </div>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table table-borderless mb-0">
                        <tr><th>Target Type</th><td>{{ ucfirst($rule->target_type) }}</td></tr>
                        <tr><th>Status</th><td><span class="badge badge-{{ $rule->status === 'active' ? 'success' : 'secondary' }}">{{ $rule->status }}</span></td></tr>
                        <tr><th>Offsets</th><td>{{ $rule->enrollment_offset_days }} / {{ $rule->completion_offset_days }} / {{ $rule->inter_course_gap_days }}</td></tr>
                        <tr><th>Created By</th><td>{{ optional($rule->creator)->full_name ?? '-' }}</td></tr>
                        <tr><th>Created At</th><td>{{ dateTimeFormat($rule->created_at, 'Y-m-d H:i', false) }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h4>Logs</h4></div>
                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Course</th>
                            <th>Purchase</th>
                            <th>Enroll</th>
                            <th>Complete</th>
                            <th>Status</th>
                            <th>Triggered By</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->id }}</td>
                                <td>{{ optional($log->user)->full_name ?? '-' }}</td>
                                <td>{{ optional($log->course)->title ?? ('#'.$log->course_id) }}</td>
                                <td>{{ optional($log->purchase_date)->format('Y-m-d') }}</td>
                                <td>{{ optional($log->fake_enroll_date)->format('Y-m-d') }}</td>
                                <td>{{ optional($log->fake_completion_date)->format('Y-m-d') }}</td>
                                <td><span class="badge badge-{{ $log->status === 'success' ? 'success' : 'warning' }}">{{ $log->status }}</span></td>
                                <td>{{ optional($log->admin)->full_name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center">No logs yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-center">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection


