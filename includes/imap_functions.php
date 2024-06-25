<?php

// Include configuration file
$config = include(__DIR__ . '/../config/config.php');

function connectIMAP($email, $password, $imapServer, $port, $ssl) {
    $mailbox = "{" . $imapServer . ":" . $port . "/imap";
    if ($ssl) {
        $mailbox .= "/ssl";
    }
    $mailbox .= "}INBOX";

    $inbox = @imap_open($mailbox, $email, $password);
    if (!$inbox) {
        error_log("IMAP connection failed: " . imap_last_error());
        return false;
    }

    return $inbox;
}

function fetchEmails($inbox, $folder) {
    $emails = imap_search($inbox, 'ALL');
    if (!$emails) {
        error_log("No emails found in the folder: " . imap_last_error());
        return;
    }

    foreach ($emails as $email_number) {
        $overview = imap_fetch_overview($inbox, $email_number, 0);
        $header = imap_headerinfo($inbox, $email_number);
        $structure = imap_fetchstructure($inbox, $email_number);
        
        $email_id = $overview[0]->message_id;
        $subject = $overview[0]->subject;
        $from_email = $overview[0]->from;
        $to_email = $overview[0]->to;
        $cc_email = isset($header->cc) ? implode(', ', array_map(function($cc) { return $cc->mailbox . '@' . $cc->host; }, $header->cc)) : '';
        $body = getBody($inbox, $email_number, $structure);
        // Change 'America/Bogota' to your desired timezone
        date_default_timezone_set('America/Bogota');
        $date = date("Y-m-d H:i:s", strtotime($overview[0]->date));
        
        saveEmailToDatabase($email_id, $subject, $from_email, $to_email, $cc_email, $body, $date, $folder);
    }
}

function getBody($inbox, $email_number, $structure) {
    $body = '';

    if ($structure->type == 1) {
        foreach ($structure->parts as $part_number => $part) {
            if ($part->type == 0 && $part->subtype == 'PLAIN') {
                $body = imap_fetchbody($inbox, $email_number, $part_number + 1);
                if ($part->encoding == 3) {
                    $body = base64_decode($body);
                } elseif ($part->encoding == 4) {
                    $body = quoted_printable_decode($body);
                }
                break;
            }
        }
    } else {
        $body = imap_body($inbox, $email_number);
        if ($structure->encoding == 3) {
            $body = base64_decode($body);
        } elseif ($structure->encoding == 4) {
            $body = quoted_printable_decode($body);
        }
    }

    return $body;
}

function saveEmailToDatabase($email_id, $subject, $from_email, $to_email, $cc_email, $body, $date, $folder) {
    global $config; // Access the configuration array

    $db = new mysqli(
        $config['db_host'], 
        $config['db_user'], 
        $config['db_password'], 
        $config['db_name']
    );

    if ($db->connect_error) {
        error_log("Database connection failed: " . $db->connect_error);
        return;
    }

    // Set character set for the connection
    if (!$db->set_charset("utf8mb4")) {
        error_log("Error setting charset: " . $db->error);
        return;
    }

    // Decode email subject if it's encoded
    $decoded_subject = imap_mime_header_decode($subject);
    if (is_array($decoded_subject) && count($decoded_subject) > 0) {
        // Concatenate decoded parts to get the final subject
        $subject = '';
        foreach ($decoded_subject as $part) {
            $subject .= $part->text;
        }
    }

    // Decode email body if it's encoded
    $body = imap_utf8($body); // Assuming the body is encoded in UTF-8
    // If the body is encoded differently, you may need to use a different decoding function

    $stmt = $db->prepare("INSERT INTO emails (email_id, subject, from_email, to_email, cc_email, body, date, folder) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE subject=?, from_email=?, to_email=?, cc_email=?, body=?, date=?, folder=?");
    if (!$stmt) {
        error_log("Database statement preparation failed: " . $db->error);
        return;
    }

    $stmt->bind_param('sssssssssssssss', $email_id, $subject, $from_email, $to_email, $cc_email, $body, $date, $folder, $subject, $from_email, $to_email, $cc_email, $body, $date, $folder);
    if (!$stmt->execute()) {
        error_log("Database statement execution failed: " . $stmt->error);
    }

    $stmt->close();
    $db->close();
}

?>