<?php
require 'connect.php';
require 'vendor/autoload.php'; // If using a library like TCPDF or FPDF (install via composer or manually)

// Check admin session
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Fetch appointments
$sql = "SELECT a.id, u.name as patient, d.name as doctor, a.appointment_datetime, a.status FROM appointments a 
        JOIN users u ON a.patient_id = u.id 
        JOIN users d ON a.doctor_id = d.id
        ORDER BY a.appointment_datetime DESC";
$result = $conn->query($sql);

// Using FPDF for example
require('fpdf/fpdf.php');
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Appointment Report',0,1,'C');
$pdf->Ln(5);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(10,10,'ID',1);
$pdf->Cell(50,10,'Patient',1);
$pdf->Cell(50,10,'Doctor',1);
$pdf->Cell(40,10,'Date & Time',1);
$pdf->Cell(30,10,'Status',1);
$pdf->Ln();

$pdf->SetFont('Arial','',12);
while ($row = $result->fetch_assoc()) {
    $pdf->Cell(10,10,$row['id'],1);
    $pdf->Cell(50,10,$row['patient'],1);
    $pdf->Cell(50,10,$row['doctor'],1);
    $pdf->Cell(40,10,$row['appointment_datetime'],1);
    $pdf->Cell(30,10,$row['status'],1);
    $pdf->Ln();
}

$pdf->Output('D','appointment_report.pdf'); // Force download
$conn->close();
exit;
