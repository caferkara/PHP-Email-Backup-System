# PHP Email Backup System

With this PHP email backup system, you can retrieve your emails from the server and store them in a database. This way, even if emails are deleted from the server, they will always remain in the database.

Please don't consider this as a large-scale email backup system. I created this simple email backup system according to my own needs and wanted to share it with everyone.

# What does this PHP email backup system do?

If you're using your hosting package's emails, you're likely to have limited storage space. Alternatively, when you set up emails on your computer via POP3, you risk losing them all if something happens to your computer. Therefore, with this system, you can fetch emails from the email server's inbox and sent folders and store them in your own database.

1. You can store email backups on an unused computer by installing a WAMP server there. Be sure to back up your computer's disk, or you could lose everything suddenly.

2. You can purchase a hosting package from any desired provider and install it there. This way, you can store your emails in your own hosting database, which is slightly more secure because hosting companies regularly back up data for you.

# System Installation

1. First, upload all the code files to a server where PHP can run.
2. Create a database, username, and password.
3. Edit the information in the config.php file inside the config folder with your own database and IMAP email settings.
4. Then, open your web browser and visit yourwebsite.com/email-backup/install/setup_database.php to create the necessary table structures in your database. After installation, you will be redirected to the homepage.

# System Usage

Once installation is complete, you'll be redirected to the homepage. To fetch emails from the Inbox and Sent folders on your server, click the "Refresh" button located in the top right corner to save them to your database. Note that the "Refresh" buttons for the Inbox and Sent folders are separate, so you'll need to click each button separately to download emails from both folders.

If you have questions about this system, you can contact me via this link: https://bit.ly/4cmrJnK

# Sobre el Sistema de Respaldo de Correos Electrónicos en PHP

[Haga clic aquí para leer más](https://caferkara.com.tr/projects/features-and-changelogs/sobre-el-sistema-de-respaldo-de-correos-electronicos-en-php/#post-3)

# License

This project is licensed under the MIT License.
