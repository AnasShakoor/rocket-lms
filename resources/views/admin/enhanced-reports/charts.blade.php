@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📊 Enhanced Reports - Charts & Analytics</h3>
                </div>
                <div class="card-body">
                    <!-- Chart Filters -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label for="chartType">Chart Type</label>
                            <select id="chartType" class="form-control">
                                <option value="completion_rate">Completion Rate</option>
                                <option value="enrollment_trend">Enrollment Trend</option>
                                <option value="bundle_performance">Bundle Performance</option>
                                <option value="cme_hours">CME Hours Distribution</option>
                                <option value="bnpl_payments">BNPL Payment Analysis</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="dateRange">Date Range</label>
                            <select id="dateRange" class="form-control">
                                <option value="7">Last 7 Days</option>
                                <option value="30" selected>Last 30 Days</option>
                                <option value="90">Last 90 Days</option>
                                <option value="365">Last Year</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="courseFilter">Course Filter</label>
                            <select id="courseFilter" class="form-control">
                                <option value="">All Courses</option>
                                @foreach($courses ?? [] as $course)
                                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button id="generateChart" class="btn btn-primary btn-block mt-4">
                                <i class="fas fa-chart-bar"></i> Generate Chart
                            </button>
                        </div>
                    </div>

                    <!-- Chart Container -->
                    <div class="row">
                        <div class="col-12">
                            <div class="chart-container" style="position: relative; height:400px;">
                                <canvas id="mainChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Chart Statistics -->
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Users</span>
                                    <span class="info-box-number" id="totalUsers">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-graduation-cap"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Completion Rate</span>
                                    <span class="info-box-number" id="completionRate">0%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Avg. CME Hours</span>
                                    <span class="info-box-number" id="avgCmeHours">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger"><i class="fas fa-credit-card"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">BNPL Usage</span>
                                    <span class="info-box-number" id="bnplUsage">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let mainChart = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize chart
    initializeChart();
    
    // Event listeners
    document.getElementById('generateChart').addEventListener('click', generateChart);
    document.getElementById('chartType').addEventListener('change', updateChartType);
    document.getElementById('dateRange').addEventListener('change', updateChartData);
    
    // Load initial chart
    generateChart();
});

function initializeChart() {
    const ctx = document.getElementById('mainChart').getContext('2d');
    mainChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Course Completion Analytics'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function generateChart() {
    const chartType = document.getElementById('chartType').value;
    const dateRange = document.getElementById('dateRange').value;
    const courseFilter = document.getElementById('courseFilter').value;
    
    // Show loading state
    document.getElementById('generateChart').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    
    // Fetch chart data
    fetch(`/admin/enhanced-reports/chart-data?type=${chartType}&days=${dateRange}&course=${courseFilter}`)
        .then(response => response.json())
        .then(data => {
            updateChart(data);
            updateStatistics(data.statistics);
            document.getElementById('generateChart').innerHTML = '<i class="fas fa-chart-bar"></i> Generate Chart';
        })
        .catch(error => {
            console.error('Error fetching chart data:', error);
            document.getElementById('generateChart').innerHTML = '<i class="fas fa-chart-bar"></i> Generate Chart';
            
            // Show sample data for demonstration
            showSampleData(chartType);
        });
}

function updateChart(data) {
    if (!mainChart) return;
    
    mainChart.data.labels = data.labels;
    mainChart.data.datasets = data.datasets;
    mainChart.options.plugins.title.text = data.title;
    mainChart.update();
}

function updateStatistics(stats) {
    if (stats) {
        document.getElementById('totalUsers').textContent = stats.total_users || 0;
        document.getElementById('completionRate').textContent = (stats.completion_rate || 0) + '%';
        document.getElementById('avgCmeHours').textContent = (stats.avg_cme_hours || 0).toFixed(1);
        document.getElementById('bnplUsage').textContent = (stats.bnpl_usage || 0) + '%';
    }
}

function showSampleData(chartType) {
    let sampleData = {
        labels: [],
        datasets: [],
        title: 'Sample Data'
    };
    
    switch(chartType) {
        case 'completion_rate':
            sampleData = {
                labels: ['Course 1', 'Course 2', 'Course 3', 'Course 4', 'Course 5'],
                datasets: [{
                    label: 'Completion Rate (%)',
                    data: [85, 72, 91, 68, 79],
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }],
                title: 'Course Completion Rates'
            };
            break;
            
        case 'enrollment_trend':
            sampleData = {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'New Enrollments',
                    data: [45, 52, 38, 67],
                    backgroundColor: 'rgba(75, 192, 192, 0.8)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    fill: false
                }],
                title: 'Enrollment Trends'
            };
            break;
            
        case 'bundle_performance':
            sampleData = {
                labels: ['Bundle A', 'Bundle B', 'Bundle C'],
                datasets: [{
                    label: 'Sales Count',
                    data: [23, 45, 12],
                    backgroundColor: 'rgba(255, 99, 132, 0.8)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }],
                title: 'Bundle Performance'
            };
            break;
            
        case 'cme_hours':
            sampleData = {
                labels: ['0-2 hrs', '3-5 hrs', '6-8 hrs', '9+ hrs'],
                datasets: [{
                    label: 'Users',
                    data: [15, 28, 42, 18],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 205, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)'
                    ],
                    borderWidth: 1
                }],
                title: 'CME Hours Distribution'
            };
            break;
            
        case 'bnpl_payments':
            sampleData = {
                labels: ['Tamara', 'Tabby', 'Spotii', 'Other'],
                datasets: [{
                    label: 'Payment Count',
                    data: [67, 34, 23, 12],
                    backgroundColor: 'rgba(153, 102, 255, 0.8)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }],
                title: 'BNPL Payment Providers'
            };
            break;
    }
    
    updateChart(sampleData);
}

function updateChartType() {
    generateChart();
}

function updateChartData() {
    generateChart();
}
</script>

<style>
.info-box {
    display: flex;
    min-height: 80px;
    background: #fff;
    width: 100%;
    box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
    border-radius: 0.25rem;
}

.info-box-icon {
    border-radius: 0.25rem 0 0 0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.875rem;
    font-weight: 300;
    width: 70px;
    text-align: center;
    color: #fff;
}

.info-box-content {
    padding: 5px 10px;
    flex: 1;
}

.info-box-text {
    display: block;
    font-size: 1rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.info-box-number {
    display: block;
    font-weight: 700;
    font-size: 1.25rem;
}
</style>
@endsection

