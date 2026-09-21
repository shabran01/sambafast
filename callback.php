<?php
// Paystack secret key.
// Do NOT hardcode a live key in this file. GitHub Push Protection refuses the
// push when a sk_live_... key is committed, and the key would otherwise sit in
// the repository history. Define PAYSTACK_SECRET_KEY in config.php, or export
// it as an environment variable instead.
$secretKey = defined('PAYSTACK_SECRET_KEY')
    ? PAYSTACK_SECRET_KEY
    : (getenv('PAYSTACK_SECRET_KEY') ?: '');

if (isset($_GET['reference'])) {
    $reference = $_GET['reference'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.paystack.co/transaction/verify/$reference");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $secretKey,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($result['status'] && $result['data']['status'] == 'success') {
        // Update customer status to active
        // Database connection
        $servername = "localhost";
        $username = "your_db_username";
        $password = "your_db_password";
        $dbname = "your_db_name";

        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $subdomain = 'isp.speedcomwifi.xyz';
        $query = "UPDATE customers SET status = 'active' WHERE subdomain = '$subdomain'";

        if ($conn->query($query) === TRUE) {
            // Redirect to admin page after successful payment
            header("Location: https://isp.speedcomwifi.xyz/admin");
            exit;
        } else {
            echo "Error updating record: " . $conn->error;
        }

        $conn->close();
    } else {
        echo "Payment verification failed.";
    }
} else {
    echo "No reference provided.";
}
?>
