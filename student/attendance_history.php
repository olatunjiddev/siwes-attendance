<?php
$page_title = "Attendance History";

require_once '../include/config.php';
require_once '../layout/student/header.php';

$student_id = $_SESSION['student']['id'];

/* ================= FILTER LOGIC ================= */
$where = "WHERE ar.student_id = ?";
$params = [$student_id];

if (!empty($_GET['from_date'])) {
    $where .= " AND ad.date >= ?";
    $params[] = $_GET['from_date'];
}

if (!empty($_GET['to_date'])) {
    $where .= " AND ad.date <= ?";
    $params[] = $_GET['to_date'];
}

if (!empty($_GET['status'])) {
    $where .= " AND ar.status = ?";
    $params[] = $_GET['status'];
}

/* ================= FETCH ATTENDANCE HISTORY ================= */
$historyStmt = $conn->prepare("
    SELECT 
        ad.date AS attendance_date,
        ar.status,
        ar.marked_at
    FROM attendance_records ar
    JOIN attendance_dates ad 
        ON ad.id = ar.attendance_date_id
    $where
    ORDER BY ad.date DESC
");
$historyStmt->execute($params);
$history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">

        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2 sidebar p-3">
            <div class="text-center mb-4">
                <h4 class="fw-bold text-primary mb-0">SIWES Student</h4>
                <small class="text-muted">Attendance System</small>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link" href="attendance.php"><i class="bi bi-calendar-check"></i> My Attendance</a>
                <a class="nav-link active" href="attendance_history.php"><i class="bi bi-clock-history"></i> Attendance History</a>
                <a class="nav-link" href="statistics.php"><i class="bi bi-graph-up"></i> Statistics</a>
                <a class="nav-link" href="profile.php"><i class="bi bi-person"></i> Profile</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">

            <h2 class="mb-4">Attendance History</h2>

            <!-- Filters -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form class="row g-3" method="GET">
                        <div class="col-md-4">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="from_date"
                                value="<?= $_GET['from_date'] ?? '' ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="to_date"
                                value="<?= $_GET['to_date'] ?? '' ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All</option>
                                <option value="present" <?= ($_GET['status'] ?? '') === 'present' ? 'selected' : '' ?>>Present</option>
                                <option value="absent" <?= ($_GET['status'] ?? '') === 'absent' ? 'selected' : '' ?>>Absent</option>
                            </select>
                        </div>

                        <div class="col-md-1 d-flex align-items-end">
                            <button class="btn btn-primary w-100">
                                <i class="bi bi-filter"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Attendance Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle">
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
                            <?php if ($history): ?>
                                <?php foreach ($history as $row): ?>
                                    <tr>
                                        <td><?= $row['attendance_date'] ?></td>
                                        <td><?= date('l', strtotime($row['attendance_date'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $row['status'] === 'present' ? 'success' : 'danger' ?>">
                                                <?= ucfirst($row['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= $row['marked_at']
                                                ? date('h:i A', strtotime($row['marked_at']))
                                                : '-' ?>
                                        </td>
                                        <td>
                                            <?= $row['status'] === 'present' ? '100%' : '0%' ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        No attendance history found
                                    </td>


                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../layout/student/footer.php'; ?>