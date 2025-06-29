<?php
session_start();
require_once("db.php");

// Require FPDF
require('fpdf/fpdf.php');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die("Access denied. Please log in.");
}

$event_id = intval($_GET['event_id'] ?? 0);
if (!$event_id) {
    die("Invalid event ID.");
}

// Verify user is registered for this event
$stmt = $conn->prepare("SELECT e.title, e.date, v.name AS venue, u.name AS attendee_name
                        FROM registrations r
                        JOIN events e ON r.event_id = e.id
                        LEFT JOIN venues v ON e.venue_id = v.id
                        JOIN users u ON r.user_id = u.id
                        WHERE r.user_id = ? AND r.event_id = ?");
$stmt->bind_param("ii", $user_id, $event_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("You are not registered for this event.");
}

$event = $result->fetch_assoc();

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();

// Title
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Event Ticket',0,1,'C');

$pdf->Ln(10);

// Event details
$pdf->SetFont('Arial','B',12);
$pdf->Cell(40,10,'Event:',0,0);
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,$event['title'],0,1);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(40,10,'Date:',0,0);
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,date('F j, Y', strtotime($event['date'])),0,1);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(40,10,'Venue:',0,0);
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,$event['venue'] ?? 'TBA',0,1);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(40,10,'Attendee:',0,0);
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,$event['attendee_name'],0,1);

// Ticket ID or unique code (optional)
$ticketId = $user_id . '-' . $event_id;
$pdf->Ln(10);
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,"Ticket ID: $ticketId",0,1,'C');

// Optional: Add a barcode or QR code here using external libs

// Output PDF
$pdf->Output('D', "ticket_{$event_id}.pdf");
exit();
?>
