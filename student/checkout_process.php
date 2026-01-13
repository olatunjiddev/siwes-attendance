<?php

require_once '../include/config.php';

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['student']['id'])) {
    header('Location: index.php');
    exit;
}

$student_id = $_SESSION['student']['id'];

/* ================= FETCH TODAY'S ATTENDANCE ================= */
$stmt = $conn->prepare("
    SELECT ar.id, ar.check_out_time, ar.status
    FROM attendance_records ar
    JOIN attendance_dates ad
        ON ar.attendance_date_id = ad.id
    WHERE ad.`date` = CURDATE()
      AND ar.student_id = ?
    LIMIT 1
");
$stmt->execute([$student_id]);
$attendance = $stmt->fetch(PDO::FETCH_ASSOC);

/* ================= VALIDATE ================= */
if (!$attendance) {
    $_SESSION['error'] = "No attendance record found for today.";
    header("Location: attendance.php");
    exit;
}

if ($attendance['status'] !== 'present') {
    $_SESSION['error'] = "You cannot check out because your status is not 'Present'.";
    header("Location: attendance.php");
    exit;
}

if ($attendance['check_out_time']) {
    $_SESSION['error'] = "You have already checked out today at " . date('h:i A', strtotime($attendance['check_out_time']));
    header("Location: attendance.php");
    exit;
}

/* ================= UPDATE CHECKOUT TIME ================= */
$update = $conn->prepare("
    UPDATE attendance_records
    SET check_out_time = NOW()
    WHERE id = ?
");
$update->execute([$attendance['id']]);

$_SESSION['success'] = "Checked out successfully at " . date('h:i A');

header("Location: attendance.php");
exit;
