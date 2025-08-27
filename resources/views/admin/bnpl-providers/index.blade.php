@extends('admin.layouts.app')

@section('title', 'BNPL Providers')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">BNPL Providers</h4>
                    <a href="{{ route('admin.bnpl-providers.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Provider
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
                                    <th>Logo</th>
                                    <th>Name</th>
                                    <th>Fee %</th>
                                    <th>Surcharge %</th>
                                    <th>Installments</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($providers as $provider)
                                    <tr>
                                        <td>{{ $provider->id }}</td>
                                        <td>
                                            @if($provider->logo_path)
                                                <img src="{{ asset('storage/' . $provider->logo_path) }}"
                                                     alt="{{ $provider->name }}"
                                                     class="img-thumbnail"
                                                     style="max-width: 50px; max-height: 50px;">
                                            @else
                                                <span class="text-muted">No Logo</span>
                                            @endif
                                        </td>
                                        <td>{{ $provider->name }}</td>
                                        <td>{{ $provider->fee_percentage }}%</td>
                                        <td>{{ $provider->surcharge_percentage ?? 8 }}%</td>
                                        <td>{{ $provider->installment_count }}</td>
                                        <td>
                                            <span class="badge badge-{{ $provider->is_active ? 'success' : 'secondary' }}">
                                                {{ $provider->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>{{ $provider->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.bnpl-providers.edit', $provider) }}"
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.bnpl-providers.destroy', $provider) }}"
                                                      method="POST"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this provider?')">
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
                                        <td colspan="8" class="text-center">No BNPL providers found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $providers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Add any additional JavaScript functionality here
});
</script>
@endpush

