<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;

    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $postal = trim($_POST['postal'] ?? '');
    $dob = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $profileImage = null;

    if (!preg_match("/^[a-zA-Z0-9._%+-]+@(gmail|yahoo)\.com$/", $email)) {
        $msg = "Only Gmail or Yahoo emails are allowed.";
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $msg]);
        } else {
            $_SESSION['notif_message'] = $msg;
            $_SESSION['notif_type'] = "error";
            header("Location: ../signup.html");
        }
        exit;
    }

    if ($password !== $confirmPassword) {
        $msg = "Passwords do not match.";
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $msg]);
        } else {
            $_SESSION['notif_message'] = $msg;
            $_SESSION['notif_type'] = "error";
            header("Location: ../signup.html");
        }
        exit;
    }

    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();

    if ($checkEmail->num_rows > 0) {
        $msg = "This email is already registered. Please use another.";
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $msg]);
        } else {
            $_SESSION['notif_message'] = $msg;
            $_SESSION['notif_type'] = "error";
            header("Location: ../signup.html");
        }
        exit;
    }
    $checkEmail->close();

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $verification_token = bin2hex(random_bytes(16));
    $account_status = 'active';
    $created_at = date('Y-m-d H:i:s');
    $updated_at = $created_at;

    
    // Ensure `user_code` column exists; if not, add it (nullable, unique)
    $colCheck = $conn->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'user_code'");
    if ($colCheck) {
        $colCheck->execute();
        $colCheck->bind_result($colCount);
        $colCheck->fetch();
        $colCheck->close();
        if (empty($colCount)) {
            // best-effort: add column; if this fails on some hosts, it will be ignored later
            @$conn->query("ALTER TABLE users ADD COLUMN user_code VARCHAR(32) NULL UNIQUE AFTER last_name");
        }
    }

    $conn->begin_transaction();
    try {
        $email_verified = isset($_POST['verified']) && $_POST['verified'] == '1' ? 1 : 0;

        // Generate a monthly incremental user code: YYYYMM-0001 (per-month sequence)
        $prefix = date('Ym');
        $like = $prefix . '-%';
        $user_code = null;
        $stmtCode = $conn->prepare("SELECT user_code FROM users WHERE user_code LIKE ? ORDER BY user_code DESC LIMIT 1");
        if ($stmtCode) {
            $stmtCode->bind_param('s', $like);
            $stmtCode->execute();
            $stmtCode->bind_result($lastCode);
            if ($stmtCode->fetch() && !empty($lastCode)) {
                $parts = explode('-', $lastCode);
                $lastNum = (int)end($parts);
                $seq = $lastNum + 1;
            } else {
                $seq = 1;
            }
            $stmtCode->close();
            $user_code = sprintf('%s-%04d', $prefix, $seq);
        } else {
            // fallback unique-ish code
            $user_code = sprintf('%s-%04d', $prefix, random_int(1, 9999));
        }

        // Insert user including the user_code (if the column exists this will store it)
        $stmtUser = $conn->prepare("
            INSERT INTO users (
                first_name, last_name, user_code, email, password_hash, email_verified, verification_token,
                reset_token, reset_token_expiry, newsletter_subscribed,
                account_status, login_attempts, last_login_attempt,
                last_login, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?, 0, NULL, NULL, ?, ?)
        ");
        $stmtUser->bind_param(
            "sssssisisss",
            $firstName, $lastName, $user_code, $email, $hashedPassword,
            $email_verified, $verification_token, $newsletter,
            $account_status, $created_at, $updated_at
        );
        if (!$stmtUser->execute()) throw new Exception($stmtUser->error);
        $user_id = $stmtUser->insert_id;
        $stmtUser->close();

        $stmtProfile = $conn->prepare("
            INSERT INTO user_profiles (
                user_id, phone_number, city, province, postal_code, profile_image_path, date_of_birth, gender, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtProfile->bind_param("issssssss", $user_id, $phone, $city, $province, $postal, $profileImage, $dob, $gender, $updated_at);
        if (!$stmtProfile->execute()) throw new Exception($stmtProfile->error);
        $stmtProfile->close();

        $stmtAdmin = $conn->prepare("
            INSERT INTO admin (user_id, role, assigned_by, is_active, created_at, updated_at)
            VALUES (?, 'vendor', NULL, 1, ?, ?)
        ");
        $stmtAdmin->bind_param("iss", $user_id, $created_at, $updated_at);
        if (!$stmtAdmin->execute()) throw new Exception($stmtAdmin->error);
        $stmtAdmin->close();

        $conn->commit();
        $msg = "Registration successful. Default role: vendor.";
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $msg]);
        } else {
            $_SESSION['notif_message'] = $msg;
            $_SESSION['notif_type'] = "success";
            header("Location: ../login.php");
        }
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $msg = "Error during registration: " . $e->getMessage();
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $msg]);
        } else {
            $_SESSION['notif_message'] = $msg;
            $_SESSION['notif_type'] = "error";
            header("Location: ../signup.php");
        }
        exit;
    }

    
} else {
    $msg = "Invalid request.";
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
    } else {
        $_SESSION['notif_message'] = $msg;
        $_SESSION['notif_type'] = "error";
        header("Location: ../signup.php");
    }
    exit;
}

?>
