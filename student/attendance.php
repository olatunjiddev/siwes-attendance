<?php
$page_title = "Student Dashboard";

require_once '../include/config.php';

require_once '../layout/student/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1>Student Attendance <?php echo $student['name']; ?></h1>
        </div>
    </div>
</div>



<?php
require_once '../layout/student/footer.php';
?>