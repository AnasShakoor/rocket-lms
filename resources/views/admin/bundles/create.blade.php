@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ $pageTitle }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="/admin/">{{trans('admin/main.dashboard')}}</a>
                </div>
                <div class="breadcrumb-item"><a href="{{ getAdminPanelUrl() }}/bundles">{{ trans('update.bundles') }}</a></div>
                <div class="breadcrumb-item">{{ $pageTitle}}</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>{{ trans('admin/main.create_new_bundle') }}</h4>
                        </div>
                        <div class="card-body">
                            <form action="{{ getAdminPanelUrl() }}/bundles/store" method="POST" enctype="multipart/form-data">
                                @csrf
                                
                                <div class="row">
                                    <!-- Basic Information -->
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label class="form-label">{{ trans('admin/main.title') }} *</label>
                                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" 
                                                   value="{{ old('title') }}" required>
                                            @error('title')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">{{ trans('admin/main.description') }} *</label>
                                            <textarea name="description" rows="5" class="form-control @error('description') is-invalid @enderror" 
                                                      required>{{ old('description') }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">{{ trans('admin/main.summary') }}</label>
                                            <textarea name="summary" rows="3" class="form-control">{{ old('summary') }}</textarea>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">{{ trans('admin/main.seo_description') }}</label>
                                            <textarea name="seo_description" rows="2" class="form-control">{{ old('seo_description') }}</textarea>
                                        </div>
                                    </div>

                                    <!-- Sidebar Settings -->
                                    <div class="col-md-4">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5>{{ trans('admin/main.bundle_settings') }}</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-group">
                                                    <label class="form-label">{{ trans('admin/main.teacher') }} *</label>
                                                    <select name="teacher_id" class="form-control @error('teacher_id') is-invalid @enderror" required>
                                                        <option value="">{{ trans('admin/main.select_teacher') }}</option>
                                                        @if(!empty($teachers))
                                                            @foreach($teachers as $teacher)
                                                                <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                                                    {{ $teacher->full_name }}
                                                                </option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    @error('teacher_id')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">{{ trans('admin/main.category') }} *</label>
                                                    <select name="category_id" class="form-control @error('category_id') is-invalid @enderror" required>
                                                        <option value="">{{ trans('admin/main.select_category') }}</option>
                                                        @if(!empty($categories))
                                                            @foreach($categories as $category)
                                                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                                    {{ $category->title }}
                                                                </option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    @error('category_id')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">{{ trans('admin/main.price') }}</label>
                                                    <input type="number" name="price" class="form-control" value="{{ old('price') }}" 
                                                           step="0.01" min="0" placeholder="0.00">
                                                    <small class="form-text text-muted">{{ trans('admin/main.leave_empty_for_free') }}</small>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">{{ trans('admin/main.access_days') }}</label>
                                                    <input type="number" name="access_days" class="form-control" value="{{ old('access_days') }}" 
                                                           min="1" placeholder="365">
                                                    <small class="form-text text-muted">{{ trans('admin/main.days_after_purchase') }}</small>
                                                </div>

                                                <div class="form-group">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" name="subscribe" class="custom-control-input" id="subscribe" value="1" {{ old('subscribe') ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="subscribe">{{ trans('admin/main.subscription_enabled') }}</label>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" name="certificate" class="custom-control-input" id="certificate" value="1" {{ old('certificate') ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="certificate">{{ trans('admin/main.certificate_enabled') }}</label>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">{{ trans('admin/main.status') }}</label>
                                                    <select name="status" class="form-control">
                                                        <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>{{ trans('admin/main.pending') }}</option>
                                                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>{{ trans('admin/main.active') }}</option>
                                                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>{{ trans('admin/main.inactive') }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Media Uploads -->
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">{{ trans('admin/main.thumbnail') }} *</label>
                                            <input type="file" name="thumbnail" class="form-control @error('thumbnail') is-invalid @enderror" 
                                                   accept="image/*" required>
                                            @error('thumbnail')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">{{ trans('admin/main.recommended_size') }}: 400x400px</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">{{ trans('admin/main.cover_image') }} *</label>
                                            <input type="file" name="image_cover" class="form-control @error('image_cover') is-invalid @enderror" 
                                                   accept="image/*" required>
                                            @error('image_cover')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">{{ trans('admin/main.recommended_size') }}: 1200x400px</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional Settings -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="form-label">{{ trans('admin/main.message_for_reviewer') }}</label>
                                            <textarea name="message_for_reviewer" rows="3" class="form-control" 
                                                      placeholder="{{ trans('admin/main.optional_message') }}">{{ old('message_for_reviewer') }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="form-group d-flex justify-content-between">
                                            <a href="{{ getAdminPanelUrl() }}/bundles" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left"></i> {{ trans('admin/main.cancel') }}
                                            </a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> {{ trans('admin/main.create_bundle') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts_bottom')
<script>
    $(document).ready(function() {
        // Form validation and enhancement
        console.log('Bundle creation form loaded successfully!');
        
        // Auto-generate slug from title
        $('input[name="title"]').on('input', function() {
            let title = $(this).val();
            // You can add slug generation logic here if needed
        });
    });
</script>
@endpush
