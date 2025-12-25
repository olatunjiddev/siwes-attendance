<?php
require_once '../include/config.php';
require_once '../layout/student/header.php';

/* ================= BASIC VARIABLES ================= */
$student_id = $_SESSION['student']['id'];
$today = date('Y-m-d');
$current_time = date('H:i:s');
$day_of_week = date('N'); // 1 = Monday

$attendance_start_time = '09:00:00';
$cutoff_time = '10:00:00';


$message = '';
$status = '';
$time_remaining = '';
$attendance = null;

/* ================= CHECK IF ADMIN HOSTED TODAY ================= */
$stmt = $conn->prepare("SELECT id FROM attendance_dates WHERE date = ?");
$stmt->execute([$today]);
$today_date = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$today_date) {
    goto render;
}

/* ================= CHECK IF STUDENT ALREADY MARKED ================= */
$check = $conn->prepare("
    SELECT * FROM attendance_records
    WHERE student_id = ? AND attendance_date_id = ?
");
$check->execute([$student_id, $today_date['id']]);
$attendance = $check->fetch(PDO::FETCH_ASSOC);

/* ================= SCORE CALCULATION ================= */
function calculateScore($time) {
    if ($time <= '09:00:00') return 100;
    if ($time <= '09:30:00') return 75;
    if ($time < '10:00:00') return 50;
    if ($time == '10:00:00') return 25;
    return 0;
}

/* ================= MARK ATTENDANCE ================= */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['mark_attendance'])
    && !$attendance
    && $current_time >= $attendance_start_time
    && $current_time <= $cutoff_time
    && $day_of_week <= 5
) {
    $score = calculateScore($current_time);

    $insert = $conn->prepare("
        INSERT INTO attendance_records
        (student_id, attendance_date_id, status, marked_at, attendance_score)
        VALUES (?, ?, 'present', NOW(), ?)
    ");
    $insert->execute([$student_id, $today_date['id'], $score]);

    $attendance = [
        'status' => 'present',
        'marked_at' => $current_time,
        'attendance_score' => $score
    ];

    $message = "Attendance marked at " . date('h:i A') . " — Score: {$score}%";
    $status = 'success';
}

/* ================= COUNTDOWN ================= */
$remaining_seconds = strtotime("$today $cutoff_time") - time();
if ($remaining_seconds > 0) {
    $time_remaining = gmdate('H:i:s', $remaining_seconds);
}

/* ================= AFTER CUTOFF FLAG ================= */
$after_cutoff = (
    $day_of_week <= 5 &&
    $today_date &&
    !$attendance &&
    $current_time > $cutoff_time
);

render:
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
                        <div class="alert alert-<?= $status ?>"><?= $message ?></div>
                    <?php endif; ?>

                    <?php if (!$today_date): ?>

                        <span class="badge bg-warning text-dark fs-5">
                            Attendance not hosted today
                        </span>

                    <?php elseif ($current_time < $attendance_start_time): ?>

                        <span class="badge bg-secondary fs-5">
                            Attendance opens at 9:00 AM
                        </span>

                    <?php elseif (!$attendance && $current_time <= $cutoff_time): ?>

                        <?php if ($time_remaining): ?>
                            <p id="countdown" class="text-danger fs-5 mb-3">
                                Time remaining: <?= $time_remaining ?>
                            </p>
                        <?php endif; ?>

                        <form method="POST">
                            <button type="submit" name="mark_attendance"
                                id="markBtn"
                                class="btn btn-success btn-lg px-4">
                                ✔ Mark Present
                            </button>
                        </form>

                    <?php else: ?>

                        <span class="badge fs-5 <?= $attendance && $attendance['status'] === 'present' ? 'bg-success' : 'bg-danger' ?>">
                            <?= $attendance ? ucfirst($attendance['status']) : 'Absent' ?>
                        </span>

                        <?php if ($attendance && $attendance['marked_at']): ?>
                            <p class="text-muted mt-2">
                                Marked at <?= date('h:i A', strtotime($attendance['marked_at'])) ?>
                                — Score: <?= $attendance['attendance_score'] ?>%
                            </p>
                        <?php endif; ?>

                    <?php endif; ?>

                    <?php if ($after_cutoff): ?>
                        <p class="text-muted mt-3">
                            Attendance time is over. Waiting for admin auto-absent.
                        </p>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div>
</div>

<script>
const countdownElem = document.getElementById('countdown');
const markBtn = document.getElementById('markBtn');

<?php if ($time_remaining): ?>
let timeParts = "<?= $time_remaining ?>".split(':');
let remainingSeconds = (+timeParts[0])*3600 + (+timeParts[1])*60 + (+timeParts[2]);

if (remainingSeconds > 0 && countdownElem) {
    setInterval(() => {
        remainingSeconds--;
        if (remainingSeconds <= 0) {
            location.reload();
        }
        const h = String(Math.floor(remainingSeconds / 3600)).padStart(2,'0');
        const m = String(Math.floor((remainingSeconds % 3600) / 60)).padStart(2,'0');
        const s = String(remainingSeconds % 60).padStart(2,'0');
        countdownElem.textContent = `Time remaining: ${h}:${m}:${s}`;
    }, 1000);
}
<?php endif; ?>
</script>

<?php require_once '../layout/student/footer.php'; ?>
