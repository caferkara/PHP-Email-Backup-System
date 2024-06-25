<?php
// Include necessary files
include(__DIR__ . '/includes/imap_functions_sent.php');
$config = include(__DIR__ . '/config/config.php');

// Extract configuration values
$emails = $config['emails'];
$imapServer = $config['imap_server'];
$imapPort = $config['imap_port'];
$imapSSL = $config['imap_ssl'];

$totalEmails = count($emails);
$processedEmails = 0;

foreach ($emails as $credentials) {
    if (empty($credentials['email']) || empty($credentials['password'])) {
        error_log("Missing email or password for one of the accounts.");
        continue;
    }

    $inbox = connectIMAP($credentials['email'], $credentials['password'], $imapServer, $imapPort, $imapSSL);
    if ($inbox) {
        fetchSentEmails($inbox);
        imap_close($inbox);
    } else {
        error_log("Failed to connect to IMAP server for email: " . $credentials['email']);
    }

    $processedEmails++;
    $progress = intval(($processedEmails / $totalEmails) * 100);
    echo json_encode(['progress' => $progress]);
    flush();
    usleep(500000); // simulate delay
}
?>
