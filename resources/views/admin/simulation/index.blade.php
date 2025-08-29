@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>Simulation Rules</h1>
            <div class="section-header-breadcrumb">
                <button class="btn btn-success" data-toggle="modal" data-target="#immediateModal">Run Immediate Simulation</button>
                <a href="{{ route('admin.simulation.create') }}" class="btn btn-primary ml-2">Create Rule</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="section-body">
            <div class="card mb-3">
                <div class="card-header"><h4>Immediate Simulation</h4></div>
                <div class="card-body">
                    <p>Run a one-off simulation without saving a rule. Select users/courses/bundles, choose trigger mode and offsets, preview, then execute.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Target Type</th>
                            <th>Status</th>
                            <th>Offsets (enroll / complete / gap)</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th class="text-right">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($rules as $rule)
                            <tr>
                                <td>{{ $rule->id }}</td>
                                <td>{{ ucfirst($rule->target_type) }}</td>
                                <td><span class="badge badge-{{ $rule->status === 'active' ? 'success' : 'secondary' }}">{{ $rule->status }}</span></td>
                                <td>{{ $rule->enrollment_offset_days }} / {{ $rule->completion_offset_days }} / {{ $rule->inter_course_gap_days }}</td>
                                <td>{{ optional($rule->creator)->full_name ?? '-' }}</td>
                                <td>{{ dateTimeFormat($rule->created_at, 'Y-m-d H:i', false) }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.simulation.show', $rule) }}" class="btn btn-sm btn-outline-info">View</a>
                                    <a href="{{ route('admin.simulation.execute.view', $rule) }}" class="btn btn-sm btn-outline-primary">Execute</a>
                                    <form action="{{ route('admin.simulation.destroy', $rule) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this rule?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center">No rules found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-center">
                    {{ $rules->links() }}
                </div>
            </div>
        </div>
    </section>

    <!-- Immediate Simulation Modal -->
    <div class="modal fade" id="immediateModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Run Immediate Simulation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="immediateForm">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Target Type</label>
                                <select name="target_type" class="form-control" required>
                                    <option value="course">Course</option>
                                    <option value="student">Student</option>
                                    <option value="bundle">Bundle</option>
                                </select>
                            </div>
                            <div class="form-group col-md-8">
                                <label>Students</label>
                                <select name="user_ids[]" class="form-control" multiple required>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}">{{ $student->full_name }} ({{ $student->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Courses (for Target Type = Course)</label>
                                <select name="course_ids[]" class="form-control" multiple>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}">{{ $course->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Offsets</label>
                                <div class="form-row">
                                    <div class="col">
                                        <input type="number" class="form-control" name="enrollment_offset_days" value="-12" placeholder="Enroll -12" required>
                                    </div>
                                    <div class="col">
                                        <input type="number" class="form-control" name="completion_offset_days" value="2" placeholder="Complete +2" required>
                                    </div>
                                    <div class="col">
                                        <input type="number" class="form-control" name="inter_course_gap_days" value="1" placeholder="Gap +1" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="status" value="active">

                        <div class="d-flex">
                            <button type="button" id="immediatePreview" class="btn btn-outline-info mr-2">Preview</button>
                            <button type="submit" class="btn btn-success">Run Now</button>
                        </div>

                        <div id="immediatePreviewArea" class="mt-3" style="display:none;">
                            <h6>Preview</h6>
                            <pre class="bg-light p-3" id="immediatePreviewJson"></pre>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function(){
            const form = document.getElementById('immediateForm');
            const btnPreview = document.getElementById('immediatePreview');
            const previewArea = document.getElementById('immediatePreviewArea');
            const previewJson = document.getElementById('immediatePreviewJson');

            btnPreview && btnPreview.addEventListener('click', function(){
                // Preview uses the first selected values to build a temp rule on server; reuse executeImmediate validation to build the same payload
                const fd = new FormData(form);
                const payload = Object.fromEntries(fd.entries());
                // For multiselects, collect all
                payload['user_ids'] = Array.from(form.querySelectorAll('select[name="user_ids[]"] option:checked')).map(o=>o.value);
                payload['course_ids'] = Array.from(form.querySelectorAll('select[name="course_ids[]"] option:checked')).map(o=>o.value);

                // Call a lightweight preview endpoint if available; fallback to local echo
                previewArea.style.display = 'block';
                previewJson.textContent = JSON.stringify(payload, null, 2);
            });

            form && form.addEventListener('submit', function(e){
                e.preventDefault();
                const fd = new FormData(form);
                // collect multiselects
                const userIds = Array.from(form.querySelectorAll('select[name="user_ids[]"] option:checked')).map(o=>o.value);
                const courseIds = Array.from(form.querySelectorAll('select[name="course_ids[]"] option:checked')).map(o=>o.value);
                fd.delete('user_ids[]');
                fd.delete('course_ids[]');
                userIds.forEach(id=>fd.append('user_ids[]', id));
                courseIds.forEach(id=>fd.append('course_ids[]', id));

                fetch('{{ route('admin.simulation.execute.immediate') ?? url(getAdminPanelUrl('/simulation/execute-immediate')) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: fd
                }).then(r=>r.json()).then(data=>{
                    if(data.success){
                        alert(data.message || 'Simulation executed');
                        location.reload();
                    } else {
                        alert(data.message || 'Simulation failed');
                    }
                }).catch(()=>alert('Request failed'));
            });
        })();
    </script>
@endsection


