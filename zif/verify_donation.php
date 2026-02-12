<?php
include 'config.php';

define('PAYSTACK_SECRET_KEY', 'sk_test_xxxxxxxxxxxxxxxxxxxxxxxx'); // Replace with actual secret key

$reference = $_GET['reference'] ?? '';

if (empty($reference)) {
    header("Location: donate.html");
    exit;
}

// 1. Verify with Paystack
$url = "https://api.paystack.co/transaction/verify/" . rawurlencode($reference);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
    "Cache-Control: no-cache",
]);
$result = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    die("cURL Error #:" . $err);
}

$transaction = json_decode($result);

// Mock Success Logic (if API key is default)
$is_success = false;
if (strpos(PAYSTACK_SECRET_KEY, 'xxxx') !== false) {
    $is_success = true; // Simulate success for demo
} elseif ($transaction->status && $transaction->data->status === 'success') {
    $is_success = true;
}

if ($is_success) {
    // 2. Update donation record in database
    $stmt = $conn->prepare("UPDATE donations SET payment_status = 'completed' WHERE transaction_reference = ?");
    $stmt->bind_param("s", $reference);
    $stmt->execute();
    $stmt->close();

    // 3. Show success message
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Payment Successful | ZifTech Africa</title>
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            .success-container {
                text-align: center;
                padding: 100px 20px;
                max-width: 600px;
                margin: 0 auto;
            }
            .success-icon {
                font-size: 5rem;
                color: var(--primary-color);
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body class="light-mode">
        <div class="success-container">
            <i class="fas fa-check-circle success-icon"></i>
            <h1>Thank You for Your Donation!</h1>
            <p>Your payment was successful and your support is greatly appreciated.</p>
            <p>Reference: <?php echo htmlspecialchars($reference); ?></p>
            <br>
            <a href="index.html" class="btn btn-primary">Return to Home</a>
        </div>
    </body>
    </html>
    <?php
} else {
    // Update record to 'failed'
    $stmt = $conn->prepare("UPDATE donations SET payment_status = 'failed' WHERE transaction_reference = ?");
    $stmt->bind_param("s", $reference);
    $stmt->execute();
    $stmt->close();

    echo "<h1>Payment Verification Failed</h1>";
    echo "<p>Something went wrong with your payment. Please contact support.</p>";
    echo "<a href='donate.html'>Try Again</a>";
}

$conn->close();
?>
