<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// session_start();
$page_title = "Student Dashboard";

require_once '../include/config.php';
require_once '../layout/student/header.php';

/* ================== AUTH / SESSION ================== */
if (!isset($_SESSION['student']['id'])) {
    header('Location: index.php');
    exit;
}
$student_id = $_SESSION['student']['id'];

/* ================== STUDENT INFO ================== */
$studentStmt = $conn->prepare("SELECT email_address FROM students WHERE id = ?");
$studentStmt->execute([$student_id]);
$student = $studentStmt->fetch(PDO::FETCH_ASSOC);

/* ================== TOTAL ATTENDANCE ================== */
$totalStmt = $conn->prepare("
    SELECT COUNT(ar.id)
    FROM attendance_records ar
    JOIN attendance_dates ad ON ad.id = ar.attendance_date_id
    WHERE ar.student_id = ?
");
$totalStmt->execute([$student_id]);
$total_days = (int)($totalStmt->fetchColumn() ?? 0);

/* ================== PRESENT DAYS ================== */
$presentStmt = $conn->prepare("
    SELECT COUNT(ar.id)
    FROM attendance_records ar
    WHERE ar.student_id = ? AND ar.status = 'present'
");
$presentStmt->execute([$student_id]);
$present_days = (int)($presentStmt->fetchColumn() ?? 0);

/* ================== ABSENT DAYS ================== */
$absent_days = max(0, $total_days - $present_days);

/* ================== ATTENDANCE PERCENTAGE ================== */
$attendance_percentage = $total_days > 0
    ? round(($present_days / $total_days) * 100)
    : 0;

/* ================== THIS MONTH ================== */
$monthStmt = $conn->prepare("
    SELECT 
        COUNT(ar.id) AS total,
        SUM(CASE WHEN ar.status='present' THEN 1 ELSE 0 END) AS present
    FROM attendance_records ar
    JOIN attendance_dates ad ON ad.id = ar.attendance_date_id
    WHERE ar.student_id = ?
    AND MONTH(ad.hosted_at) = MONTH(CURRENT_DATE())
    AND YEAR(ad.hosted_at) = YEAR(CURRENT_DATE())
");
$monthStmt->execute([$student_id]);
$month = $monthStmt->fetch(PDO::FETCH_ASSOC);

$month_total   = (int)($month['total'] ?? 0);
$month_present = (int)($month['present'] ?? 0);

/* ================== RECENT ATTENDANCE ================== */
$recentStmt = $conn->prepare("
    SELECT 
        ad.date AS attendance_date,
        ar.status,
        ar.marked_at,
        ar.attendance_score
    FROM attendance_records ar
    JOIN attendance_dates ad ON ad.id = ar.attendance_date_id
    WHERE ar.student_id = ?
    ORDER BY ad.hosted_at DESC
    LIMIT 5
");
$recentStmt->execute([$student_id]);
$recentAttendance = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

/* ================== STATUS ================== */
$current_status = $attendance_percentage >= 75 ? 'Good Standing' : 'At Risk';
$status_class  = $attendance_percentage >= 75 ? 'success' : 'danger';
?>

<!-- Main Content -->
<div class="col-md-9 col-lg-10">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Dashboard</h2>
         <span class="text-muted pr-5">Welcome back, <?= htmlspecialchars($_SESSION['student']['name'] ?? 'Student') ?></span>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <!-- Total Attendance -->
        <div class="col-md-3 mb-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Attendance</h6>
                            <h3 class="mb-0"><?= $attendance_percentage ?>%</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="bi bi-calendar-check text-primary" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Present Days -->
        <div class="col-md-3 mb-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Present Days</h6>
                            <h3 class="mb-0"><?= $present_days ?></h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Absent Days -->
        <div class="col-md-3 mb-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Absent Days</h6>
                            <h3 class="mb-0"><?= $absent_days ?></h3>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded">
                            <i class="bi bi-x-circle text-danger" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- This Month -->
        <div class="col-md-3 mb-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">This Month</h6>
                            <h3 class="mb-0"><?= $month_present ?>/<?= $month_total ?></h3>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded">
                            <i class="bi bi-calendar-month text-info" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Attendance -->
    <div class="row">
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
                                    <th>Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentAttendance as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['attendance_date']) ?></td>
                                        <td><?= date('l', strtotime($row['attendance_date'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $row['status'] === 'present' ? 'success' : 'danger' ?>">
                                                <?= ucfirst($row['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= $row['marked_at'] ? date('h:i A', strtotime($row['marked_at'])) : '-' ?></td>
                                        <td>
                                            <?php
                                            $score = is_numeric($row['attendance_score']) ? $row['attendance_score'] : 0;
                                            echo htmlspecialchars($score) . '%';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentAttendance)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No attendance records yet</td>
                                    </tr>
                                <?php endif; ?>
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
                        <a href="attendance.php" class="btn btn-primary">
                            <i class="bi bi-calendar-check"></i> Mark Attendance
                        </a>
                        <a href="attendance_pdf.php" class="btn btn-outline-primary">
                            <i class="bi bi-download"></i> Download Report
                        </a>
                        <button class="btn btn-outline-secondary" onclick="window.print()">
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
                            <div class="progress-bar bg-primary"
                                role="progressbar"
                                style="width: <?= $attendance_percentage ?>%">
                            </div>
                        </div>
                        <small><?= $attendance_percentage ?>% of 75% required</small>
                    </div>
                    <hr>
                    <div>
                        <p class="mb-1"><strong>Current Status:</strong></p>
                        <span class="badge bg-<?= $status_class ?>">
                            <?= $current_status ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../layout/student/footer.php'; ?>
