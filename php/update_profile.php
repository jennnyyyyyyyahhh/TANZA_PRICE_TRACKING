<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'connection.php'; // $conn

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first_name  = trim($_POST['first_name']);
    $last_name   = trim($_POST['last_name']);
    $email       = trim($_POST['email']);
    $phone       = trim($_POST['phone_number']);
    $barangay    = trim($_POST['barangay']);
    $city        = trim($_POST['city']);
    $province    = trim($_POST['province']);
    $postal_code = trim($_POST['postal_code']);
    $dob         = trim($_POST['date_of_birth']);
    $gender      = trim($_POST['gender']);

    $emergency_name         = trim($_POST['emergency_name']);
    $emergency_relationship = trim($_POST['emergency_relationship']);
    $emergency_phone        = trim($_POST['emergency_phone']);
    $emergency_alt_phone    = trim($_POST['emergency_alt_phone']);

    $updated_at = date('Y-m-d H:i:s');

    // --- Handle Profile Image Upload ---
    $profile_image_path = null;
    if (!empty($_FILES['profile_image']['name'])) {
        $upload_dir = "../uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $filename = basename($_FILES['profile_image']['name']);
        $target_file = $upload_dir . time() . "_" . $filename;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            $profile_image_path = str_replace("../", "", $target_file);
        }
    }

    // --- Update users table ---
    $stmt1 = $conn->prepare("
        UPDATE users 
        SET first_name=?, last_name=?, email=? 
        WHERE id=?
    ");
    $stmt1->bind_param("sssi", $first_name, $last_name, $email, $user_id);
    $stmt1->execute();
    $stmt1->close();

    // --- Update user_profiles table ---
    if ($profile_image_path) {
        $stmt2 = $conn->prepare("
            UPDATE user_profiles 
            SET phone_number=?, barangay=?, city=?, province=?, postal_code=?, 
                profile_image_path=?, date_of_birth=?, gender=?, updated_at=? 
            WHERE user_id=?
        ");
        $stmt2->bind_param("sssssssssi", $phone, $barangay, $city, $province, $postal_code, $profile_image_path, $dob, $gender, $updated_at, $user_id);
    } else {
        $stmt2 = $conn->prepare("
            UPDATE user_profiles 
            SET phone_number=?, barangay=?, city=?, province=?, postal_code=?, 
                date_of_birth=?, gender=?, updated_at=? 
            WHERE user_id=?
        ");
        $stmt2->bind_param("ssssssssi", $phone, $barangay, $city, $province, $postal_code, $dob, $gender, $updated_at, $user_id);
    }
    $stmt2->execute();
    $stmt2->close();

    // --- Insert or Update emergency_contacts ---
    $stmt3 = $conn->prepare("
        INSERT INTO emergency_contacts 
        (user_id, contact_name, relationship, contact_number, alt_contact_number)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            contact_name=VALUES(contact_name),
            relationship=VALUES(relationship),
            contact_number=VALUES(contact_number),
            alt_contact_number=VALUES(alt_contact_number)
    ");
    $stmt3->bind_param("issss", $user_id, $emergency_name, $emergency_relationship, $emergency_phone, $emergency_alt_phone);
    $stmt3->execute();
    $stmt3->close();

    header("Location: ../vendor-profile.php?updated=1");
    exit;
}

$conn->close();
?>
