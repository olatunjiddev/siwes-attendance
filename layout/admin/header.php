<?php
require_once './session.php';

if (!isset($_SESSION['admin'])) {
    redirect('../admin/login');
}

$admin = $_SESSION['admin'];
$page_title = $page_title ?? 'Admin Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            background: #fff;
            min-height: calc(100vh - 56px);
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .nav-link {
            color: #495057;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 5px;
        }

        .nav-link:hover,
        .nav-link.active {
            background-color: #0d6efd;
            color: #fff;
        }

        /* Mobile Sidebar */
        @media (max-width: 991.98px) {
            #sidebar {
                position: fixed;
                top: 56px;
                left: -260px;
                width: 260px;
                height: 100%;
                z-index: 1050;
                transition: left 0.3s ease-in-out;
            }

            #sidebar.active {
                left: 0;
            }

            #content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        
        <!-- Sidebar Toggle Button (Mobile Only) -->
        <button class="btn btn-primary d-lg-none me-2" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>

        <a class="navbar-brand fw-bold" href="#">
            <i class="bi bi-calendar-check"></i> Admin Attendance System
        </a>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR -->
        <aside class="col-lg-2 col-md-3 sidebar p-3" id="sidebar">
            <div class="text-center mb-4">
                  <h4 class="fw-bold text-primary mb-0">SIWES Admin</h4>
                <small class="text-muted">Attendance System</small>
            </div>

             <nav class="nav flex-column">
                <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link" href="manage_students.php"><i class="bi bi-people"></i> Students</a>
                <a class="nav-link" href="attendance_records.php"><i class="bi bi-calendar-check"></i> Attendance Records</a>
                <a class="nav-link" href="host_attendance.php"><i class="bi bi-graph-up"></i> Host Attendance</a>
                <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
       
            </nav>
        </aside>


