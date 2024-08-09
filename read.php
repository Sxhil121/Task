<?php
require_once '../config/db.php'; // Include database connection
include 'navbar.php';

// Define how many records you want per page
$records_per_page = 5;

// Get the current page from URL, default to 1 if not set
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start_from = ($current_page - 1) * $records_per_page;

try {
    // Get the total number of records
    $total_stmt = $pdo->prepare("SELECT COUNT(DISTINCT u.id) AS total FROM users u LEFT JOIN experience e ON u.id = e.user_id");
    $total_stmt->execute();
    $total_records = $total_stmt->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);

    // Fetch users and their experience data for the current page
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.mobile, 
               COUNT(e.id) AS total_companies, 
               SUM(e.years_of_experience) AS total_years, 
               SUM(e.months_of_experience) AS total_months
        FROM users u
        LEFT JOIN experience e ON u.id = e.user_id
        GROUP BY u.id
        LIMIT :start_from, :records_per_page
    ");
    $stmt->bindValue(':start_from', $start_from, PDO::PARAM_INT);
    $stmt->bindValue(':records_per_page', $records_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Experience Table</title>
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .edit-button {
            color: #007bff;
            text-decoration: none;
        }

        .edit-button:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h1 class="mb-4">User Experience List</h1>

        <table class="table table-striped table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Total Companies Served</th>
                    <th>Total Experience</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user):
                    // Calculate total experience
                    $total_years = $user['total_years'] + intdiv($user['total_months'], 12);
                    $remaining_months = $user['total_months'] % 12;
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['mobile']); ?></td>
                        <td><?php echo htmlspecialchars($user['total_companies']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($total_years) . ' Years'; ?>
                            <?php if ($remaining_months > 0): ?>
                                , <?php echo htmlspecialchars($remaining_months) . ' Months'; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="update.php?id=<?php echo htmlspecialchars($user['id']); ?>" class="edit-button">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination links -->
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php for ($page = 1; $page <= $total_pages; $page++): ?>
                    <li class="page-item <?php echo ($page == $current_page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page; ?>"><?php echo $page; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Bootstrap JS and dependencies (Optional) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>