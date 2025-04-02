<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$conn = new mysqli('localhost', 'root', '', 'saddb'); // Update with your database credentials
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create issues table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS issues (
    issue_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    description TEXT NOT NULL,
    photo_url VARCHAR(255),
    location VARCHAR(255),
    status ENUM('pending', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    issue_category ENUM('Waste Collection', 'Illegal Dumping', 'Recycling', 'Garbage Truck') NOT NULL,
    specific_issue VARCHAR(255) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)";

if (!$conn->query($create_table_sql)) {
    die("Error creating table: " . $conn->error);
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

if ($role !== 'resident') {
    header("Location: login.php");
    exit();
}
include 'includes/header.php';
require 'config/db.php';

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $issue_category = trim($_POST['issue_category']);
    $issue_type = trim($_POST['issue_type']);
    $description = trim($_POST['description']);
    $image = $_FILES['image'] ?? null;
    $image_path = null;
    $location = trim($_POST['location'] ?? '');
    $status = 'pending'; // Default status

    if (empty($issue_category) || empty($issue_type) || empty($description)) {
        $error = "Please fill in all fields.";
    } else {
        // Handle image upload
        if ($image && $image['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($image['type'], $allowed_types)) {
                $upload_dir = 'uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $image_path = $upload_dir . time() . '_' . basename($image['name']);
                if (!move_uploaded_file($image['tmp_name'], $image_path)) {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Invalid image format. Only JPG, PNG, and GIF are allowed.";
            }
        }
    }

    if (empty($error)) {
        try {
            $sql = "INSERT INTO issues (user_id, description, photo_url, location, status, created_at, issue_category, specific_issue) 
                    VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)";
            $stmt = $conn->prepare($sql);
            
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("issssss", $user_id, $description, $image_path, $location, $status, $issue_category, $issue_type);

            if ($stmt->execute()) {
                $issue_id = $conn->insert_id;
                $_SESSION['last_issue'] = [
                    'id' => $issue_id,
                    'category' => $issue_category,
                    'type' => $issue_type,
                    'description' => $description,
                    'location' => $location,
                    'image_path' => $image_path,
                    'status' => $status,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
        } catch (Exception $e) {
            $error = "Error reporting the issue: " . $e->getMessage();
            error_log("Database error: " . $e->getMessage());
        }
    }
}
?>

<div class="container mt-4">
    <div class="card shadow-sm mb-4">
        <div class="card-body text-center">
            <h2 class="card-title mb-0">Report an Issue</h2>
            <p class="text-muted mt-2">Help us improve by reporting any issues you encounter.</p>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"> <?= htmlspecialchars($error) ?> </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="issue_category" class="form-label">Issue Category</label>
                    <select name="issue_category" id="issue_category" class="form-select" required onchange="updateIssueTypes()">
                        <option value="" disabled selected>Select Category</option>
                        <option value="Waste Collection">Waste Collection Issues</option>
                        <option value="Illegal Dumping">Illegal Dumping</option>
                        <option value="Recycling">Recycling Issues</option>
                        <option value="Garbage Truck">Garbage Truck Issues</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="issue_type" class="form-label">Specific Issue</label>
                    <select name="issue_type" id="issue_type" class="form-select" required>
                        <option value="" disabled selected>Select Issue Type</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="4" required></textarea>
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">Upload Image (optional)</label>
                    <input type="file" name="image" id="image" class="form-control">
                </div>

                <div class="mb-3">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" name="location" id="location" class="form-control" placeholder="Enter location details">
                </div>

                <button type="submit" class="btn btn-primary">Submit Issue</button>
            </form>
        </div>
    </div>
</div>

<!-- Report Summary Modal -->
<div class="modal fade" id="reportSummaryModal" tabindex="-1" aria-labelledby="reportSummaryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportSummaryModalLabel">Issue Report Summary</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="reportSummaryContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="printReportBtn">
                    <i class="bi bi-printer"></i> Print Report
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function updateIssueTypes() {
    const category = document.getElementById("issue_category").value;
    const issueTypeSelect = document.getElementById("issue_type");

    const issues = {
        "Waste Collection": ["Delayed Pickup", "Missed Pickup", "Damaged Bins"],
        "Illegal Dumping": ["Dumping in Restricted Areas", "Industrial Waste Dumping", "Household Waste in Public Spaces"],
        "Recycling": ["Non-Collection of Recyclables", "Lack of Recycling Bins", "Contaminated Recycling"],
        "Garbage Truck": ["Leaking Garbage Truck", "Unsafe Driving", "Truck Breakdown"]
    };

    issueTypeSelect.innerHTML = '<option value="" disabled selected>Select Issue Type</option>';

    if (category in issues) {
        issues[category].forEach(issue => {
            let option = document.createElement("option");
            option.value = issue;
            option.textContent = issue;
            issueTypeSelect.appendChild(option);
        });
    }
}

// Show modal with report summary after successful submission
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['last_issue'])): ?>
    const issue = <?php echo json_encode($_SESSION['last_issue']); ?>;
    
    // Format the date
    const date = new Date(issue.created_at);
    const formattedDate = date.toLocaleString();
    
    // Create the summary content
    const summaryContent = `
        <div class="report-summary">
            <h6>Report ID: #${issue.id}</h6>
            <p><strong>Category:</strong> ${issue.category}</p>
            <p><strong>Issue Type:</strong> ${issue.type}</p>
            <p><strong>Location:</strong> ${issue.location || 'Not specified'}</p>
            <p><strong>Description:</strong></p>
            <p>${issue.description}</p>
            ${issue.image_path ? `<p><strong>Image:</strong> <a href="${issue.image_path}" target="_blank">View Image</a></p>` : ''}
            <p><strong>Status:</strong> <span class="badge bg-${issue.status === 'pending' ? 'warning' : 'success'}">${issue.status.toUpperCase()}</span></p>
            <p><strong>Submitted:</strong> ${formattedDate}</p>
        </div>
    `;
    
    // Show the modal with the summary
    document.getElementById('reportSummaryContent').innerHTML = summaryContent;
    
    // Create and show the modal
    const modalElement = document.getElementById('reportSummaryModal');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    
    // Handle print button click
    document.getElementById('printReportBtn').addEventListener('click', function() {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Issue Report #${issue.id}</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    .report-summary { max-width: 800px; margin: 0 auto; }
                    .badge { padding: 5px 10px; border-radius: 3px; }
                    .bg-warning { background-color: #ffc107; color: black; }
                    .bg-success { background-color: #28a745; color: white; }
                </style>
            </head>
            <body>
                <div class="report-summary">
                    <h2>Issue Report Summary</h2>
                    ${summaryContent}
                </div>
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    });
    <?php endif; ?>
});
</script>

<?php
// Clear the last issue from session after showing the modal
if (isset($_SESSION['last_issue'])) {
    unset($_SESSION['last_issue']);
}
include 'includes/footer.php';
?>