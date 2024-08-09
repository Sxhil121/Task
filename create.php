<?php
require_once '../config/db.php'; // Include the database connection
include 'navbar.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $gender = trim($_POST['gender']);
    $experiences = isset($_POST['experience']) ? $_POST['experience'] : [];

    $errors = validateUserInput($name, $email, $mobile, $gender, $experiences, $pdo);

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO users (name, email, mobile, gender) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $mobile, $gender]);

            $user_id = $pdo->lastInsertId();

            foreach ($experiences as $exp) {
                $stmt = $pdo->prepare("INSERT INTO experience (user_id, company, years_of_experience, months_of_experience) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $exp['company'], $exp['years'], $exp['months']]);
            }

            $pdo->commit();
            header("Location: read.php");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "Failed to add user: " . $e->getMessage();
        }
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
        $query = "SELECT * FROM users WHERE mobile = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$mobile]);
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
    <title>Create User</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function validateForm() {
            let errors = [];

            const name = document.forms["userForm"]["name"].value;
            const email = document.forms["userForm"]["email"].value;
            const mobile = document.forms["userForm"]["mobile"].value;
            const gender = document.forms["userForm"]["gender"].value;
            const experiences = document.querySelectorAll(".experience-entry");

            // Clear previous errors
            document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
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
                    errors.push("All experience fields must be filled out correctly.");
                    exp.querySelector('.error-message').textContent = "All experience fields must be filled out correctly.";
                }
            });

            if (errors.length > 0) {
                return false;
            }

            return true;
        }

        function validateEmail(email) {
            const re =
                /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\\.,;:\s@\"]+\.)+[^<>()[\]\\.,;:\s@\"]{2,})$/i;
            return re.test(String(email).toLowerCase());
        }

        function addExperience() {
            const container = document.getElementById('experience-container');
            const index = container.children.length;
            const div = document.createElement('div');
            div.className = 'experience-entry mb-3 p-3 border rounded';
            div.innerHTML = `
        <h4>Experience ${index + 1}</h4>
        <div class="form-group">
            <label for="experience[${index}][company]">Company:</label>
            <input type="text" id="experience[${index}][company]" name="experience[${index}][company]" class="form-control" required>
            <span class="error-message text-danger" id="experience[${index}][company]-error"></span>
        </div>
        <div class="form-group">
            <label for="experience[${index}][years]">Years:</label>
            <input type="number" id="experience[${index}][years]" name="experience[${index}][years]" class="form-control" min="0" required>
            <span class="error-message text-danger" id="experience[${index}][years]-error"></span>
        </div>
        <div class="form-group">
            <label for="experience[${index}][months]">Months:</label>
            <input type="number" id="experience[${index}][months]" name="experience[${index}][months]" class="form-control" min="0" max="11" required>
            <span class="error-message text-danger" id="experience[${index}][months]-error"></span>
        </div>
        <button type="button" class="btn btn-danger btn-sm" onclick="removeExperience(this)">Remove</button>
    `;
            container.appendChild(div);
        }

        function removeExperience(button) {
            const container = document.getElementById('experience-container');
            container.removeChild(button.parentElement);
        }
    </script>
</head>

<body>
    <div class="container">
        <div class="card my-4">
            <div class="card-header">
                <h1>Create User</h1>
            </div>
            <div class="card-body">
                <form name="userForm" action="" method="POST" onsubmit="return validateForm()">
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($name ?? ''); ?>">
                        <span class="error-message text-danger" id="name-error"><?php echo htmlspecialchars($errors['name'] ?? ''); ?></span>
                    </div>

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        <span class="error-message text-danger" id="email-error"><?php echo htmlspecialchars($errors['email'] ?? ''); ?></span>
                    </div>

                    <div class="form-group">
                        <label for="mobile">Mobile:</label>
                        <input type="text" id="mobile" name="mobile" class="form-control" value="<?php echo htmlspecialchars($mobile ?? ''); ?>">
                        <span class="error-message text-danger" id="mobile-error"><?php echo htmlspecialchars($errors['mobile'] ?? ''); ?></span>
                    </div>

                    <div class="form-group">
                        <label for="gender">Gender:</label>
                        <select id="gender" name="gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo isset($gender) && $gender == 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo isset($gender) && $gender == 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo isset($gender) && $gender == 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <span class="error-message text-danger" id="gender-error"><?php echo htmlspecialchars($errors['gender'] ?? ''); ?></span>
                    </div>

                    <div class="experience-container" id="experience-container">
                        <h3>Experience</h3>
                        <?php if (!empty($experiences)): foreach ($experiences as $index => $exp): ?>
                                <div class="experience-entry mb-3 p-3 border rounded">
                                    <h4>Experience <?php echo $index + 1; ?></h4>
                                    <div class="form-group">
                                        <label for="experience[<?php echo $index; ?>][company]">Company:</label>
                                        <input type="text" id="experience[<?php echo $index; ?>][company]" name="experience[<?php echo $index; ?>][company]" class="form-control" value="<?php echo htmlspecialchars($exp['company']); ?>">
                                        <?php if (isset($errors["experience[$index]"])): ?><span class="error-message text-danger"><?php echo htmlspecialchars($errors["experience[$index]"]); ?></span><?php endif; ?>
                                    </div>
                                    <div class="form-group">
                                        <label for="experience[<?php echo $index; ?>][years]">Years:</label>
                                        <input type="number" id="experience[<?php echo $index; ?>][years]" name="experience[<?php echo $index; ?>][years]" class="form-control" min="0" value="<?php echo htmlspecialchars($exp['years']); ?>">
                                        <?php if (isset($errors["experience[$index]"])): ?><span class="error-message text-danger"><?php echo htmlspecialchars($errors["experience[$index]"]); ?></span><?php endif; ?>
                                    </div>
                                    <div class="form-group">
                                        <label for="experience[<?php echo $index; ?>][months]">Months:</label>
                                        <input type="number" id="experience[<?php echo $index; ?>][months]" name="experience[<?php echo $index; ?>][months]" class="form-control" min="0" max="11" value="<?php echo htmlspecialchars($exp['months']); ?>">
                                        <?php if (isset($errors["experience[$index]"])): ?><span class="error-message text-danger"><?php echo htmlspecialchars($errors["experience[$index]"]); ?></span><?php endif; ?>
                                    </div>
                                </div>
                        <?php endforeach;
                        endif; ?>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <button type="button" class="btn btn-success" onclick="addExperience()">Add Experience</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
<?php include 'footer.php'; ?>

</html>