<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../include/config.php';
require_once '../layout/student/header.php';
session_start();

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['student']['id'])) {
    header('Location: login.php');
    exit;
}

$student_id  = $_SESSION['student']['id'];
$now         = time();
$day_of_week = date('N'); // 1 = Monday, 7 = Sunday
$is_weekday  = ($day_of_week >= 1 && $day_of_week <= 5);

$message = '';
$status  = '';
$attendance = null;
$can_checkout = false;
$remaining_minutes = null;
$current_status = null;
$grade = null;
$check_in_time = null;
$checkout_time = null;

/* ================= FETCH TODAY'S ATTENDANCE ================= */
$stmt = $conn->prepare("
    SELECT 
        ad.id AS attendance_date_id,
        ad.hosted_at,
        ar.status,
        ar.attendance_score,
        ar.marked_at,
        ar.check_in_time,
        ar.check_out_time,
        ar.auth_code,
        COALESCE(ar.auth_used,0) AS auth_used
    FROM attendance_dates ad
    LEFT JOIN attendance_records ar 
        ON ar.attendance_date_id = ad.id
        AND ar.student_id = ?
    WHERE ad.`date` = CURDATE()
    LIMIT 1
");
$stmt->execute([$student_id]);
$attendance = $stmt->fetch(PDO::FETCH_ASSOC);

/* ================= TIME CALCULATIONS ================= */
$attendance_open = false;
$attendance_closed = false;

if ($attendance) {
    $current_status = $attendance['status'] ?? 'absent';
    $grade = $attendance['attendance_score'] ?? null;
    $check_in_time = $attendance['check_in_time'] ?? null;
    $checkout_time = $attendance['check_out_time'] ?? null;

    $hosted_time = strtotime($attendance['hosted_at']);
    $minutes_since_hosted = max(0, floor(($now - $hosted_time) / 60));
    $remaining_minutes = max(0, 60 - $minutes_since_hosted);

    if ($minutes_since_hosted <= 60) {
        $attendance_open = true;
    } else {
        $attendance_closed = true;
    }

    // Check if student can checkout
    if ($current_status === 'present' && !$checkout_time) {
        $can_checkout = true;
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
    && ($attendance['status'] ?? 'absent') === 'absent'
    && $attendance_open
    && $is_weekday
) {
    $submitted_code = trim($_POST['auth_code'] ?? '');

    // Fetch today's record to verify auth code
    $record_stmt = $conn->prepare("
        SELECT auth_code, auth_used 
        FROM attendance_records 
        WHERE student_id = ? 
        AND attendance_date_id = ?
        LIMIT 1
    ");
    $record_stmt->execute([$student_id, $attendance['attendance_date_id']]);
    $record = $record_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record || $record['auth_code'] !== $submitted_code) {
        $_SESSION['error'] = "Invalid authorization code.";
    } elseif ($record['auth_used']) {
        $_SESSION['error'] = "This code has already been used.";
    } else {
        $score = calculateScoreByMinutes($minutes_since_hosted);

        $update = $conn->prepare("
            UPDATE attendance_records
            SET 
                status = 'present',
                attendance_score = ?,
                marked_at = NOW(),
                check_in_time = NOW(),
                auth_used = 1
            WHERE student_id = ?
            AND attendance_date_id = ?
        ");

        $update->execute([$score, $student_id, $attendance['attendance_date_id']]);

        $current_status = 'present';
        $grade = $score;
        $check_in_time = date('H:i:s');

        $_SESSION['success'] = "Attendance marked successfully — Score: {$score}%";
        // Reload page to avoid form resubmission
        header('Location: attendance.php');
        exit;
    }
}

?>

<div class="col-md-9 col-lg-10 p-4">
    <div class="card shadow-sm border-0">
        <div class="card-body text-center">

            <h4 class="mb-2">📍 Mark Attendance</h4>
            <p class="text-muted mb-4"><?= date('d M Y') ?></p>

            <!-- Session messages -->
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <?php if (!$is_weekday): ?>
                <div class="alert alert-info">Weekend: Attendance not required.</div>
                <a href="index.php" class="btn btn-outline-secondary mt-3">Back to Dashboard</a>

            <?php elseif (!$attendance_open && $current_status !== 'present'): ?>
                <div class="alert alert-warning">Attendance window is closed or not yet opened by admin.</div>
                <a href="index.php" class="btn btn-outline-secondary mt-3">Back to Dashboard</a>

            <?php elseif ($current_status === 'present'): ?>
                <i class="bi bi-check-circle-fill text-success mb-3" style="font-size:4rem;"></i>
                <div class="alert alert-success">
                    Attendance confirmed! Grade: <strong><?= $grade ?>%</strong><br>
                    Checked in at: <?= $check_in_time ? date('h:i A', strtotime($check_in_time)) : '--:--' ?>
                </div>

                <?php if ($can_checkout): ?>
                    <h4>Checkout</h4>
                    <form action="checkout_process.php" method="post">
                        <button type="submit" name="confirm_checkout" class="btn btn-warning btn-lg">
                            Check Out
                        </button>
                    </form>
                <?php elseif ($checkout_time): ?>
                    <div class="alert alert-info mt-2">
                        You have checked out at <?= date('h:i A', strtotime($checkout_time)) ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <i class="bi bi-calendar2-check text-primary mb-3" style="font-size:4rem;"></i>
                <div class="alert alert-danger mb-3">Your current status is <strong>Absent</strong>.</div>

                <?php if ($remaining_minutes <= 0): ?>
                    <div class="alert alert-danger">Attendance window has closed. Grade: 0%</div>
                <?php else: ?>
                    <h4>Confirm Attendance</h4>
                    <p class="small text-muted mb-3">
                        Time remaining to mark attendance: <?= $remaining_minutes ?> min
                    </p>
                    <form method="POST" class="text-start">
                        <div class="mb-3">
                            <label class="form-label">Authorization Code</label>
                            <input type="text" name="auth_code" class="form-control form-control-lg" placeholder="Enter 6-digit code" maxlength="6" pattern="\d{6}" required>
                            <div class="form-text small">Enter the 6-digit code provided for you today.</div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="mark_attendance" class="btn btn-success btn-lg">✔ Mark Present</button>
                        </div>
                    </form>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once '../layout/student/footer.php'; ?>
