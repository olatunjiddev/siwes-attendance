<?php
// session_start();
$page_title = "Attendance History";

require_once '../include/config.php';
require_once '../layout/student/header.php';

/* ================== AUTH / SESSION ================== */
if (!isset($_SESSION['student']['id'])) {
    header('Location: login.php');
    exit;
}

$student_id = $_SESSION['student']['id'];

/* ================== FILTER LOGIC ================== */
$where = "WHERE ar.student_id = ?";
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

/* ================== FETCH ATTENDANCE HISTORY ================== */
$historyStmt = $conn->prepare("
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
");
$historyStmt->execute($params);
$history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
?>


        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <h2 class="mb-4">Attendance History</h2>

            <!-- Filters -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form class="row g-3" method="GET">
                        <div class="col-md-4">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="from_date" value="<?= htmlspecialchars($from_date) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="to_date" value="<?= htmlspecialchars($to_date) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All</option>
                                <option value="present" <?= $status === 'present' ? 'selected' : '' ?>>Present</option>
                                <option value="absent" <?= $status === 'absent' ? 'selected' : '' ?>>Absent</option>
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
                            <?php if (!empty($history)): ?>
                                <?php foreach ($history as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['attendance_date']) ?></td>
                                        <td><?= date('l', strtotime($row['attendance_date'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $row['status'] === 'present' ? 'success' : 'danger' ?>">
                                                <?= ucfirst($row['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= $row['marked_at'] ? date('h:i A', strtotime($row['marked_at'])) : '-' ?></td>
                                        <td>
                                            <?php if (isset($row['attendance_score']) && $row['attendance_score'] !== null && $row['attendance_score'] !== ''): ?>
                                                <?= (int)$row['attendance_score'] ?>%
                                            <?php else: ?>
                                                0%
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No attendance history found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                     <div class="mb-3">
                <a href="export.php" class="btn btn-success">
                    Export To Excel
                </a>
                <a href="attendance_pdf.php?<?= http_build_query($_GET) ?>" 
   class="btn btn-danger">
   <i class="bi bi-file-earmark-pdf"></i> Download PDF
</a>

            </div>
                </div>
            </div>

        </div>

<?php require_once '../layout/student/footer.php'; ?>
