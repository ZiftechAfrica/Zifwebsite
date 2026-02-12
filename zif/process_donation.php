<?php
include 'config.php';

// Paystack API Configuration (Example)
// In a real application, you would put these in a secure config or .env
define('PAYSTACK_SECRET_KEY', 'sk_test_xxxxxxxxxxxxxxxxxxxxxxxx'); // Replace with actual secret key

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $currency = 'USD'; // Default currency

    // 1. Generate a unique reference
    $reference = 'ZIF-' . time() . '-' . mt_rand(1000, 9999);

    // 2. Save the initial donation record as 'pending'
    $stmt = $conn->prepare("INSERT INTO donations (donor_name, donor_email, amount, currency, transaction_reference, payment_status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("ssdss", $name, $email, $amount, $currency, $reference);
    
    if ($stmt->execute()) {
        // 3. Initialize Payment with Paystack
        $url = "https://api.paystack.co/transaction/initialize";
        
        // Paystack expects amount in kobo (if NGN) or cents (if USD)
        // For simplicity, let's assume it handles the decimal or convert to cents
        $amount_in_cents = $amount * 100;

        $fields = [
            'email' => $email,
            'amount' => $amount_in_cents,
            'reference' => $reference,
            'callback_url' => 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/verify_donation.php',
            'metadata' => [
                'donor_name' => $name
            ]
        ];

        $fields_string = http_build_query($fields);

        // Open connection
        $ch = curl_init();
        
        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
            "Cache-Control: no-cache",
        ));
        
        // So that curl_exec returns the contents of the cURL; rather than echoing it
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 

        // Execute post
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            die("cURL Error #:" . $err);
        }

        $transaction = json_decode($result);

        if ($transaction->status) {
            // Redirect to Paystack Checkout page
            header('Location: ' . $transaction->data->authorization_url);
            exit;
        } else {
            // If Paystack initialization fails, show error or redirect back
            // For demo purposes, we'll simulate a success redirect if no key is provided
            if (strpos(PAYSTACK_SECRET_KEY, 'xxxx') !== false) {
                echo "<h3>Payment Gateway Mock Mode</h3>";
                echo "<p>Paystack Secret Key not set. Simulating a successful transaction...</p>";
                echo "<p><a href='verify_donation.php?reference=$reference'>Click here to simulate payment success callback</a></p>";
            } else {
                echo "Paystack API Error: " . $transaction->message;
            }
        }
    } else {
        echo "Error saving donation record: " . $conn->error;
    }
    $stmt->close();
}
$conn->close();
?>
