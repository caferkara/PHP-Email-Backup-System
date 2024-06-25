<?php
// Include the configuration file
$config = include(__DIR__ . '/../config/config.php');

// Extract database configuration
$servername = $config['db_host'];
$username = $config['db_user'];
$password = $config['db_password'];
$dbname = $config['db_name'];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    showError("Connection failed: " . $conn->connect_error);
    exit();
}

// SQL to create 'emails' table with utf8mb4_general_ci collation
$sql_emails = "CREATE TABLE IF NOT EXISTS emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_id VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    from_email VARCHAR(255) NOT NULL,
    to_email VARCHAR(255) NOT NULL,
    cc_email VARCHAR(255),
    body TEXT,
    date DATETIME,
    folder ENUM('inbox', 'sent'),
    UNIQUE(email_id(191))
) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;";

if ($conn->query($sql_emails) === TRUE) {
    showMessage("Inbox folder table created successfully");
} else {
    showError("Error creating table 'emails': " . $conn->error);
}

// SQL to create 'sent_emails' table with utf8mb4_general_ci collation
$sql_sent_emails = "CREATE TABLE IF NOT EXISTS sent_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    from_email VARCHAR(255) NOT NULL,
    to_email TEXT NOT NULL,
    cc_email TEXT,
    email_body TEXT,
    email_date DATETIME NOT NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;";

if ($conn->query($sql_sent_emails) === TRUE) {
    showMessage("Sent folder table created successfully");
} else {
    showError("Error creating table 'sent_emails': " . $conn->error);
}

// Close connection
$conn->close();

// Display redirection message and redirect after 2 seconds
echo '<div style="text-align: center; font-size: 20px; color: black; margin-top: 20px;">You are directed to the homepage. Please wait.</div>';

echo '<script>
    setTimeout(function(){
        window.location.href = "../index.php";
    }, 3000);
</script>';

function showMessage($message) {
    echo '<div style="text-align: center; color: green; font-size: 20px; margin-top: 20px;">' . $message . '</div>';
}

function showError($message) {
    echo '<div style="text-align: center; color: red; font-size: 20px; margin-top: 20px;">' . $message . '</div>';
}
?>