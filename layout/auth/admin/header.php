<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Login'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-header {
            padding: 2rem 0;
            text-align: center;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .logo-header img {
            max-height: 60px;
        }
        .logo-header .logo-text {
            font-size: 1.5rem;
            font-weight: bold;
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <header class="logo-header">
        <div class="container">
            <div class="d-flex align-items-center justify-content-center">
                <i class="bi bi-calendar-check text-primary" style="font-size: 2.5rem; margin-right: 1rem;"></i>
                <span class="logo-text">Admin Attendance System</span>
            </div>
        </div>
    </header>
    <main class="main-content">

