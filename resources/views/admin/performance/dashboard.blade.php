@extends('admin.layouts.app')

@section('title', 'Performance Dashboard')

@push('styles')
<style>
    .performance-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .metric-value {
        font-size: 2rem;
        font-weight: bold;
        color: #2563eb;
    }
    
    .metric-label {
        color: #6b7280;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .status-active {
        background-color: #dcfce7;
        color: #166534;
    }
    
    .status-error {
        background-color: #fef2f2;
        color: #dc2626;
    }
    
    .chart-container {
        height: 300px;
        margin: 20px 0;
    }
    
    .optimization-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        padding: 12px 24px;
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .optimization-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .cache-btn {
        background: #10b981;
        border: none;
        color: white;
        padding: 8px 16px;
        border-radius: 4px;
        font-size: 0.875rem;
        margin: 4px;
    }
    
    .cache-btn:hover {
        background: #059669;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Performance Dashboard</h1>
                <div>
                    <button class="optimization-btn" onclick="runOptimization()">
                        🚀 Run Full Optimization
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="row">
        <div class="col-md-3">
            <div class="performance-card text-center">
                <div class="metric-value">{{ formatBytes($metrics['memory_usage']) }}</div>
                <div class="metric-label">Memory Usage</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="performance-card text-center">
                <div class="metric-value">{{ formatBytes($metrics['peak_memory']) }}</div>
                <div class="metric-label">Peak Memory</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="performance-card text-center">
                <div class="metric-value">{{ $metrics['cache_hits'] }}</div>
                <div class="metric-label">Cache Hits</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="performance-card text-center">
                <div class="metric-value">{{ $metrics['cache_misses'] }}</div>
                <div class="metric-label">Cache Misses</div>
            </div>
        </div>
    </div>

    <!-- Cache Management -->
    <div class="row">
        <div class="col-md-6">
            <div class="performance-card">
                <h5 class="mb-3">Cache Management</h5>
                <div class="mb-3">
                    <strong>Driver:</strong> {{ $cacheStats['driver'] }}<br>
                    <strong>Status:</strong> 
                    <span class="status-badge {{ $cacheStats['status'] === 'Active' ? 'status-active' : 'status-error' }}">
                        {{ $cacheStats['status'] }}
                    </span><br>
                    <strong>Keys:</strong> {{ $cacheStats['keys_count'] }}<br>
                    <strong>Memory:</strong> {{ $cacheStats['memory_usage'] }}
                </div>
                <div>
                    <button class="cache-btn" onclick="clearCache('all')">Clear All</button>
                    <button class="cache-btn" onclick="clearCache('application')">App Cache</button>
                    <button class="cache-btn" onclick="clearCache('views')">View Cache</button>
                    <button class="cache-btn" onclick="clearCache('routes')">Route Cache</button>
                    <button class="cache-btn" onclick="clearCache('config')">Config Cache</button>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="performance-card">
                <h5 class="mb-3">Database Status</h5>
                <div class="mb-3">
                    <strong>Connection:</strong> {{ $databaseStats['connection'] }}<br>
                    <strong>Database:</strong> {{ $databaseStats['database'] }}<br>
                    <strong>Status:</strong> 
                    <span class="status-badge {{ $databaseStats['status'] === 'Connected' ? 'status-active' : 'status-error' }}">
                        {{ $databaseStats['status'] }}
                    </span><br>
                    <strong>Tables:</strong> {{ $databaseStats['tables_count'] }}<br>
                    <strong>Size:</strong> {{ $databaseStats['total_size'] }}
                </div>
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="row">
        <div class="col-md-6">
            <div class="performance-card">
                <h5 class="mb-3">System Information</h5>
                <div class="row">
                    <div class="col-6">
                        <strong>PHP Version:</strong><br>
                        <strong>Laravel Version:</strong><br>
                        <strong>Memory Limit:</strong><br>
                        <strong>Max Execution:</strong>
                    </div>
                    <div class="col-6">
                        {{ $systemStats['php_version'] }}<br>
                        {{ $systemStats['laravel_version'] }}<br>
                        {{ $systemStats['memory_limit'] }}<br>
                        {{ $systemStats['max_execution_time'] }}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="performance-card">
                <h5 class="mb-3">Storage Information</h5>
                <div class="row">
                    <div class="col-6">
                        <strong>Free Space:</strong><br>
                        <strong>Total Space:</strong><br>
                        <strong>Upload Max:</strong><br>
                        <strong>Post Max:</strong>
                    </div>
                    <div class="col-6">
                        {{ $systemStats['disk_free_space'] }}<br>
                        {{ $systemStats['disk_total_space'] }}<br>
                        {{ $systemStats['upload_max_filesize'] }}<br>
                        {{ $systemStats['post_max_size'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Charts -->
    <div class="row">
        <div class="col-12">
            <div class="performance-card">
                <h5 class="mb-3">Performance Trends</h5>
                <div class="mb-3">
                    <select id="periodSelect" class="form-select" style="width: auto;">
                        <option value="1h">Last Hour</option>
                        <option value="6h">Last 6 Hours</option>
                        <option value="24h" selected>Last 24 Hours</option>
                        <option value="7d">Last 7 Days</option>
                    </select>
                </div>
                <div class="chart-container">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 mb-0">Optimizing application...</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let performanceChart;

document.addEventListener('DOMContentLoaded', function() {
    initializeChart();
    loadMetrics();
    
    // Period selector change
    document.getElementById('periodSelect').addEventListener('change', function() {
        loadMetrics();
    });
});

function initializeChart() {
    const ctx = document.getElementById('performanceChart').getContext('2d');
    performanceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Execution Time (ms)',
                data: [],
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                tension: 0.4
            }, {
                label: 'Memory Usage (MB)',
                data: [],
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Time'
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Execution Time (ms)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Memory Usage (MB)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
}

function loadMetrics() {
    const period = document.getElementById('periodSelect').value;
    
    fetch(`/admin/performance/metrics?period=${period}`)
        .then(response => response.json())
        .then(data => {
            updateChart(data);
        })
        .catch(error => {
            console.error('Error loading metrics:', error);
        });
}

function updateChart(data) {
    const labels = data.labels.map(timestamp => {
        const date = new Date(timestamp * 1000);
        return date.toLocaleTimeString();
    });
    
    performanceChart.data.labels = labels;
    performanceChart.data.datasets[0].data = data.execution_times;
    performanceChart.data.datasets[1].data = data.memory_usage.map(bytes => bytes / 1024 / 1024); // Convert to MB
    
    performanceChart.update();
}

function runOptimization() {
    if (!confirm('This will run a full performance optimization. Continue?')) {
        return;
    }
    
    // Show loading modal
    const modal = new bootstrap.Modal(document.getElementById('loadingModal'));
    modal.show();
    
    fetch('/admin/performance/optimize', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        modal.hide();
        
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ ' + (data.error || 'Optimization failed'));
        }
    })
    .catch(error => {
        modal.hide();
        console.error('Error:', error);
        alert('❌ Optimization failed: ' + error.message);
    });
}

function clearCache(type) {
    if (!confirm(`This will clear the ${type} cache. Continue?`)) {
        return;
    }
    
    fetch(`/admin/performance/clear-cache/${type}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ ' + (data.error || 'Cache clearing failed'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Cache clearing failed: ' + error.message);
    });
}

// Helper function to format bytes
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}
</script>
@endpush
