<?php
// File: auth.php
// Location: /admin/
session_start();
require_once '../config.php';

$login_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty(trim($_POST["username"])) && !empty(trim($_POST["password"]))) {
        $sql = "SELECT id, username, password, real_name, role, is_active FROM users WHERE username = :username";

        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":username", $_POST["username"], PDO::PARAM_STR);

            if ($stmt->execute()) {
                if ($stmt->rowCount() == 1) {
                    if ($row = $stmt->fetch()) {
                        // Check if user is active
                        if ($row["is_active"] != 1) {
                            $login_error = "บัญชีผู้ใช้นี้ถูกระงับการใช้งาน";
                        } else {
                            // Check password
                            if ($_POST["password"] == $row["password"]) {
                                // Password is correct, start a new session
                                session_regenerate_id();

                                $_SESSION["user_loggedin"] = true;
                                $_SESSION["user_id"] = $row["id"];
                                $_SESSION["user_username"] = $row["username"];
                                $_SESSION["user_real_name"] = $row["real_name"];
                                $_SESSION["user_role"] = $row["role"];

                                header("location: dashboard.php");
                                exit;
                            } else {
                                $login_error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
                            }
                        }
                    }
                } else {
                    $login_error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
                }
            } else {
                $login_error = "เกิดข้อผิดพลาดบางอย่าง โปรดลองอีกครั้งในภายหลัง";
            }
            unset($stmt);
        }
    } else {
        $login_error = "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน";
    }

    if (!empty($login_error)) {
        $_SESSION['login_error'] = $login_error;
        header("location: login.php");
        exit;
    }
}
unset($pdo);
?>
