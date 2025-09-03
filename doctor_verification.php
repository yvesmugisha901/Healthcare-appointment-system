<?php
session_start();
require 'connect.php'; // make sure this file exists and $conn is defined

// Get user_id from URL
$user_id = $_GET['user_id'] ?? null;
if (!$user_id) {
    die("Invalid access. No user ID provided.");
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qualification   = trim($_POST['qualification'] ?? '');
    $specialization  = trim($_POST['specialization'] ?? '');
    $license_no      = trim($_POST['license_no'] ?? '');
    
    // Handle document upload
    $document_path = '';
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['document']['tmp_name'];
        $fileName = $_FILES['document']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExt = ['pdf','jpg','jpeg','png'];
        
        if (in_array($fileExt, $allowedExt)) {
            $newFileName = 'doc_'.$user_id.'_'.time().'.'.$fileExt;
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $destPath = $uploadDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $document_path = $destPath;
            } else {
                $error = "Failed to upload document.";
            }
        } else {
            $error = "Invalid file type. Only PDF, JPG, JPEG, PNG allowed.";
        }
    } else {
        $error = "Please upload your certificate/document.";
    }

    if (!$error && $qualification && $specialization && $license_no) {
        // Insert into doctors table
        $stmt = $conn->prepare("INSERT INTO doctors (user_id, qualification, specialization, license_no, document_path, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("issss", $user_id, $qualification, $specialization, $license_no, $document_path);
        if ($stmt->execute()) {
            $success = "Verification submitted successfully! Awaiting admin approval.";
            header("refresh:3; url=login.php"); // redirect after 3 seconds
        } else {
            $error = "Database error: " . $conn->error;
        }
        $stmt->close();
    } elseif (!$error) {
        $error = "All fields are required!";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doctor Verification</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white shadow-lg rounded-2xl p-8 w-full max-w-md">
    <h2 class="text-2xl font-bold text-center text-blue-600 mb-6">Doctor Verification</h2>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4 text-sm">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-4 text-sm">
            <?= htmlspecialchars($success) ?> Redirecting to login...
        </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="mb-4">
            <label class="block text-gray-700 font-medium mb-1">Qualification</label>
            <input type="text" name="qualification" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-medium mb-1">Specialization</label>
            <input type="text" name="specialization" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-medium mb-1">License Number</label>
            <input type="text" name="license_no" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-medium mb-1">Upload Certificate/Document</label>
            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
        </div>

        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
            Submit Verification
        </button>
    </form>
</div>

</body>
</html>
