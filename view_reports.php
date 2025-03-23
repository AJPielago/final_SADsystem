<?php
session_start();
require 'config/db.php';
include 'includes/header.php';
?>

<div class="container-fluid mt-4 d-flex flex-column min-vh-100"> <!-- Ensure full height for footer placement -->
    <div class="card shadow-sm">
        <div class="card-body text-center">
            <h2 class="card-title">Reported Issues</h2>
            <p class="text-muted">Review and manage reported issues.</p>
        </div>
    </div>

    <div class="mt-4 flex-grow-1"> <!-- Push content to fill space -->
        <?php
        // Fetch reported issues
        $sql = "SELECT * FROM issues ORDER BY created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0): ?>
            <div class="table-responsive"> <!-- Make table responsive -->
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Description</th>
                            <th>Photo</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Reported At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($issue = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($issue['issue_id']) ?></td>
                                <td><?= nl2br(htmlspecialchars($issue['description'])) ?></td>
                                <td>
                                    <?php
                                    $image_path = 'uploads/' . basename($issue['photo_url']);
                                    if (!empty($issue['photo_url']) && file_exists($image_path)): ?>
                                        <!-- Clickable Image with Modal Trigger -->
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#imageModal<?= $issue['issue_id'] ?>">
                                            <img src="<?= htmlspecialchars($image_path) ?>" alt="Issue Image" width="100" class="img-thumbnail">
                                        </a>

                                        <!-- Bootstrap Modal (Enlarged Image) -->
                                        <div class="modal fade" id="imageModal<?= $issue['issue_id'] ?>" tabindex="-1" aria-labelledby="imageModalLabel<?= $issue['issue_id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="imageModalLabel<?= $issue['issue_id'] ?>">Issue Image</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body text-center">
                                                        <img src="<?= htmlspecialchars($image_path) ?>" alt="Full Size Image" class="img-fluid" style="max-width: 100%; max-height: 90vh;">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($issue['gps_location'] ?? 'Unknown') ?></td>
                                <td>
                                    <span class="badge bg-<?= ($issue['status'] == 'pending') ? 'warning' : 'success' ?>">
                                        <?= htmlspecialchars(ucfirst($issue['status'])) ?>
                                    </span>
                                </td>
                                <td><?= date('F j, Y g:i A', strtotime($issue['created_at'])) ?></td>
                                <td>
                                    <form action="update_issue_status.php" method="POST" class="d-flex">
                                        <input type="hidden" name="issue_id" value="<?= htmlspecialchars($issue['issue_id']) ?>">
                                        <select name="status" class="form-select form-select-sm me-2">
                                            <option value="pending" <?= ($issue['status'] == 'pending') ? 'selected' : '' ?>>Pending</option>
                                            <option value="resolved" <?= ($issue['status'] == 'resolved') ? 'selected' : '' ?>>Resolved</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div> <!-- Close .table-responsive -->
        <?php else: ?>
            <div class="alert alert-warning text-center">No reported issues found.</div>
        <?php endif; ?>
    </div> <!-- Close content div -->
</div> <!-- Close .container-fluid -->

<style>
/* Ensure full page height and footer positioning */
html, body {
    height: 100%;
    margin: 0;
    display: flex;
    flex-direction: column;
}

.container-fluid {
    flex: 1;
}

.table {
    width: 100%;
    table-layout: fixed;
    word-wrap: break-word;
}

footer {
    background-color: #343a40;
    color: white;
    text-align: center;
    padding: 5px 0;
    margin-top: auto;
}
</style>

<?php include 'includes/footer.php'; ?>
