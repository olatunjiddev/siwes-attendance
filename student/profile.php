<?php
$page_title = "Profile";
require_once '../include/config.php';
require_once '../layout/student/header.php';

$student_id = $_SESSION['student']['id'];

// Fetch current student info
$stmt = $conn->prepare("SELECT name, email_address, school, department, gender, phone FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

$success = $error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password && $password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        if ($password) {
            // Update password as plain text
            $updateStmt = $conn->prepare("UPDATE students SET name = ?, email_address = ?, password = ? WHERE id = ?");
            $updateStmt->execute([$name, $email, $password, $student_id]);
        } else {
            // Keep existing password
            $updateStmt = $conn->prepare("UPDATE students SET name = ?, email_address = ? WHERE id = ?");
            $updateStmt->execute([$name, $email, $student_id]);
        }
        $success = "Profile updated successfully.";

        // Refresh student data
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
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
                <a class="nav-link" href="attendance_history.php"><i class="bi bi-clock-history"></i> Attendance History</a>
                <a class="nav-link" href="statistics.php"><i class="bi bi-graph-up"></i> Statistics</a>
                <a class="nav-link active" href="profile.php"><i class="bi bi-person"></i> Profile</a>
                <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Student Profile</h2>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" required
                                   value="<?php echo htmlspecialchars($student['name']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required
                                   value="<?php echo htmlspecialchars($student['email_address']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">School</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['school']); ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['department']); ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['gender']); ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['phone']); ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <input type="text" class="form-control" name="password" placeholder="Leave blank to keep current">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password</label>
                            <input type="text" class="form-control" name="confirm_password" placeholder="Leave blank to keep current">
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
