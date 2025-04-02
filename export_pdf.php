<?php
require_once 'config/db.php';
require_once 'includes/pdf_utils.php';

// Get the report type from the URL
$report_type = isset($_GET['type']) ? $_GET['type'] : '';

// Get date range if specified
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

switch($report_type) {
    case 'collection_requests':
        // Get collection requests data
        $sql = "SELECT cr.*, u.name as user_name, u.email 
                FROM collection_requests cr 
                JOIN users u ON cr.user_id = u.id 
                WHERE cr.created_at BETWEEN ? AND ?
                ORDER BY cr.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Generate HTML content for PDF
        $html = '<h2>Collection Requests Report</h2>';
        $html .= '<p>Period: ' . date('M d, Y', strtotime($start_date)) . ' to ' . date('M d, Y', strtotime($end_date)) . '</p>';
        $html .= '<table border="1" cellpadding="5">
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Address</th>
                    </tr>';
        
        while($row = $result->fetch_assoc()) {
            $html .= '<tr>
                        <td>' . date('M d, Y', strtotime($row['created_at'])) . '</td>
                        <td>' . htmlspecialchars($row['user_name']) . '</td>
                        <td>' . htmlspecialchars($row['email']) . '</td>
                        <td>' . ucfirst($row['status']) . '</td>
                        <td>' . htmlspecialchars($row['address']) . '</td>
                    </tr>';
        }
        $html .= '</table>';
        
        generatePDF('Collection Requests Report', $html, 'collection_requests.pdf');
        break;
        
    case 'waste_statistics':
        // Get waste statistics data
        $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests
                FROM collection_requests 
                WHERE created_at BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Generate HTML content for PDF
        $html = '<h2>Waste Collection Statistics</h2>';
        $html .= '<p>Period: ' . date('M d, Y', strtotime($start_date)) . ' to ' . date('M d, Y', strtotime($end_date)) . '</p>';
        $html .= '<table border="1" cellpadding="5">
                    <tr>
                        <th>Date</th>
                        <th>Total Requests</th>
                        <th>Completed</th>
                        <th>Pending</th>
                    </tr>';
        
        while($row = $result->fetch_assoc()) {
            $html .= '<tr>
                        <td>' . date('M d, Y', strtotime($row['date'])) . '</td>
                        <td>' . $row['total_requests'] . '</td>
                        <td>' . $row['completed_requests'] . '</td>
                        <td>' . $row['pending_requests'] . '</td>
                    </tr>';
        }
        $html .= '</table>';
        
        generatePDF('Waste Collection Statistics', $html, 'waste_statistics.pdf');
        break;
        
    default:
        die('Invalid report type');
}
?> 