<?php
if (session_status() === PHP_SESSION_NONE) {
    // session_start();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../include/config.php';

/* ================= AUTH ================= */
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$type = $_GET['type'] ?? '';

/* ================= STUDENTS EXPORT ================= */
if ($type === 'students') {

    $sql = "
        SELECT 
            STUDENT_ID,
            name,
            email_address,
            school,
            department,
            gender,
            phone,
            date_joined
        FROM students
        ORDER BY date_joined DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = 'students_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

    fputcsv($out, [
        '#', 'Student ID', 'Name', 'Email',
        'School', 'Department', 'Gender',
        'Phone', 'Date Joined'
    ]);

    $i = 1;
    foreach ($rows as $r) {
        fputcsv($out, [
            $i++,
            $r['STUDENT_ID'],
            $r['name'],
            $r['email_address'],
            $r['school'],
            $r['department'],
            $r['gender'],
            $r['phone'],
            date('Y-m-d', strtotime($r['date_joined']))
        ]);
    }

    fclose($out);
    exit;
}

/* ================= ATTENDANCE EXPORT ================= */
if ($type === 'attendance') {

    $sql = "
        SELECT 
            ar.id,
            s.name,
            s.email_address,
            ad.date AS attendance_date,
            ar.status,
            ar.marked_at,
            ar.attendance_score
        FROM attendance_records ar
        JOIN students s ON s.id = ar.student_id
        JOIN attendance_dates ad ON ad.id = ar.attendance_date_id
        ORDER BY ad.date DESC, ar.marked_at DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = 'attendance_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, [
        '#', 'Student Name', 'Email',
        'Date', 'Day', 'Status',
        'Marked At', 'Score'
    ]);

    $i = 1;
    foreach ($records as $r) {
        $day = $r['attendance_date']
            ? date('l', strtotime($r['attendance_date']))
            : '';

        fputcsv($out, [
            $i++,
            $r['name'],
            $r['email_address'],
            date('Y-m-d', strtotime($r['attendance_date'])),
            $day,
            ucfirst($r['status']),
            $r['marked_at'] ? date('h:i A', strtotime($r['marked_at'])) : '-',
            (int)$r['attendance_score'] . '%'
        ]);
    }

    fclose($out);
    exit;
}

/* ================= INVALID TYPE ================= */
http_response_code(400);
echo 'Invalid export type';
exit;
