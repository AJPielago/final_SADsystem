<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/db.php'; // Include your database connection file

// Verify if the user is an admin
$user_id = $_SESSION['user_id'];
$conn = new mysqli('localhost', 'root', '', 'saddb'); // Update with your database credentials
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if ($role !== 'admin') {
    header("Location: login.php");
    exit();
}

// Add this before including the header
$pageTitle = "Admin Dashboard";
// Add these CSS classes to create a sticky footer layout
$bodyClass = "d-flex flex-column min-vh-100";
$mainContentClass = "flex-grow-1";

include 'includes/header.php';

// Fetch pending pickup requests from the database
$sql = "SELECT p.*, b.building_name FROM pickuprequests p join buildings b ON p.building_id = b.building_id  WHERE status = 'pending'";
$result = $conn->query($sql);
?>

<!-- Add a wrapper div with flex-grow-1 to push the footer down -->
<div class="<?php echo $mainContentClass; ?>">
    <div class="container mt-4">
        <h2>Pending Pickup Requests</h2>

        <?php if ($result->num_rows > 0): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>User ID</th>
                        <th>Building_Id</th>
                        <th>Building_Name</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['request_id']) ?></td>
                            <td><?= htmlspecialchars($row['user_id']) ?></td>
                            <td><?= htmlspecialchars($row['building_id']) ?></td>
                            <td><?= htmlspecialchars($row['building_name']) ?></td>
                            <td><?= htmlspecialchars($row['latitude']) ?></td>
                            <td><?= htmlspecialchars($row['longitude']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                            <td>
                                <form action="admin/approve_pickup.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?= htmlspecialchars($row['request_id']) ?>">
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                </form>
                                <form action="admin/reject_pickup.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?= htmlspecialchars($row['request_id']) ?>">
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">No pending pickup requests found.</div>
        <?php endif; ?>

        <!-- Add Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <!-- Charts Section -->
        <div class="row mt-5">
            <!-- Building Requests Chart -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Building Request Statistics</h5>
                        <canvas id="buildingChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Users per Building Chart -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Users per Building</h5>
                        <canvas id="usersChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Issue Categories Chart -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Most Reported Issues</h5>
                        <canvas id="issuesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // Fetch building statistics - Updated query to use correct table names
        $building_stats_query = "SELECT b.building_name, COUNT(p.request_id) as request_count 
                                FROM buildings b 
                                LEFT JOIN pickuprequests p ON b.building_id = p.building_id 
                                GROUP BY b.building_id, b.building_name 
                                ORDER BY request_count DESC 
                                LIMIT 5";
        $building_result = $conn->query($building_stats_query);
        if (!$building_result) {
            echo "Building query error: " . $conn->error;
        }
        
        // Fetch issue categories - Modified to properly handle ENUM categories
        $issues_query = "SELECT 
            issue_category,
            COUNT(*) as issue_count 
            FROM issues 
            GROUP BY issue_category 
            ORDER BY issue_count DESC";
        $issues_result = $conn->query($issues_query);
        if (!$issues_result) {
            echo "Issues query error: " . $conn->error;
        }

        // Add users per building query
        $users_stats_query = "SELECT b.building_name, COUNT(u.user_id) as user_count 
                             FROM buildings b 
                             LEFT JOIN users u ON b.building_id = u.building_id 
                             GROUP BY b.building_id, b.building_name 
                             ORDER BY user_count DESC 
                             LIMIT 5";
        $users_result = $conn->query($users_stats_query);
        if (!$users_result) {
            echo "Users query error: " . $conn->error;
        }

        // Debug: Print the data
        $building_data = array();
        $building_labels = array();
        $issues_data = array();
        $issues_labels = array();
        $users_data = array();
        $users_labels = array();
        
        if ($building_result) {
            while($row = $building_result->fetch_assoc()) {
                $building_labels[] = "'" . addslashes($row['building_name']) . "'";
                $building_data[] = $row['request_count'];
            }
        }
        
        if ($issues_result) {
            while($row = $issues_result->fetch_assoc()) {
                // Format the category label to look better in the chart
                $category = str_replace('_', ' ', $row['issue_category']);
                $issues_labels[] = "'" . addslashes($category) . "'";
                $issues_data[] = $row['issue_count'];
            }
        }

        if ($users_result) {
            while($row = $users_result->fetch_assoc()) {
                $users_labels[] = "'" . addslashes($row['building_name']) . "'";
                $users_data[] = $row['user_count'];
            }
        }
        ?>

        <script>
        // Building Requests Chart
        const buildingCtx = document.getElementById('buildingChart').getContext('2d');
        new Chart(buildingCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', $building_labels); ?>],
                datasets: [{
                    label: 'Number of Requests',
                    data: [<?php echo implode(',', $building_data); ?>],
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Users per Building Chart
        const usersCtx = document.getElementById('usersChart').getContext('2d');
        new Chart(usersCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', $users_labels); ?>],
                datasets: [{
                    label: 'Number of Users',
                    data: [<?php echo implode(',', $users_data); ?>],
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Issues Categories Chart
        const issuesCtx = document.getElementById('issuesChart').getContext('2d');
        new Chart(issuesCtx, {
            type: 'pie',
            data: {
                labels: [<?php echo implode(',', $issues_labels); ?>],
                datasets: [{
                    data: [<?php echo implode(',', $issues_data); ?>],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 206, 86, 0.5)',
                        'rgba(75, 192, 192, 0.5)',
                        'rgba(153, 102, 255, 0.5)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
        </script>
    </div>
</div>

<?php include 'includes/footer.php'; ?>