<?php
// session_start();
$page_title = "View Attendance";
require_once '../include/config.php';

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

/* ================= GET DATE ID ================= */
$date_id = $_GET['date_id'] ?? null;

if (!$date_id) {
    header('Location: host_attendance.php');
    exit;
}

/* ================= FETCH ATTENDANCE DATE ================= */
$dateStmt = $conn->prepare("SELECT * FROM attendance_dates WHERE id = ?");
$dateStmt->execute([$date_id]);
$attendanceDate = $dateStmt->fetch(PDO::FETCH_ASSOC);

if (!$attendanceDate) {
    header('Location: host_attendance.php');
    exit;
}

$message = '';
$status = '';

/* ================= HANDLE STATUS UPDATE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $student_id = $_POST['student_id'];
    $status_value = $_POST['status'];

    // Check if record exists
    $checkStmt = $conn->prepare("SELECT id FROM attendance_records WHERE attendance_date_id = ? AND student_id = ?");
    $checkStmt->execute([$date_id, $student_id]);

    if ($checkStmt->rowCount() > 0) {
        // Update existing record
        $updateStmt = $conn->prepare("UPDATE attendance_records SET status = ? WHERE attendance_date_id = ? AND student_id = ?");
        $updateStmt->execute([$status_value, $date_id, $student_id]);
    } else {
        // Insert new record
        $insertStmt = $conn->prepare("INSERT INTO attendance_records (attendance_date_id, student_id, status) VALUES (?, ?, ?)");
        $insertStmt->execute([$date_id, $student_id, $status_value]);
    }

    $message = "Attendance updated successfully.";
    $status = 'success';
}

/* ================= FETCH ALL STUDENTS ================= */
$studentsStmt = $conn->query("SELECT id, name, email_address FROM students ORDER BY name ASC");
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= FETCH EXISTING ATTENDANCE ================= */
$attendanceStmt = $conn->prepare("SELECT student_id, status FROM attendance_records WHERE attendance_date_id = ?");
$attendanceStmt->execute([$date_id]);
$existingAttendance = $attendanceStmt->fetchAll(PDO::FETCH_KEY_PAIR);

require_once '../layout/admin/header.php';
?>


        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <h2 class="mb-4">Attendance for <?= $attendanceDate['date'] ?> (<?= date('l', strtotime($attendanceDate['date'])) ?>)</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?= $status ?>"><?= $message ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $index => $student): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($student['name']) ?></td>
                                    <td><?= htmlspecialchars($student['email_address']) ?></td>
                                    <td>
                                        <?php
                                        $current_status = $existingAttendance[$student['id']] ?? 'absent';
                                        if ($current_status === 'present') {
                                            echo '<span class="badge bg-success">Present</span>';
                                        } else {
                                            echo '<span class="badge bg-danger">Absent</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-flex gap-1">
                                            <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                            <select name="status" class="form-select form-select-sm">
                                                <option value="present" <?= $current_status === 'present' ? 'selected' : '' ?>>Present</option>
                                                <option value="absent" <?= $current_status === 'absent' ? 'selected' : '' ?>>Absent</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No students found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

<?php require_once '../layout/admin/footer.php'; ?>