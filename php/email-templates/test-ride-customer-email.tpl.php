<?php 

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

/* @var string $full_name */
/* @var string $test_ride_id */
/* @var string $date */
/* @var string $pincode */
/* @var string $image_url */
/* @var array $emailConfig */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #ef0012;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            background: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .highlight {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .side-by-side {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>Test Ride Request Successful!</h2>
        </div>
        <div class='content'>
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td class="side-by-side">
                        <p>Dear <?php echo htmlspecialchars($full_name); ?>,</p>
                    </td>
                    <td class="side-by-side" width="120" style="padding-right:16px;">
                        <img src="<?php echo htmlspecialchars($image_url); ?>" alt="Kinetic EV DX" width="120" style="display:block; border-radius:8px;" />
                    </td>
                </tr>
            </table>
            
            <h3>Your Kinetic EV Test Ride is Successful!</h3>
            <p>Thank you for choosing to experience the future of sustainable mobility with us! We're thrilled that you want to test ride our amazing Kinetic DX EV.</p>
            
            <div class='highlight'>
                <strong>Your Test Ride request is Successful!</strong><br>
                <strong>Reference ID:</strong> <?php echo htmlspecialchars($test_ride_id); ?><br>
                <strong>Location:</strong> Pin Code <?php echo htmlspecialchars($pincode); ?>
            </div>
            
            <p><strong>What happens next?</strong></p>
            <p>Our dedicated test ride specialists will contact you shortly:</p>
            <p>• Confirm the exact location and timing that works best for you<br>
            • Share exciting details about the vehicle features you'll experience<br>
            • Answer any questions you might have about our Kinetic DX EV<br>
            • Discuss special offers and booking options available to you</p>
            
            <p>We will arrange a test ride at your nearest KineticEV showroom or authorized dealer.</p>
            
            <p><strong>Get ready for an incredible experience!</strong> Our Kinetic DX EV offers amazing acceleration, whisper-quiet rides, and zero emissions - you're going to love it!</p>
            
            <p>Can't wait to speak with us? Feel free to call our test ride hotline at <a href="tel:8600096800" style="color: #ef0012; text-decoration: none; font-weight: bold;">86000 96800</a> - we're here to help!</p>
            
            <p>We're excited to show you why thousands of customers have already chosen Kinetic EV for their daily commute. Get ready to fall in love with electric! ⚡</p>
            
            <p>Best regards,<br>
            <strong>Test Ride Experience Team<br>
            Kinetic EV</strong></p>
        </div>
    </div>
</body>
</html>
