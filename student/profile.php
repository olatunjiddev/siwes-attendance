<?php
$page_title = "My Profile";
require_once '../include/config.php';
require_once '../layout/student/header.php';

$student_id = $_SESSION['student']['id'];

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

<!-- Main Content -->
<div class="col-md-9 col-lg-10">
    <h2 class="mb-4">My Profile</h2>

    <div class="row">

        <!-- LEFT PROFILE CARD -->
        <div class="col-md-4">
            <div class="card text-center shadow-sm border-0">
                <div class="card-body">

                    <div class="profile-avatar mb-3">
                        <?= $initials ?>
                    </div>

                    <h5 class="mb-1"><?= htmlspecialchars($student['name']) ?></h5>
                    <small class="text-muted"><?= htmlspecialchars($student['email_address']) ?></small>

                    <hr>

                    <!-- <div class="row text-center">
                        <div class="col">
                            <strong><?= htmlspecialchars($student['department']) ?></strong><br>
                            <small>Department</small>
                        </div>
                        <div class="col">
                            <strong><?= htmlspecialchars($student['school']) ?></strong><br>
                            <small>School</small>
                        </div>
                    </div> -->

                    <a href="edit_profile.php" class="btn btn-outline-primary btn-sm mt-3">
                        Edit Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- RIGHT DETAILS CARD -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="mb-3">Personal Details</h5>

                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Full Name</th>
                            <td><?= htmlspecialchars($student['name']) ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?= htmlspecialchars($student['email_address']) ?></td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td><?= htmlspecialchars($student['phone']) ?></td>
                        </tr>
                        <tr>
                            <th>Gender</th>
                            <td><?= htmlspecialchars($student['gender']) ?></td>
                        </tr>
                        <tr>
                            <th>School</th>
                            <td><?= htmlspecialchars($student['school']) ?></td>
                        </tr>
                        <tr>
                            <th>Department</th>
                            <td><?= htmlspecialchars($student['department']) ?></td>
                        </tr>
                        <tr>
                            <th>Account Created</th>
                            <td><?= date("F d, Y", strtotime($student['created_at'])) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once '../layout/student/footer.php'; ?>
