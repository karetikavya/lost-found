<?php
require "db.php";

// Read search and filter values from the URL
$search = $_GET["search"] ?? "";
$category = $_GET["category"] ?? "";
$status = $_GET["status"] ?? "";
$sort = $_GET["sort"] ?? "new";

if (!in_array($sort, ["new", "old", "relevance"], true)) {
    $sort = "new";
}

// Start building the SQL query
$sql = "SELECT * FROM items WHERE 1=1";
$params = [];

// Search by item name
if ($search !== "") {
    $sql .= " AND name LIKE ?";
    $params[] = "%$search%";
}

// Filter by category
if ($category !== "") {
    $sql .= " AND category = ?";
    $params[] = $category;
}

// Filter by status
if ($status !== "") {
    $sql .= " AND status = ?";
    $params[] = $status;
}

// Relevance prioritizes names that begin with the search term, then partial matches.
if ($sort === "old") {
    $sql .= " ORDER BY created_at ASC";
} elseif ($sort === "relevance" && $search !== "") {
    $sql .= " ORDER BY CASE
        WHEN name LIKE ? THEN 0
        WHEN name LIKE ? THEN 1
        ELSE 2
    END, created_at DESC";
    $params[] = "$search%";
    $params[] = "%$search%";
} else {
    $sql .= " ORDER BY created_at DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert text into safe HTML
function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Campus Lost & Found</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header>
    <h1>Campus Lost & Found</h1>
    <p>Lost something? Found something? Let's reconnect!</p>
</header>

<main>

    <div class="top-bar">
        <h2>Browse Items</h2>
        <a class="button" href="#post-item">+ Post an Item</a>
    </div>

    <!-- Search and filters -->
    <form method="GET" class="filters">

        <input
            type="search"
            name="search"
            placeholder="Search item name..."
            value="<?= e($search) ?>"
        >

        <select name="category">
            <option value="">All Categories</option>
            <option value="Electronics"
                <?= $category === "Electronics" ? "selected" : "" ?>>
                Electronics
            </option>
            <option value="Accessories"
                <?= $category === "Accessories" ? "selected" : "" ?>>
                Accessories
            </option>
            <option value="Books"
                <?= $category === "Books" ? "selected" : "" ?>>
                Books
            </option>
        </select>

        <select name="status">
            <option value="">All Statuses</option>
            <option value="Lost"
                <?= $status === "Lost" ? "selected" : "" ?>>
                Lost
            </option>
            <option value="Found"
                <?= $status === "Found" ? "selected" : "" ?>>
                Found
            </option>
            <option value="Claimed"
                <?= $status === "Claimed" ? "selected" : "" ?>>
                Claimed
            </option>
        </select>

        <select name="sort" aria-label="Sort items">
            <option value="new" <?= $sort === "new" ? "selected" : "" ?>>
                Newest first
            </option>
            <option value="old" <?= $sort === "old" ? "selected" : "" ?>>
                Oldest first
            </option>
            <option value="relevance" <?= $sort === "relevance" ? "selected" : "" ?>>
                Relevance
            </option>
        </select>

        <button type="submit">Search</button>
        <a href="index.php">Reset</a>

    </form>

    <!-- Item cards -->
    <section class="item-grid">

        <?php if (count($items) === 0): ?>

            <p>No items found. Try different search filters.</p>

        <?php else: ?>

            <?php foreach ($items as $item): ?>

                <article class="item-card"
                    <?php if (!empty($item["image_url"])): ?>
                        data-image="<?= e($item["image_url"]) ?>"
                        data-image-alt="<?= e($item["name"]) ?>"
                        role="button"
                        tabindex="0"
                        aria-label="View full image of <?= e($item["name"]) ?>"
                    <?php endif; ?>
                >

                    <?php if (!empty($item["image_url"])): ?>
                        <img
                            class="item-image"
                            src="<?= e($item["image_url"]) ?>"
                            alt="<?= e($item["name"]) ?>"
                        >
                    <?php else: ?>
                        <div class="image-placeholder">
                            📦
                        </div>
                    <?php endif; ?>

                    <div class="item-content">

                        <span class="status">
                            <?= e($item["status"]) ?>
                        </span>

                        <h3><?= e($item["name"]) ?></h3>

                        <p>
                            Category: <?= e($item["category"]) ?>
                        </p>

                        <p>
                            Location: <?= e($item["location"]) ?>
                        </p>

                        <p>
                            Date: <?= e($item["item_date"]) ?>
                        </p>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

    <!-- Form for posting an item -->
    <section id="post-item" class="post-section">

        <h2>Post a Lost or Found Item</h2>

        <form action="add-item.php" method="POST"
              enctype="multipart/form-data" class="post-form">

            <label>Item Name</label>
            <input type="text" name="name"
                   maxlength="100" required>

            <label>Category</label>
            <select name="category" required>
                <option value="">Choose category</option>
                <option>Electronics</option>
                <option>Accessories</option>
                <option>Books</option>
                <option>Clothing</option>
                <option>Other</option>
            </select>

            <label>Location</label>
            <input type="text" name="location"
                   maxlength="100" required>

            <label>Date</label>
            <input type="date" name="item_date"
                   max="<?= date('Y-m-d') ?>" required>

            <label>Status</label>
            <select name="status" required>
                <option value="Lost">Lost</option>
                <option value="Found">Found</option>
            </select>

            <label for="image">Image (optional)</label>
            <input type="file" id="image" name="image" accept="image/*">
            <p id="image-help" class="image-help">Images are resized to a maximum of 600 × 600 pixels before upload.</p>
            <img id="image-preview" class="image-preview" alt="Selected image preview" hidden>

            <button type="submit">Post Item</button>

        </form>

    </section>

</main>

<div id="image-modal" class="image-modal" role="dialog" aria-modal="true"
     aria-labelledby="modal-image-title" hidden>
    <div class="image-modal-content">
        <button type="button" id="close-image-modal" class="modal-close"
                aria-label="Close image preview">×</button>
        <h2 id="modal-image-title" class="sr-only">Item image preview</h2>
        <img id="modal-image" alt="">
    </div>
</div>

<script>
const imageInput = document.getElementById("image");
const preview = document.getElementById("image-preview");
const imageHelp = document.getElementById("image-help");
const imageModal = document.getElementById("image-modal");
const modalImage = document.getElementById("modal-image");
const closeImageModal = document.getElementById("close-image-modal");

imageInput.addEventListener("change", async () => {
    const [file] = imageInput.files;
    preview.hidden = true;

    if (!file) return;

    if (!file.type.startsWith("image/")) {
        imageHelp.textContent = "Please choose an image file.";
        imageInput.value = "";
        return;
    }

    try {
        const source = await createImageBitmap(file);
        const scale = Math.min(1, 600 / source.width, 600 / source.height);
        const canvas = document.createElement("canvas");
        canvas.width = Math.max(1, Math.round(source.width * scale));
        canvas.height = Math.max(1, Math.round(source.height * scale));
        canvas.getContext("2d").drawImage(source, 0, 0, canvas.width, canvas.height);
        source.close();

        const blob = await new Promise(resolve => canvas.toBlob(resolve, "image/jpeg", 0.88));
        if (!blob) throw new Error("Image could not be resized.");

        const resizedFile = new File([blob], file.name.replace(/\.[^.]+$/, "") + ".jpg", {
            type: "image/jpeg"
        });
        const files = new DataTransfer();
        files.items.add(resizedFile);
        imageInput.files = files.files;
        preview.src = URL.createObjectURL(resizedFile);
        preview.hidden = false;
        imageHelp.textContent = `Ready to upload: ${canvas.width} × ${canvas.height} pixels.`;
    } catch (error) {
        imageHelp.textContent = "Unable to prepare this image. Please choose another file.";
        imageInput.value = "";
    }
});

function closeModal() {
    imageModal.hidden = true;
    modalImage.src = "";
}

function openModal(card) {
    modalImage.src = card.dataset.image;
    modalImage.alt = card.dataset.imageAlt;
    imageModal.hidden = false;
    closeImageModal.focus();
}

document.querySelectorAll(".item-card[data-image]").forEach(card => {
    card.addEventListener("click", () => openModal(card));
    card.addEventListener("keydown", event => {
        if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            openModal(card);
        }
    });
});

closeImageModal.addEventListener("click", closeModal);
imageModal.addEventListener("click", event => {
    if (event.target === imageModal) closeModal();
});
document.addEventListener("keydown", event => {
    if (event.key === "Escape" && !imageModal.hidden) closeModal();
});
</script>

</body>
</html>
