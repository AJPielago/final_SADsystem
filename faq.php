<?php
require 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="text-center mb-4">Frequently Asked Questions</h1>
                    
                    <div class="accordion" id="faqAccordion">
                        <!-- General Questions -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    What is Green Bin?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Green Bin is an innovative waste management solution for BGC that uses AI and real-time monitoring to optimize waste collection routes and schedules. It replaces the traditional fixed-schedule collection with a dynamic, efficient system.
                                </div>
                            </div>
                        </div>

                        <!-- Collection Schedule -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    How does the dynamic collection schedule work?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Our system uses sensors to monitor waste bin fill levels in real-time. When bins reach a certain capacity, the system automatically schedules a collection. AI algorithms optimize collection routes to minimize travel time and fuel consumption.
                                </div>
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    How will I know when my waste will be collected?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    The system sends notifications through email when collection is scheduled. You'll receive updates about the estimated collection time and any changes to the schedule.
                                </div>
                            </div>
                        </div>

                        <!-- Waste Categories -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    What types of waste does the system handle?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    The system handles all types of waste including general waste, recyclables, and organic waste. Each type has designated collection bins and optimized collection schedules.
                                </div>
                            </div>
                        </div>

                        <!-- System Benefits -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    What are the benefits of using this system?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Benefits include reduced waiting times for collection, optimized routes that save fuel and reduce emissions, real-time updates on collection schedules, and improved overall waste management efficiency in BGC.
                                </div>
                            </div>
                        </div>

                        <!-- Technical Support -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                    What happens if there's a technical issue?
                                </button>
                            </h2>
                            <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Our system has backup protocols and 24/7 technical support. If there's an issue, you can contact our support team through our email, and we'll ensure your waste collection needs are met.
                                </div>
                            </div>
                        </div>

                        <!-- Participation -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                                    How can I participate in the program?
                                </button>
                            </h2>
                            <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Residents and businesses in BGC can register through our email. We'll provide smart waste bins and guide you through the setup process to ensure smooth integration with the system.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <p class="mb-0">Still have questions? <a href="contact.php" class="text-success text-decoration-none">Contact us</a> for more information.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require 'includes/footer.php';
?> 