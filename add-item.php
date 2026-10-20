<?php
require "db.php";

// Only accept form submissions
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

// Read form values
$name = trim($_POST["name"] ?? "");
$category = $_POST["category"] ?? "";
$location = trim($_POST["location"] ?? "");
$item_date = $_POST["item_date"] ?? "";
$status = $_POST["status"] ?? "";
$image_url = "";

// Allow only these categories
$allowed_categories = [
    "Electronics",
    "Accessories",
    "Books",
    "Clothing",
    "Other"
];

// Allow only Lost or Found
$allowed_statuses = ["Lost", "Found"];

// Validate required fields
if (
    $name === "" ||
    $location === "" ||
    !in_array($category, $allowed_categories, true) ||
    !in_array($status, $allowed_statuses, true)
) {
    die("Invalid form data. Please go back and try again.");
}

// Validate the date
$date = DateTime::createFromFormat("!Y-m-d", $item_date);

if (
    !$date ||
    $date->format("Y-m-d") !== $item_date ||
    $item_date > date("Y-m-d")
) {
    die("Please enter a valid date.");
}

// Save an optional uploaded image. The browser resizes images before submission;
// these checks also protect the server from invalid or unexpectedly large files.
if (isset($_FILES["image"]) && $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
        die("Image upload failed. Please try again.");
    }

    if ($_FILES["image"]["size"] > 5 * 1024 * 1024) {
        die("The uploaded image is too large.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES["image"]["tmp_name"]);
    $extensions = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/gif" => "gif",
        "image/webp" => "webp"
    ];

    if (!isset($extensions[$mime])) {
        die("Please upload a JPEG, PNG, GIF, or WebP image.");
    }

    $upload_dir = __DIR__ . "/uploads";
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
        die("Unable to create the uploads folder.");
    }

    $filename = bin2hex(random_bytes(16)) . "." . $extensions[$mime];
    $destination = $upload_dir . "/" . $filename;
    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $destination)) {
        die("Unable to save the uploaded image.");
    }

    $image_url = "uploads/" . $filename;
}

// Insert the item using a prepared statement
$sql = "INSERT INTO items
        (name, category, location, item_date, status, image_url)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $name,
    $category,
    $location,
    $item_date,
    $status,
    $image_url
]);

// Return to the main page
header("Location: index.php");
exit;
