<?php
// Include the database configuration
require_once 'config.php';

$hostname = '{your_email_server:993/imap/ssl}Sent Items'; // Update with the correct IMAP folder path for "Sent"
$username = 'youremail@example.com'; // Your email
$password = 'your_email_password'; // Email password

$sent_folder = imap_open($hostname, $username, $password) or die('Cannot connect to IMAP: ' . imap_last_error());

$emails = imap_search($sent_folder, 'ALL');
if ($emails) {
    rsort($emails); // Sort emails by newest first
    foreach ($emails as $email_number) {
        $overview = imap_fetch_overview($sent_folder, $email_number, 0)[0];
        $message_id = $overview->message_id; // Unique message ID

        // Check if the email with this message_id is already in the "sent_emails" table
        $check_stmt = $mysqli->prepare("SELECT id FROM sent_emails WHERE message_id = ?");
        $check_stmt->bind_param('s', $message_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows == 0) { // Email doesn't exist, proceed with saving
            $subject = decode_imap_text($overview->subject);
            $from = decode_imap_text($overview->from);
            $date = date("Y-m-d H:i:s", strtotime($overview->date));

            // Fetch "To" emails
            $header = imap_headerinfo($sent_folder, $email_number);
            $to_addresses = [];
            if (!empty($header->to)) {
                foreach ($header->to as $to) {
                    $to_addresses[] = $to->mailbox . '@' . $to->host;
                }
            }
            $to_address = implode(',', $to_addresses);

            // Fetch CC emails if available
            $cc_addresses = [];
            if (!empty($header->cc)) {
                foreach ($header->cc as $cc) {
                    $cc_addresses[] = $cc->mailbox . '@' . $cc->host;
                }
            }
            $cc_address = implode(',', $cc_addresses);

            // Fetch the structure of the email
            $structure = imap_fetchstructure($sent_folder, $email_number);

            // Get message body and attachments
            $message = '';
            $html_message = '';
            $attachments = [];
            parse_email_parts($sent_folder, $email_number, $structure, 0, $message, $html_message, $attachments);

            // Prefer HTML message over plain text, if available
            $message_to_save = !empty($html_message) ? $html_message : $message;

            // Save attachments as a comma-separated string
            $attachments_str = implode(',', $attachments);

            // Fetch the raw message (header + body)
            $raw_message = imap_fetchheader($sent_folder, $email_number) . $message_to_save;

            // Save sent email to MySQL database
            $stmt = $mysqli->prepare("INSERT INTO sent_emails (subject, from_address, to_address, date, message, attachments, cc_address, message_id, raw_message) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssssssss', $subject, $from, $to_address, $date, $message_to_save, $attachments_str, $cc_address, $message_id, $raw_message);
            $stmt->execute();
        }

        // Close the statement
        $check_stmt->close();
    }
}

// Close the IMAP connection
imap_close($sent_folder);

// Redirect to the success page
header('Location: sent-email-success-message.php');
exit; // Always call exit after header redirect to stop further script execution

// Function to decode IMAP text with proper character encoding
function decode_imap_text($text) {
    if (is_null($text)) {
        return ''; // Handle null case to avoid warnings
    }

    $decoded_text = imap_mime_header_decode($text);
    $text = '';
    foreach ($decoded_text as $part) {
        $text .= $part->text;
    }
    return $text;
}

// Function to recursively parse email parts
function parse_email_parts($inbox, $email_number, $structure, $part_number, &$message, &$html_message, &$attachments) {
    if (isset($structure->parts)) { // Multipart email
        foreach ($structure->parts as $index => $sub_structure) {
            $new_part_number = ($part_number == 0) ? ($index + 1) : ($part_number . '.' . ($index + 1));
            parse_email_parts($inbox, $email_number, $sub_structure, $new_part_number, $message, $html_message, $attachments);
        }
    } else { // Single part email
        // Process the message part
        $body = imap_fetchbody($inbox, $email_number, $part_number);

        // Handle different content types
        if ($structure->type == 0) { // Text message
            if ($structure->subtype == 'PLAIN') {
                $message .= decode_body($body, $structure->encoding); // Fetch plain text
            } elseif ($structure->subtype == 'HTML') {
                $html_message .= decode_body($body, $structure->encoding); // Fetch HTML content
            }
        }

        // Handling attachments
        if ($structure->ifdisposition && strtolower($structure->disposition) == 'attachment') {
            $attachment_data = imap_fetchbody($inbox, $email_number, $part_number);
            $filename = '';

            // Get filename if available
            if (isset($structure->dparameters[0]->value)) {
                $filename = decode_imap_text($structure->dparameters[0]->value);
            } elseif (isset($structure->parameters[0]->value)) {
                $filename = decode_imap_text($structure->parameters[0]->value);
            }

            // If no filename, create one (for cases like inline attachments)
            if (empty($filename)) {
                $filename = 'attachment_' . $part_number . '.txt'; // Default to .txt for text/plain attachments
            }

            // Only process specific formats (JPG, PNG, TXT, PDF, DOC, XLS, CSV, ZIP)
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'txt', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'zip'];
            $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($file_extension, $allowed_extensions)) {
                // Save attachment to a file
                $file_path = 'sent-attachments/' . basename($filename);
                if (!file_exists('sent-attachments')) {
                    mkdir('sent-attachments', 0777, true);
                }
                file_put_contents($file_path, decode_body($attachment_data, $structure->encoding));
                $attachments[] = $filename;
            }
        }
    }
}

// Function to decode the email body based on encoding
function decode_body($body, $encoding) {
    switch ($encoding) {
        case 0: return $body; // 7BIT
        case 1: return quoted_printable_decode($body); // 8BIT
        case 2: return imap_binary($body); // BINARY
        case 3: return base64_decode($body); // BASE64
        case 4: return quoted_printable_decode($body); // QUOTED-PRINTABLE
        case 5: return $body; // OTHER
        default: return $body;
    }
}

?>