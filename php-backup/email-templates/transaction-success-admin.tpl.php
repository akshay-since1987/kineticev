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
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #28a745; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #555; }
        .value { margin-left: 10px; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin: 15px 0; color: #155724; }
        .amount { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin: 10px 0; font-size: 16px; font-weight: bold; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>Booking Success Notification</h2>
        </div>
        <div class='content'>
            <div class='success'>
                <strong>✅ Payment Successful:</strong> A new booking transaction has been completed successfully!
            </div>
            
            <div class='field'>
                <span class='label'>Transaction ID:</span>
                <span class='value'><?php echo htmlspecialchars($txnid); ?></span>
            </div>
            
            <div class='field'>
                <span class='label'>Customer Name:</span>
                <span class='value'><?php echo htmlspecialchars($details['firstname'] ?? 'N/A'); ?></span>
            </div>
            
            <div class='field'>
                <span class='label'>Email:</span>
                <span class='value'><?php echo htmlspecialchars($details['email'] ?? 'N/A'); ?></span>
            </div>
            
            <div class='field'>
                <span class='label'>Phone:</span>
                <span class='value'><?php echo htmlspecialchars($details['phone'] ?? 'N/A'); ?></span>
            </div>
            
            <div class='field'>
                <span class='label'>Payment Amount:</span>
                <div class='amount'>₹<?php echo htmlspecialchars($details['amount'] ?? '0.00'); ?></div>
            </div>
            
            <div class='field'>
                <span class='label'>Vehicle Variant:</span>
                <span class='value'><?php echo htmlspecialchars($details['variant'] ?? 'N/A'); ?></span>
            </div>
            
            <div class='field'>
                <span class='label'>Vehicle Color:</span>
                <span class='value'><?php echo htmlspecialchars($details['color'] ?? 'N/A'); ?></span>
            </div>
            
            <div class='field'>
                <span class='label'>Booking Date:</span>
                <span class='value'><?php echo date('d M Y, h:i A'); ?></span>
            </div>
            
            <div class='field'>
                <span class='label'>Customer Address:</span>
                <div class='value' style='background: white; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px;'>
                    <?php echo htmlspecialchars($details['address'] ?? 'N/A'); ?><br>
                    <?php echo htmlspecialchars($details['city'] ?? ''); ?><?php echo !empty($details['city']) && !empty($details['state']) ? ', ' : ''; ?><?php echo htmlspecialchars($details['state'] ?? ''); ?><br>
                    Pin Code: <?php echo htmlspecialchars($details['pincode'] ?? 'N/A'); ?>
                </div>
            </div>
        </div>
        <div class='footer'>
            <p>KineticEV Booking Management System</p>
            <p>Please process this booking and arrange delivery coordination with the customer.</p>
        </div>
    </div>
</body>
</html>
