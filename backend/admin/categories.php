<?php
/**
 * NomReNom - Administrator Food Categories Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

requireAdmin();
$pdo = getDbConnection();
$adminUser = currentUser();

// Handle Category Creation or Update
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $id = (int)($_POST['category_id'] ?? 0);
        $name = clean($_POST['name'] ?? '');
        $description = clean($_POST['description'] ?? '');
        $icon = clean($_POST['icon'] ?? 'bi-cup-hot');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name)) {
            $error = 'Category name is required.';
        } else {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

            if ($action === 'create') {
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, icon, sort_order, is_active, created_at) 
                                       VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $slug, $description, $icon, $sortOrder, $isActive]);
                logAdminActivity($pdo, $adminUser['id'], 'create', 'category', (int)$pdo->lastInsertId(), "Created category '{$name}'");
                setFlash('success', "Category '{$name}' created successfully.");
            } else {
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, icon = ?, sort_order = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $description, $icon, $sortOrder, $isActive, $id]);
                logAdminActivity($pdo, $adminUser['id'], 'update', 'category', $id, "Updated category '{$name}'");
                setFlash('success', "Category '{$name}' updated successfully.");
            }
            header('Location: ' . BASE_URL . '/admin/categories.php');
            exit;
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['category_id'] ?? 0);
        if ($id > 0) {
            // Unlink from products first
            $pdo->prepare("UPDATE food_boxes SET category_id = NULL WHERE category_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
            logAdminActivity($pdo, $adminUser['id'], 'delete', 'category', $id, "Deleted category ID {$id}");
            setFlash('info', 'Category deleted successfully.');
        }
        header('Location: ' . BASE_URL . '/admin/categories.php');
        exit;
    }
}

// Fetch categories with product counts
$stmt = $pdo->query("SELECT c.*, COUNT(f.id) AS product_count 
                     FROM categories c 
                     LEFT JOIN food_boxes f ON c.id = f.category_id 
                     GROUP BY c.id 
                     ORDER BY c.sort_order ASC, c.name ASC");
$categories = $stmt->fetchAll();

$pageTitle = 'Manage Food Categories';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark m-0">Food Categories</h3>
        <p class="text-muted small m-0">Manage catalog categories, icons, and display order</p>
    </div>
    <button type="button" class="btn btn-nrn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="prepareCreateModal()">
        <i class="bi bi-plus-circle me-1"></i> Add New Category
    </button>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm rounded-3"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="bg-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4 py-3" style="width: 60px;">Icon</th>
                    <th class="py-3">Category Name</th>
                    <th class="py-3">Slug</th>
                    <th class="py-3">Description</th>
                    <th class="py-3 text-center">Products</th>
                    <th class="py-3 text-center">Order</th>
                    <th class="py-3 text-center">Status</th>
                    <th class="pe-4 py-3 text-end" style="width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">No categories created yet. Click 'Add New Category' above.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td class="ps-4 py-3">
                                <div class="d-inline-flex p-2 rounded-3 bg-light text-success fs-5">
                                    <i class="bi <?= htmlspecialchars($cat['icon'] ?? 'bi-cup-hot') ?>"></i>
                                </div>
                            </td>
                            <td class="py-3">
                                <strong class="text-dark d-block"><?= htmlspecialchars($cat['name']) ?></strong>
                            </td>
                            <td class="py-3 text-muted small">
                                <code><?= htmlspecialchars($cat['slug']) ?></code>
                            </td>
                            <td class="py-3 text-muted small" style="max-width: 250px;">
                                <?= htmlspecialchars(mb_strimwidth($cat['description'] ?? '', 0, 75, '...')) ?>
                            </td>
                            <td class="py-3 text-center">
                                <span class="badge bg-light text-dark border"><?= (int)$cat['product_count'] ?> items</span>
                            </td>
                            <td class="py-3 text-center text-muted">
                                <?= (int)$cat['sort_order'] ?>
                            </td>
                            <td class="py-3 text-center">
                                <span class="badge <?= $cat['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= $cat['is_active'] ? 'Active' : 'Disabled' ?>
                                </span>
                            </td>
                            <td class="pe-4 py-3 text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary me-1" 
                                        onclick='prepareEditModal(<?= json_encode($cat) ?>)'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="<?= BASE_URL ?>/admin/categories.php" class="d-inline" onsubmit="return confirm('Delete this category? Linked products will become uncategorized.');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Category Create / Edit Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form method="POST" action="<?= BASE_URL ?>/admin/categories.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" id="modalAction" value="create">
                <input type="hidden" name="category_id" id="modalCategoryId" value="0">

                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalTitle">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="modalCatName" class="form-control" placeholder="e.g. Croissants & Pastries" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Icon Class (Bootstrap Icons)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-star" id="iconPreview"></i></span>
                            <input type="text" name="icon" id="modalCatIcon" class="form-control" placeholder="bi-box-seam" value="bi-box-seam">
                        </div>
                        <small class="text-muted">Examples: bi-box-seam, bi-bag-check, bi-cup-hot, bi-cake2, bi-egg-fried</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="description" id="modalCatDesc" class="form-control" rows="2" placeholder="Brief summary of foods in this category..."></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Sort Order</label>
                            <input type="number" name="sort_order" id="modalCatOrder" class="form-control" value="0">
                        </div>
                        <div class="col-6 d-flex align-items-end pb-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="modalCatActive" checked>
                                <label class="form-check-label small fw-bold" for="modalCatActive">Active & Visible</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-nrn-primary px-4">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function prepareCreateModal() {
    document.getElementById('modalAction').value = 'create';
    document.getElementById('modalCategoryId').value = '0';
    document.getElementById('modalTitle').textContent = 'Add New Category';
    document.getElementById('modalCatName').value = '';
    document.getElementById('modalCatIcon').value = 'bi-box-seam';
    document.getElementById('modalCatDesc').value = '';
    document.getElementById('modalCatOrder').value = '0';
    document.getElementById('modalCatActive').checked = true;
    updateIconPreview('bi-box-seam');
}

function prepareEditModal(cat) {
    document.getElementById('modalAction').value = 'update';
    document.getElementById('modalCategoryId').value = cat.id;
    document.getElementById('modalTitle').textContent = 'Edit Category: ' + cat.name;
    document.getElementById('modalCatName').value = cat.name;
    document.getElementById('modalCatIcon').value = cat.icon || 'bi-box-seam';
    document.getElementById('modalCatDesc').value = cat.description || '';
    document.getElementById('modalCatOrder').value = cat.sort_order || 0;
    document.getElementById('modalCatActive').checked = (parseInt(cat.is_active) === 1);
    updateIconPreview(cat.icon || 'bi-box-seam');
    const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
    modal.show();
}

function updateIconPreview(cls) {
    const preview = document.getElementById('iconPreview');
    if (preview) {
        preview.className = 'bi ' + cls;
    }
}

document.getElementById('modalCatIcon').addEventListener('input', function() {
    updateIconPreview(this.value.trim());
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
