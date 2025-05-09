<?php
session_start();
include '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email already exists
    $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $error = "Email is already registered.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $password);
        $stmt->execute();

        $_SESSION['user_id'] = $stmt->insert_id;
        $_SESSION['user_name'] = $name;
        header("Location: ../dashboard.php");
        exit;
    }
}
?>

<!-- HTML Form -->
 <!DOCTYPE html>
 <html lang="en">
 <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        /* Reset and base styles */
body {
    margin: 0;
    padding: 0;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    background: #f6f8fc;
    color: #333;
}

/* Form container */
form {
    width: 400px;
    margin: 80px auto;
    padding: 30px 35px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.07);
    animation: fadeIn 0.4s ease-in-out;
}

/* Heading */
h2 {
    text-align: center;
    margin-bottom: 25px;
    color: #222;
    font-size: 26px;
}

/* Inputs */
form input[type="text"],
form input[type="email"],
form input[type="password"] {
    width: 100%;
    padding: 12px 14px;
    margin-bottom: 18px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

form input:focus {
    border-color: #4a90e2;
    outline: none;
}

/* Button */
form button {
    width: 100%;
    padding: 12px;
    font-size: 16px;
    background-color: #4a90e2;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.3s ease, transform 0.2s ease;
}

form button:hover {
    background-color: #367adf;
    transform: translateY(-1px);
}

/* Error Message */
p {
    text-align: center;
    color: red;
    font-weight: 500;
}

/* Link */
a {
    display: block;
    text-align: center;
    margin-top: 1px;
    color: #4a90e2;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

/* Fade animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

    </style>
 </head>
 <body>
 <h2>Register</h2>
<form method="post">
    <input type="text" name="name" placeholder="Full Name" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Register</button>
</form>
<?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
<a href="login.php">Already registered? Login here</a>
 </body>
 </html>

