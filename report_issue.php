<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';
require 'config/db.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $issue_category = trim($_POST['issue_category']);
    $issue_type = trim($_POST['issue_type']);
    $description = trim($_POST['description']);
    $image = $_FILES['image'] ?? null;
    $image_path = null;
    $location = trim($_POST['location'] ?? '');
    $status = 'open'; // Default status

    if (empty($issue_category) || empty($issue_type) || empty($description)) {
        $error = "Please fill in all fields.";
    } else {
        // Handle image upload
        if ($image && $image['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($image['type'], $allowed_types)) {
                $image_path = 'uploads/' . time() . '_' . basename($image['name']);
                move_uploaded_file($image['tmp_name'], $image_path);
            } else {
                $error = "Invalid image format. Only JPG, PNG, and GIF are allowed.";
            }
        }
    }

    if (!isset($error)) {
        $sql = "INSERT INTO issues (user_id, description, photo_url, location, status, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $user_id, $description, $image_path, $location, $status);

        if ($stmt->execute()) {
            $success = "Issue reported successfully!";
        } else {
            $error = "Error reporting the issue. Please try again.";
        }
    }
}
?>

<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-body text-center">
            <h2 class="card-title">Report an Issue</h2>
            <p class="text-muted">Help us improve by reporting any issues you encounter.</p>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"> <?= htmlspecialchars($error) ?> </div>
    <?php elseif (isset($success)): ?>
        <div class="alert alert-success"> <?= htmlspecialchars($success) ?> </div>
    <?php endif; ?>

    <div class="mt-4">
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="issue_category" class="form-label">Issue Category</label>
                <select name="issue_category" id="issue_category" class="form-control" required onchange="updateIssueTypes()">
                    <option value="" disabled selected>Select Category</option>
                    <option value="Waste Collection">Waste Collection Issues</option>
                    <option value="Illegal Dumping">Illegal Dumping</option>
                    <option value="Recycling">Recycling Issues</option>
                    <option value="Garbage Truck">Garbage Truck Issues</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="issue_type" class="form-label">Specific Issue</label>
                <select name="issue_type" id="issue_type" class="form-control" required>
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
</script>

<?php include 'includes/footer.php'; ?>
