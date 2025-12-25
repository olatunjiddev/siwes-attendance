<?php
$page_title = "Admin Login Page";
require_once '../include/config.php';

if (isset($_POST['login'])) {

    $email_address = trim($_POST['email_address']);
    $password = trim($_POST['password']);

    // Use the admins table for authentication
    $sql = "SELECT * FROM admins WHERE email_address = :email_address AND password = :password";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'email_address' => $email_address,
        'password' => $password,
    ]);

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        $_SESSION['admin'] = $admin;
        header("Location: index.php");
        exit;
    } else {
        $msg = '<div class="alert alert-danger">Invalid email address or password</div>';
    }

    if (isset($error)) {
        $msg = '<div class="alert alert-danger">' . $error . '</div>';
    }
}

require_once '../layout/auth/admin/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-shield-lock text-danger" style="font-size: 4rem;"></i>
                        <h2 class="mt-3 mb-1">Admin Login</h2>
                        <p class="text-muted">Sign in to access the admin dashboard</p>
                    </div>

                    <form action="#" method="post">

                        <?php echo isset($msg) ? $msg : ''; ?>
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope"></i> Email Address
                            </label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email_address" placeholder="Enter your email" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock"></i> Password
                            </label>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Enter your password" required>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="rememberMe">
                            <label class="form-check-label" for="rememberMe">
                                Remember me
                            </label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="login" class="btn btn-danger btn-lg">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </button>
                        </div>

                        <div class="text-center mt-3">
                            <a href="#" class="text-decoration-none">Forgot password?</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../layout/auth/admin/footer.php';
?>