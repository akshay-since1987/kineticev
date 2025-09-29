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
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 5px;
        }
        .highlight {
            background-color: #f8d7da;
            font-weight: bold;
            padding: 2px 4px;
            border-radius: 3px;
            color: #721c24;
        }
        .support-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        p {
            margin: 0 0 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <p>Dear Customer,</p>
        
        <p>Thank you for choosing <strong>Kinetic EV</strong> and attempting to book your Kinetic DX EV with us!</p>

        <p>We noticed there was a small hiccup with your payment for transaction <span class="highlight"><?php echo htmlspecialchars($txnid); ?></span>. Don't worry - this happens sometimes. You can continue to book again, go to: <a href="https://kineticev.in/book-now">https://kineticev.in/book-now</a>!</p>

        <div class="support-box">
            <p><strong>Great News - Our Team Will Reach Out to You!</strong></p>
            <p>Our payment specialists will contact you within the next few hours to:</p>
            <p>• Help you complete your booking with a hassle-free payment process<br>
            • Offer alternative payment options that work best for you<br>
            • Answer any questions about your chosen vehicle<br>
            • Ensure you get the best deal and experience with Kinetic EV</p>
        </div>
        
        <p><strong>Your Dream Kinetic DX EV is Just One Call Away!</strong></p>
        
        <p>We understand how exciting it is to get your hands on your new Kinetic EV, and we don't want a simple payment glitch to delay your journey towards sustainable mobility. Our team is committed to making this as smooth as possible for you.</p>
        
        <p>In the meantime, if you'd like to speak with us immediately, feel free to call our dedicated booking support line at <a href="tel:+918600096800" style="color: #007bff; text-decoration: none; font-weight: bold;">+91 860 009 6800</a>. We're available and ready to help!</p>
        
        <p>Thank you for your patience, and we look forward to welcoming you to the Kinetic EV family very soon! 🚀</p>
        
        <br>
        <p>Best regards,<br>
        <strong>Booking Support Team<br>
        Kinetic EV</strong></p>
    </div>
</body>
</html>
