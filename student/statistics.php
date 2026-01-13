<?php
$page_title = "Attendance Statistics";

require_once '../include/config.php';
require_once '../layout/student/header.php';

$student_id = $_SESSION['student']['id'];

/* ================= OVERALL STATS ================= */
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) AS total_days,
        SUM(ar.status = 'present') AS present_days,
        SUM(ar.status = 'absent') AS absent_days
    FROM attendance_records ar
    JOIN attendance_dates ad ON ad.id = ar.attendance_date_id
    WHERE ar.student_id = ?
");
$stmt->execute([$student_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$stats['total_days']   = (int)($stats['total_days'] ?? 0);
$stats['present_days'] = (int)($stats['present_days'] ?? 0);
$stats['absent_days']  = (int)($stats['absent_days'] ?? 0);

// Attendance percentage
$attendance_percentage = $stats['total_days'] > 0
    ? round(($stats['present_days'] / $stats['total_days']) * 100, 2)
    : 0;

/* ================= MONTHLY STATS ================= */
$monthlyStmt = $conn->prepare("
    SELECT 
        MONTH(ad.hosted_at) AS month,
        SUM(ar.status = 'present') AS present_count,
        SUM(ar.status = 'absent') AS absent_count
    FROM attendance_records ar
    JOIN attendance_dates ad ON ad.id = ar.attendance_date_id
    WHERE ar.student_id = ?
    GROUP BY MONTH(ad.hosted_at)
    ORDER BY MONTH(ad.hosted_at)
");
$monthlyStmt->execute([$student_id]);
$monthlyData = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= PREPARE CHART DATA ================= */
$months = [];
$presentCounts = [];
$absentCounts = [];

foreach ($monthlyData as $row) {
    $months[] = date('F', mktime(0, 0, 0, (int)$row['month'], 1));
    $presentCounts[] = (int)$row['present_count'];
    $absentCounts[]  = (int)$row['absent_count'];
}
?>



        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Attendance Statistics</h2>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <h5>Total Days</h5>
                            <h3><?= $stats['total_days'] ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <h5>Present</h5>
                            <h3><?= $stats['present_days'] ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <h5>Absent</h5>
                            <h3><?= $stats['absent_days'] ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <h5>Attendance %</h5>
                            <h3><?= $attendance_percentage ?>%</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Attendance Chart -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../layout/student/footer.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('attendanceChart').getContext('2d');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [
            {
                label: 'Present',
                data: <?= json_encode($presentCounts) ?>,
                backgroundColor: 'rgba(40, 167, 69, 0.7)'
            },
            {
                label: 'Absent',
                data: <?= json_encode($absentCounts) ?>,
                backgroundColor: 'rgba(220, 53, 69, 0.7)'
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0 }
            }
        }
    }
});
</script>
