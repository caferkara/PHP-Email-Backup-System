<?php
// Include configuration file
$config = include(__DIR__ . '/../config/config.php');

// Function to connect to IMAP server
function connectIMAP($email, $password, $imapServer, $port, $ssl) {
    $mailbox = "{" . $imapServer . ":" . $port . "/imap";
    if ($ssl) {
        $mailbox .= "/ssl";
    }
    $mailbox .= "}Sent Items"; // Adjust folder name as per your IMAP settings

    $inbox = @imap_open($mailbox, $email, $password);
    if (!$inbox) {
        error_log("IMAP connection failed: " . imap_last_error());
        return false;
    }

    return $inbox;
}

// Function to fetch emails from Sent Items folder
function fetchSentEmails($inbox) {
    $emails = imap_search($inbox, 'ALL');

    if (!$emails) {
        error_log("No emails found in Sent Items folder: " . imap_last_error());
        return false;
    }

    foreach ($emails as $email_number) {
        $overview = imap_fetch_overview($inbox, $email_number, 0);
        $header = imap_headerinfo($inbox, $email_number);
        $structure = imap_fetchstructure($inbox, $email_number);

        $email_id = $overview[0]->message_id;
        $subject = decodeMimeStr($overview[0]->subject);
        $from_email = $overview[0]->from;
        $to_email = $overview[0]->to;
        $cc_email = isset($header->cc) ? implode(', ', array_map(function($cc) { return $cc->mailbox . '@' . $cc->host; }, $header->cc)) : '';
        $email_body = getEmailBody($inbox, $email_number, $structure);
        $date = date("Y-m-d H:i:s", strtotime($overview[0]->date));

        // Check if the email_id already exists in the database
        if (!emailExists($email_id)) {
            saveSentEmailToDatabase($email_id, $subject, $from_email, $to_email, $cc_email, $email_body, $date);
        }
    }

    return true;
}

// Function to decode MIME-encoded string
function decodeMimeStr($string, $charset = 'UTF-8') {
    return iconv_mime_decode($string, 0, $charset);
}

// Function to check if an email with the given message_id exists in the database
function emailExists($message_id) {
    global $config;

    $conn = new mysqli(
        $config['db_host'],
        $config['db_user'],
        $config['db_password'],
        $config['db_name']
    );

    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        return true; // Return true to prevent insertion in case of connection failure
    }

    $stmt = $conn->prepare("SELECT id FROM sent_emails WHERE message_id = ?");
    if (!$stmt) {
        error_log("Database statement preparation failed: " . $conn->error);
        $conn->close();
        return true; // Return true to prevent insertion in case of preparation failure
    }

    $stmt->bind_param("s", $message_id);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;

    $stmt->close();
    $conn->close();

    return $exists;
}

// Function to fetch email body
function getEmailBody($inbox, $email_number, $structure) {
    $email_body = '';

    if ($structure->type == 1) { // multipart
        foreach ($structure->parts as $part_number => $part) {
            if ($part->type == 0 && $part->subtype == 'PLAIN') {
                $email_body = imap_fetchbody($inbox, $email_number, $part_number + 1);
                if ($part->encoding == 3) {
                    $email_body = base64_decode($email_body);
                } elseif ($part->encoding == 4) {
                    $email_body = quoted_printable_decode($email_body);
                }
                break;
            }
        }
    } else { // single part
        $email_body = imap_body($inbox, $email_number);
        if ($structure->encoding == 3) {
            $email_body = base64_decode($email_body);
        } elseif ($structure->encoding == 4) {
            $email_body = quoted_printable_decode($email_body);
        }
    }

    return $email_body;
}

// Function to save sent email to database
function saveSentEmailToDatabase($email_id, $subject, $from_email, $to_email, $cc_email, $email_body, $date) {
    global $config;

    $conn = new mysqli(
        $config['db_host'],
        $config['db_user'],
        $config['db_password'],
        $config['db_name']
    );

    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        return false;
    }

    $stmt = $conn->prepare("INSERT INTO sent_emails (message_id, subject, from_email, to_email, cc_email, email_body, email_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Database statement preparation failed: " . $conn->error);
        $conn->close();
        return false;
    }

    $stmt->bind_param('sssssss', $email_id, $subject, $from_email, $to_email, $cc_email, $email_body, $date);
    if (!$stmt->execute()) {
        error_log("Database statement execution failed: " . $stmt->error);
        $stmt->close();
        $conn->close();
        return false;
    }

    $stmt->close();
    $conn->close();
    return true;
}
?>