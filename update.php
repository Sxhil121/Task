<?php
require_once '../config/db.php'; // Include the database connection
include 'navbar.php';

// Check if the 'id' parameter is present in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('User ID is required');
}

$user_id = (int) $_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['delete'])) { // Check if the delete button was clicked
        try {
            // Begin transaction
            $pdo->beginTransaction();

            // Delete experience records associated with the user
            $stmt = $pdo->prepare("DELETE FROM experience WHERE user_id = ?");
            $stmt->execute([$user_id]);

            // Delete the user record
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);

            // Commit transaction
            $pdo->commit();

            // Redirect to the read.php page after deletion
            header("Location: read.php");
            exit(); // Ensure no further code is executed after redirection
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            echo "Failed to delete user: " . $e->getMessage();
        }
    } else {
        // Get updated data from POST request
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $mobile = trim($_POST['mobile']);
        $gender = trim($_POST['gender']);
        $experiences = isset($_POST['experience']) ? $_POST['experience'] : [];

        // Validate user input
        $errors = validateUserInput($name, $email, $mobile, $gender, $experiences, $pdo);

        if (empty($errors)) {
            try {
                // Begin transaction
                $pdo->beginTransaction();

                // Update user data in the Users table
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, mobile = ?, gender = ? WHERE id = ?");
                $stmt->execute([$name, $email, $mobile, $gender, $user_id]);

                // Delete existing experience records
                $stmt = $pdo->prepare("DELETE FROM experience WHERE user_id = ?");
                $stmt->execute([$user_id]);

                // Insert updated experience records
                foreach ($experiences as $exp) {
                    $stmt = $pdo->prepare("INSERT INTO experience (user_id, company, years_of_experience, months_of_experience) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user_id, $exp['company'], $exp['years'], $exp['months']]);
                }

                // Commit transaction
                $pdo->commit();

                header("Location: read.php");
                exit();
            } catch (PDOException $e) {
                // Rollback transaction on error
                $pdo->rollBack();
                echo 'Update failed: ' . $e->getMessage();
            }
        } else {
            // Display validation errors
            foreach ($errors as $error) {
                echo '<p class="alert alert-danger">' . htmlspecialchars($error) . '</p>';
            }
        }
    }
} else {
    try {
        // Fetch existing user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            die('User not found');
        }
        // Fetch existing experience data
        $stmt = $pdo->prepare("SELECT * FROM experience WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die('Fetch failed: ' . $e->getMessage());
    }
}

function validateUserInput($name, $email, $mobile, $gender, $experiences, $pdo)
{
    $errors = [];

    if (empty($name)) {
        $errors['name'] = "Name is required";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Valid email is required";
    }

    if (empty($mobile) || !preg_match('/^[0-9]{10,15}$/', $mobile)) {
        $errors['mobile'] = "Valid mobile number is required";
    } else {
        $query = "SELECT * FROM users WHERE mobile = ? AND id != ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$mobile, $_GET['id']]);
        if ($stmt->rowCount() > 0) {
            $errors['mobile'] = "Mobile number already exists";
        }
    }

    if (empty($gender) || !in_array($gender, ['Male', 'Female', 'Other'])) {
        $errors['gender'] = "Gender is required";
    }

    foreach ($experiences as $key => $exp) {
        if (empty($exp['company']) || empty($exp['years']) || empty($exp['months'])) {
            $errors["experience[$key]"] = "All experience fields are required";
        }
    }

    return $errors;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        .experience-entry {
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
            background-color: #f5f5f5;
        }

        .experience-entry button {
            background-color: #f44336;
            color: white;
        }

        .experience-entry button:hover {
            background-color: #d32f2f;
        }
    </style>
    <script>
        function validateForm() {
            let errors = [];

            const name = document.forms["userForm"]["name"].value;
            const email = document.forms["userForm"]["email"].value;
            const mobile = document.forms["userForm"]["mobile"].value;
            const gender = document.forms["userForm"]["gender"].value;
            const experiences = document.querySelectorAll(".experience-entry");

            // Clear previous errors
            document.querySelectorAll('.alert-danger').forEach(el => el.textContent = '');
            document.querySelectorAll('input, select').forEach(el => el.classList.remove('is-invalid'));

            if (name === "") {
                errors.push("Name is required");
                document.getElementById('name-error').textContent = "Name is required";
                document.getElementById('name').classList.add('is-invalid');
            }

            if (email === "" || !validateEmail(email)) {
                errors.push("Valid email is required");
                document.getElementById('email-error').textContent = "Valid email is required";
                document.getElementById('email').classList.add('is-invalid');
            }

            if (mobile === "" || !/^[0-9]{10,15}$/.test(mobile)) {
                errors.push("Valid mobile number is required");
                document.getElementById('mobile-error').textContent = "Valid mobile number is required";
                document.getElementById('mobile').classList.add('is-invalid');
            }

            if (gender === "") {
                errors.push("Gender is required");
                document.getElementById('gender-error').textContent = "Gender is required";
                document.getElementById('gender').classList.add('is-invalid');
            }

            experiences.forEach((exp, index) => {
                const company = exp.querySelector('input[name*="[company]"]').value;
                const years = exp.querySelector('input[name*="[years]"]').value;
                const months = exp.querySelector('input[name*="[months]"]').value;

                if (company === "" || years < 0 || months < 0 || months > 11) {
                    errors.push("All experience fields are required");
                    exp.querySelector('.error-message').textContent = "All experience fields are required";
                    exp.classList.add('is-invalid');
                }
            });

            return errors.length === 0;
        }

        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        function addExperience() {
            const experienceContainer = document.getElementById('experience-container');
            const experienceCount = experienceContainer.children.length;

            const experienceEntry = document.createElement('div');
            experienceEntry.classList.add('experience-entry');
            experienceEntry.innerHTML = `
                <h3>Experience ${experienceCount + 1}</h3>
                                <label>Company:
                    <input type="text" name="experience[${experienceCount}][company]" class="form-control" />
                </label>
                <label>Years:
                    <input type="number" name="experience[${experienceCount}][years]" class="form-control" />
                </label>
                <label>Months:
                    <input type="number" name="experience[${experienceCount}][months]" class="form-control" />
                </label>
                <button type="button" class="btn btn-danger" onclick="removeExperience(this)">Remove</button>
                <p class="error-message text-danger"></p>
            `;
            experienceContainer.appendChild(experienceEntry);
        }

        function removeExperience(button) {
            const experienceContainer = document.getElementById('experience-container');
            experienceContainer.removeChild(button.parentElement);
        }
    </script>
</head>

<body>
    <div class="container">
        <h1 class="my-4">Edit User</h1>
        <form name="userForm" method="post" onsubmit="return validateForm()">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($user_id); ?>" />

            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>">
                <p id="name-error" class="text-danger"></p>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>">
                <p id="email-error" class="text-danger"></p>
            </div>

            <div class="form-group">
                <label for="mobile">Mobile:</label>
                <input type="text" id="mobile" name="mobile" class="form-control" value="<?php echo htmlspecialchars($user['mobile']); ?>">
                <p id="mobile-error" class="text-danger"></p>
            </div>

            <div class="form-group">
                <label>Gender:</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" id="gender-male" name="gender" value="Male" <?php echo (strcasecmp($user['gender'], 'Male') == 0) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="gender-male">Male</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" id="gender-female" name="gender" value="Female" <?php echo (strcasecmp($user['gender'], 'Female') == 0) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="gender-female">Female</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" id="gender-other" name="gender" value="Other" <?php echo (strcasecmp($user['gender'], 'Other') == 0) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="gender-other">Other</label>
                </div>
                <p id="gender-error" class="text-danger"></p>
            </div>

            <div id="experience-container">
                <?php foreach ($experiences as $index => $exp): ?>
                    <div class="experience-entry">
                        <h3>Experience <?php echo $index + 1; ?></h3>
                        <label>Company:
                            <input type="text" name="experience[<?php echo $index; ?>][company]" class="form-control" value="<?php echo htmlspecialchars($exp['company']); ?>" />
                        </label>
                        <label>Years:
                            <input type="number" name="experience[<?php echo $index; ?>][years]" class="form-control" value="<?php echo htmlspecialchars($exp['years_of_experience']); ?>" />
                        </label>
                        <label>Months:
                            <input type="number" name="experience[<?php echo $index; ?>][months]" class="form-control" value="<?php echo htmlspecialchars($exp['months_of_experience']); ?>" />
                        </label>
                        <button type="button" class="btn btn-danger" onclick="removeExperience(this)">Remove</button>
                        <p class="error-message text-danger"></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="form-group my-3">
                <button type="button" class="btn btn-primary" onclick="addExperience()">Add Experience</button>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-success">Update</button>
                <input type="submit" name="delete" value="Delete User" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">
            </div>
        </form>
    </div>
</body>

</html>