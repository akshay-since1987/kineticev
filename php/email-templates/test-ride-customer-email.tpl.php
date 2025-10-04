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
            <h2>Thanks for Your Interest in Kinetic EV!</h2>
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
            
            <p>Thanks for showing your interest in Kinetic EV! Our team will get back to you shortly with all the information.</p>

            <div class='highlight'>
                <strong>Reference ID:</strong> <?php echo htmlspecialchars($test_ride_id); ?><br>
                <strong>Location:</strong> Pin Code <?php echo htmlspecialchars($pincode); ?>
            </div>
            
            <p>The Kinetic DX EV delivers thrilling acceleration, whisper-quiet rides, and zero emissions – an experience you won't forget! Want to know more right away? For assistance please contact our dedicated support team at <a href="tel:8600096800" style="color: #ef0012; text-decoration: none; font-weight: bold;">86000 96800</a> – we're here to help!</p>
            
            <p>Get ready to join thousands of happy Kinetic EV owners and embrace the future of electric mobility. ⚡</p>
            
            <p>Best regards,<br>
            <strong>Test Ride Experience Team<br>
            Kinetic EV</strong></p>
        </div>
    </div>
</body>
</html>
