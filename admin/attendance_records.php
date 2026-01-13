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
        ar.id AS record_id,
        s.STUDENT_ID AS student_id,   -- ✅ Use your exact column
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
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Marked At</th>
                        <th>Score</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($attendanceRecords)): ?>
                        <?php foreach ($attendanceRecords as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars($record['student_id']) ?></td>
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
                                    <?= is_numeric($record['attendance_score'])
                                        ? htmlspecialchars($record['attendance_score']) . '%'
                                        : '0%' ?>
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

            <div class="mt-3">
                <a href="export_excel.php?type=attendance" class="btn btn-primary">
                    Export To Excel
                </a>
                <a href="attendance_pdf.php?type=attendance" class="btn btn-danger">
                    Download PDF
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../layout/admin/footer.php'; ?>
