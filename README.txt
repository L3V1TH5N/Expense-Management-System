📄 Expense Management System — Installation Guide


System Requirements
1. XAMPP (or equivalent web server stack with Apache, MySQL, PHP ≥ 7.4)
2. Composer (for PHP dependency management)
3. Modern web browser (e.g., Chrome, Firefox)


⚙️ Installation Steps
1. Create the Database
    a. Launch phpMyAdmin (or any MySQL client).
    b. Create a new database named:
        - expense_management

2. Import the Database Schema
    a. Navigate to the database/schema.sql file.
    b. Import the file into the expense_management database.

3. Install Composer Dependencies
    a. Open a terminal/command prompt in the project directory.
    b. Run the following command:
        -composer install

4. Configure Database Connection
    a. Open includes/config.php.
    b. Update the following values with your local database credentials:

        php
        Copy
        Edit
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'expense_management');
        define('DB_USER', 'your_username');
        define('DB_PASS', 'your_password');

5. Set File Permissions
    a. Ensure the reports/ directory is writable by the web server to allow report generation and file storage.

6. Start XAMPP Services
    a. Open the XAMPP Control Panel.
    b. Click Start for both Apache and MySQL services.

7. Access the System
    a. Open your preferred browser.
    b. Enter the following URL:
        http://localhost/expense_management_system/login.php





🔐 Default Login Credentials
Role	    Username	        Password
Admin	    admin	            admin123 or password
Encoder	    encoder	            encoder123 / password / password01



⚠️ Important: For security, update all default passwords immediately after installation.
