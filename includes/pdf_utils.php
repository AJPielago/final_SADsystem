<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function generatePDF($title, $content, $filename) {
    // Configure DomPDF options
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', true);
    
    // Create DomPDF instance
    $dompdf = new Dompdf($options);
    
    // Add CSS for styling
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . $title . '</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .header { text-align: center; margin-bottom: 20px; }
            .header img { max-width: 100px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .footer { text-align: center; margin-top: 30px; font-size: 10px; }
        </style>
    </head>
    <body>
        <div class="header">
            <img src="assets/images/logo-modified.png" alt="Green Bin Logo">
            <h1>' . $title . '</h1>
        </div>
        ' . $content . '
        <div class="footer">
            <p>Generated on ' . date('Y-m-d H:i:s') . '</p>
        </div>
    </body>
    </html>';
    
    // Load HTML content
    $dompdf->loadHtml($html);
    
    // Set paper size and orientation
    $dompdf->setPaper('A4', 'portrait');
    
    // Render PDF
    $dompdf->render();
    
    // Output PDF
    $dompdf->stream($filename, array("Attachment" => true));
}
?> 