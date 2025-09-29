<?php

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

/**
 * Common modal containers for all pages
 * @param bool $include_test_drive - Include test drive modal (default: true)
 * @param bool $include_video_playlist - Include video playlist modal (default: true)
 */
function renderModals($include_test_drive = true, $include_video_playlist = true)
{
    ?>
    <div class="modal-container hidden-modal">
        <?php if ($include_test_drive): ?>
            <div class="overlay overlay--transparent" id="popup-test-drive">
                <div class="popup">
                    <button class="close-btn">&times;</button>
                    <form action="/api/submit-test-drive" method="post" ajax-updated="true">
                        <div class="popup-header">
                            <h2 class="heading">Book your <strong>Kinetic Test Ride</strong></h2>
                        </div>
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="full_name" required placeholder="Your Full Name"
                                data-validation="required,alphabets_only,min_length:2"
                                data-error-required="This field is required"
                                data-error-pattern="Enter a valid name"
                                data-error-min-length="Name should be larger than 2 characters">
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="phone">Mobile Number</label>
                            <input type="tel" id="phone" name="phone" required placeholder="+91 0000 00 0000"
                                data-validation="required,indian_mobile"
                                data-error-required="This field is required"
                                data-error-pattern="Please enter a valid 10-digit mobile number">
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required placeholder="Email Address"
                                data-validation="required,email"
                                data-error-required="This field is required"
                                data-error-pattern="Please enter a valid email address">
                            <div class="error-message"></div>
                        </div>
                        <div class="form-group">
                            <label for="test_ride_date">Preferred Date</label>
                            <input type="date" id="test_ride_date" name="date" required placeholder="Date..."
                                min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                data-validation="required,future_date"
                                data-error-required="This field is required"
                                data-error-pattern="Please select a future date (tomorrow or later)">
                            <div class="error-message"></div>
                        </div>
                        <div class="form-group">
                            <label for="pincode">Pin Code</label>
                            <input type="text" id="pincode" name="pincode" required placeholder="Pin Code"
                                data-validation="required,indian_pincode"
                                data-error-required="This field is required"
                                data-error-pattern="Please enter a valid 6-digit pin code">
                            <div class="error-message"></div>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" placeholder="Message..."></textarea>
                            <div class="error-message"></div>
                        </div>

                        <button type="submit" class="submit-btn">Submit</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($include_video_playlist): ?>
            <div class="overlay overlay--video-playlist" id="popup-video-playlist">
                <div class="popup">
                    <button class="close-btn">&times;</button>
                    <div class="popup-content">
                        <!-- Video playlist status indicator -->
                        <div class="play-list-container">
                            <div class="video-wrapper" data-item="easy-charge">
                                <video controls preload="metadata" playsinline webkit-playsinline>
                                    <source src="/-/videos/easy-charge.mp4" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                            <div class="video-wrapper" data-item="easy-flip">
                                <video controls preload="metadata" playsinline webkit-playsinline>
                                    <source src="/-/videos/easy-flip.mp4" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                            <div class="video-wrapper" data-item="storage-and-charging">
                                <video controls preload="metadata" playsinline webkit-playsinline>
                                    <source src="/-/videos/storage-and-charging.mp4" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                            <div class="video-wrapper" data-item="easy-key">
                                <video controls preload="metadata" playsinline webkit-playsinline>
                                    <source src="/-/videos/easy-key.mp4" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                            <div class="video-wrapper" data-item="my-kiney">
                                <video controls preload="metadata" playsinline webkit-playsinline>
                                    <source src="/-/videos/my-kiney.mp4" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                            <div class="video-wrapper" data-item="battery">
                                <video controls preload="metadata" playsinline webkit-playsinline>
                                    <source src="/-/videos/battery.mp4" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
?>