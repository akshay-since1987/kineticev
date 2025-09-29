<?php 

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

/* @var string $test_ride_id */
/* @var string $full_name */
/* @var string $phone */
/* @var string $email */
/* @var string $date */
/* @var string $pincode */
/* @var string $message */
/* @var array $emailConfig */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #555; }
        .value { margin-left: 10px; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
        .urgent { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>New Test Ride Request</h2>
        </div>
        <div class='content'>
            <div class='urgent'>
                <strong>Action Required:</strong> Customer wants to schedule a test ride
            </div>
            <div class='field'>
                <span class='label'>Test Ride ID:</span>
                <span class='value'><?php echo htmlspecialchars($test_ride_id); ?></span>
            </div>
            <div class='field'>
                <span class='label'>Customer Name:</span>
                <span class='value'><?php echo htmlspecialchars($full_name); ?></span>
            </div>
            <div class='field'>
                <span class='label'>Phone:</span>
                <span class='value'><?php echo htmlspecialchars($phone); ?></span>
            </div>
            <div class='field'>
                <span class='label'>Email:</span>
                <span class='value'><?php echo htmlspecialchars($email); ?></span>
            </div>
            <?php
            // <div class='field'>
            //     <span class='label'>Preferred Date:</span>
            //     <span class='value'>
            ?>
            <?php 
                // echo date('d M Y', strtotime($date)); 
            ?>
            <?php
            // </span>
            // </div>
            ?>
            <div class='field'>
                <span class='label'>Location (Pin Code):</span>
                <span class='value'><?php echo htmlspecialchars($pincode); ?></span>
            </div>
            <div class='field'>
                <span class='label'>Message:</span>
                <div class='value' style='background: white; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin-top: 10px;'>
                    <?php echo nl2br(htmlspecialchars($message)); ?>
                </div>
            </div>
            <div class='field'>
                <span class='label'>Submitted:</span>
                <span class='value'><?php echo date('d M Y, h:i A'); ?></span>
            </div>
            <div class='field'>
                <span class='label'>IP Address:</span>
                <span class='value'><?php echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'Unknown'); ?></span>
            </div>
        </div>
        <div class='footer'>
            <p>KineticEV Test Ride Management System</p>
            <p>Please contact the customer within <?php echo htmlspecialchars($emailConfig['response_time']); ?> to arrange the test ride.</p>
        </div>
    </div>
</body>
</html>
