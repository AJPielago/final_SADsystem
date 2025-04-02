<?php
// Check if Composer's autoloader exists
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die('Composer autoloader not found. Please run "composer require dompdf/dompdf"');
}

require __DIR__ . '/vendor/autoload.php';

// Check if DomPDF classes exist
if (!class_exists('Dompdf\Dompdf')) {
    die('Dompdf class not found. Please check your installation.');
}

// Try to create a simple PDF
try {
    $dompdf = new Dompdf\Dompdf();
    $html = '<h1>Dompdf Test</h1><p>If you can see this PDF, Dompdf is working correctly!</p>';
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("test.pdf", array("Attachment" => false));
} catch (Exception $e) {
    die('Error generating PDF: ' . $e->getMessage());
}
?> 