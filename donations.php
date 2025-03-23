<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">Donate for Recycling</h3>
                </div>
                <div class="card-body">

                    <!-- Nearby Recycling Centers -->
                    <h4 class="text-center mt-4">🏭 Nearby Recycling Centers</h4>
                    <div class="row">

                        <!-- Recycling Center 1 -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <img src="assets/recycling_center_1.jpg" class="card-img-top" alt="Recycling Center 1" style="height: 200px; object-fit: cover;">
                                <div class="card-body">
                                    <h5 class="card-title">The Plastic Flamingo (The Plaf)</h5>
                                    <p class="card-text">2/F The Trade & Financial Tower, 32nd St., Bonifacio Global City, Taguig</p>
                                </div>
                            </div>
                        </div>

                        <!-- Recycling Center 2 -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <img src="assets/recycling_center_2.png" class="card-img-top" alt="Recycling Center 2" style="height: 200px; object-fit: cover;">
                                <div class="card-body">
                                    <h5 class="card-title">BGC Central Park Recycling Hub</h5>
                                    <p class="card-text">3rd Avenue corner 9th St., Bonifacio Global City, Taguig</p>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
?>
