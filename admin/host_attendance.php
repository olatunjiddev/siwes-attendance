<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$page_title = "Host Attendance";
require_once '../include/config.php';
require_once '../layout/admin/header.php';

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['admin']['id'])) {
    header('Location: login.php');
    exit;
}

/* ================= HANDLE FORM SUBMISSIONS ================= */
$message = '';
$status  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ========== HOST NEW DATE ========== */
    if (isset($_POST['add_date'])) {
        $date = $_POST['date'] ?? null;

        if (!$date) {
            $message = "Please select a valid date.";
            $status  = 'danger';
        } else {

            // Check if date already exists
            $check = $conn->prepare("SELECT id FROM attendance_dates WHERE date = ?");
            $check->execute([$date]);

            if ($check->rowCount() > 0) {
                $message = "Attendance already hosted for this date.";
                $status  = 'warning';
            } else {

                try {
                    $conn->beginTransaction();

                    // Insert attendance date
                    $insertDate = $conn->prepare("
                        INSERT INTO attendance_dates (date, hosted_at)
                        VALUES (?, NOW())
                    ");
                    $insertDate->execute([$date]);
                    $attendance_date_id = $conn->lastInsertId();

                    // Fetch all students
                    $students = $conn->query("
                        SELECT id FROM students
                    ")->fetchAll(PDO::FETCH_ASSOC);

                    // Prepare insert statement once
                    $insertAttendance = $conn->prepare("
                        INSERT INTO attendance_records
                        (student_id, attendance_date_id, status, attendance_score, marked_at, created_at)
                        VALUES (?, ?, 'absent', 0, NULL, NOW())
                    ");

                    // Insert absent records
                    foreach ($students as $student) {
                        $insertAttendance->execute([
                            $student['id'],
                            $attendance_date_id
                        ]);
                    }

                    $conn->commit();

                    $message = "Attendance hosted successfully. All students marked absent by default.";
                    $status  = 'success';

                } catch (Exception $e) {
                    $conn->rollBack();
                    $message = "Error hosting attendance. Please try again.";
                    $status  = 'danger';
                }
            }
        }
    }

    /* ========== REMOVE DATE ========== */
    if (isset($_POST['remove_date'])) {
        $date_id = $_POST['date_id'] ?? null;

        if (!$date_id) {
            $message = "Invalid attendance date.";
            $status  = 'danger';
        } else {
            try {
                $conn->beginTransaction();

                $conn->prepare("
                    DELETE FROM attendance_records 
                    WHERE attendance_date_id = ?
                ")->execute([$date_id]);

                $conn->prepare("
                    DELETE FROM attendance_dates 
                    WHERE id = ?
                ")->execute([$date_id]);

                $conn->commit();

                $message = "Attendance date removed successfully.";
                $status  = 'success';

            } catch (Exception $e) {
                $conn->rollBack();
                $message = "Failed to remove attendance date.";
                $status  = 'danger';
            }
        }
    }
}

/* ================= FETCH ALL ATTENDANCE DATES ================= */
$datesStmt = $conn->query("
    SELECT 
        ad.id,
        ad.date,
        ad.hosted_at,
        SUM(ar.status = 'present') AS present_count,
        SUM(ar.status = 'absent')  AS absent_count
    FROM attendance_dates ad
    LEFT JOIN attendance_records ar 
        ON ar.attendance_date_id = ad.id
    GROUP BY ad.id
    ORDER BY ad.date DESC
");
$attendanceDates = $datesStmt->fetchAll(PDO::FETCH_ASSOC);
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
                <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link" href="manage_students.php"><i class="bi bi-people"></i> Manage Students</a>
                <a class="nav-link" href="attendance_records.php"><i class="bi bi-calendar-check"></i> Attendance Records</a>
                <a class="nav-link active" href="host_attendance.php"><i class="bi bi-calendar-plus"></i> Host Attendance</a>
                <a class="nav-link" href="profile.php"><i class="bi bi-person"></i> Profile</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <h2 class="mb-4">Host Attendance Dates</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?= htmlspecialchars($status) ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Add Attendance -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="POST" class="d-flex gap-3">
                        <input type="date" name="date" class="form-control" required>
                        <button type="submit" name="add_date" class="btn btn-primary w-25">
                            <i class="bi bi-plus-circle"></i> Host Date
                        </button>
                    </form>
                </div>
            </div>

            <!-- Attendance Table -->
            <div class="card">
                <div class="card-header">Hosted Attendance Dates</div>
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
                            <?php if ($attendanceDates): ?>
                                <?php foreach ($attendanceDates as $i => $row): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= htmlspecialchars($row['date']) ?></td>
                                        <td><?= date('l', strtotime($row['date'])) ?></td>
                                        <td><?= (int)$row['present_count'] ?></td>
                                        <td><?= (int)$row['absent_count'] ?></td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="view_attendance.php?date_id=<?= $row['id'] ?>"
                                                   class="btn btn-sm btn-info">View</a>
                                                <form method="POST"
                                                      onsubmit="return confirm('Remove this attendance date?');">
                                                    <input type="hidden" name="date_id" value="<?= $row['id'] ?>">
                                                    <button type="submit" name="remove_date"
                                                            class="btn btn-sm btn-danger">
                                                        Remove
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        No attendance dates hosted yet.
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
