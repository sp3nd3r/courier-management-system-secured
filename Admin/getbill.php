<?php
require('assets/plugin/pdf/fpdf.php');
include('../server/api.php');
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/rbac.php';
require_once dirname(__DIR__) . '/includes/sanitise.php';

secure_session_start();

// The bill is no longer printed for whatever customer_id the URL names. The id
// comes from the session; if the request still carries one it has to match, or
// the attempt is logged and refused with 403. Staff may print any bill.
$requested_customer_id = isset($_REQUEST['customer_id'])
    ? clean_id($_REQUEST['customer_id'])
    : null;

$bill_customer_id = assert_owns($requested_customer_id);

class PDF extends FPDF
{
    // Load data
    function BasicTable()
    {
        global $bill_customer_id;

        $data = getBille($bill_customer_id);

        $sum = 0;
        if ($row = db_fetch($data)) {
            $sum++;
            $this->Ln();
            $this->Cell(70, 6, 'Courier Reference : #' . $row['request_id']);
            $this->Ln();
            $this->Cell(70, 6, 'Name : ' . $row['name']);
            $this->Ln();
            $this->Cell(70, 6, 'Email : ' . $row['email']);
            $this->Ln();
            $this->Cell(70, 6, 'Phone Number : ' . $row['phone']);
            $this->Ln();
            $this->Cell(70, 6, 'Courier Date : ' . $row['date_updated']);
            $this->Ln();
            $this->Ln();
            $this->Cell(70, 6, 'Weight : ' . $row['weight']);
            $this->Ln();
            $this->Cell(70, 6, 'Receiver Address : ' . $row['red_address']);
            $this->Ln();
            $this->Cell(70, 6, 'Receiver Phone: ' . $row['res_phone']);
            $this->Ln();
            $this->Ln();
            $this->Cell(60, 10, 'Total Fee : Rs.' . $row['total_fee'] . '.00');
        }

    }

}

$pdf = new PDF();
// Column headings
// Data loading
$pdf->SetFont('Arial', '', 14);
$pdf->AddPage();
$pdf->Cell(80);
$pdf->Cell(30,10,'Royal Express',2,0,'C');
$pdf->Ln();
$pdf->Cell(80);
$pdf->Cell(30,10,'Your Receipt',2,0,'C');
$pdf->Ln();
$pdf->Cell(80);
$pdf->Cell(30,10, date("Y-M-d-D"),2,0,'C');
$pdf->Ln();
$pdf->BasicTable();
$pdf->Output();
