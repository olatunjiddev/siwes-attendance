<?php
// session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../include/config.php'; // Database connection
require_once '../layout/admin/header.php';

$page_title = "Admin Dashboard";

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

/* ================= TODAY ATTENDANCE STATUS ================= */
$today = date('Y-m-d');
$attendance_status = 'Not Hosted';
$unmarked_students = 0;

// Fetch today's attendance date
$stmt = $conn->prepare("SELECT id, hosted_at, closed FROM attendance_dates WHERE date = ?");
$stmt->execute([$today]);
$today_date = $stmt->fetch(PDO::FETCH_ASSOC);

if ($today_date) {
    $hosted_at_time = strtotime($today_date['hosted_at'] ?? '');
    $now = time();

    // Count students not marked today
    $stmt2 = $conn->prepare("
        SELECT COUNT(*) 
        FROM students s
        WHERE NOT EXISTS (
            SELECT 1 
            FROM attendance_records ar
            WHERE ar.student_id = s.id
            AND ar.attendance_date_id = ?
        )
    ");
    $stmt2->execute([$today_date['id']]);
    $unmarked_students = (int)$stmt2->fetchColumn();

    // Determine status
    if (!empty($today_date['closed'])) {
        $attendance_status = 'Closed';
    } elseif ($unmarked_students > 0) {
        $attendance_status = 'Pending';
    } else {
        $attendance_status = 'Completed';
    }
}

/* ================= STATISTICS ================= */
$total_students = (int)$conn->query("SELECT COUNT(*) FROM students")->fetchColumn();
$total_present = (int)$conn->query("SELECT COUNT(*) FROM attendance_records WHERE status = 'present'")->fetchColumn();
$total_absent = (int)$conn->query("SELECT COUNT(*) FROM attendance_records WHERE status = 'absent'")->fetchColumn();

/* ================= THIS MONTH SUMMARY ================= */
$monthStmt = $conn->query("
    SELECT 
        COUNT(*) AS total,
        SUM(ar.status = 'present') AS present,
        SUM(ar.status = 'absent') AS absent
    FROM attendance_records ar
    JOIN attendance_dates ad ON ad.id = ar.attendance_date_id
    WHERE MONTH(ad.date) = MONTH(CURRENT_DATE())
      AND YEAR(ad.date) = YEAR(CURRENT_DATE())
");
$month = $monthStmt->fetch(PDO::FETCH_ASSOC);

/* ================= RECENT ATTENDANCE ================= */
$recentStmt = $conn->query("
    SELECT 
        s.email_address,
        ad.date AS attendance_date,
        ar.status,
        ar.marked_at
    FROM attendance_records ar
    JOIN students s ON s.id = ar.student_id
    JOIN attendance_dates ad ON ad.id = ar.attendance_date_id
    ORDER BY ad.date DESC, ar.marked_at DESC
    LIMIT 10
");
$recentAttendance = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar p-3">
            <div class="text-center mb-4">
                <h4 class="fw-bold text-primary mb-0">SIWES Admin</h4>
                <small class="text-muted">Attendance System</small>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link active" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link" href="manage_students.php"><i class="bi bi-people"></i> Students</a>
                <a class="nav-link" href="attendance_records.php"><i class="bi bi-calendar-check"></i> Attendance Records</a>
                <a class="nav-link" href="host_attendance.php"><i class="bi bi-graph-up"></i> Host Attendance</a>
                <a class="nav-link" href="profile.php"><i class="bi bi-person"></i> Profile</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Dashboard</h2>
                <span class="text-muted">Welcome back, <?= htmlspecialchars($_SESSION['admin']['email_address'] ?? 'Admin') ?></span>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <?php 
                $stats = [
                    ['label'=>'Total Students','value'=>$total_students,'icon'=>'bi-people','color'=>'text-primary'],
                    ['label'=>'Present Records','value'=>$total_present,'icon'=>'bi-check-circle','color'=>'text-success'],
                    ['label'=>'Absent Records','value'=>$total_absent,'icon'=>'bi-x-circle','color'=>'text-danger'],
                    ['label'=>'This Month','value'=>($month['present'] ?? 0).'/'.($month['total'] ?? 0),'icon'=>'bi-calendar-month','color'=>'text-info']
                ];
                foreach ($stats as $stat): ?>
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted"><?= $stat['label'] ?></h6>
                                    <h3><?= $stat['value'] ?></h3>
                                </div>
                                <i class="bi <?= $stat['icon'] ?> <?= $stat['color'] ?> fs-1"></i>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Today's Attendance Status -->
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Today's Attendance</h6>
                        <?php if (!$today_date): ?>
                            <span class="badge bg-secondary">Not Hosted</span>
                        <?php else: ?>
                            <?php if ($attendance_status === 'Pending'): ?>
                                <span class="badge bg-danger">Pending (<?= $unmarked_students ?> students)</span>
                                <script>setInterval(() => { location.reload(); }, 30000);</script>
                            <?php elseif ($attendance_status === 'Completed'): ?>
                                <span class="badge bg-success">Completed</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Closed</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Attendance -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Attendance</h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Student Email</th>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Status</th>
                                <th>Time</th>
                                <th>Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recentAttendance): ?>
                                <?php foreach ($recentAttendance as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['email_address']) ?></td>
                                        <td><?= date('Y-m-d', strtotime($row['attendance_date'])) ?></td>
                                        <td><?= date('l', strtotime($row['attendance_date'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $row['status'] === 'present' ? 'success' : 'danger' ?>">
                                                <?= ucfirst($row['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= $row['marked_at'] ? date('h:i A', strtotime($row['marked_at'])) : '-' ?></td>
                                        <td><?= $row['status'] === 'present' ? '100%' : '0%' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No attendance records yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../layout/admin/footer.php'; ?>
