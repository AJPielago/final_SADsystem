<?php
session_start();
include 'includes/header.php';
require 'config/db.php';

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details with building name
$sql = "SELECT u.full_name, u.email, u.phone_number, u.role, u.building_id, u.profile_picture, b.building_name as building 
        FROM users u 
        LEFT JOIN buildings b ON u.building_id = b.building_id 
        WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch all buildings for the dropdown
$buildings_sql = "SELECT building_id, building_name FROM buildings ORDER BY building_name";
$buildings_result = mysqli_query($conn, $buildings_sql);

// Close statement after fetching
$stmt->close();

// Check if user exists
if (!$user) {
    echo "<p class='alert alert-danger'>User not found.</p>";
    include 'includes/footer.php';
    exit();
}
?>

<title>Edit Profile - Green Bin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
    .profile-picture-container {
        position: relative;
        display: inline-block;
    }
    
    .profile-picture {
        border: 3px solid #28a745;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    
    .profile-picture-container i {
        font-size: 150px;
        color: #6c757d;
    }
    
    #profile_picture {
        max-width: 300px;
        margin: 0 auto;
    }
    
    .form-text {
        color: #6c757d;
        font-size: 0.875rem;
    }
</style>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="text-center">Edit Profile</h2>

            <?php
            if (isset($_GET['success'])) {
                echo '<div class="alert alert-success">Profile updated successfully!</div>';
            }
            if (isset($_GET['error'])) {
                echo '<div class="alert alert-danger">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            ?>

            <form action="controllers/update_profile.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_id); ?>">
                
                <!-- Profile Picture Section -->
                <div class="mb-4 text-center">
                    <div class="profile-picture-container mb-3">
                        <?php if (isset($user['profile_picture']) && $user['profile_picture'] != ''): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                 alt="Profile Picture" 
                                 class="rounded-circle profile-picture"
                                 style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <i class="bi bi-person-circle display-1 text-muted"></i>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Change Profile Picture</label>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                        <div class="form-text">Recommended size: 150x150 pixels. Maximum file size: 2MB</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           value="<?= htmlspecialchars($user['full_name']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($user['email']); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label for="phone_number" class="form-label">Phone Number</label>
                    <input type="text" class="form-control" id="phone_number" name="phone_number" 
                           value="<?= htmlspecialchars($user['phone_number']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="building_id" class="form-label">Building</label>
                    <select class="form-select" id="building_id" name="building_id" required>
                        <option value="">Select Building</option>
                        <?php while ($building = mysqli_fetch_assoc($buildings_result)): ?>
                            <option value="<?= $building['building_id'] ?>" 
                                    <?= ($user['building_id'] == $building['building_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($building['building_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <input type="text" class="form-control" id="role" value="<?= htmlspecialchars($user['role']); ?>" readonly>
                </div>

                <button type="submit" class="btn btn-primary w-100">Update Profile</button>
            </form>

            <!-- Change Password Button -->
            <button type="button" class="btn btn-warning mt-3 w-100" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                Change Password
            </button>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordLabel">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="controllers/change_password.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Old Password</label>
                        <input type="password" name="old_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <input type="hidden" name="user_id" value="<?= $user_id ?>">
                    <button type="submit" class="btn btn-primary w-100">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Include Bootstrap JavaScript (for modal functionality) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'includes/footer.php'; ?>
