<?php
// attendant/job_application.php - Page to display job details before applying

ini_set('display_errors', 1); // Temporarily show errors for debugging
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if user is logged in and is an attendant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'attendant') {
    header("Location: ../index.php"); // Redirect to login page
    exit();
}

$attendant_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Attendant';

require_once '../dbconnect.php';

// Check for database connection errors
if ($conn->connect_error) {
    error_log("Database connection failed in job_application.php: " . $conn->connect_error);
    die("Database connection failed. Please try again later."); // For development
}

$job = null;
$error_message = null;
$already_applied = false;

// Get Job_ID from the URL (from the "Apply Now" link on job_details.php)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $job_id = intval($_GET['id']);

    // Fetch job details to display for confirmation
    $sql_job_stmt = $conn->prepare("SELECT jp.*, u.Name as PatientName, u.Phone as PatientPhone
                                  FROM job_posting jp
                                  JOIN users u ON jp.Patient_ID = u.User_ID
                                  WHERE jp.Job_ID = ? AND jp.Status = 'open'"); // Only show details for open jobs
    if ($sql_job_stmt) {
        $sql_job_stmt->bind_param("i", $job_id);
        $sql_job_stmt->execute();
        $result_job = $sql_job_stmt->get_result();

        if ($result_job && $result_job->num_rows > 0) {
            $job = $result_job->fetch_assoc();

            // Check if attendant has already applied for this job (Status pending or accepted)
            $sql_check_application_stmt = $conn->prepare("SELECT Application_ID FROM job_application WHERE Job_ID = ? AND Attendant_ID = ? AND (Status = 'pending' OR Status = 'accepted')");
            if($sql_check_application_stmt) {
                $sql_check_application_stmt->bind_param("ii", $job_id, $attendant_id);
                $sql_check_application_stmt->execute();
                $sql_check_application_stmt->store_result();
                if ($sql_check_application_stmt->num_rows > 0) {
                    $already_applied = true;
                    $error_message = "You have already applied for this job or your application has been accepted.";
                }
                $sql_check_application_stmt->close();
            } else {
                 error_log("Database error checking existing application in job_application.php: " . $conn->error);
                 // Don't set error_message here, just log, as we still want to show job details if possible
            }

        } else {
            $error_message = "Job not found or is no longer open.";
        }
        $sql_job_stmt->close();
    } else {
         error_log("Database error preparing job fetch in job_application.php: " . $conn->error);
         $error_message = "Database error fetching job details.";
    }

} else {
    // Job ID not provided in URL
    $error_message = "No job ID specified.";
}

$conn->close(); // Close connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Job - Attendant - HomeCareBD</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .container {
            max-width: 700px;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        .job-details p {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">Confirm Job Application</h2>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($job && !$already_applied): // Show job details and form if job found and not already applied ?>
            <div class="job-details">
                <h3><?php echo htmlspecialchars($job['Job_title']); ?></h3>
                <p><strong>Patient:</strong> <?php echo htmlspecialchars($job['PatientName']); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($job['Location']); ?></p>
                <p><strong>Dates:</strong> <?php echo htmlspecialchars(date('M d, Y', strtotime($job['Start_date']))); ?> to <?php echo htmlspecialchars(date('M d, Y', strtotime($job['End_date']))); ?></p>
                <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($job['Job_description'])); ?></p>

                <hr>

                <p>Review the job details above. Click "Confirm Application" to submit your application to the admin for review.</p>
                <form action="process_application.php" method="post">
                    <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job['Job_ID']); ?>">
                    <button type="submit" name="submit_application" class="btn btn-primary">Confirm Application</button>
                    <a href="job_details.php" class="btn btn-secondary">Cancel</a>
                </form>

            <?php elseif (!$job && !$error_message): // If job is null and no specific error set, maybe an unexpected issue ?>
                 <div class="alert alert-warning">Could not load job details.</div>
                 <a href="job_details.php" class="btn btn-secondary">Back to Available Jobs</a>
            <?php elseif ($already_applied && $job): // If job found but already applied, show job details but not the form ?>
                 <div class="job-details">
                     <h3><?php echo htmlspecialchars($job['Job_title']); ?></h3>
                     <p><strong>Patient:</strong> <?php echo htmlspecialchars($job['PatientName']); ?></p>
                     <p><strong>Location:</strong> <?php echo htmlspecialchars($job['Location']); ?></p>
                     <p><strong>Dates:</strong> <?php echo htmlspecialchars(date('M d, Y', strtotime($job['Start_date']))); ?> to <?php echo htmlspecialchars(date('M d, Y', strtotime($job['End_date']))); ?></p>
                     <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($job['Job_description'])); ?></p>
                     <hr>
                     <p class="text-info">You have already applied for this job.</p>
                     <a href="job_details.php" class="btn btn-secondary">Back to Available Jobs</a>
                 </div>
            <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>