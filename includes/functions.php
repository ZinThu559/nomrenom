<?php
/**
 * NomReNom - General Helper Functions & Utilities
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/cart.php';

/**
 * Format currency amount into Vietnamese Dong (VND)
 * @param float|int $amount
 * @return string
 */
function formatVnd($amount): string {
    return number_format((float)$amount, 0, ',', '.') . ' ' . CURRENCY_SYMBOL;
}

/**
 * Clean & sanitize user input string
 * @param mixed $data
 * @return string
 */
function clean($data): string {
    if (is_array($data)) {
        return array_map('clean', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Set a session flash message
 * @param string $type success|danger|warning|info
 * @param string $message
 */
function setFlash(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Retrieve and clear all session flash messages
 * @return array
 */
function getFlash(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Render Bootstrap alert HTML for active flash messages
 * @return string
 */
function renderFlash(): string {
    $messages = getFlash();
    if (empty($messages)) {
        return '';
    }

    $html = '<div class="flash-container mb-4">';
    foreach ($messages as $msg) {
        $type = htmlspecialchars($msg['type']);
        $text = $msg['message']; // Can contain structured HTML if trusted
        $icon = match($type) {
            'success' => 'bi-check-circle-fill',
            'danger' => 'bi-exclamation-triangle-fill',
            'warning' => 'bi-exclamation-circle-fill',
            default => 'bi-info-circle-fill'
        };
        $html .= "<div class='alert alert-{$type} alert-dismissible fade show shadow-sm d-flex align-items-center' role='alert'>
                    <i class='bi {$icon} fs-5 me-2'></i>
                    <div class='flex-grow-1'>{$text}</div>
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                  </div>";
    }
    $html .= '</div>';
    return $html;
}

/**
 * Generate a unique, professional reservation tracking reference
 * Format: NRN-YYYYMMDD-XXXX (e.g., NRN-20261105-8F2B)
 * @return string
 */
function generateReservationCode(): string {
    $datePart = date('Ymd');
    $randPart = strtoupper(substr(bin2hex(random_bytes(4)), 0, 4));
    return 'NRN-' . $datePart . '-' . $randPart;
}

/**
 * Generate cryptographic random token
 * @param int $bytes
 * @return string
 */
function generateSecureToken(int $bytes = 32): string {
    return bin2hex(random_bytes($bytes));
}

/**
 * Format datetime string into Vietnamese standard display (d/m/Y H:i)
 * @param string|null $datetime
 * @param string $format
 * @return string
 */
function formatDateTime(?string $datetime, string $format = 'd/m/Y H:i'): string {
    if (empty($datetime)) return 'N/A';
    try {
        $dt = new DateTime($datetime);
        return $dt->format($format);
    } catch (Exception $e) {
        return $datetime;
    }
}

/**
 * Format time string (H:i:s to H:i)
 * @param string|null $time
 * @return string
 */
function formatTime(?string $time): string {
    if (empty($time)) return 'N/A';
    return substr($time, 0, 5);
}

/**
 * Calculate savings percentage between original bakery price and surplus price
 * @param float $original
 * @param float $current
 * @return int
 */
function calculateSavingsPercent(float $original, float $current): int {
    if ($original <= 0 || $current >= $original) return 0;
    return (int)round((($original - $current) / $original) * 100);
}

/**
 * Record an administrative activity in the audit log
 * @param PDO $pdo
 * @param int $adminId
 * @param string $action
 * @param string $entityType
 * @param int|null $entityId
 * @param string|null $details
 */
function logAdminActivity(PDO $pdo, int $adminId, string $action, string $entityType, ?int $entityId = null, ?string $details = null): void {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("INSERT INTO admin_activity_logs (admin_user_id, action, entity_type, entity_id, details, ip_address) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$adminId, $action, $entityType, $entityId, $details, $ip]);
    } catch (Exception $e) {
        error_log("Failed to write admin activity log: " . $e->getMessage());
    }
}
