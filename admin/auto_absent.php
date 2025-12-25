<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../include/config.php';
session_start();

/* ===================== SECURITY CHECK ===================== */

// Block if admin not logged in
if (!isset($_SESSION['admin']['id'])) {
    http_response_code(403);
    exit('Access denied');
}

// Prevent GET access
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

/* ===================== TIME RULE ===================== */

$today = date('Y-m-d');
$current_time = date('H:i:s');
$cutoff_time = '10:00:00';

// Block execution before cutoff
if ($current_time < $cutoff_time) {
    exit('Auto-absent cannot run before 10:00 AM');
}

/* ===================== CHECK ATTENDANCE DATE ===================== */

$stmt = $conn->prepare("SELECT id FROM attendance_dates WHERE date = ?");
$stmt->execute([$today]);
$attendance_date = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$attendance_date) {
    exit('No attendance session created for today');
}

$attendance_date_id = $attendance_date['id'];

/* ===================== INSERT ABSENT RECORDS ===================== */

/*
 This inserts ABSENT for:
 - all students
 - who do NOT already have a record today
*/

$sql = "
INSERT INTO attendance_records (student_id, attendance_date_id, status)
SELECT s.id, ?, 'absent'
FROM students s
WHERE NOT EXISTS (
    SELECT 1 FROM attendance_records ar
    WHERE ar.student_id = s.id
    AND ar.attendance_date_id = ?
)
";

$stmt = $conn->prepare($sql);
$stmt->execute([$attendance_date_id, $attendance_date_id]);

$affected = $stmt->rowCount();

/* ===================== LOG (OPTIONAL BUT RECOMMENDED) ===================== */

$log = $conn->prepare("
    INSERT INTO admin_logs (admin_id, action, created_at)
    VALUES (?, ?, NOW())
");
$log->execute([
    $_SESSION['admin']['id'],
    "Auto-absent executed for {$today} ({$affected} students marked absent)"
]);

/* ===================== RESPONSE ===================== */

echo "Auto-absent completed successfully. {$affected} students marked absent.";
?>