<?php
$page_title = "Edit Profile";
require_once '../include/config.php';
require_once '../layout/student/header.php';

$student_id = $_SESSION['student']['id'];

$success = $error = '';

/* Fetch student data */
$stmt = $conn->prepare("
    SELECT name, email_address, school, department, gender, phone, created_at
    FROM students
    WHERE id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

/* Get initials for avatar */
$initials = strtoupper(substr($student['name'], 0, 1));

/* Handle form submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $gender     = trim($_POST['gender']);
    $department = trim($_POST['department']);
    $password   = trim($_POST['password']);
    $confirm    = trim($_POST['confirm_password']);

    if (!$name || !$email) {
        $error = "Name and Email are required.";
    } elseif ($password && $password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        if ($password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare("
                UPDATE students
                SET name = ?, email_address = ?, phone = ?, gender = ?, department = ?, password = ?
                WHERE id = ?
            ");
            $update->execute([$name, $email, $phone, $gender, $department, $hashed_password, $student_id]);
        } else {
            $update = $conn->prepare("
                UPDATE students
                SET name = ?, email_address = ?, phone = ?, gender = ?, department = ?
                WHERE id = ?
            ");
            $update->execute([$name, $email, $phone, $gender, $department, $student_id]);
        }

        $success = "Profile updated successfully.";

        /* Refresh data */
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        $initials = strtoupper(substr($student['name'], 0, 1));
    }
}
?>

<style>
.profile-avatar {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    background: #0d6efd;
    color: #fff;
    font-size: 42px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: auto;
}
</style>

<div class="col-md-9 col-lg-10">
    <h2 class="mb-4">Edit Profile</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="row">

        <!-- Left card: Avatar -->
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow-sm border-0">
                <div class="card-body">
                    <div class="profile-avatar mb-3"><?= $initials ?></div>
                    <h5><?= htmlspecialchars($student['name']) ?></h5>
                    <small class="text-muted"><?= htmlspecialchars($student['email_address']) ?></small>
                    <hr>
                    <div class="text-muted">
                        Account Created:<br>
                        <?= date("F d, Y", strtotime($student['created_at'])) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right card: Edit form -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form method="POST" class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required
                                   value="<?= htmlspecialchars($student['name']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?= htmlspecialchars($student['email_address']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control"
                                   value="<?= htmlspecialchars($student['phone']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="Male" <?= $student['gender']=="Male"?"selected":"" ?>>Male</option>
                                <option value="Female" <?= $student['gender']=="Female"?"selected":"" ?>>Female</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-control"
                                   value="<?= htmlspecialchars($student['department']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control"
                                   placeholder="Leave blank to keep current">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control"
                                   placeholder="Leave blank to keep current">
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once '../layout/student/footer.php'; ?>
