@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>Create Simulation Rule</h1>
            <div class="section-header-breadcrumb">
                <a href="{{ route('admin.simulation.index') }}" class="btn btn-light">Back</a>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="section-body">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.simulation.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Target Type</label>
                                    <select name="target_type" class="form-control" required>
                                        <option value="course">Course</option>
                                        <option value="student">Student</option>
                                        <option value="bundle">Bundle</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Enrollment Offset (days)</label>
                                    <input type="number" class="form-control" name="enrollment_offset_days" value="-12" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Completion Offset (days)</label>
                                    <input type="number" class="form-control" name="completion_offset_days" value="2" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Inter-course Gap (days)</label>
                                    <input type="number" class="form-control" name="inter_course_gap_days" value="1" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Students</label>
                                    <select name="user_ids[]" class="form-control" multiple required>
                                        @foreach($students as $student)
                                            <option value="{{ $student->id }}">{{ $student->full_name }} ({{ $student->email }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Courses (for Target Type = Course)</label>
                                    <select name="course_ids[]" class="form-control" multiple>
                                        @foreach($courses as $course)
                                            <option value="{{ $course->id }}">{{ $course->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 text-right">
                                <button type="submit" class="btn btn-primary">Save Rule</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection


