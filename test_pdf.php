<?php
require 'includes/pdf_utils.php';

// Test content with more complex data
$title = "Green Bin - Collection Report";
$content = '
<h2>Collection Requests Summary</h2>
<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>

<h3>Daily Statistics</h3>
<table>
    <tr>
        <th>Date</th>
        <th>Total Requests</th>
        <th>Completed</th>
        <th>Pending</th>
    </tr>
    <tr>
        <td>' . date('Y-m-d') . '</td>
        <td>15</td>
        <td>10</td>
        <td>5</td>
    </tr>
    <tr>
        <td>' . date('Y-m-d', strtotime('-1 day')) . '</td>
        <td>12</td>
        <td>8</td>
        <td>4</td>
    </tr>
</table>

<h3>Recent Collection Requests</h3>
<table>
    <tr>
        <th>Date</th>
        <th>User</th>
        <th>Address</th>
        <th>Status</th>
    </tr>
    <tr>
        <td>' . date('Y-m-d H:i') . '</td>
        <td>John Doe</td>
        <td>123 Main St, BGC</td>
        <td>Pending</td>
    </tr>
    <tr>
        <td>' . date('Y-m-d H:i', strtotime('-1 hour')) . '</td>
        <td>Jane Smith</td>
        <td>456 High St, BGC</td>
        <td>Completed</td>
    </tr>
</table>';

// Generate PDF
generatePDF($title, $content, 'collection_report.pdf');
?> 