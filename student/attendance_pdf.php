<?php
require_once '../include/config.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/* ================= AUTH ================= */
if (!isset($_SESSION['student']['id'])) {
    header('Location: login.php');
    exit;
}

$student_id   = $_SESSION['student']['id'];
$student_name = $_SESSION['student']['name'] ?? 'Student';
$matric_no    = $_SESSION['student']['STUDENT_ID'] ?? $student_id;

/* ================= FILTERS (UNCHANGED) ================= */
$where  = "WHERE ar.STUDENT_ID = ?";
$params = [$student_id];

$from_date = $_GET['from_date'] ?? '';
$to_date   = $_GET['to_date'] ?? '';
$status    = $_GET['status'] ?? '';

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

/* ================= FETCH ATTENDANCE (UNCHANGED) ================= */
$stmt = $conn->prepare("
    SELECT 
        ad.date AS attendance_date,
        ar.status,
        ar.marked_at,
        ar.attendance_score
    FROM attendance_records ar
    JOIN attendance_dates ad ON ad.id = ar.attendance_date_id
    $where
    ORDER BY ad.date DESC
");
$stmt->execute($params);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= STATS ================= */
$total_days   = count($history);
$present_days = 0;

foreach ($history as $row) {
    if ($row['status'] === 'present') {
        $present_days++;
    }
}

$absent_days = $total_days - $present_days;
$attendance_percentage = $total_days > 0
    ? round(($present_days / $total_days) * 100, 2)
    : 0;

/* ================= LOGO ================= */
$logoPath = '../assets/logo.png';
$logoBase64 = '';

if (file_exists($logoPath)) {
    $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
}

/* ================= PDF HTML ================= */
$html = '
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    .header { text-align: center; margin-bottom: 10px; }
    .header img { width: 80px; }
    h2 { margin: 5px 0; }

    .student-info {
        margin: 10px 0;
        font-size: 11px;
    }

    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #000; padding: 6px; text-align: center; }
    th { background: #f0f0f0; }

    .present { color: green; font-weight: bold; }
    .absent { color: red; font-weight: bold; }

    footer {
        position: fixed;
        bottom: -15px;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 10px;
    }
</style>

<div class="header">
    '.($logoBase64 ? '<img src="'.$logoBase64.'">' : '').'
    <h2>SIWES Attendance Management System</h2>
    <strong>Attendance History Report</strong>
</div>

<div class="student-info">
    <strong>Name:</strong> '.$student_name.'<br>
    <strong>Matric No:</strong> '.$matric_no.'<br>
    <strong>From:</strong> '.($from_date ?: 'All').' |
    <strong>To:</strong> '.($to_date ?: 'All').' |
    <strong>Status:</strong> '.($status ?: 'All').'
</div>

<table>
<thead>
<tr>
    <th>Date</th>
    <th>Day</th>
    <th>Status</th>
    <th>Time</th>
    <th>Score</th>
</tr>
</thead>
<tbody>
';

if ($history) {
    foreach ($history as $row) {
        $html .= '
        <tr>
            <td>'.$row['attendance_date'].'</td>
            <td>'.date('l', strtotime($row['attendance_date'])).'</td>
            <td class="'.$row['status'].'">'.ucfirst($row['status']).'</td>
            <td>'.($row['marked_at'] ? date('h:i A', strtotime($row['marked_at'])) : '-').'</td>
            <td>'.((int)($row['attendance_score'] ?? 0)).'%</td>
        </tr>';
    }
} else {
    $html .= '
    <tr>
        <td colspan="5">No attendance history found</td>
    </tr>';
}

$html .= '
</tbody>
</table>

<p style="margin-top:10px; font-weight:bold;">
    Total Days: '.$total_days.' |
    Present: '.$present_days.' |
    Absent: '.$absent_days.' |
    Attendance: '.$attendance_percentage.'%
</p>

<footer>
    Generated on '.date('d M Y, h:i A').'
</footer>
';

/* ================= PDF ================= */
$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream('attendance_history.pdf', ['Attachment' => true]);
exit;
