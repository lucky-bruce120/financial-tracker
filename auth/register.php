<?php
session_start();
include '../config/db.php';

$name = "";
$email = "";

$error = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if ($name == "") {
        $error[] = "Name is required.";
    }
    if ($email == "") {
        $error[] = "Email is required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error[] = "Invalid email format.";
    }
    if ($password == "") {
        $error[] = "Password is required.";
    }
    if ($password !== $confirm) {
        $error[] = "Passwords do not match.";
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $error[] = "Email is already registered.";
    }
    $stmt->close();

    if (count($error) === 0) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hashed_password);
        $stmt->execute();

        if ($stmt) {
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['user_name'] = $name;
            header("Location: ../dashboard.php");
            exit;
        } else {
            $error[] = "Registration failed. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="auth-style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            width: 100%;
            max-width: 400px;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #4f93ff;
            margin-bottom: 20px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        label {
            font-weight: bold;
            color: #555;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="submit"] {
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
        }
        input:focus {
            outline: none;
            border-color: #4f93ff;
            box-shadow: 0 0 8px rgba(79, 147, 255, 0.5);
        }
        input[type="submit"] {
            background-color: #4f93ff;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #3273dc;
        }
        p {
            text-align: center;
            margin-top: 20px;
            color: #555;
        }
        a {
            text-align: center;
            display: block;
            margin-top: 15px;
            color: #4f93ff;
            text-decoration: none;
            font-weight: bold;
        }
        a:hover {
            color: #3273dc;
            text-decoration: underline;
        }
        .modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000;
        }
        .modal-content {
            background: #fff; padding: 20px; border-radius: 10px; max-width: 350px; margin: auto; text-align: center;
        }
        .modal-content button { margin-top: 15px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Register</h1>
    <form method="post">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required value="<?= htmlspecialchars($name) ?>">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email) ?>">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <label for="confirm">Confirm Password:</label>
        <input type="password" id="confirm" name="confirm" required>
        <input type="submit" value="Register">
    </form>
    <p>Already have an account? <a href="login.php">Login</a></p>
</div>

<!-- Modal Notification -->
<div id="notificationModal" class="modal">
    <div class="modal-content">
        <h3 id="notificationMessage"></h3>
        <button onclick="closeModal('notificationModal')">OK</button>
    </div>
</div>


--
<script>
function showModal(modalId, message) {
    var modal = document.getElementById(modalId);
    document.getElementById('notificationMessage').textContent = message;
    modal.style.display = 'flex';
}
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}
// Show error(s) as modal if exists
<?php if (!empty($error)): ?>
    showModal('notificationModal', <?= json_encode(implode("\n", $error)) ?>);
<?php endif; ?>
</script>
</body>
</html>

