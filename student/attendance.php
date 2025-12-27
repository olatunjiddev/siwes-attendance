<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../include/config.php';
require_once '../layout/student/header.php';

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['student']['id'])) {
    header('Location: login.php');
    exit;
}

$student_id  = $_SESSION['student']['id'];
$now         = time();
$day_of_week = date('N'); // 1 = Monday, 7 = Sunday

$message = '';
$status  = '';
$attendance = null;

/* ================= FETCH TODAY'S ATTENDANCE ================= */
$stmt = $conn->prepare("
    SELECT 
        ad.id AS attendance_date_id,
        ad.hosted_at,
        ar.status,
        ar.attendance_score,
        ar.marked_at
    FROM attendance_dates ad
    JOIN attendance_records ar 
        ON ar.attendance_date_id = ad.id
    WHERE ad.date = CURDATE()
      AND ar.student_id = ?
    LIMIT 1
");
$stmt->execute([$student_id]);
$attendance = $stmt->fetch(PDO::FETCH_ASSOC);

/* ================= TIME CALCULATIONS ================= */
$minutes_since_hosted = null;
$attendance_open   = false;
$attendance_closed = false;
$time_remaining    = null;

if ($attendance) {
    $hosted_time = strtotime($attendance['hosted_at']);
    $minutes_since_hosted = max(0, floor(($now - $hosted_time) / 60));

    if ($minutes_since_hosted <= 60) {
        $attendance_open = true;
        $time_remaining = 60 - $minutes_since_hosted;
    } else {
        $attendance_closed = true;
    }
}

/* ================= SCORE FUNCTION ================= */
function calculateScoreByMinutes($minutes) {
    if ($minutes <= 15) return 100;
    if ($minutes <= 30) return 75;
    if ($minutes <= 45) return 50;
    if ($minutes <= 60) return 25;
    return 0;
}

/* ================= MARK ATTENDANCE ================= */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['mark_attendance'])
    && $attendance
    && $attendance['status'] === 'absent'
    && $attendance_open
    && $day_of_week <= 6
) {
    $score = calculateScoreByMinutes($minutes_since_hosted);

    $update = $conn->prepare("
        UPDATE attendance_records
        SET 
            status = 'present',
            attendance_score = ?,
            marked_at = NOW()
        WHERE student_id = ?
        AND attendance_date_id = ?
    ");
    $update->execute([
        $score,
        $student_id,
        $attendance['attendance_date_id']
    ]);

    $attendance['status'] = 'present';
    $attendance['attendance_score'] = $score;
    $attendance['marked_at'] = date('H:i:s');

    $message = "Attendance marked successfully — Score: {$score}%";
    $status  = 'success';
}
?>

<div class="container-fluid">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar p-3">
            <div class="text-center mb-4">
                <h4 class="fw-bold text-primary mb-0">SIWES Student</h4>
                <small class="text-muted">Attendance System</small>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link" href="index.php">Dashboard</a>
                <a class="nav-link active" href="attendance.php">My Attendance</a>
                <a class="nav-link" href="attendance_history.php">Attendance History</a>
                <a class="nav-link" href="statistics.php">Statistics</a>
                <a class="nav-link" href="profile.php">Profile</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center">

                    <h4 class="mb-2">📍 Mark Attendance</h4>
                    <p class="text-muted mb-4"><?= date('d M Y') ?></p>

                    <?php if ($message): ?>
                        <div class="alert alert-<?= htmlspecialchars($status) ?>">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!$attendance): ?>

                        <span class="badge bg-warning text-dark fs-5">
                            Attendance not hosted yet
                        </span>

                    <?php elseif ($attendance['status'] === 'present'): ?>

                        <span class="badge bg-success fs-5">Present</span>
                        <p class="text-muted mt-2">
                            Marked at <?= date('h:i A', strtotime($attendance['marked_at'])) ?>
                            — Score: <?= (int)$attendance['attendance_score'] ?>%
                        </p>

                    <?php elseif ($attendance_open): ?>

                        <p class="text-danger fs-5 mb-3">
                            Time remaining: <?= (int)$time_remaining ?> minutes
                        </p>

                        <form method="POST">
                            <button type="submit" name="mark_attendance"
                                class="btn btn-success btn-lg px-4">
                                ✔ Mark Present
                            </button>
                        </form>

                    <?php elseif ($attendance_closed): ?>

                        <span class="badge bg-danger fs-5">
                            Attendance closed — marked absent
                        </span>

                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once '../layout/student/footer.php'; ?>
