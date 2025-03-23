<?php
session_start();
include 'includes/header.php';
require 'config/db.php';

// Fetch available donations
// Fetch available donations
$sql = "SELECT d.*, u.full_name FROM donations d 
        JOIN users u ON d.user_id = u.user_id 
        WHERE d.status = 'available' 
        ORDER BY d.created_at DESC"; // Use created_at instead of submitted_at

$result = $conn->query($sql);

// Debug: Check if the query failed
if (!$result) {
    die("Query failed: " . $conn->error);
}

?>

<div class="container mt-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Donations</h3>
                </div>
                <div class="card-body">
                    <?php if ($result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Donor</th>
                                        <th>Item</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Image</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['donation_id']) ?></td>
                                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                                            <td><?= htmlspecialchars($row['item_name']) ?></td>
                                            <td><?= htmlspecialchars($row['category']) ?></td>
                                            <td><?= htmlspecialchars($row['item_description']) ?></td>
                                            <td>
                                                <?php if ($row['image']): ?>
                                                    <img src="<?= htmlspecialchars($row['image']) ?>" alt="Item Image" style="width: 100px; height: auto;">
                                                <?php else: ?>
                                                    <span>No image</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">No donations placed.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
