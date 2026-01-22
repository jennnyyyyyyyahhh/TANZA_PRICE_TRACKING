<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // --- User table fields ---
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;

    // --- Profile table fields ---
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $postal = trim($_POST['postal'] ?? '');
    $businessName = trim($_POST['business_name'] ?? '');
    $businessType = trim($_POST['business_type'] ?? '');
    $businessDesc = trim($_POST['business_description'] ?? '');
    $profileImage = null;
    $dob = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');

    // --- Validation ---
    if (!preg_match("/^[a-zA-Z0-9._%+-]+@(gmail|yahoo)\.com$/", $email)) {
        die("Error: Please use a Gmail or Yahoo email address.");
    }

    if ($password !== $confirmPassword) {
        die("Error: Passwords do not match.");
    }

    // --- Email duplication check ---
    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();
    if ($checkEmail->num_rows > 0) {
        die("Error: Email is already registered.");
    }
    $checkEmail->close();

    // --- Defaults and metadata ---
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $business_permit_path = null;
    $email_verified = 0;
    $verification_token = bin2hex(random_bytes(16));
    $reset_token = null;
    $reset_token_expiry = null;
    $account_status = 'active';
    $login_attempts = 0;
    $last_login_attempt = null;
    $last_login = null;
    $created_at = date('Y-m-d H:i:s');
    $updated_at = $created_at;

    // --- Begin transaction ---
    $conn->begin_transaction();

    try {
        // --- Insert into users ---
        $stmtUser = $conn->prepare("
            INSERT INTO users (
                first_name, last_name, email, password_hash, email_verified, verification_token,
                reset_token, reset_token_expiry, newsletter_subscribed,
                account_status, login_attempts, last_login_attempt,
                last_login, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmtUser->bind_param(
            "ssssisisssissss",
            $firstName,
            $lastName,
            $email,
            $hashedPassword,
            $email_verified,
            $verification_token,
            $reset_token,
            $reset_token_expiry,
            $newsletter,
            $account_status,
            $login_attempts,
            $last_login_attempt,
            $last_login,
            $created_at,
            $updated_at
        );

        if (!$stmtUser->execute()) {
            throw new Exception("User insert failed: " . $stmtUser->error);
        }

        $user_id = $stmtUser->insert_id;
        $stmtUser->close();

        // --- Insert into user_profiles ---
        $stmtProfile = $conn->prepare("
            INSERT INTO user_profiles (
                user_id, phone_number,  city, province,
                postal_code,  profile_image_path,
                date_of_birth, gender, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmtProfile->bind_param(
            "issssssss",
            $user_id,
            $phone,
            $city,
            $province,
            $postal,
            $profileImage,
            $dob,
            $gender,
            $updated_at
        );

        if (!$stmtProfile->execute()) {
            throw new Exception("Profile insert failed: " . $stmtProfile->error);
        }

        $stmtProfile->close();
        $conn->commit();

        echo "<script>
                alert('Registration successful! Please log in to continue.');
                window.location.href = '/login.html';
              </script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "Transaction failed: " . $e->getMessage();
    }

    $conn->close();
} else {
    echo "Invalid request.";
}
?>
