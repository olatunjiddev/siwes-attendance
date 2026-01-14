<?php
$page_title = "Manage Students";
require_once '../include/config.php';

/* ================== AUTH CHECK ================== */
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$msg = '';

/* ================== ADD STUDENT ================== */
if (isset($_POST['add_student'])) {
    $student_id  = trim($_POST['STUDENT_ID'] ?? '');
    $name        = trim($_POST['name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $school      = trim($_POST['school'] ?? '');
    $department  = trim($_POST['department'] ?? '');
    $gender      = trim($_POST['gender'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $password    = trim($_POST['password'] ?? '');

    if ($student_id && $name && $email && $school && $department && $gender && $phone && $password) {
        $check = $conn->prepare("SELECT id FROM students WHERE STUDENT_ID = ? OR email_address = ?");
        $check->execute([$student_id, $email]);

        if ($check->rowCount() > 0) {
            $msg = '<div class="alert alert-danger">Student ID or Email already exists</div>';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                INSERT INTO students
                (STUDENT_ID, name, email_address, school, department, gender, phone, password, date_joined)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $student_id,
                $name,
                $email,
                $school,
                $department,
                $gender,
                $phone,
                $hashedPassword,
                date('Y-m-d')
            ]);

            $msg = '<div class="alert alert-success">Student added successfully</div>';
        }
    } else {
        $msg = '<div class="alert alert-danger">All fields are required</div>';
    }
}

/* ================== UPDATE STUDENT ================== */
if (isset($_POST['update_student'])) {
    $id         = (int)($_POST['id'] ?? 0);
    $student_id = trim($_POST['STUDENT_ID'] ?? '');
    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $school     = trim($_POST['school'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $gender     = trim($_POST['gender'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');

    if ($id && $student_id && $name && $email && $school && $department && $gender && $phone) {
        $check = $conn->prepare("
            SELECT id FROM students
            WHERE (STUDENT_ID = ? OR email_address = ?) AND id != ?
        ");
        $check->execute([$student_id, $email, $id]);

        if ($check->rowCount() > 0) {
            $msg = '<div class="alert alert-danger">Duplicate Student ID or Email</div>';
        } else {
            $stmt = $conn->prepare("
                UPDATE students SET
                    STUDENT_ID = ?,
                    name = ?,
                    email_address = ?,
                    school = ?,
                    department = ?,
                    gender = ?,
                    phone = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $student_id,
                $name,
                $email,
                $school,
                $department,
                $gender,
                $phone,
                $id
            ]);
            $msg = '<div class="alert alert-success">Student updated successfully</div>';
        }
    } else {
        $msg = '<div class="alert alert-danger">All fields are required</div>';
    }
}

/* ================== DELETE STUDENT ================== */
if (isset($_POST['delete_student'])) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $conn->prepare("DELETE FROM attendance_records WHERE student_id = ?")->execute([$id]);
        $conn->prepare("DELETE FROM students WHERE id = ?")->execute([$id]);
        $msg = '<div class="alert alert-success">Student deleted successfully</div>';
    }
}

/* ================== FETCH TODAY'S AUTH CODES ================== */
$today = date('Y-m-d');
$dateStmt = $conn->prepare("SELECT id FROM attendance_dates WHERE date = ?");
$dateStmt->execute([$today]);
$todayDate = $dateStmt->fetch(PDO::FETCH_ASSOC);

$codeMap = [];
if ($todayDate) {
    $codes = $conn->prepare("
        SELECT student_id, auth_code, COALESCE(auth_used,0) AS auth_used
        FROM attendance_records
        WHERE attendance_date_id = ?
    ");
    $codes->execute([$todayDate['id']]);
    foreach ($codes->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $codeMap[$row['student_id']] = $row;
    }
}

/* ================== FETCH ALL STUDENTS ================== */
$students = $conn->query("SELECT * FROM students ORDER BY date_joined DESC")->fetchAll(PDO::FETCH_ASSOC);

require_once '../layout/admin/header.php';
?>

<div class="col-md-9 col-lg-10 mt-4">
    <h4 class="fw-bold mb-3">Manage Students</h4>

    <?= $msg ?>
       
    <!-- ADD STUDENT -->
    <div class="card mb-4">
        <div class="card-header">Add New Student</div>
        <div class="card-body">
            <form method="post">
                <div class="row g-3">
                    <div class="col-md-3"><input type="text" name="STUDENT_ID" class="form-control" placeholder="Student ID" required></div>
                    <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Full Name" required></div>
                    <div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
                    <div class="col-md-3"><input type="text" name="school" class="form-control" placeholder="School" required></div>
                    <div class="col-md-3"><input type="text" name="department" class="form-control" placeholder="Department" required></div>
                    <div class="col-md-3">
                        <select name="gender" class="form-control" required>
                            <option value="">Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="col-md-3"><input type="text" name="phone" class="form-control" placeholder="Phone" required></div>
                    <div class="col-md-3"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
                </div>
                <button type="submit" name="add_student" class="btn btn-primary mt-3">Add Student</button>
            </form>
        </div>
    </div>

    <!-- STUDENTS TABLE -->
    <div class="card">
        <div class="card-header">Student List</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>School</th>
                    <th>Department</th>
                    <th>Gender</th>
                    <th>Phone</th>
                    <th>Date Joined</th>
                    <th>Auth Code</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($students): foreach ($students as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['STUDENT_ID']) ?></td>
                        <td><?= htmlspecialchars($s['name']) ?></td>
                        <td><?= htmlspecialchars($s['email_address']) ?></td>
                        <td><?= htmlspecialchars($s['school']) ?></td>
                        <td><?= htmlspecialchars($s['department']) ?></td>
                        <td><?= htmlspecialchars($s['gender']) ?></td>
                        <td><?= htmlspecialchars($s['phone']) ?></td>
                        <td><?= htmlspecialchars($s['date_joined']) ?></td>

                        <?php $c = $codeMap[$s['id']] ?? null; ?>
                        <td class="text-center">
                            <?php if ($c && $c['auth_code']): ?>
                                <?= htmlspecialchars($c['auth_code']) ?>
                                <?php if ($c['auth_used']): ?>
                                    <span class="badge bg-danger ms-1">Used</span>
                                <?php else: ?>
                                    <span class="badge bg-success ms-1">Unused</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-secondary">N/A</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <div class="d-flex gap-2">
                                <button
                                    class="btn btn-sm btn-warning editBtn"
                                    data-id="<?= $s['id'] ?>"
                                    data-student_id="<?= htmlspecialchars($s['STUDENT_ID']) ?>"
                                    data-name="<?= htmlspecialchars($s['name']) ?>"
                                    data-email="<?= htmlspecialchars($s['email_address']) ?>"
                                    data-school="<?= htmlspecialchars($s['school']) ?>"
                                    data-department="<?= htmlspecialchars($s['department']) ?>"
                                    data-gender="<?= htmlspecialchars($s['gender']) ?>"
                                    data-phone="<?= htmlspecialchars($s['phone']) ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editModal">Edit</button>

                                <form method="post" onsubmit="return confirm('Delete student?')">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <button name="delete_student" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="10" class="text-center">No students found</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mb-3 p-3">
             <a href="export_excel.php?type=students" class="btn btn-success mb-3">
    Export Students Excel
</a>
                <a href="attendance_pdf.php?type=students" class="btn btn-danger mb-3">Download Students PDF</a>
            </div>

    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="edit-id">
                <input type="text" name="STUDENT_ID" id="edit-student_id" class="form-control mb-2" placeholder="Student ID" required>
                <input type="text" name="name" id="edit-name" class="form-control mb-2" placeholder="Full Name" required>
                <input type="email" name="email" id="edit-email" class="form-control mb-2" placeholder="Email" required>
                <input type="text" name="school" id="edit-school" class="form-control mb-2" placeholder="School" required>
                <input type="text" name="department" id="edit-department" class="form-control mb-2" placeholder="Department" required>
                <select name="gender" id="edit-gender" class="form-control mb-2" required>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
                <input type="text" name="phone" id="edit-phone" class="form-control mb-2" placeholder="Phone" required>
            </div>
            <div class="modal-footer">
                <button name="update_student" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>


<script>
document.querySelectorAll('.editBtn').forEach(btn => {
    btn.onclick = () => {
        document.getElementById('edit-id').value = btn.dataset.id;
        document.getElementById('edit-student_id').value = btn.dataset.student_id;
        document.getElementById('edit-name').value = btn.dataset.name;
        document.getElementById('edit-email').value = btn.dataset.email;
        document.getElementById('edit-school').value = btn.dataset.school;
        document.getElementById('edit-department').value = btn.dataset.department;
        document.getElementById('edit-gender').value = btn.dataset.gender;
        document.getElementById('edit-phone').value = btn.dataset.phone;
    };
});
</script>

<?php require_once '../layout/admin/footer.php'; ?>
