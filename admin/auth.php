<?php
// Start the session to store login status
session_start();

// Include the database configuration file. The path is relative to the current file's location.
require_once '../config.php';

// Initialize an error message variable
$login_error = '';

// Process form data only when the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check if username and password are set and not empty
    if (!empty(trim($_POST["username"])) && !empty(trim($_POST["password"]))) {
        // Prepare a select statement
        $sql = "SELECT id, username, password FROM admins WHERE username = :username";

        if ($stmt = $pdo->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":username", $_POST["username"], PDO::PARAM_STR);

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Check if username exists, if yes then verify password
                if ($stmt->rowCount() == 1) {
                    if ($row = $stmt->fetch()) {
                        $id = $row["id"];
                        $username = $row["username"];
                        $db_password = $row["password"];
                        
                        // Since the requirement is plain text password, we compare directly
                        if ($_POST["password"] == $db_password) {
                            // Password is correct, so start a new session
                            session_start();

                            // Store data in session variables
                            $_SESSION["admin_loggedin"] = true;
                            $_SESSION["admin_id"] = $id;
                            $_SESSION["admin_username"] = $username;

                            // Redirect user to the admin dashboard
                            header("location: dashboard.php");
                            exit;
                        } else {
                            // Password is not valid
                            $login_error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
                        }
                    }
                } else {
                    // Username doesn't exist
                    $login_error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
                }
            } else {
                // This would be a server/database error
                $login_error = "เกิดข้อผิดพลาดบางอย่าง โปรดลองอีกครั้งในภายหลัง";
            }

            // Close statement
            unset($stmt);
        }
    } else {
        $login_error = "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน";
    }

    // If there was a login error, store it in the session and redirect back to login page
    if (!empty($login_error)) {
        $_SESSION['login_error'] = $login_error;
        header("location: login.php");
        exit;
    }
}

// Close connection
unset($pdo);
?>
