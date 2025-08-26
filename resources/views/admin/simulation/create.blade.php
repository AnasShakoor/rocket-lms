@extends('admin.layouts.app')

@section('title', 'Create Simulation Rule')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            
            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
            
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Create New Simulation Rule</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.simulation.store') }}" method="POST" id="simulationForm">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="target_type">Target Type <span class="text-danger">*</span></label>
                                    <select name="target_type" id="target_type" class="form-control @error('target_type') is-invalid @enderror" required>
                                        <option value="">Select Target Type</option>
                                        <option value="course" {{ old('target_type') == 'course' ? 'selected' : '' }}>Single Course</option>
                                        <option value="student" {{ old('target_type') == 'student' ? 'selected' : '' }}>Student (All Courses)</option>
                                        <option value="bundle" {{ old('target_type') == 'bundle' ? 'selected' : '' }}>Course Bundle</option>
                                    </select>
                                    @error('target_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="enrollment_offset_days">Enrollment Offset (Days) <span class="text-danger">*</span></label>
                                    <input type="number" name="enrollment_offset_days" id="enrollment_offset_days" 
                                           class="form-control @error('enrollment_offset_days') is-invalid @enderror" 
                                           value="{{ old('enrollment_offset_days', -11) }}" required>
                                    <small class="form-text text-muted">
                                        Negative value = days before purchase date (e.g., -11 means 11 days before purchase)
                                    </small>
                                    @error('enrollment_offset_days')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="completion_offset_days">Completion Offset (Days) <span class="text-danger">*</span></label>
                                    <input type="number" name="completion_offset_days" id="completion_offset_days" 
                                           class="form-control @error('completion_offset_days') is-invalid @enderror" 
                                           value="{{ old('completion_offset_days', 1) }}" min="1" required>
                                    <small class="form-text text-muted">
                                        Days after fake enrollment date (must be positive)
                                    </small>
                                    @error('completion_offset_days')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="inter_course_gap_days">Inter-Course Gap (Days) <span class="text-danger">*</span></label>
                                    <input type="number" name="inter_course_gap_days" id="inter_course_gap_days" 
                                           class="form-control @error('inter_course_gap_days') is-invalid @enderror" 
                                           value="{{ old('inter_course_gap_days', 1) }}" min="0" required>
                                    <small class="form-text text-muted">
                                        Gap between courses in sequence (0 = no gap)
                                    </small>
                                    @error('inter_course_gap_days')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status">Status <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row" id="courseOrderSection" style="display: none;">
                            <div class="col-12">
                                <div class="form-group">
                                    <label>Course Order (Drag to reorder)</label>
                                    <div id="courseOrderList" class="border rounded p-3">
                                        <p class="text-muted">Select a target first to see available courses</p>
                                    </div>
                                    <input type="hidden" name="course_order" id="courseOrderInput">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Create Rule
                                    </button>
                                    <a href="{{ route('admin.simulation.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back to List
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const courses = @json($courses);
    const bundles = @json($bundles);
    
    // Target type change handler
    $('#target_type').change(function() {
        const targetType = $(this).val();
        const courseOrderSection = $('#courseOrderSection');
        
        // Show/hide course order section based on target type
        if (targetType === 'course') {
            if (courses && courses.length > 0) {
                courseOrderSection.show();
            } else {
                courseOrderSection.hide();
            }
        } else if (targetType === 'student') {
            courseOrderSection.show();
        } else if (targetType === 'bundle') {
            if (bundles && bundles.length > 0) {
                courseOrderSection.show();
            } else {
                courseOrderSection.hide();
            }
        }
    });
    
    // Form validation
    $('#simulationForm').submit(function(e) {
        console.log('Form submission started');
        
        const targetType = $('#target_type').val();
        const enrollmentOffset = parseInt($('#enrollment_offset_days').val());
        const completionOffset = parseInt($('#completion_offset_days').val());
        const interCourseGap = parseInt($('#inter_course_gap_days').val());
        const status = $('#status').val();
        
        console.log('Form data:', {
            target_type: targetType,
            enrollment_offset_days: enrollmentOffset,
            completion_offset_days: completionOffset,
            inter_course_gap_days: interCourseGap,
            status: status
        });
        
        if (!targetType) {
            e.preventDefault();
            alert('Please select a target type');
            return false;
        }
        
        if (completionOffset <= 0) {
            e.preventDefault();
            alert('Completion offset must be greater than 0');
            return false;
        }
        
        if (enrollmentOffset >= 0 && completionOffset === 1) {
            if (!confirm('Warning: With a positive enrollment offset and 1-day completion offset, some courses might have the same enrollment and completion date. Continue?')) {
                e.preventDefault();
                return false;
            }
        }
        
        console.log('Form validation passed, submitting...');
        return true;
    });
});
</script>
@endpush
