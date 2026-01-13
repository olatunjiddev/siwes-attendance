<?php
require_once '../include/config.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/* ================= AUTH ================= */
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$type = $_GET['type'] ?? '';

$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$dompdf = new Dompdf($options);

/* ================= LOGO ================= */
$logoPath = '../assets/logo.png';
$logo = '';
if (file_exists($logoPath)) {
    $logo = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
}

/* =====================================================
   STUDENTS PDF
===================================================== */
if ($type === 'students') {

    $sql = "
        SELECT STUDENT_ID, name, email_address, school, department, gender, phone, date_joined
        FROM students
        ORDER BY date_joined DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html = "
    <style>
        body { font-family: DejaVu Sans; font-size: 12px; }
        h2 { text-align: center; }
        table { width:100%; border-collapse: collapse; }
        th, td { border:1px solid #000; padding:6px; text-align:center; }
        th { background:#f2f2f2; }
        .logo { text-align:center; margin-bottom:10px; }
    </style>

    <div class='logo'>
        ".($logo ? "<img src='$logo' width='80'>" : "")."
        <h2>Students List</h2>
    </div>

    <table>
        <tr>
            <th>#</th><th>ID</th><th>Name</th><th>Email</th>
            <th>School</th><th>Department</th><th>Gender</th>
            <th>Phone</th><th>Date Joined</th>
        </tr>";

    $i = 1;
    foreach ($students as $s) {
        $html .= "
        <tr>
            <td>{$i}</td>
            <td>{$s['STUDENT_ID']}</td>
            <td>{$s['name']}</td>
            <td>{$s['email_address']}</td>
            <td>{$s['school']}</td>
            <td>{$s['department']}</td>
            <td>{$s['gender']}</td>
            <td>{$s['phone']}</td>
            <td>".date('Y-m-d', strtotime($s['date_joined']))."</td>
        </tr>";
        $i++;
    }

    $html .= "</table>";

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream('students.pdf', ['Attachment' => true]);
    exit;
}

/* =====================================================
   ATTENDANCE PDF
===================================================== */
if ($type === 'attendance') {

    $sql = "
        SELECT 
            s.STUDENT_ID,
            s.name,
            s.email_address,
            ad.date AS attendance_date,
            ar.status,
            ar.marked_at,
            ar.attendance_score
        FROM attendance_records ar
        JOIN students s ON s.id = ar.student_id
        JOIN attendance_dates ad ON ad.id = ar.attendance_date_id
        ORDER BY ad.date DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = count($records);
    $present = count(array_filter($records, fn($r) => $r['status'] === 'present'));
    $absent = $total - $present;
    $percent = $total ? round(($present / $total) * 100, 2) : 0;

    $html = "
    <style>
        body { font-family: DejaVu Sans; font-size: 11px; }
        table { width:100%; border-collapse: collapse; }
        th, td { border:1px solid #000; padding:5px; text-align:center; }
        th { background:#f2f2f2; }
        .present { color:green; font-weight:bold; }
        .absent { color:red; font-weight:bold; }
        .header { text-align:center; }
    </style>

    <div class='header'>
        ".($logo ? "<img src='$logo' width='80'>" : "")."
        <h2>Attendance Report</h2>
    </div>

    <table>
        <tr>
            <th>#</th><th>ID</th><th>Name</th><th>Email</th>
            <th>Date</th><th>Day</th><th>Status</th>
            <th>Marked At</th><th>Score</th>
        </tr>";

    $i = 1;
    foreach ($records as $r) {
        $html .= "
        <tr>
            <td>{$i}</td>
            <td>{$r['STUDENT_ID']}</td>
            <td>{$r['name']}</td>
            <td>{$r['email_address']}</td>
            <td>".date('Y-m-d', strtotime($r['attendance_date']))."</td>
            <td>".date('l', strtotime($r['attendance_date']))."</td>
            <td class='{$r['status']}'>".ucfirst($r['status'])."</td>
            <td>".($r['marked_at'] ? date('h:i A', strtotime($r['marked_at'])) : '-')."</td>
            <td>{$r['attendance_score']}%</td>
        </tr>";
        $i++;
    }

    $html .= "
    </table>
    <p style='text-align:center;font-weight:bold;'>
        Total: $total | Present: $present | Absent: $absent | Attendance: $percent%
    </p>";

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream('attendance.pdf', ['Attachment' => true]);
    exit;
}

/* ================= INVALID ================= */
echo 'Invalid PDF export type';
exit;
