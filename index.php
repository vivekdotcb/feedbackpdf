<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
require_once('vendor/tecnickcom/tcpdf/tcpdf.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {

    // Create uploads folder if it doesn't exist
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Move uploaded file
    $uploadPath = $uploadDir . basename($_FILES['excel_file']['name']);
    if (!move_uploaded_file($_FILES['excel_file']['tmp_name'], $uploadPath)) {
        die('❌ Failed to save file.');
    }

    // Load Excel
    $spreadsheet = IOFactory::load($uploadPath);
    $sheet = $spreadsheet->getActiveSheet();
    $data = $sheet->toArray(null, true, true, true);

    // Extract headers
    $headers = array_shift($data);
    $headerKeys = array_values($headers);

    // Build associative data
    $records = [];
    foreach ($data as $row) {
        $record = [];
        foreach ($headers as $col => $header) {
            $record[$header] = $row[$col];
        }
        $records[] = $record;
    }

    // Group by Training Name
    $grouped = [];
    foreach ($records as $record) {
        $training = trim($record['Training Name']);
        $grouped[$training][] = $record;
    }

    // Generate PDF in Landscape
    $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetTitle('Training Feedback Report');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->AddPage('L');

    foreach ($grouped as $trainingName => $rows) {
        $pdf->SetFont('', 'B', 11);
        $pdf->Write(0, $trainingName, '', 0, 'L', true);
        $pdf->SetFont('', '', 9);

        $html = '<table border="1" cellpadding="3"><thead><tr>';
        foreach ($headerKeys as $header) {
            $html .= '<th style="background-color:#f0f0f0;">' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $record) {
            $html .= '<tr>';
            foreach ($headerKeys as $header) {
                $html .= '<td>' . htmlspecialchars($record[$header]) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table><br><br>';
        $pdf->writeHTML($html, true, false, true, false, '');
    }

    $pdf->Output('training_feedback_report.pdf', 'I');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Excel & Generate PDF</title>
</head>
<body>
    <h2>Upload Excel File to Generate Landscape PDF Report</h2>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="file" name="excel_file" accept=".xlsx,.xls" required>
        <button type="submit">Generate PDF</button>
    </form>
</body>
</html>
