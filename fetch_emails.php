<?php
// Include the database configuration
require_once 'config.php';

$hostname = '{your_email_server:993/imap/ssl}INBOX'; // Update your IMAP server details
$username = 'youremail@example.com'; // Your email
$password = 'your_email_password'; // Email password

$inbox = imap_open($hostname, $username, $password) or die('Cannot connect to IMAP: ' . imap_last_error());

$emails = imap_search($inbox, 'ALL');
if ($emails) {
    rsort($emails); // Sort emails by newest first
    foreach ($emails as $email_number) {
        $overview = imap_fetch_overview($inbox, $email_number, 0)[0];
        $message_id = $overview->message_id; // Unique message ID

        // Check if the email with this message_id is already in the database
        $check_stmt = $mysqli->prepare("SELECT id FROM emails WHERE message_id = ?");
        $check_stmt->bind_param('s', $message_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows == 0) { // Email doesn't exist, proceed with saving
            $subject = decode_imap_text($overview->subject);
            $from = decode_imap_text($overview->from);
            $date = date("Y-m-d H:i:s", strtotime($overview->date));

            // Fetch "To" emails
            $header = imap_headerinfo($inbox, $email_number);
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
            $structure = imap_fetchstructure($inbox, $email_number);

            // Get message body and attachments
            $message = '';
            $attachments = [];
            $html_message = '';
            $found_html = false; // Variable to track if HTML has been found
            parse_email_parts($inbox, $email_number, $structure, 0, $message, $html_message, $attachments, $found_html);

            // Use HTML message if found, otherwise fallback to plain text
            $final_message = ($found_html) ? $html_message : $message;

            // Save attachments as a comma-separated string
            $attachments_str = implode(',', $attachments);

            // Fetch the raw message (header + body)
            $raw_message = imap_fetchheader($inbox, $email_number) . $final_message;

            // Save email to MySQL database
            $stmt = $mysqli->prepare("INSERT INTO emails (subject, from_address, to_address, date, message, attachments, cc_address, message_id, raw_message) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssssssss', $subject, $from, $to_address, $date, $final_message, $attachments_str, $cc_address, $message_id, $raw_message);
            $stmt->execute();
        }

        // Close the statement
        $check_stmt->close();
    }
}

// Close the IMAP connection
imap_close($inbox);

// Redirect to the success page
header('Location: inbox-email-success-message.php');
exit; // Always call exit after header redirect to stop further script execution

// Function to decode IMAP text (such as filenames or subject)
function decode_imap_text($text) {
    $decoded_text = '';
    $elements = imap_mime_header_decode($text);
    foreach ($elements as $element) {
        $decoded_text .= $element->text;
    }
    return $decoded_text;
}

// Function to recursively parse email parts
function parse_email_parts($inbox, $email_number, $structure, $part_number, &$message, &$html_message, &$attachments, &$found_html) {
    if (isset($structure->parts)) { // Multipart email
        foreach ($structure->parts as $index => $sub_structure) {
            $new_part_number = ($part_number == 0) ? ($index + 1) : ($part_number . '.' . ($index + 1));
            parse_email_parts($inbox, $email_number, $sub_structure, $new_part_number, $message, $html_message, $attachments, $found_html);
        }
    } else { // Single part email
        if ($structure->type == 0) { // Text message
            $body = imap_fetchbody($inbox, $email_number, $part_number);

            // If HTML is found, only process HTML and ignore plain text
            if ($structure->subtype == 'HTML') {
                $html_message .= decode_body($body, $structure->encoding);
                $found_html = true; // Mark HTML as found, stop processing plain text
            } elseif ($structure->subtype == 'PLAIN' && !$found_html) {
                // Only process plain text if HTML has not been found
                $message .= decode_body($body, $structure->encoding);
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
                $file_path = 'attachments/' . basename($filename);
                if (!file_exists('attachments')) {
                    mkdir('attachments', 0777, true);
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