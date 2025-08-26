@extends('admin.layouts.app')

@section('title', 'Create BNPL Provider')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Create New BNPL Provider</h4>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.bnpl-providers.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Provider Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="logo_path">Logo</label>
                                    <input type="file" name="logo_path" id="logo_path"
                                           class="form-control @error('logo_path') is-invalid @enderror"
                                           accept="image/*">
                                    <small class="form-text text-muted">Accepted formats: JPEG, PNG, JPG, GIF (max 2MB)</small>
                                    @error('logo_path')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fee_percentage">Fee Percentage <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="fee_percentage" id="fee_percentage"
                                               class="form-control @error('fee_percentage') is-invalid @enderror"
                                               value="{{ old('fee_percentage', 0) }}"
                                               step="0.01" min="0" max="100" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    @error('fee_percentage')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="installment_count">Installment Count <span class="text-danger">*</span></label>
                                    <input type="number" name="installment_count" id="installment_count"
                                           class="form-control @error('installment_count') is-invalid @enderror"
                                           value="{{ old('installment_count', 4) }}"
                                           min="1" max="60" required>
                                    @error('installment_count')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="is_active">Status <span class="text-danger">*</span></label>
                                    <select name="is_active" id="is_active" class="form-control @error('is_active') is-invalid @enderror" required>
                                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    @error('is_active')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- BNPL Fee Section -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="surcharge_percentage">Surcharge in % <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="surcharge_percentage" id="surcharge_percentage"
                                               class="form-control @error('surcharge_percentage') is-invalid @enderror"
                                               value="{{ old('surcharge_percentage', 8) }}"
                                               step="0.01" min="8" max="100" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Min is 8%</small>
                                    @error('surcharge_percentage')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="bnpl_fee">BNPL Fee</label>
                                    <div class="input-group">
                                        <input type="number" name="bnpl_fee" id="bnpl_fee"
                                               class="form-control @error('bnpl_fee') is-invalid @enderror"
                                               value="{{ old('bnpl_fee', 0) }}"
                                               step="0.01" min="0" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">SAR</span>
                                        </div>
                                    </div>
                                    @error('bnpl_fee')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fee_description">Fee Description</label>
                                    <textarea name="fee_description" id="fee_description" rows="2"
                                              class="form-control @error('fee_description') is-invalid @enderror"
                                              placeholder="Fee added on top of (course price + VAT)">{{ old('fee_description') }}</textarea>
                                    @error('fee_description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- API Keys Section -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="public_api_key">Public API Key</label>
                                    <input type="text" name="public_api_key" id="public_api_key"
                                           class="form-control @error('public_api_key') is-invalid @enderror"
                                           value="{{ old('public_api_key') }}">
                                    @error('public_api_key')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="secret_api_key">Secret API Key</label>
                                    <input type="password" name="secret_api_key" id="secret_api_key"
                                           class="form-control @error('secret_api_key') is-invalid @enderror"
                                           value="{{ old('secret_api_key') }}">
                                    @error('secret_api_key')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Merchant Details Section -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="merchant_code">Merchant Code</label>
                                    <input type="text" name="merchant_code" id="merchant_code"
                                           class="form-control @error('merchant_code') is-invalid @enderror"
                                           value="{{ old('merchant_code') }}">
                                    @error('merchant_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="merchant_id">Merchant ID</label>
                                    <input type="text" name="merchant_id" id="merchant_id"
                                           class="form-control @error('merchant_id') is-invalid @enderror"
                                           value="{{ old('merchant_id') }}">
                                    @error('merchant_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- MISpay Plug-In Details Section -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="app_id">APP ID</label>
                                    <input type="text" name="app_id" id="app_id"
                                           class="form-control @error('app_id') is-invalid @enderror"
                                           value="{{ old('app_id') }}">
                                    @error('app_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="app_secret_key">APP Secret Key</label>
                                    <input type="password" name="app_secret_key" id="app_secret_key"
                                           class="form-control @error('app_secret_key') is-invalid @enderror"
                                           value="{{ old('app_secret_key') }}">
                                    @error('app_secret_key')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="widget_access_key">Widget Access Key</label>
                                    <input type="text" name="widget_access_key" id="widget_access_key"
                                           class="form-control @error('widget_access_key') is-invalid @enderror"
                                           value="{{ old('widget_access_key') }}">
                                    @error('widget_access_key')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="config">Configuration (JSON)</label>
                                    <textarea name="config" id="config" rows="4"
                                              class="form-control @error('config') is-invalid @enderror"
                                              placeholder='{"key": "value"}'>{{ old('config') }}</textarea>
                                    <small class="form-text text-muted">Optional JSON configuration for provider-specific settings</small>
                                    @error('config')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Create Provider
                                    </button>
                                    <a href="{{ route('admin.bnpl-providers.index') }}" class="btn btn-secondary">
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
    // Form validation
    $('#config').on('blur', function() {
        const value = $(this).val().trim();
        if (value && value !== '') {
            try {
                JSON.parse(value);
                $(this).removeClass('is-invalid').addClass('is-valid');
            } catch (e) {
                $(this).removeClass('is-valid').addClass('is-invalid');
            }
        } else {
            $(this).removeClass('is-valid is-invalid');
        }
    });
});
</script>
@endpush

