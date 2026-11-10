<?php
/**
 * NomReNom - Add / Edit Food Box
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$pdo = getDbConnection();
$adminId = currentUserId();

$id = (int)($_GET['id'] ?? 0);
$isEdit = ($id > 0);
$box = null;

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM food_boxes WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $box = $stmt->fetch();
    if (!$box) {
        setFlash('danger', 'Food box not found for editing.');
        header('Location: ' . BASE_URL . '/admin/boxes.php');
        exit;
    }
}

// Fetch all active bakeries and categories
$bakeries = $pdo->query("SELECT id, name FROM bakeries WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
$categories = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $name = clean($_POST['name'] ?? '');
    $bakeryId = (int)($_POST['bakery_id'] ?? 0);
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $boxType = clean($_POST['box_type'] ?? 'snack_box');
    $price = (float)($_POST['price'] ?? 40000);
    $originalValue = (float)($_POST['original_value'] ?? $price * 1.5);
    $description = clean($_POST['description'] ?? '');
    $contentsSample = clean($_POST['contents_sample'] ?? '');
    $availableQty = (int)($_POST['available_qty'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name))) . '-' . time();

    // Validation
    if (empty($name) || mb_strlen($name) < 3) {
        $errors[] = 'Food item name must be at least 3 characters.';
    }
    if ($bakeryId <= 0) {
        $errors[] = 'Please select a partner bakery.';
    }
    if ($price <= 0) {
        $errors[] = 'Selling price must be greater than 0 VND.';
    }
    if ($originalValue < $price) {
        $errors[] = 'Original retail benchmark value must be greater than or equal to the selling price.';
    }

    // Handle Image Upload
    $imageUrl = $box['image_url'] ?? 'assets/img/food_boxes/snack-box-1.jpg';
    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
            $errors[] = 'Invalid image file format (Allowed: JPG, PNG, WEBP, SVG).';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Image size cannot exceed 5MB.';
        } else {
            $uploadDir = ROOT_PATH . '/storage/uploads/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $filename = 'box_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destination = $uploadDir . $filename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $imageUrl = 'storage/uploads/' . $filename;
            } else {
                $errors[] = 'Unable to upload image file. Please verify directory write permissions.';
            }
        }
    }

    if (empty($errors)) {
        if ($isEdit) {
            $update = $pdo->prepare("UPDATE food_boxes SET 
                bakery_id = ?, category_id = ?, name = ?, box_type = ?, price = ?, original_value = ?, 
                description = ?, contents_sample = ?, image_url = ?, available_qty = ?, is_active = ? 
                WHERE id = ?");
            $update->execute([
                $bakeryId, $categoryId ?: null, $name, $boxType, $price, $originalValue,
                $description, $contentsSample, $imageUrl, $availableQty, $isActive, $id
            ]);
            logAdminActivity($pdo, $adminId, 'UPDATE_BOX', 'FOOD_BOXES', $id, "Updated food product {$name}.");
            setFlash('success', 'Food item updated successfully.');
        } else {
            $insert = $pdo->prepare("INSERT INTO food_boxes 
                (bakery_id, category_id, name, slug, box_type, price, original_value, description, contents_sample, image_url, available_qty, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([
                $bakeryId, $categoryId ?: null, $name, $slug, $boxType, $price, $originalValue,
                $description, $contentsSample, $imageUrl, $availableQty, $isActive
            ]);
            $newId = (int)$pdo->lastInsertId();
            logAdminActivity($pdo, $adminId, 'CREATE_BOX', 'FOOD_BOXES', $newId, "Created new food product {$name}.");
            setFlash('success', 'New food item added successfully.');
        }

        header('Location: ' . BASE_URL . '/admin/boxes.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'Edit Food Box' : 'Add New Food Box';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <h4 class="font-serif text-green-dark m-0"><?= $pageTitle ?></h4>
                    <small class="text-muted">Configure product specifications and standardized pricing</small>
                </div>
                <a href="<?= BASE_URL ?>/admin/boxes.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Return
                </a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger small mb-4">
                    <ul class="m-0 ps-3">
                        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>/admin/box_form.php<?= $isEdit ? '?id=' . $id : '' ?>" method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Food Box Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? ($box['name'] ?? '')) ?>" placeholder="e.g. Afternoon Sweet Pastry Box" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Partner Bakery <span class="text-danger">*</span></label>
                        <select name="bakery_id" class="form-select" required>
                            <option value="">-- Select Bakery --</option>
                            <?php foreach ($bakeries as $bk): 
                                $sel = (isset($_POST['bakery_id']) && $_POST['bakery_id'] == $bk['id']) || ($box && $box['bakery_id'] == $bk['id']);
                            ?>
                                <option value="<?= $bk['id'] ?>" <?= $sel ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($bk['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Food Category <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat): 
                                $selCat = (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) || ($box && ($box['category_id'] ?? 0) == $cat['id']);
                            ?>
                                <option value="<?= $cat['id'] ?>" <?= $selCat ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Type Identifier</label>
                        <select name="box_type" id="boxTypeSelect" class="form-select" onchange="adjustDefaultPrice(this.value)">
                            <option value="snack_box" <?= ($_POST['box_type'] ?? ($box['box_type'] ?? '')) === 'snack_box' ? 'selected' : '' ?>>Snack Box (Starting 40,000₫)</option>
                            <option value="meal_box" <?= ($_POST['box_type'] ?? ($box['box_type'] ?? '')) === 'meal_box' ? 'selected' : '' ?>>Meal Box (Starting 70,000₫)</option>
                            <option value="pastry" <?= ($_POST['box_type'] ?? ($box['box_type'] ?? '')) === 'pastry' ? 'selected' : '' ?>>Pastry / Croissant</option>
                            <option value="artisan_bread" <?= ($_POST['box_type'] ?? ($box['box_type'] ?? '')) === 'artisan_bread' ? 'selected' : '' ?>>Artisan Bread / Sourdough</option>
                            <option value="sweet_treat" <?= ($_POST['box_type'] ?? ($box['box_type'] ?? '')) === 'sweet_treat' ? 'selected' : '' ?>>Cake / Sweet Treat</option>
                            <option value="beverage" <?= ($_POST['box_type'] ?? ($box['box_type'] ?? '')) === 'beverage' ? 'selected' : '' ?>>Beverage / Coffee</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Surplus Price (VND) <span class="text-danger">*</span></label>
                        <input type="number" name="price" id="boxPriceInput" class="form-control fw-bold text-success" value="<?= htmlspecialchars($_POST['price'] ?? ($box['price'] ?? 40000)) ?>" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Surplus Price (VND) <span class="text-danger">*</span></label>
                        <input type="number" name="price" id="boxPriceInput" class="form-control fw-bold text-success" value="<?= htmlspecialchars($_POST['price'] ?? ($box['price'] ?? PRICE_SNACK_BOX)) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Original Retail Value (VND) <span class="text-danger">*</span></label>
                        <input type="number" name="original_value" class="form-control" value="<?= htmlspecialchars($_POST['original_value'] ?? ($box['original_value'] ?? 95000)) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Available Quantity (Stock) <span class="text-danger">*</span></label>
                        <input type="number" name="available_qty" class="form-control fw-bold" value="<?= htmlspecialchars($_POST['available_qty'] ?? ($box['available_qty'] ?? 10)) ?>" min="0" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Product Description <span class="text-danger">*</span></label>
                    <textarea name="description" rows="3" class="form-control" placeholder="Summary of pastries, flavours, and bakery ingredients..." required><?= htmlspecialchars($_POST['description'] ?? ($box['description'] ?? '')) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Items Included / Daily Surprise Contents</label>
                    <textarea name="contents_sample" rows="2" class="form-control" placeholder="e.g. Typically contains 1 butter croissant, 1 chocolate muffin, 2 butter cookies..."><?= htmlspecialchars($_POST['contents_sample'] ?? ($box['contents_sample'] ?? '')) ?></textarea>
                    <div class="form-text small">Exact contents depend on the bakery's unsold daily fresh bake.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Product Image (Upload new image)</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <?php if ($box && !empty($box['image_url'])): ?>
                        <div class="mt-2 small text-muted d-flex align-items-center gap-2">
                            <span>Current image:</span>
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($box['image_url']) ?>" alt="Current" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="is_active" id="isActiveCheck" <?= (!isset($box) || $box['is_active']) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold small" for="isActiveCheck">Make this box available for reservations now (Active)</label>
                </div>

                <button type="submit" class="btn btn-nrn-primary btn-lg w-100 py-3">
                    <i class="bi bi-save-fill me-2"></i> <?= $isEdit ? 'Save Changes' : 'Create Food Box' ?>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function adjustDefaultPrice(boxType) {
    const priceInput = document.getElementById('boxPriceInput');
    if (boxType === 'snack_box') {
        priceInput.value = 40000;
    } else if (boxType === 'meal_box') {
        priceInput.value = 70000;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
