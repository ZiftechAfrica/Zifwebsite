<?php
include 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'contact') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $subject = $_POST['subject'] ?? '';
        $message = $_POST['message'] ?? '';

        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Message sent successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to send message.']);
        }
        $stmt->close();

    } elseif ($action === 'careers') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $position = $_POST['position'] ?? '';
        $resume_link = $_POST['resume'] ?? ''; // Updated to match HTML 'name="resume"'

        $stmt = $conn->prepare("INSERT INTO job_applications (name, email, position, resume_link) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $position, $resume_link);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Application submitted successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to submit application.']);
        }
        $stmt->close();

    } elseif ($action === 'ambassador') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $university = $_POST['university'] ?? '';
        $course = $_POST['course'] ?? '';
        $year = $_POST['year'] ?? '';

        $stmt = $conn->prepare("INSERT INTO ambassador_applications (name, email, university, course, year_of_study) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $name, $email, $university, $course, $year);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Ambassador application submitted successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to submit application.']);
        }
        $stmt->close();
    }
}
$conn->close();
?>
