<?php
// attendant/process_application.php - Processes the job application confirmation

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

// Include database connection from the parent directory
require_once '../dbconnect.php';

// Check for database connection errors
if ($conn->connect_error) {
    error_log("Database connection failed in process_application.php: " . $conn->connect_error);
    header("Location: job_details.php?error=" . urlencode("Database connection failed. Please try again later."));
    exit();
}

// Check if the form was submitted via POST and required data exists
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_application'], $_POST['job_id'])) {
    $job_id = intval($_POST['job_id']); // Sanitize Job ID

    // Validate Job_ID
    if ($job_id <= 0) {
        header("Location: job_details.php?error=" . urlencode("Invalid job ID."));
        exit();
    }

    // --- Prevent Duplicate Applications ---
    // Check if attendant has already applied for this job with 'pending' or 'accepted' status
    $stmt_check_applied = $conn->prepare("SELECT Application_ID FROM job_application WHERE Job_ID = ? AND Attendant_ID = ? AND (Status = 'pending' OR Status = 'accepted')");
    if ($stmt_check_applied) {
        $stmt_check_applied->bind_param("ii", $job_id, $attendant_id);
        $stmt_check_applied->execute();
        $stmt_check_applied->store_result();

        if ($stmt_check_applied->num_rows > 0) {
            $stmt_check_applied->close();
            $conn->close();
            header("Location: job_details.php?error=" . urlencode("You have already applied for or been accepted for this job."));
            exit();
        }
        $stmt_check_applied->close();
    } else {
        error_log("Database error checking duplicate application in process_application.php: " . $conn->error);
        $conn->close();
        header("Location: job_details.php?error=" . urlencode("Database error."));
        exit();
    }
    // --- End Prevent Duplicate Applications ---


    // --- Insert the Job Application ---
    // Insert the job application into the database with 'pending' status
    // Assuming 'job_application' table has columns: Job_ID, Attendant_ID, Application_Date, Status, Created_at
    $stmt_insert = $conn->prepare("INSERT INTO job_application (Job_ID, Attendant_ID, Application_Date, Status, Created_at) VALUES (?, ?, CURDATE(), 'pending', NOW())");

    if ($stmt_insert) {
        // Bind parameters: ii -> two integers (Job_ID, Attendant_ID)
        $stmt_insert->bind_param("ii", $job_id, $attendant_id);

        if ($stmt_insert->execute()) {
            // Application submitted successfully

            // TODO: Implement Admin Notification Logic Here
            // This is where you would add code to notify the admin
            // that a new application has been submitted for them to review.
            // E.g., insert into a 'notifications' table for admin users.

            $success_message = "Application submitted successfully. Waiting for admin approval.";
            // Redirect back to the available jobs page with a success message
            header("Location: job_details.php?success=" . urlencode($success_message));
            exit();
        } else {
            // Error during insertion
            $error_message = "Error submitting application: " . $stmt_insert->error;
            error_log("Job application insert failed in process_application.php: " . $stmt_insert->error); // Log the actual database error
            header("Location: job_details.php?error=" . urlencode($error_message));
            exit();
        }
        $stmt_insert->close();
    } else {
        // Error preparing insert statement
        $error_message = "Database error: Could not prepare application statement. " . $conn->error;
        error_log("Prepare statement failed in process_application.php: " . $conn->error);
        header("Location: job_details.php?error=" . urlencode($error_message));
        exit();
    }

    $conn->close(); // Close connection

} else {
    // If accessed directly or missing POST data
    header("Location: job_details.php?error=" . urlencode("Invalid request method or data."));
    exit();
}
?>