<?php

session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {

    header("Location: ../index.php"); 
    exit();
}


$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'PatientP';  


require_once '../dbconnect.php'; 

$user_data = null;
$error_message = '';

$stmt_user = $conn->prepare("SELECT User_ID, Name, Email, Phone FROM users WHERE User_ID = ?");
if ($stmt_user) {
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $user_result = $stmt_user->get_result();
    $user_data = $user_result->fetch_assoc();
    $stmt_user->close();
} else {
    $error_message .= "Database error fetching user data: " . $conn->error;
}


if (isset($conn) && $conn) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Patient - Home Care BD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
     <style>
        :root {
            --primary-color: #2c4f87;
            --secondary-color: #4caf50;
            --light-bg: #f5f9ff;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
        }

        .sidebar {
            background-color: var(--primary-color);
            color: white;
            min-height: 100vh;
            padding-top: 20px;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 10px 20px;
            margin-bottom: 5px;
            border-radius: 5px;
        }

        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar .nav-link i {
            margin-right: 10px;
        }

        .main-content {
            padding: 20px;
        }

        .dashboard-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
         .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
         .settings-option {
             border-bottom: 1px solid #eee;
             padding: 15px 0;
         }
         .settings-option:last-child {
             border-bottom: none;
         }
         .settings-option a {
             text-decoration: none;
             color: var(--primary-color);
             font-weight: 500;
         }
         .settings-option a:hover {
             text-decoration: underline;
         }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="text-center mb-4">
                    <h4 class="mb-0"><i class="fas fa-heartbeat me-2"></i>Home Care BD</h4>
                </div>
                 <div class="text-center mb-4">
                    <img src="../images/avatar-placeholder.jpg" class="profile-image mb-3" alt="Profile">
                    <h5 class="mb-0"><?php echo htmlspecialchars($name); ?></h5>
                    <p class="text-light mb-0">Patient</p>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="profile.php"> <i class="fas fa-user"></i> My Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="job_posting.php"> <i class="fas fa-search"></i> Find Attendants
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_my_jobs.php"> <i class="fas fa-briefcase"></i> My Job Postings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="schedules.php"> <i class="fas fa-calendar-check"></i> My Schedules
                        </a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="medical_history.php"> <i class="fas fa-notes-medical"></i> Medical History
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="messages.php"> <i class="fas fa-envelope"></i> Messages
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="emergency.php"> <i class="fas fa-ambulance"></i> Emergency
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="payment_history.php"> <i class="fas fa-money-bill"></i> Payments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="submit_feedback.php"> <i class="fas fa-user"></i> Feedback
                        </a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="request_appointment.php"> <i class="fas fa-calendar-plus"></i> Request Appointment
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="settings.php"> <i class="fas fa-cog"></i> Settings
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                         <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Settings</h2>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <div class="dashboard-card">
                    <?php if ($user_data): ?>
                        <h4>Account Settings for <?php echo htmlspecialchars($user_data['Name']); ?></h4>
                        <p>Email: <?php echo htmlspecialchars($user_data['Email']); ?></p>
                        <p>Phone: <?php echo htmlspecialchars($user_data['Phone']); ?></p>

                        <div class="settings-option">
                            <a href="edit_patient_profile.php"><i class="fas fa-user-edit me-2"></i>Edit Profile Information</a>
                        </div>
                        <div class="settings-option">
                            <a href="change_password.php"><i class="fas fa-key me-2"></i>Change Password</a>
                        </div>
                        <div class="settings-option">
                             <a href="manage_notifications.php"><i class="fas fa-bell me-2"></i>Notification Preferences</a>
                        </div>
                         <div class="settings-option">
                             <a href="delete_account.php" class="text-danger"><i class="fas fa-trash-alt me-2"></i>Delete Account</a>
                        </div>

                    <?php else: ?>
                        <div class="alert alert-danger">Could not load user data for settings.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        document.addEventListener('DOMContentLoaded', function() {
            const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
            const currentPath = window.location.pathname.split('/').pop();

            sidebarLinks.forEach(link => {
                const linkPath = link.getAttribute('href').split('/').pop();
                if (linkPath === currentPath) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
