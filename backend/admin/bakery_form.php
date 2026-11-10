<?php
/**
 * NomReNom - Add / Edit Partner Bakery
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
$bakery = null;

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM bakeries WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $bakery = $stmt->fetch();
    if (!$bakery) {
        setFlash('danger', 'Bakery not found for editing.');
        header('Location: ' . BASE_URL . '/admin/bakeries.php');
        exit;
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $name = clean($_POST['name'] ?? '');
    $description = clean($_POST['description'] ?? '');
    $address = clean($_POST['address'] ?? '');
    $district = clean($_POST['district'] ?? '');
    $city = clean($_POST['city'] ?? 'Ho Chi Minh City');
    $phone = clean($_POST['phone'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $openingTime = clean($_POST['opening_time'] ?? '07:30:00');
    $closingTime = clean($_POST['closing_time'] ?? '21:30:00');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name))) . '-' . time();

    if (empty($name) || mb_strlen($name) < 3) {
        $errors[] = 'Bakery name must be at least 3 characters.';
    }
    if (empty($address)) {
        $errors[] = 'Please specify the street address.';
    }
    if (empty($district)) {
        $errors[] = 'Please specify the district/campus area.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Handle Logo Upload
    $logoUrl = $bakery['logo_url'] ?? 'assets/img/bakeries/bakery-logo-1.svg';
    if (!empty($_FILES['logo']['name'])) {
        $file = $_FILES['logo'];
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
            $errors[] = 'Invalid logo image format (Allowed: JPG, PNG, WEBP, SVG).';
        } else {
            $uploadDir = ROOT_PATH . '/storage/uploads/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $filename = 'bakery_logo_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                $logoUrl = 'storage/uploads/' . $filename;
            }
        }
    }

    if (empty($errors)) {
        if ($isEdit) {
            $update = $pdo->prepare("UPDATE bakeries SET 
                name = ?, description = ?, address = ?, district = ?, city = ?, 
                phone = ?, email = ?, opening_time = ?, closing_time = ?, logo_url = ?, is_active = ? 
                WHERE id = ?");
            $update->execute([
                $name, $description, $address, $district, $city,
                $phone, $email, $openingTime, $closingTime, $logoUrl, $isActive, $id
            ]);
            logAdminActivity($pdo, $adminId, 'UPDATE_BAKERY', 'BAKERIES', $id, "Updated bakery partner {$name}.");
            setFlash('success', 'Bakery partner information updated.');
        } else {
            $insert = $pdo->prepare("INSERT INTO bakeries 
                (name, slug, description, address, district, city, phone, email, opening_time, closing_time, logo_url, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([
                $name, $slug, $description, $address, $district, $city,
                $phone, $email, $openingTime, $closingTime, $logoUrl, $isActive
            ]);
            $newId = (int)$pdo->lastInsertId();
            logAdminActivity($pdo, $adminId, 'CREATE_BAKERY', 'BAKERIES', $newId, "Added new partner bakery {$name}.");
            setFlash('success', 'New bakery partner added successfully.');
        }
        header('Location: ' . BASE_URL . '/admin/bakeries.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'Edit Bakery Partner' : 'Add New Bakery Partner';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <h4 class="font-serif text-green-dark m-0"><?= $pageTitle ?></h4>
                    <small class="text-muted">Bakery partner contact details, pickup location, and operating hours</small>
                </div>
                <a href="<?= BASE_URL ?>/admin/bakeries.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Return
                </a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger small mb-4">
                    <ul class="m-0 ps-3"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>/admin/bakery_form.php<?= $isEdit ? '?id=' . $id : '' ?>" method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Bakery Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? ($bakery['name'] ?? '')) ?>" placeholder="e.g. Sweet Dreams Artisan Bakery" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">About & Specialties</label>
                    <textarea name="description" rows="3" class="form-control" placeholder="Specializes in sourdough breads, French viennoiserie, and artisan croissants..."><?= htmlspecialchars($_POST['description'] ?? ($bakery['description'] ?? '')) ?></textarea>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">Street Address <span class="text-danger">*</span></label>
                        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($_POST['address'] ?? ($bakery['address'] ?? '')) ?>" placeholder="Street name and number..." required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">District / Area <span class="text-danger">*</span></label>
                        <input type="text" name="district" class="form-control" value="<?= htmlspecialchars($_POST['district'] ?? ($bakery['district'] ?? 'District 1')) ?>" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Contact Phone <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? ($bakery['phone'] ?? '')) ?>" placeholder="028 3812 3456" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Business Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? ($bakery['email'] ?? '')) ?>" placeholder="contact@bakery.vn" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Opening Time</label>
                        <input type="time" name="opening_time" class="form-control" value="<?= htmlspecialchars($_POST['opening_time'] ?? ($bakery['opening_time'] ?? '07:30:00')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Closing Time</label>
                        <input type="time" name="closing_time" class="form-control" value="<?= htmlspecialchars($_POST['closing_time'] ?? ($bakery['closing_time'] ?? '21:30:00')) ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Bakery Logo Image</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                </div>

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="is_active" id="bkActiveCheck" <?= (!isset($bakery) || $bakery['is_active']) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold small" for="bkActiveCheck">Active partnership (Enabled on customer website)</label>
                </div>

                <button type="submit" class="btn btn-nrn-primary btn-lg w-100 py-3">
                    <i class="bi bi-save-fill me-2"></i> <?= $isEdit ? 'Save Changes' : 'Create Bakery Partner' ?>
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
