<?php
require_once 'includes/print_utils.php';

// Get the current page content
$current_page = $_SERVER['HTTP_REFERER'] ?? '';
if (empty($current_page)) {
    die('No page specified');
}

// Get the page title
$title = getPageTitle();

// Get the main content area
$content = '';
if (isset($_POST['content'])) {
    $content = $_POST['content'];
} else {
    die('No content provided');
}

// Generate the PDF with a proper filename
$filename = strtolower(str_replace(' ', '_', $title)) . '_' . date('Y-m-d') . '.pdf';

// Generate the PDF
generatePrintablePDF($title, $content, $filename);
?> 