<?php
// session_start();
$page_title = "Admin Profile";
require_once '../include/config.php';

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$status = '';

$admin_id = $_SESSION['admin']['id'];

/* ================= HANDLE PROFILE UPDATE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$email) {
        $message = "Email is required.";
        $status = 'danger';
    } elseif ($password && $password !== $confirm_password) {
        $message = "Passwords do not match.";
        $status = 'danger';
    } else {
        if ($password) {
            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admins SET email_address = ?, password = ? WHERE id = ?");
            $stmt->execute([$email, $hashed_password, $admin_id]);
        } else {
            // Update only email
            $stmt = $conn->prepare("UPDATE admins SET email_address = ? WHERE id = ?");
            $stmt->execute([$email, $admin_id]);
        }

        // Update session info
        $_SESSION['admin']['email_address'] = $email;

        $message = "Profile updated successfully.";
        $status = 'success';
    }
}

/* ================= FETCH CURRENT ADMIN INFO ================= */
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

require_once '../layout/admin/header.php';
?>

<div class="container-fluid">
    <div class="row">

        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2 sidebar p-3">
            <div class="text-center mb-4">
                <h4 class="fw-bold text-primary mb-0">SIWES Admin</h4>
                <small class="text-muted">Attendance System</small>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link" href="index.php">Dashboard</a>
                <a class="nav-link" href="manage_students.php">Manage Students</a>
                <a class="nav-link" href="attendance_records.php">Attendance Records</a>
                <a class="nav-link" href="host_attendance.php">Host Attendance</a>
                <a class="nav-link active" href="profile.php">Profile</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <h2 class="mb-4">Admin Profile</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?= $status ?>"><?= $message ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($admin['email_address']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current">
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Leave blank to keep current">
                        </div>
                        <div class="col-12">
                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../layout/admin/footer.php'; ?>