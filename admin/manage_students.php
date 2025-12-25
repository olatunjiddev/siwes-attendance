<?php
// session_start();
$page_title = "Manage Students";
require_once '../include/config.php';

// Redirect if not logged in as admin
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// ================== ADD STUDENT ==================
if (isset($_POST['add_student'])) {

    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $school     = trim($_POST['school'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $gender     = trim($_POST['gender'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $password   = trim($_POST['password'] ?? '');

    if ($name && $email && $school && $department && $gender && $phone && $password) {

        $stmt = $conn->prepare("
            INSERT INTO students
            (name, email_address, school, department, gender, phone, password, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $name,
            $email,
            $school,
            $department,
            $gender,
            $phone,
            $password // use password_hash() in production
        ]);

        $msg = '<div class="alert alert-success">Student added successfully</div>';
    } else {
        $msg = '<div class="alert alert-danger">All fields are required</div>';
    }
}

// ================== UPDATE STUDENT ==================
if (isset($_POST['update_student'])) {

    $id         = (int)($_POST['id'] ?? 0);
    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $school     = trim($_POST['school'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $gender     = trim($_POST['gender'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');

    if ($id && $name && $email && $school && $department && $gender && $phone) {

        $stmt = $conn->prepare("
            UPDATE students SET
                name = ?,
                email_address = ?,
                school = ?,
                department = ?,
                gender = ?,
                phone = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $name,
            $email,
            $school,
            $department,
            $gender,
            $phone,
            $id
        ]);

        $msg = '<div class="alert alert-success">Student updated successfully</div>';
    } else {
        $msg = '<div class="alert alert-danger">All fields are required</div>';
    }
}

// ================== DELETE STUDENT ==================
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $msg = '<div class="alert alert-success">Student deleted successfully</div>';
}

// ================== FETCH STUDENTS ==================
$students = $conn
    ->query("SELECT * FROM students ORDER BY id DESC")
    ->fetchAll(PDO::FETCH_ASSOC);

require_once '../layout/admin/header.php';
?>

<div class="container-fluid">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar p-3">
            <div class="text-center mb-4">
                <h4 class="fw-bold text-primary mb-0">SIWES Admin</h4>
                <small class="text-muted">Attendance System</small>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link" href="index.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a class="nav-link active" href="manage_students.php">
                    <i class="bi bi-people"></i> Students
                </a>
                <a class="nav-link" href="attendance_records.php">
                    <i class="bi bi-calendar-check"></i> Attendance Records
                </a>
                <a class="nav-link" href="host_attendance.php">
                    <i class="bi bi-graph-up"></i> Host Attendance
                </a>
                <a class="nav-link" href="profile.php">
                    <i class="bi bi-person"></i> Profile
                </a>
            </nav>
        </div>
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <h2 class="mb-4">Manage Students</h2>

            <?= $msg ?? '' ?>

            <!-- ADD STUDENT -->
            <div class="card mb-4">
                <div class="card-header">Add New Student</div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <input type="text" name="name" class="form-control" placeholder="Full Name" required>
                            </div>
                            <div class="col-md-4">
                                <input type="email" name="email" class="form-control" placeholder="Email" required>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="school" class="form-control" placeholder="School" required>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="department" class="form-control" placeholder="Department" required>
                            </div>
                            <div class="col-md-4">
                                <select name="gender" class="form-control" required>
                                    <option value="">Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="phone" class="form-control" placeholder="Phone" required>
                            </div>
                            <div class="col-md-4">
                                <input type="password" name="password" class="form-control" placeholder="Password" required>
                            </div>
                        </div>
                        <button type="submit" name="add_student" class="btn btn-primary mt-3">
                            Add Student
                        </button>
                    </form>
                </div>
            </div>

            <!-- STUDENT TABLE -->
            <div class="card">
                <div class="card-header">Student List</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>School</th>
                                <th>Department</th>
                                <th>Gender</th>
                                <th>Phone</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($students): foreach ($students as $s): ?>
                                    <tr>
                                        <td><?= $s['id'] ?></td>
                                        <td><?= htmlspecialchars($s['name']) ?></td>
                                        <td><?= htmlspecialchars($s['email_address']) ?></td>
                                        <td><?= htmlspecialchars($s['school']) ?></td>
                                        <td><?= htmlspecialchars($s['department']) ?></td>
                                        <td><?= htmlspecialchars($s['gender']) ?></td>
                                        <td><?= htmlspecialchars($s['phone']) ?></td>
                                        <td><?= $s['created_at'] ?></td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button
                                                    class="btn btn-sm btn-warning editBtn"
                                                    data-id="<?= $s['id'] ?>"
                                                    data-name="<?= htmlspecialchars($s['name']) ?>"
                                                    data-email="<?= htmlspecialchars($s['email_address']) ?>"
                                                    data-school="<?= htmlspecialchars($s['school']) ?>"
                                                    data-department="<?= htmlspecialchars($s['department']) ?>"
                                                    data-gender="<?= htmlspecialchars($s['gender']) ?>"
                                                    data-phone="<?= htmlspecialchars($s['phone']) ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editModal">
                                                    Edit
                                                </button>

                                                <a href="?delete=<?= $s['id'] ?>"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Delete student?')">
                                                    Delete
                                                </a>
                                            </div>
                                        </td>

                                    </tr>
                                <?php endforeach;
                            else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">No students found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5>Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="edit-id">

                <input type="text" name="name" id="edit-name" class="form-control mb-2" required>
                <input type="email" name="email" id="edit-email" class="form-control mb-2" required>
                <input type="text" name="school" id="edit-school" class="form-control mb-2" required>
                <input type="text" name="department" id="edit-department" class="form-control mb-2" required>

                <select name="gender" id="edit-gender" class="form-control mb-2" required>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>

                <input type="text" name="phone" id="edit-phone" class="form-control" required>
            </div>
            <div class="modal-footer">
                <button type="submit" name="update_student" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.editBtn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit-id').value = btn.dataset.id;
            document.getElementById('edit-name').value = btn.dataset.name;
            document.getElementById('edit-email').value = btn.dataset.email;
            document.getElementById('edit-school').value = btn.dataset.school;
            document.getElementById('edit-department').value = btn.dataset.department;
            document.getElementById('edit-gender').value = btn.dataset.gender;
            document.getElementById('edit-phone').value = btn.dataset.phone;
        });
    });
</script>

<?php require_once '../layout/admin/footer.php'; ?>