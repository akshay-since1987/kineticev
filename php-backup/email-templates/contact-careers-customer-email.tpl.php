<?php 

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

/* @var string $contact_id */
/* @var string $full_name */
/* @var string $help_type */
/* @var array $emailConfig */
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset='UTF-8'>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
        }

        .header {
            background: #007bff;
            color: white;
            padding: 15px 20px;
            text-align: center;
            margin: 0;
        }

        .header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: normal;
        }

        .content {
            padding: 20px;
            background: white;
        }

        .content p {
            margin: 0 0 15px 0;
            font-size: 14px;
        }

        .reference {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class='container'>
        <div class='header'>
            <h2>Thank You for Contacting Us!</h2>
        </div>
        <div class='content'>
            <p>Dear <?php echo htmlspecialchars($full_name); ?>,</p>
            <p>Thank you for contacting <strong>KineticEV</strong>. We have received your <?php echo htmlspecialchars($help_type); ?> request with the following reference: <span class="reference"><?php echo htmlspecialchars($contact_id); ?></span>.</p>
            <p>Our team will review your application and get back to you shortly.</p>
            
            <p>Best regards,<br>
            <strong>Support Team<br>
            Kinetic EV</strong></p>
        </div>
    </div>
</body>

</html>