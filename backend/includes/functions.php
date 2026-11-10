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
 * Get human-readable condition grade label & icon badge
 * @param string $grade
 * @return string HTML
 */
function getConditionGradeBadge(string $grade): string {
    switch ($grade) {
        case 'dented_box':
            return '<span class="badge bg-warning bg-opacity-10 text-dark border border-warning" title="Packaging dented or creased, snack inside 100% sealed & fresh"><i class="bi bi-box-seam me-1 text-warning"></i>Dented Box</span>';
        case 'crumbled_biscuits':
            return '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger" title="Biscuits/wafers cracked during transit, 100% tasty"><i class="bi bi-cookie me-1"></i>Crumbled / Broken</span>';
        case 'melted_reset':
            return '<span class="badge bg-info bg-opacity-10 text-dark border border-info" title="Chocolate melted & solidified in cool air, 100% safe"><i class="bi bi-droplet-half me-1 text-info"></i>Melted &amp; Re-set</span>';
        case 'near_expiry':
        default:
            return '<span class="badge bg-secondary bg-opacity-10 text-dark border border-secondary" title="Approaching printed date (7 to 30 days left)"><i class="bi bi-hourglass-split me-1 text-secondary"></i>Near-Expiry (7-30d)</span>';
    }
}

/**
 * Get simple name of condition grade
 * @param string $grade
 * @return string
 */
function getConditionGradeName(string $grade): string {
    switch ($grade) {
        case 'dented_box': return 'Dented Packaging';
        case 'crumbled_biscuits': return 'Crumbled / Broken Shape';
        case 'melted_reset': return 'Melted & Re-set Chocolate';
        case 'near_expiry': default: return 'Near-Expiry Date';
    }
}

/**
 * Calculate days remaining before printed expiry date
 * Enforces rule: 7 to 30 days allowed, <7 days rejected
 * @param string $expiryDate Y-m-d
 * @return array ['days' => int, 'badge' => string, 'formatted' => string, 'is_valid' => bool]
 */
function getExpiryCountdown(string $expiryDate): array {
    try {
        $today = new DateTime('today');
        $exp = new DateTime($expiryDate);
        $interval = $today->diff($exp);
        $days = (int)$interval->format('%r%a');
        $formatted = $exp->format('d/m/Y');

        if ($days < 0) {
            $badge = '<span class="badge bg-danger text-white"><i class="bi bi-x-circle me-1"></i>Expired (' . $formatted . ')</span>';
            $isValid = false;
        } elseif ($days < 7) {
            $badge = '<span class="badge bg-danger text-white" title="Strict rule: Nothing under 7 days left"><i class="bi bi-exclamation-triangle me-1"></i>&lt; 7 Days Left (' . $days . 'd)</span>';
            $isValid = false;
        } elseif ($days <= 14) {
            $badge = '<span class="badge bg-warning bg-opacity-10 text-dark border border-warning" title="1 to 2 weeks left"><i class="bi bi-clock-history me-1 text-warning"></i>' . $days . ' Days Left (Exp: ' . $formatted . ')</span>';
            $isValid = true;
        } else {
            $badge = '<span class="badge bg-success bg-opacity-10 text-success border border-success" title="Valid listing"><i class="bi bi-calendar-check me-1"></i>' . $days . ' Days Left (Exp: ' . $formatted . ')</span>';
            $isValid = true;
        }

        return [
            'days' => $days,
            'badge' => $badge,
            'formatted' => $formatted,
            'is_valid' => $isValid
        ];
    } catch (Exception $e) {
        return [
            'days' => 0,
            'badge' => '<span class="badge bg-secondary">Exp: ' . htmlspecialchars($expiryDate) . '</span>',
            'formatted' => $expiryDate,
            'is_valid' => true
        ];
    }
}

/**
 * Convenience helper to directly output the badge HTML
 */
function getExpiryCountdownBadge(?string $expiryDate): string {
    if (empty($expiryDate)) {
        return '<span class="badge bg-light text-muted border">Printed on package</span>';
    }
    $info = getExpiryCountdown($expiryDate);
    return $info['badge'];
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
