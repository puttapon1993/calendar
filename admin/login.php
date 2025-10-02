<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ส่วนจัดการ</title>

    <!-- Google Fonts: Sarabun -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #F4F7FC;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: #fff;
            padding: 2.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-container h1 {
            text-align: center;
            color: #333;
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Important for padding */
        }
        .login-button {
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 4px;
            background-color: #0056b3;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .login-button:hover {
            background-color: #004494;
        }
        /* Add a placeholder for error messages */
        .error-message {
            color: #D8000C;
            background-color: #FFD2D2;
            padding: 0.75rem;
            border-radius: 4px;
            text-align: center;
            margin-bottom: 1.5rem;
            display: none; /* Hidden by default */
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>ส่วนจัดการปฏิทิน</h1>

        <!-- Error Message Placeholder -->
        <div class="error-message" id="errorMessage">
            <!-- Login errors will be shown here -->
        </div>

        <!-- The form will post data to an authentication script we will create later -->
        <form action="auth.php" method="POST">
            <div class="form-group">
                <label for="username">ชื่อผู้ใช้</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">รหัสผ่าน</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="login-button">เข้าสู่ระบบ</button>
        </form>
    </div>
</body>
</html>
