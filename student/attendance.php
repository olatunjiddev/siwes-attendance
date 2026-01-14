<?php
/*************************************************
 * ATTENDANCE PAGE (STUDENT)
 *************************************************/

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../include/config.php';
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['student']['id'])) {
    header('Location: login.php');
    exit;
}

$student_id  = $_SESSION['student']['id'];
$now         = time();
$day_of_week = date('N'); // 1 = Monday, 7 = Sunday
$is_weekday  = ($day_of_week <= 5);

/* ================= DEFAULTS ================= */
$attendance = null;
$current_status = 'absent';
$grade = null;
$check_in_time = null;
$checkout_time = null;
$remaining_minutes = null;
$attendance_open = false;
$can_checkout = false;
$minutes_since_hosted = 0;

/* ================= FETCH TODAY'S ATTENDANCE ================= */
$stmt = $conn->prepare("
    SELECT 
        ad.id AS attendance_date_id,
        ad.hosted_at,
        ar.id AS record_id,
        ar.status,
        ar.attendance_score,
        ar.marked_at,
        ar.check_in_time,
        ar.check_out_time,
        ar.auth_code,
        COALESCE(ar.auth_used, 0) AS auth_used
    FROM attendance_dates ad
    LEFT JOIN attendance_records ar 
        ON ar.attendance_date_id = ad.id
        AND ar.student_id = ?
    WHERE ad.date = CURDATE()
    LIMIT 1
");
$stmt->execute([$student_id]);
$attendance = $stmt->fetch(PDO::FETCH_ASSOC);

/* ================= TIME CALCULATIONS ================= */
if ($attendance) {

    $current_status = $attendance['status'] ?? 'absent';
    $grade          = $attendance['attendance_score'];
    $check_in_time  = $attendance['check_in_time'];
    $checkout_time  = $attendance['check_out_time'];

    $hosted_time = strtotime($attendance['hosted_at']);
    $minutes_since_hosted = max(0, floor(($now - $hosted_time) / 60));
    $remaining_minutes = max(0, 60 - $minutes_since_hosted);

    $attendance_open = ($minutes_since_hosted <= 60);

    if ($current_status === 'present' && !$checkout_time) {
        $can_checkout = true;
    }
}

/* ================= SCORE FUNCTION ================= */
function calculateScoreByMinutes(int $minutes): int {
    if ($minutes <= 15) return 100;
    if ($minutes <= 30) return 75;
    if ($minutes <= 45) return 50;
    if ($minutes <= 60) return 25;
    return 0;
}

/* ================= MARK ATTENDANCE (POST HANDLER) ================= */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['mark_attendance'])
) {

    if (!$attendance || !$attendance_open || !$is_weekday) {
        $_SESSION['error'] = "Attendance window is closed.";
        header('Location: attendance.php');
        exit;
    }

    if ($current_status !== 'absent') {
        $_SESSION['error'] = "Attendance already marked.";
        header('Location: attendance.php');
        exit;
    }

    $submitted_code = trim($_POST['auth_code'] ?? '');

    if ($submitted_code !== $attendance['auth_code']) {
        $_SESSION['error'] = "Invalid authorization code.";
        header('Location: attendance.php');
        exit;
    }

    if ($attendance['auth_used']) {
        $_SESSION['error'] = "Authorization code already used.";
        header('Location: attendance.php');
        exit;
    }

    $score = calculateScoreByMinutes($minutes_since_hosted);

    $update = $conn->prepare("
        UPDATE attendance_records
        SET 
            status = 'present',
            attendance_score = ?,
            marked_at = NOW(),
            check_in_time = NOW(),
            auth_used = 1
        WHERE id = ?
    ");
    $update->execute([$score, $attendance['record_id']]);

    $_SESSION['success'] = "Attendance marked successfully — Score: {$score}%";
    header('Location: attendance.php');
    exit;
}

/* ================= HTML OUTPUT ================= */
require_once '../layout/student/header.php';
?>

<div class="col-md-9 col-lg-10 p-4">
    <div class="card shadow-sm border-0">
        <div class="card-body text-center">

            <h4 class="mb-2">📍 Mark Attendance</h4>
            <p class="text-muted mb-4"><?= date('d M Y') ?></p>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (!$is_weekday): ?>

                <div class="alert alert-info">Weekend: Attendance not required.</div>

            <?php elseif (!$attendance_open && $current_status !== 'present'): ?>

                <div class="alert alert-warning">Attendance window closed.</div>

            <?php elseif ($current_status === 'present'): ?>

                <i class="bi bi-check-circle-fill text-success" style="font-size:4rem;"></i>
                <div class="alert alert-success mt-3">
                    Grade: <strong><?= $grade ?>%</strong><br>
                    Checked in at <?= date('h:i A', strtotime($check_in_time)) ?>
                </div>

            <?php else: ?>

                <div class="alert alert-danger">Status: Absent</div>
                <p class="small text-muted">Time remaining: <?= $remaining_minutes ?> minutes</p>

                <form method="post" class="text-start">
                    <div class="mb-3">
                        <label class="form-label">Authorization Code</label>
                        <input type="text"
                               name="auth_code"
                               class="form-control form-control-lg"
                               maxlength="6"
                               pattern="\d{6}"
                               required>
                    </div>
                    <button type="submit" name="mark_attendance" class="btn btn-success btn-lg w-100">
                        ✔ Mark Present
                    </button>
                </form>

            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once '../layout/student/footer.php'; ?>
