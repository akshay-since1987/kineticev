<?php 

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

/* @var string $txnid */ 
/* @var array $details */ 
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
            font-weight: bold;
        }

        p {
            margin: 0 0 15px 0;
        }
    </style>
</head>

<body>
    <div class='container'>
        <div class='header'>
            <h2>🎉 Booking Confirmed!</h2>
        </div>
        <div class='content'>
            <p>Dear Customer,</p>

            <p>Thank you for reaching out to us.</p>

            <p>We are pleased to confirm that we have successfully received the booking for your Kinetic DX EV, and your
                booking is confirmed:</p>

            <div class='highlight'>
                <strong>Your Booking is Confirmed!</strong><br>
                <strong>Transaction ID:</strong> <?php echo htmlspecialchars($txnid); ?>
            </div>

            <p>If you have any other questions or need assistance, please feel free to reply to this email or contact
                our customer support team at <a href="tel:+918600096800"
                    style="color: #ef0012; text-decoration: none; font-weight: bold;">+91 860 009 6800</a>.</p>

            <p>Thank you for choosing us!</p>

            <p>Best regards,<br>
                <strong>Support Team<br>
                    Kinetic EV</strong>
            </p>
        </div>
    </div>
</body>

</html>