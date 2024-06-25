<?php
return [
    'imap_server' => 'your_imap_host_server',  //your imap host
    'imap_port' => 993,  //usually imap port is 993. To be sure, you should check the imap port of your own mail server
    'imap_ssl' => true,  //you don't need to change this
    'db_host' => 'localhost',  //you don't need to change this
    'db_name' => 'change_here',  //your database name
    'db_user' => 'change_here',  //your database username
    'db_password' => 'change_here',  //your database password
    'emails' => [
        ['email' => 'your_email_address', 'password' => 'your_email_password'],
    ]
];
?>