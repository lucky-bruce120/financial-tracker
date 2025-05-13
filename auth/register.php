<?php
session_start();
include '../config/db.php';

$error = []; // Initialize the error array

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $cpassword = trim($_POST['cpassword']);

    // Validation checks
    if (empty($name) || empty($email) || empty($password) || empty($cpassword)) {
        array_push($error, "All fields are required");
    }
    if (strlen($password) < 8) {
        array_push($error, "Password must be at least 8 characters");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        array_push($error, "Invalid email format");
    }
    if ($password != $cpassword) {
        array_push($error, "Passwords do not match");
    }
    if (!preg_match("/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/", $password)) {
        array_push($error, 'Password must contain at least one number, one uppercase letter, and one lowercase letter.');
    }

    // Check if email already exists
    $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        array_push($error, "Email is already registered.");
    }

    // If there are errors, pass them to the modal
    if (count($error) > 0) {
        $error_messages = json_encode($error); // Convert errors to JSON for JavaScript
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into the database
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hashed_password);
        $stmt->execute();

        if ($stmt) {
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['user_name'] = $name;
            header("Location: ../dashboard.php");
            exit;
        } else {
            echo "<div class='alert alert-danger'>Registration failed. Please try again.</div>";
        }
    }
}
?>

<!-- HTML Form -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f6f8fc;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        /* Form container */
        form {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.07);
            animation: fadeIn 0.4s ease-in-out;
        }

        /* Heading */
        form h2 {
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
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        form input:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 5px rgba(74, 144, 226, 0.5);
            outline: none;
        }

        /* Button */
        form button {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-weight: bold;
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

        /* Link Section */
        .lik {
            text-align: center;
            margin-top: 20px;
        }

        .lik p {
            font-size: 14px;
            color: #555;
        }

        .lik a {
            color: #4a90e2;
            text-decoration: none;
            font-weight: bold;
        }

        .lik a:hover {
            text-decoration: underline;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .modal-content h3 {
            margin-bottom: 15px;
            font-size: 20px;
            color: #333;
        }

        .modal-content ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .modal-content ul li {
            color: red;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .modal-content button {
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #4a90e2;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .modal-content button:hover {
            background-color: #367adf;
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

        /* Responsive Design */
        @media (max-width: 480px) {
            form {
                padding: 20px;
            }

            form h2 {
                font-size: 22px;
            }

            .modal-content {
                padding: 15px;
            }

            .modal-content h3 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <form method="post">
        <h2>Register</h2>
        <input type="text" name="name" placeholder="Full Name" required><br>
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <input type="password" name="cpassword" placeholder="Confirm Password" required><br>
        <button type="submit">Register</button>

        <div class="lik">
            <p>Already have an account?</p>
            <a href="login.php">Login here</a>
        </div>
    </form>

    <!-- Modal -->
    <div id="errorModal" class="modal">
        <div class="modal-content">
            <h3></h3>
            <ul id="errorList"></ul>
            <button onclick="closeModal()">Close</button>
        </div>
    </div>

    <script>
        // Display errors in the modal
        const errors = <?= isset($error_messages) ? $error_messages : '[]' ?>;
        if (errors.length > 0) {
            const errorList = document.getElementById('errorList');
            errors.forEach(err => {
                const li = document.createElement('li');
                li.textContent = err;
                errorList.appendChild(li);
            });
            document.getElementById('errorModal').style.display = 'flex';
        }

        // Close the modal
        function closeModal() {
            document.getElementById('errorModal').style.display = 'none';
        }
    </script>
</body>
</html>

