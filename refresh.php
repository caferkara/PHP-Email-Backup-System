<?php
include(__DIR__ . '/includes/imap_functions.php');
include(__DIR__ . '/includes/db_functions.php');
$config = include(__DIR__ . '/config/config.php');

$emails = $config['emails'];
$imapServer = $config['imap_server'];
$imapPort = $config['imap_port'];
$imapSSL = $config['imap_ssl'];
$folder = $_POST['folder'];

$totalEmails = count($emails);
$processedEmails = 0;
$timePerEmail = 400000;

foreach ($emails as $credentials) {
    if (empty($credentials['email']) || empty($credentials['password'])) {
        error_log("Missing email or password for one of the accounts.");
        continue;
    }

    $inbox = connectIMAP($credentials['email'], $credentials['password'], $imapServer, $imapPort, $imapSSL);
    if ($inbox) {
        fetchEmails($inbox, $folder);
        imap_close($inbox);
    } else {
        error_log("Failed to connect to IMAP server for email: " . $credentials['email']);
    }

    $processedEmails++;
    usleep($timePerEmail); // Simulate delay
}

echo json_encode(['status' => 'success']);
?>