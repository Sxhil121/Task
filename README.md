# Task
Project Overview

In this project, I developed a PHP-based CRUD (Create, Read, Update, Delete) application using XAMPP. The application manages user information and their associated experience records.

### Setting Up with XAMPP

1. **Installation and Setup**:
   - I installed XAMPP to create a local development environment with Apache (web server) and MySQL (database server).
   - Using phpMyAdmin, I created the necessary database and configured the connection in a `db.php` file.

2. **Project Structure**:
   - **`config/`**: Contains `db.php` for database connection.
   - **`users/`**: Includes CRUD operations (`create.php`, `read.php`, `update.php`, `delete.php`).
   - **`assets/`**: Holds `script.js` for client-side validation.
   - **`includes/`**: Contains utility scripts like `validate_input.php`.

3. **Features**:
   - **Create**: Users and their experiences are added via `create.php`, with both client-side and server-side validation.
   - **Read**: Users are listed in `read.php` with total experience details. Pagination is implemented for easier navigation.
   - **Update**: Users and their experiences can be edited in `update.php`, with transactions ensuring data consistency.
   - **Delete**: Users can be removed directly from the listing in `read.php`.

4. **Styling and User Interface**:
   - Bootstrap is used for a consistent and responsive design across all pages, with a navbar for easy navigation.

5. **Error Handling**:
   - PDO is used for secure database interactions, with proper error handling and transactions to manage updates.

### Conclusion

This project showcases my ability to create a structured and functional web application using PHP and MySQL within XAMPP, focusing on proper database management, error handling, and a user-friendly interface.
