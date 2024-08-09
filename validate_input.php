<?php
// Function to validate user input
function validateUserInput($name, $email, $mobile, $gender, $experience, $pdo) {
    $errors = [];

    if (empty($name)) {
        $errors[] = "Name is required";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }

    if (empty($mobile) || !preg_match('/^[0-9]{10,15}$/', $mobile)) {
        $errors[] = "Valid mobile number is required";
    } else {
        // Check for unique mobile
        $query = "SELECT * FROM Users WHERE mobile = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$mobile]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Mobile number already exists";
        }
    }

    if (empty($gender) || !in_array($gender, ['Male', 'Female', 'Other'])) {
        $errors[] = "Gender is required";
    }

    foreach ($experience as $exp) {
        if (empty($exp['company']) || empty($exp['years']) || empty($exp['months'])) {
            $errors[] = "All experience fields are required";
        }
    }

    return $errors;
}
?>
