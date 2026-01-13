<?php
// session_start();
$page_title = "Export Attendance CSV";

require_once '../include/config.php';

// AUTH
if (!isset($_SESSION['student']['id'])) {
    header('Location: ../index.php');
    exit;
}

$student_id = $_SESSION['student']['id'];

/* ========== Read & validate filters (same rules as the page) ========== */
$from_date = $_GET['from_date'] ?? '';
$to_date   = $_GET['to_date'] ?? '';
$status    = $_GET['status'] ?? '';

$where = "WHERE ar.student_id = ?";
$params = [$student_id];

if ($from_date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_date)) {
    $where .= " AND ad.date >= ?";
    $params[] = $from_date;
}

if ($to_date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_date)) {
    $where .= " AND ad.date <= ?";
    $params[] = $to_date;
}

if ($status && in_array($status, ['present', 'absent'])) {
    $where .= " AND ar.status = ?";
    $params[] = $status;
}

/* ================== Fetch attendance records ================== */
$sql = "
    SELECT 
        ad.date AS attendance_date,
        ar.status,
        ar.marked_at,
        ar.attendance_score
    FROM attendance_records ar
    JOIN attendance_dates ad 
        ON ad.id = ar.attendance_date_id
    $where
    ORDER BY ad.date DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================== Output as CSV (Excel-friendly) ================== */
// Filename includes student id and timestamp
$filename = sprintf('attendance_%s_%s.csv', $student_id, date('Ymd_His'));

// Headers to force download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open output stream
$out = fopen('php://output', 'w');

// Write UTF-8 BOM so Excel recognizes UTF-8
fwrite($out, "\xEF\xBB\xBF");

// Column headers
fputcsv($out, ['Date', 'Day', 'Status', 'Time', 'Score']);

// Rows
foreach ($rows as $r) {
    $date = $r['attendance_date'];
    $day = $date ? date('l', strtotime($date)) : '';
    $statusLabel = isset($r['status']) ? ucfirst($r['status']) : '';
    $time = $r['marked_at'] ? date('h:i A', strtotime($r['marked_at'])) : '-';
    $score = (isset($r['attendance_score']) && $r['attendance_score'] !== null && $r['attendance_score'] !== '')
        ? ((int)$r['attendance_score'] . '%')
        : '0%';

    fputcsv($out, [$date, $day, $statusLabel, $time, $score]);
}

fclose($out);
exit;
?>