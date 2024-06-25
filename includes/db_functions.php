<?php

$config = include('config/config.php');

// Database connection function
function getDatabaseConnection() {
    global $config;
    $db = new mysqli($config['db_host'], $config['db_user'], $config['db_password'], $config['db_name']);
    if ($db->connect_error) {
        error_log("Database connection failed: " . $db->connect_error);
        return null;
    }

    // Set character set for the connection
    if (!$db->set_charset("utf8mb4")) {
        error_log("Error setting charset: " . $db->error);
        return null;
    }

    return $db;
}

// Function to get total number of emails in the 'sent_emails' table
function getTotalSentEmailsCount($search = '') {
    $db = getDatabaseConnection();
    if (!$db) {
        return 0;
    }

    $whereClause = '';
    if (!empty($search)) {
        $search = $db->real_escape_string($search);
        $whereClause = "WHERE subject LIKE '%$search%' OR from_email LIKE '%$search%' OR to_email LIKE '%$search%' OR cc_email LIKE '%$search%'";
    }

    $sqlTotal = "SELECT COUNT(*) AS total FROM sent_emails $whereClause";
    $resultTotal = $db->query($sqlTotal);
    $totalEmails = 0;

    if ($resultTotal && $resultTotal->num_rows > 0) {
        $rowTotal = $resultTotal->fetch_assoc();
        $totalEmails = $rowTotal['total'];
    }

    $db->close();

    return $totalEmails;
}

// Function to get emails from a specific folder sorted by date with pagination support
function getEmails($folder, $limit = 20, $offset = 0) {
    $db = getDatabaseConnection();
    if (!$db) {
        return [];
    }

    $stmt = $db->prepare("SELECT * FROM emails WHERE folder = ? ORDER BY date DESC LIMIT ?, ?");
    if (!$stmt) {
        error_log("Database statement preparation failed: " . $db->error);
        return [];
    }

    $stmt->bind_param('sii', $folder, $offset, $limit);
    if (!$stmt->execute()) {
        error_log("Database statement execution failed: " . $stmt->error);
        return [];
    }

    $result = $stmt->get_result();
    if (!$result) {
        error_log("Error retrieving result set: " . $db->error);
        return [];
    }

    $emails = [];
    while ($row = $result->fetch_assoc()) {
        $emails[] = $row;
    }

    $stmt->close();
    $db->close();

    return $emails;
}

// Function to get total number of emails in a specific folder
function getTotalEmailCount($folder) {
    $db = getDatabaseConnection();
    if (!$db) {
        return 0;
    }

    $stmt = $db->prepare("SELECT COUNT(*) FROM emails WHERE folder = ?");
    if (!$stmt) {
        error_log("Database statement preparation failed: " . $db->error);
        return 0;
    }

    $stmt->bind_param('s', $folder);
    if (!$stmt->execute()) {
        error_log("Database statement execution failed: " . $stmt->error);
        return 0;
    }

    $stmt->bind_result($count);
    $stmt->fetch();

    $stmt->close();
    $db->close();

    return $count;
}

// Function to get an email by its ID
function getEmailById($email_id) {
    $db = getDatabaseConnection();
    if (!$db) {
        return null;
    }

    $stmt = $db->prepare("SELECT * FROM emails WHERE id = ?");
    if (!$stmt) {
        error_log("Database statement preparation failed: " . $db->error);
        return null;
    }

    $stmt->bind_param('i', $email_id);
    if (!$stmt->execute()) {
        error_log("Database statement execution failed: " . $stmt->error);
        return null;
    }

    $result = $stmt->get_result();
    $email = $result->fetch_assoc();

    $stmt->close();
    $db->close();

    return $email;
}

// Function to get total number of emails in a specific folder
function getTotalEmailsByFolder($folder) {
    $db = getDatabaseConnection();
    if (!$db) {
        return 0;
    }

    $stmt = $db->prepare("SELECT COUNT(*) FROM emails WHERE folder = ?");
    if (!$stmt) {
        error_log("Database statement preparation failed: " . $db->error);
        return 0;
    }

    $stmt->bind_param('s', $folder);
    if (!$stmt->execute()) {
        error_log("Database statement execution failed: " . $stmt->error);
        return 0;
    }

    $stmt->bind_result($count);
    $stmt->fetch();

    $stmt->close();
    $db->close();

    return $count;
}

?>