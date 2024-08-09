<?php
// delete_user.php
include 'config/db.php';
include 'create.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Delete user and associated experience
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    echo "User deleted successfully.";
}
?>
