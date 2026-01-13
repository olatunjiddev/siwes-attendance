<?php
// host_attendance.php (final, strict derived time logic)

$page_title = "Host Attendance";
require_once '../include/config.php';

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['admin']['id'])) {
    header('Location: login.php');
    exit;
}

/* ================= CONFIG ================= */
$ATTENDANCE_DURATION_MINUTES = 60;

/* ================= HANDLE FORM SUBMISSIONS ================= */
$message = '';
$status  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ========== HOST NEW DATE ========== */
    if (isset($_POST['add_date'])) {

        $date = $_POST['date'] ?? null;

        if (!$date) {
            $message = "Please select a valid date.";
            $status  = 'danger';
        } else {

            $check = $conn->prepare(
                "SELECT id FROM attendance_dates WHERE date = ?"
            );
            $check->execute([$date]);

            if ($check->rowCount()) {
                $message = "Attendance already hosted for this date.";
                $status  = 'warning';
            } else {

                try {
                    $conn->beginTransaction();

                    $conn->prepare("
                        INSERT INTO attendance_dates (date, hosted_at)
                        VALUES (?, NOW())
                    ")->execute([$date]);

                    $attendance_date_id = $conn->lastInsertId();

                    $students = $conn->query(
                        "SELECT id FROM students"
                    )->fetchAll(PDO::FETCH_ASSOC);

                    $insert = $conn->prepare("
                        INSERT INTO attendance_records
                        (student_id, attendance_date_id, status, attendance_score, created_at, auth_code, auth_used)
                        VALUES (?, ?, 'absent', 0, NOW(), ?, 0)
                    ");

                    $usedCodes = [];

                    foreach ($students as $student) {
                        do {
                            $code = (string) random_int(100000, 999999);
                        } while (isset($usedCodes[$code]));

                        $usedCodes[$code] = true;

                        $insert->execute([
                            $student['id'],
                            $attendance_date_id,
                            $code
                        ]);
                    }

                    $conn->commit();
                    $message = "Attendance hosted successfully.";
                    $status  = 'success';

                } catch (Exception $e) {
                    if ($conn->inTransaction()) {
                        $conn->rollBack();
                    }
                    $message = "Failed to host attendance.";
                    $status  = 'danger';
                }
            }
        }
    }

    /* ========== OPEN DATE (TODAY / FUTURE ONLY) ========== */
    if (isset($_POST['open_date'])) {
        $date_id = $_POST['date_id'] ?? null;

        if ($date_id) {
            $stmt = $conn->prepare("
                UPDATE attendance_dates
                SET time_opened = NOW()
                WHERE id = ?
                  AND time_opened IS NULL
                  AND date >= CURDATE()
            ");
            $stmt->execute([$date_id]);

            if ($stmt->rowCount()) {
                $message = "Attendance opened.";
                $status  = 'success';
            } else {
                $message = "Cannot open past or already opened attendance.";
                $status  = 'warning';
            }
        }
    }

    /* ========== REMOVE DATE ========== */
    if (isset($_POST['remove_date'])) {
        $date_id = $_POST['date_id'] ?? null;

        if ($date_id) {
            try {
                $conn->beginTransaction();

                $conn->prepare("
                    DELETE FROM attendance_records
                    WHERE attendance_date_id = ?
                ")->execute([$date_id]);

                $conn->prepare("
                    DELETE FROM attendance_dates
                    WHERE id = ?
                ")->execute([$date_id]);

                $conn->commit();
                $message = "Attendance removed.";
                $status  = 'success';

            } catch (Exception $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                $message = "Failed to remove attendance.";
                $status  = 'danger';
            }
        }
    }
}

/* ================= AUTO-DISABLE AUTH CODES ================= */
$conn->exec("
    UPDATE attendance_records ar
    JOIN attendance_dates ad ON ad.id = ar.attendance_date_id
    SET ar.auth_used = 1
    WHERE ar.auth_used = 0
      AND (
            ad.date < CURDATE()
         OR NOW() > DATE_ADD(ad.time_opened, INTERVAL {$ATTENDANCE_DURATION_MINUTES} MINUTE)
      )
");

/* ================= FETCH ATTENDANCE DATES ================= */
$datesStmt = $conn->query("
    SELECT 
        ad.id,
        ad.date,
        ad.hosted_at,
        ad.time_opened,
        DATE_ADD(ad.time_opened, INTERVAL {$ATTENDANCE_DURATION_MINUTES} MINUTE) AS time_closed,
        CASE
            WHEN ad.date < CURDATE() THEN 'closed'
            WHEN ad.time_opened IS NULL THEN 'scheduled'
            WHEN NOW() <= DATE_ADD(ad.time_opened, INTERVAL {$ATTENDANCE_DURATION_MINUTES} MINUTE)
                THEN 'open'
            ELSE 'closed'
        END AS status,
        SUM(ar.status = 'present') AS present_count,
        SUM(ar.status = 'absent')  AS absent_count
    FROM attendance_dates ad
    LEFT JOIN attendance_records ar
        ON ar.attendance_date_id = ad.id
    GROUP BY ad.id
    ORDER BY ad.date DESC
");

$attendanceDates = $datesStmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../layout/admin/header.php';
?>

<div class="col-md-9 col-lg-10">
    <h2 class="mb-4">Host Attendance Dates</h2>

    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($status) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Host Attendance -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" class="d-flex gap-3">
                <input type="date" name="date" class="form-control" required>
                <button type="submit" name="add_date" class="btn btn-primary w-25">
                    Host Date
                </button>
            </form>
            <small class="text-muted d-block mt-2">
                Attendance opens for <?= $ATTENDANCE_DURATION_MINUTES ?> minutes.
            </small>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Opened</th>
                        <th>Closes</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($attendanceDates): ?>
                    <?php foreach ($attendanceDates as $i => $row): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($row['date']) ?></td>
                            <td><?= date('l', strtotime($row['date'])) ?></td>
                            <td><?= (int)$row['present_count'] ?></td>
                            <td><?= (int)$row['absent_count'] ?></td>
                            <td><?= $row['time_opened']
                                ? date('Y-m-d h:i A', strtotime($row['time_opened']))
                                : '-' ?>
                            </td>
                            <td><?= $row['time_opened']
                                ? date('Y-m-d h:i A', strtotime($row['time_closed']))
                                : '-' ?>
                            </td>
                            <td>
                                <?php
                                $badge = match ($row['status']) {
                                    'open'      => 'success',
                                    'closed'    => 'dark',
                                    'scheduled' => 'info',
                                    default     => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?= $badge ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['status'] === 'scheduled'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="date_id" value="<?= $row['id'] ?>">
                                        <button name="open_date" class="btn btn-sm btn-success">
                                            Open
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" class="mt-1"
                                      onsubmit="return confirm('Delete this attendance date?');">
                                    <input type="hidden" name="date_id" value="<?= $row['id'] ?>">
                                    <button name="remove_date" class="btn btn-sm btn-danger">
                                        Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">
                            No attendance hosted yet.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../layout/admin/footer.php'; ?>
