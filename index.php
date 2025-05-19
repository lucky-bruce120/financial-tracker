<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Personal Financial Tracker</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Reset & Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f7faf7;
            color: #2c3e50;
            line-height: 1.6;
        }

        a {
            text-decoration: none;
            font-weight: bold;
        }

        /* Header */
        header {
            background-color: #2e7d32;
            color: #fff;
            padding: 20px 0;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        nav a {
            color: #ffffff;
            margin: 0 15px;
            font-size: 1rem;
            transition: color 0.3s ease;
        }

        nav a:hover {
            color: #c8e6c9;
        }

        /* Hero Section */
        .hero {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
            padding: 60px 40px;
            background-color: #ffffff;
        }

        .hero-text h2 {
            font-size: 2.5em;
            color: #2e7d32;
            margin-bottom: 20px;
        }

        .hero-text p {
            font-size: 1.2em;
            color: #555;
        }

        .hero-image img {
            width: 100%;
            max-width: 500px;
            animation: float 4s ease-in-out infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        /* Features */
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            padding: 60px 40px;
        }

        .feature {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.06);
            transition: transform 0.3s ease;
        }

        .feature:hover {
            transform: translateY(-5px);
        }

        .feature h3 {
            color: #2e7d32;
            margin-bottom: 10px;
            font-size: 1.2em;
        }

        .feature p {
            color: #333;
        }

        /* Call to Action */
        .cta {
            text-align: center;
            padding: 50px 20px;
            background-color: #2e7d32;
            color: #fff;
        }

        .cta a {
            display: inline-block;
            padding: 12px 25px;
            margin: 10px;
            background: #ffffff;
            color: #2e7d32;
            font-weight: bold;
            border-radius: 8px;
            transition: background 0.3s, color 0.3s;
        }

        .cta a:hover {
            background-color: #c8e6c9;
            color: #1b5e20;
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 20px;
            background-color: #e8f5e9;
            color: #4e6459;
            font-size: 14px;
        }

        /* Responsive Fix */
        @media (max-width: 768px) {
            .hero {
                grid-template-columns: 1fr;
                text-align: center;
            }
            .hero-image {
                order: -1;
            }
        }
    </style>
</head>
<body>

    <header>
        <h1>📊 Personal Financial Tracker</h1>
        <nav>
            <a href="auth/login.php">Login</a>
            <a href="auth/register.php">Register</a>
        </nav>
    </header>

    <section class="hero">
        <div class="hero-text">
            <h2>Take Control of Your Finances</h2>
            <p>Track income, manage expenses, set goals, and gain insights into your financial life.</p>
        </div>
        <div class="hero-image">
            <img src="illustration.svg" alt="Financial Planning">
        </div>
    </section>

    <section class="features">
        <div class="feature">
            <h3>🔐 Secure Login</h3>
            <p>Private, password-protected accounts for each user.</p>
        </div>
        <div class="feature">
            <h3>💰 Track Everything</h3>
            <p>Record your income and spending with real-time analysis.</p>
        </div>
        <div class="feature">
            <h3>📊 Financial Reports</h3>
            <p>Access monthly charts and category-based summaries.</p>
        </div>
        <div class="feature">
            <h3>🎯 Smart Goal Setting</h3>
            <p>Set financial goals and monitor your progress.</p>
        </div>
        <div class="feature">
            <h3>⏰ Bill Reminders</h3>
            <p>Never miss a payment or financial deadline again.</p>
        </div>
        
    </section>

    <section class="cta">
        <h2>Ready to Get Started?</h2>
        <p>Join hundreds managing their money the smart way.</p>
        <a href="auth/register.php">Register Now</a>
        <a href="auth/login.php">Login</a>
    </section>

    <footer>
        &copy; <?php echo date("Y"); ?> Personal Financial Tracker. All rights reserved.
    </footer>

</body>
</html>