<?php
$page_title = "Student Dashboard";

require_once '../include/config.php';

require_once '../layout/student/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2 sidebar p-3">
            <nav class="nav flex-column">
                <a class="nav-link active" href="#">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a class="nav-link" href="#">
                    <i class="bi bi-calendar-check"></i> My Attendance
                </a>
                <a class="nav-link" href="#">
                    <i class="bi bi-clock-history"></i> Attendance History
                </a>
                <a class="nav-link" href="#">
                    <i class="bi bi-graph-up"></i> Statistics
                </a>
                <a class="nav-link" href="#">
                    <i class="bi bi-person"></i> Profile
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Dashboard</h2>
                <span class="text-muted">Welcome back! <?php echo $student['email_address']; ?></span>
            </div>

            <?php echo addNumber(10, 20); ?>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card stat-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Total Attendance</h6>
                                    <h3 class="mb-0">85%</h3>
                                </div>
                                <div class="bg-primary bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-calendar-check text-primary" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card stat-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Present Days</h6>
                                    <h3 class="mb-0">42</h3>
                                </div>
                                <div class="bg-success bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card stat-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Absent Days</h6>
                                    <h3 class="mb-0">7</h3>
                                </div>
                                <div class="bg-danger bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-x-circle text-danger" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card stat-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">This Month</h6>
                                    <h3 class="mb-0">20/22</h3>
                                </div>
                                <div class="bg-info bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-calendar-month text-info" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity and Quick Actions -->
            <div class="row">
                <!-- Recent Attendance -->
                <div class="col-lg-8 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Attendance</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Day</th>
                                            <th>Status</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>2024-01-15</td>
                                            <td>Monday</td>
                                            <td><span class="badge bg-success">Present</span></td>
                                            <td>09:00 AM</td>
                                        </tr>
                                        <tr>
                                            <td>2024-01-14</td>
                                            <td>Sunday</td>
                                            <td><span class="badge bg-success">Present</span></td>
                                            <td>09:05 AM</td>
                                        </tr>
                                        <tr>
                                            <td>2024-01-13</td>
                                            <td>Saturday</td>
                                            <td><span class="badge bg-danger">Absent</span></td>
                                            <td>-</td>
                                        </tr>
                                        <tr>
                                            <td>2024-01-12</td>
                                            <td>Friday</td>
                                            <td><span class="badge bg-success">Present</span></td>
                                            <td>08:55 AM</td>
                                        </tr>
                                        <tr>
                                            <td>2024-01-11</td>
                                            <td>Thursday</td>
                                            <td><span class="badge bg-success">Present</span></td>
                                            <td>09:10 AM</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0"><i class="bi bi-lightning-charge"></i> Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary">
                                    <i class="bi bi-calendar-check"></i> Mark Attendance
                                </button>
                                <button class="btn btn-outline-primary">
                                    <i class="bi bi-download"></i> Download Report
                                </button>
                                <button class="btn btn-outline-secondary">
                                    <i class="bi bi-printer"></i> Print Report
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0"><i class="bi bi-info-circle"></i> Attendance Info</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <small class="text-muted">Required Attendance</small>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: 85%"></div>
                                </div>
                                <small>85% of 75% required</small>
                            </div>
                            <hr>
                            <div>
                                <p class="mb-1"><strong>Current Status:</strong></p>
                                <span class="badge bg-success">Good Standing</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../layout/student/footer.php';
?>

