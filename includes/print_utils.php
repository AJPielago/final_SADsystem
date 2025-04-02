<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function generatePrintablePDF($title, $content, $filename = 'document.pdf') {
    try {
        // Configure DomPDF options
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true); // Enable remote images
        
        // Create DomPDF instance
        $dompdf = new Dompdf($options);
        
        // Get the logo path
        $logo_path = __DIR__ . '/../assets/logo-modified.png';
        $logo_data = '';
        if (file_exists($logo_path)) {
            $logo_data = base64_encode(file_get_contents($logo_path));
            $logo_src = 'data:image/png;base64,' . $logo_data;
        } else {
            $logo_src = '';
        }
        
        // Add CSS for styling
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . $title . '</title>
            <style>
                body { 
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    margin: 20px;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 20px;
                    padding-bottom: 20px;
                    border-bottom: 2px solid #eee;
                }
                .header img { 
                    max-width: 100px;
                    margin-bottom: 10px;
                }
                .content {
                    margin-top: 20px;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin: 20px 0;
                }
                th, td { 
                    border: 1px solid #ddd; 
                    padding: 8px; 
                    text-align: left; 
                }
                th { 
                    background-color: #f2f2f2; 
                }
                .footer { 
                    text-align: center; 
                    margin-top: 30px; 
                    font-size: 10px;
                    color: #666;
                }
                .chart-container {
                    margin: 20px 0;
                    text-align: center;
                }
                .chart-container img {
                    max-width: 100%;
                    height: auto;
                    margin: 10px 0;
                }
                .chart-title {
                    font-weight: bold;
                    margin-bottom: 10px;
                }
                @media print {
                    .no-print {
                        display: none;
                    }
                    body {
                        margin: 0;
                        padding: 20px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="header">
                ' . ($logo_src ? '<img src="' . $logo_src . '" alt="Green Bin Logo">' : '<h2>Green Bin</h2>') . '
                <h1>' . $title . '</h1>
            </div>
            <div class="content">
                ' . $content . '
            </div>
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
    } catch (Exception $e) {
        die('Error generating PDF: ' . $e->getMessage());
    }
}

// Function to get the current page title
function getPageTitle() {
    $current_page = basename($_SERVER['HTTP_REFERER'] ?? $_SERVER['PHP_SELF']);
    $titles = [
        'dashboard.php' => 'Dashboard',
        'collection_requests.php' => 'Collection Requests',
        'waste_statistics.php' => 'Waste Statistics',
        'profile.php' => 'User Profile',
        'pickup_history.php' => 'Pickup History',
        'donations.php' => 'Donations',
        'community_hub.php' => 'Community Hub',
        'redeem_rewards.php' => 'Redeem Rewards',
        'view_reports.php' => 'Reports',
        'view_feedback.php' => 'Feedback',
        'view_donations.php' => 'Donations Report',
        'assigned_pickups.php' => 'Assigned Pickups',
        'collector_dashboard.php' => 'Collector Dashboard',
        'admin_dashboard.php' => 'Admin Dashboard',
        'manage_users.php' => 'Manage Users',
        'manage_schedules.php' => 'Manage Schedules',
        'reschedule_requests.php' => 'Reschedule Requests',
        'pickup.php' => 'Request Pickup',
        'report_issue.php' => 'Report Issue',
        'user_reschedule.php' => 'Reschedule Requests'
    ];
    
    return isset($titles[$current_page]) ? $titles[$current_page] : 'Document';
}
?> 