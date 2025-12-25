<?php
// session_start();
$page_title = "Host Attendance";
require_once '../include/config.php';

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

/* ================= ADD NEW ATTENDANCE DATE ================= */
$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Date
    if (isset($_POST['add_date'])) {
        $date = $_POST['date'] ?? null;

        if ($date) {
            $checkStmt = $conn->prepare("SELECT id FROM attendance_dates WHERE date = ?");
            $checkStmt->execute([$date]);
            if ($checkStmt->rowCount() > 0) {
                $message = "This date is already hosted.";
                $status = 'warning';
            } else {
                $insertStmt = $conn->prepare("INSERT INTO attendance_dates (date) VALUES (?)");
                $insertStmt->execute([$date]);
                $message = "Attendance date hosted successfully.";
                $status = 'success';
            }
        } else {
            $message = "Please select a date.";
            $status = 'danger';
        }
    }

    // Remove Date
    if (isset($_POST['remove_date'])) {
        $date_id = $_POST['date_id'] ?? null;
        if ($date_id) {
            // Delete related attendance records first
            $conn->prepare("DELETE FROM attendance_records WHERE attendance_date_id = ?")->execute([$date_id]);
            // Then delete the hosted date
            $conn->prepare("DELETE FROM attendance_dates WHERE id = ?")->execute([$date_id]);
            $message = "Attendance date removed successfully.";
            $status = 'success';
        } else {
            $message = "Invalid date ID.";
            $status = 'danger';
        }
    }
}

/* ================= FETCH ALL ATTENDANCE DATES ================= */
$datesStmt = $conn->query("
    SELECT ad.id, ad.date, 
           (SELECT COUNT(*) FROM attendance_records ar WHERE ar.attendance_date_id = ad.id AND ar.status='present') AS present_count,
           (SELECT COUNT(*) FROM attendance_records ar WHERE ar.attendance_date_id = ad.id AND ar.status='absent') AS absent_count
    FROM attendance_dates ad
    ORDER BY ad.date DESC
");
$attendanceDates = $datesStmt->fetchAll(PDO::FETCH_ASSOC);

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
                <a class="nav-link" href="attendance_records.php">
                    <i class="bi bi-calendar-check"></i> Attendance Records
                </a>
                <a class="nav-link active" href="host_attendance.php">
                    <i class="bi bi-graph-up"></i> Host Attendance
                </a>
                <a class="nav-link" href="profile.php">
                    <i class="bi bi-person"></i> Profile
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <h2 class="mb-4">Host Attendance Dates</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?= $status ?>"><?= $message ?></div>
            <?php endif; ?>

            <!-- Add Attendance Date -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="POST" class="d-flex gap-3">
                        <input type="date" name="date" class="form-control" required>
                        <button type="submit" name="add_date" class="btn btn-primary w-25"><i class="bi bi-plus-circle"></i> Host Date</button>
                    </form>
                </div>
            </div>

            <!-- Attendance Dates Table -->
            <div class="card">
                <div class="card-header">
                    All Hosted Attendance Dates
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($attendanceDates)): ?>
                                <?php foreach ($attendanceDates as $index => $row): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= $row['date'] ?></td>
                                        <td><?= date('l', strtotime($row['date'])) ?></td>
                                        <td><?= $row['present_count'] ?></td>
                                        <td><?= $row['absent_count'] ?></td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="view_attendance.php?date_id=<?= $row['id'] ?>" class="btn btn-sm btn-info">
                                                    View
                                                </a>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to remove this date?');">
                                                    <input type="hidden" name="date_id" value="<?= $row['id'] ?>">
                                                    <button type="submit" name="remove_date" class="btn btn-sm btn-danger">
                                                        Remove
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No attendance dates hosted yet.</td>
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