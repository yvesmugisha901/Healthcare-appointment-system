<?php
session_start();
require 'connect.php';

header('Content-Type: application/json');

// Only allow logged-in patients
if (!isset($_SESSION['user_id']) || strtolower(trim($_SESSION['role'])) !== 'patient') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['appointment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing appointment ID']);
    exit;
}

$appointmentId = intval($_POST['appointment_id']);
$patientId = $_SESSION['user_id'];

// Update appointment status to 'Cancelled'
$stmt = $conn->prepare("UPDATE appointments SET status='Cancelled' WHERE id=? AND patient_id=?");
$stmt->bind_param("ii", $appointmentId, $patientId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel appointment.']);
}

$stmt->close();
$conn->close();
