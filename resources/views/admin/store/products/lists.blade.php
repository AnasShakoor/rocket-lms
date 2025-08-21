@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ $pageTitle }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="/admin/">{{trans('admin/main.dashboard')}}</a>
                </div>
                <div class="breadcrumb-item">{{ $pageTitle}}</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>{{ trans('admin/main.store_products') }}</h4>
                            <div class="card-header-action">
                                <a href="{{ getAdminPanelUrl() }}/store/products/create" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> {{ trans('admin/main.new_product') }}
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @if(!empty($products) and !$products->isEmpty())
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>{{ trans('admin/main.product') }}</th>
                                                <th>{{ trans('admin/main.seller') }}</th>
                                                <th>{{ trans('admin/main.category') }}</th>
                                                <th>{{ trans('admin/main.status') }}</th>
                                                <th>{{ trans('admin/main.price') }}</th>
                                                <th>{{ trans('admin/main.sales') }}</th>
                                                <th>{{ trans('admin/main.actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($products as $product)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            @if(!empty($product->thumbnail))
                                                                <img src="{{ $product->thumbnail }}" alt="{{ $product->title }}" class="rounded mr-3" width="50" height="50">
                                                            @else
                                                                <div class="rounded bg-secondary mr-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                                    <i class="fas fa-box text-white"></i>
                                                                </div>
                                                            @endif
                                                            <div>
                                                                <strong>{{ $product->title }}</strong>
                                                                @if(!empty($product->summary))
                                                                    <br><small class="text-muted">{{ Str::limit($product->summary, 50) }}</small>
                                                                @endif
                                                                <br><small class="text-muted">ID: {{ $product->id }}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if(!empty($product->seller))
                                                            <span class="badge badge-info">{{ $product->seller->full_name }}</span>
                                                        @else
                                                            <span class="badge badge-secondary">{{ trans('admin/main.no_seller') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(!empty($product->category))
                                                            <span class="badge badge-secondary">{{ $product->category->title }}</span>
                                                        @else
                                                            <span class="badge badge-light">{{ trans('admin/main.no_category') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($product->status == 'active')
                                                            <span class="badge badge-success">{{ trans('admin/main.active') }}</span>
                                                        @elseif($product->status == 'pending')
                                                            <span class="badge badge-warning">{{ trans('admin/main.pending') }}</span>
                                                        @elseif($product->status == 'inactive')
                                                            <span class="badge badge-danger">{{ trans('admin/main.inactive') }}</span>
                                                        @else
                                                            <span class="badge badge-secondary">{{ $product->status }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(!empty($product->price) && $product->price > 0)
                                                            <span class="text-success font-weight-bold">{{ number_format($product->price, 2) }}</span>
                                                        @else
                                                            <span class="text-muted">{{ trans('admin/main.free') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(!empty($product->sales) && $product->sales->count() > 0)
                                                            <span class="badge badge-primary">{{ $product->sales->count() }}</span>
                                                        @else
                                                            <span class="badge badge-light">0</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="{{ getAdminPanelUrl() }}/store/products/{{ $product->id }}/edit" class="btn btn-sm btn-info" title="{{ trans('admin/main.edit') }}">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            
                                                            @if($product->status == 'pending')
                                                                <a href="{{ getAdminPanelUrl() }}/store/products/{{ $product->id }}/approve" class="btn btn-sm btn-success" title="{{ trans('admin/main.approve') }}" onclick="return confirm('{{ trans('admin/main.are_you_sure_approve') }}')">
                                                                    <i class="fas fa-check"></i>
                                                                </a>
                                                                <a href="{{ getAdminPanelUrl() }}/store/products/{{ $product->id }}/reject" class="btn btn-sm btn-danger" title="{{ trans('admin/main.reject') }}" onclick="return confirm('{{ trans('admin/main.are_you_sure_reject') }}')">
                                                                    <i class="fas fa-times"></i>
                                                                </a>
                                                            @endif
                                                            
                                                            @if($product->status == 'active')
                                                                <a href="{{ getAdminPanelUrl() }}/store/products/{{ $product->id }}/unpublish" class="btn btn-sm btn-warning" title="{{ trans('admin/main.unpublish') }}" onclick="return confirm('{{ trans('admin/main.are_you_sure_unpublish') }}')">
                                                                    <i class="fas fa-eye-slash"></i>
                                                                </a>
                                                            @endif
                                                            
                                                            <a href="{{ getAdminPanelUrl() }}/store/products/{{ $product->id }}/reviews" class="btn btn-sm btn-primary" title="{{ trans('admin/main.reviews') }}">
                                                                <i class="fas fa-star"></i>
                                                            </a>
                                                            
                                                            <a href="{{ getAdminPanelUrl() }}/store/products/{{ $product->id }}/delete" class="btn btn-sm btn-danger" title="{{ trans('admin/main.delete') }}" onclick="return confirm('{{ trans('admin/main.are_you_sure_delete') }}')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                @if($products->hasPages())
                                    <div class="d-flex justify-content-center">
                                        {{ $products->links() }}
                                    </div>
                                @endif
                                
                                <div class="mt-4">
                                    <div class="row text-center">
                                        <div class="col-md-3">
                                            <div class="card bg-primary text-white">
                                                <div class="card-body">
                                                    <h4>{{ $totalProducts ?? 0 }}</h4>
                                                    <p class="mb-0">{{ trans('admin/main.total_products') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-warning text-white">
                                                <div class="card-body">
                                                    <h4>{{ $totalPendingProducts ?? 0 }}</h4>
                                                    <p class="mb-0">{{ trans('admin/main.pending_products') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-success text-white">
                                                <div class="card-body">
                                                    <h4>{{ $totalSales ?? 0 }}</h4>
                                                    <p class="mb-0">{{ trans('admin/main.total_sales') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-info text-white">
                                                <div class="card-body">
                                                    <h4>{{ $totalRevenue ?? 0 }}</h4>
                                                    <p class="mb-0">{{ trans('admin/main.total_revenue') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <img src="/assets/default/img/empty.png" alt="No products" class="img-fluid" style="max-width: 200px;">
                                    <h4 class="mt-3">{{ trans('admin/main.no_products_found') }}</h4>
                                    <p class="text-muted">{{ trans('admin/main.no_products_found_hint') }}</p>
                                    <a href="{{ getAdminPanelUrl() }}/store/products/create" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> {{ trans('admin/main.create_first_product') }}
                                    </a>
                                </div>
                            @endif
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
        // Store products management functionality
        console.log('Store products management loaded successfully!');
    });
</script>
@endpush
