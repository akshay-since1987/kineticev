<?php 

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(404);
    exit();
}

/* @var string $contact_id */
/* @var string $full_name */
/* @var string $phone */
/* @var string $email */
/* @var string $help_type */
/* @var string $message */
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
            background: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .content {
            background: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
        }

        .field {
            margin-bottom: 15px;
        }

        .label {
            font-weight: bold;
            color: #555;
        }

        .value {
            margin-left: 10px;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class='container'>
        <div class='header'>
            <h2>New Contact Form Submission</h2>
        </div>
        <div class='content'>
            <div class='field'>
                <span class='label'>Contact ID:</span>
                <span class='value'><?php echo htmlspecialchars($contact_id); ?></span>
            </div>
            <div class='field'>
                <span class='label'>Name:</span>
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
            <div class='field'>
                <span class='label'>Help Type:</span>
                <span class='value'><?php echo htmlspecialchars(ucfirst($help_type)); ?></span>
            </div>
            <div class='field'>
                <span class='label'>Message:</span>
                <div class='value'
                    style='background: white; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin-top: 10px;'>
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
            <p>KineticEV Contact Management System</p>
            <p>Please respond to the customer within 24 hours.</p>
        </div>
    </div>
</body>

</html>