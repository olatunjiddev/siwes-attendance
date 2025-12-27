<?php
// session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

$page_title = "Attendance Records";
require_once '../include/config.php';

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

/* ================= FETCH ATTENDANCE RECORDS ================= */
$sql = "
    SELECT 
        ar.id,
        s.name,
        s.email_address,
        ad.date AS attendance_date,
        ar.status,
        ar.marked_at,
        ar.attendance_score
    FROM attendance_records ar
    JOIN students s ON s.id = ar.student_id
    JOIN attendance_dates ad ON ad.id = ar.attendance_date_id
    ORDER BY ad.date DESC, ar.marked_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../layout/admin/header.php';
?>

<div class="container-fluid">
    <div class="row">

        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2 sidebar p-3">
            <div class="text-center mb-4">
                <h4 class="fw-bold text-primary mb-0">SIWES Admin</h4>
                <small class="text-muted">Attendance System</small>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link" href="index.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a class="nav-link" href="manage_students.php">
                    <i class="bi bi-people"></i> Manage Students
                </a>
                <a class="nav-link active" href="attendance_records.php">
                    <i class="bi bi-calendar-check"></i> Attendance Records
                </a>
                <a class="nav-link" href="host_attendance.php">
                    <i class="bi bi-graph-up"></i> Host Attendance
                </a>
                <a class="nav-link" href="profile.php">
                    <i class="bi bi-person"></i> Profile
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <h2 class="mb-4">Attendance Records</h2>

            <div class="card">
                <div class="card-header">
                    All Attendance Records
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Marked At</th>
                                <th>Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($attendanceRecords)): ?>
                                <?php foreach ($attendanceRecords as $index => $record): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($record['name']) ?></td>
                                        <td><?= htmlspecialchars($record['email_address']) ?></td>
                                        <td><?= date('Y-m-d', strtotime($record['attendance_date'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $record['status'] === 'present' ? 'success' : 'danger' ?>">
                                                <?= ucfirst($record['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= $record['marked_at'] 
                                                ? date('h:i A', strtotime($record['marked_at'])) 
                                                : '-' ?>
                                        </td>
                                        <td>
                                            <?= $record['status'] === 'present' ? '100%' : '0%' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        No attendance records found.
                                    </td>
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
