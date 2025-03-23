<?php
// Check if the user is logged in and has the "resident" role
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if ($user_id) {
    // Ensure the connection is still open
    if ($conn->ping()) {
        // Query to get the role of the logged-in user
        $query = "SELECT role FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $role = $user['role']; // Assuming 'role' is the column storing the user's role
        }
        $stmt->close();
    } else {
        // Handle connection failure
        echo "Database connection lost.";
    }
}
?>

</div> <!-- End of main container -->

<!-- Floating Feedback Button (only visible for residents) -->
<?php if (isset($role) && $role === 'resident'): ?>
    <a href="#" class="floating-feedback-btn" data-bs-toggle="modal" data-bs-target="#feedbackModal" aria-label="Send Feedback">
        <i class="bi bi-pencil"></i>
    </a>
<?php endif; ?>

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feedbackModalLabel">Send Your Feedback</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="feedbackForm">
                    <div class="mb-3">
                        <label for="feedback" class="form-label">Your Feedback</label>
                        <textarea id="feedback" name="feedback" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="feedback_email" class="form-label">Your Email (Optional)</label>
                        <input type="email" id="feedback_email" name="email" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Feedback</button>
                    <div id="feedbackMessage" class="mt-3"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5 class="mb-3">
                    <i class="bi bi-recycle nature-icon"></i>
                    SAD System
                </h5>
                <p class="mb-0">Smart Automated Disposal System for efficient waste management and recycling.</p>
            </div>
            <div class="col-md-4">
                <h5 class="mb-3">Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="about.php" class="text-white text-decoration-none">About Us</a></li>
                    <li><a href="contact.php" class="text-white text-decoration-none">Contact</a></li>
                    <li><a href="faq.php" class="text-white text-decoration-none">FAQ</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5 class="mb-3">Connect With Us</h5>
                <div class="d-flex gap-3">
                    <a href="#" class="text-white"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-white"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="text-white"><i class="bi bi-instagram"></i></a>
                </div>
            </div>
        </div>
        <hr class="my-4 border-light">
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0">&copy; <?= date('Y') ?> SAD System. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="mb-0">Making the world greener, one pickup at a time.</p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script>
    // Add smooth scrolling to all links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // Handle the form submission via AJAX
    document.getElementById('feedbackForm').addEventListener('submit', function (e) {
        e.preventDefault(); // Prevent the default form submission

        var feedback = document.getElementById('feedback').value;
        var email = document.getElementById('feedback_email').value;

        var formData = new FormData();
        formData.append('feedback', feedback);
        formData.append('email', email);

        // Send the AJAX request to submit_feedback.php
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'user/submit_feedback.php', true);

        xhr.onload = function () {
            if (xhr.status === 200) {
                document.getElementById('feedbackMessage').innerHTML = "<p class='alert alert-success'>Thank you for your feedback!</p>";
                document.getElementById('feedbackForm').reset(); // Reset the form
                setTimeout(function() {
                    // Close the modal after a delay
                    var modal = bootstrap.Modal.getInstance(document.getElementById('feedbackModal'));
                    modal.hide();
                }, 2000);
            } else {
                document.getElementById('feedbackMessage').innerHTML = "<p class='alert alert-danger'>There was an error submitting your feedback. Please try again.</p>";
            }
        };

        xhr.send(formData);
    });
</script>

<!-- Custom CSS for Floating Button -->
<style>
    .floating-feedback-btn {
        position: fixed;
        bottom: 90px;
        right: 30px;
        background-color: #007bff;
        color: white;
        border-radius: 50%;
        padding: 12px;
        font-size: 20px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        z-index: 1000;
        text-align: center;
    }
    .floating-feedback-btn:hover {
        background-color: #0056b3;
    }
    .floating-feedback-btn i {
        font-size: 1.2em;
    }
</style>

</body>
</html>
