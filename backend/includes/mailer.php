<?php
/**
 * NomReNom - Email Service & Verification Dispatcher
 * Supporting Real SMTP & Local Audit Logging
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/functions.php';

/**
 * Direct pure PHP SMTP socket transport with TLS encryption
 * Dispatches real emails to Gmail, Outlook, Yahoo, or custom SMTP servers
 */
function sendSmtpSocket(string $to, string $toName, string $subject, string $htmlBody): bool {
    if (!defined('SMTP_HOST') || empty(SMTP_HOST) || SMTP_USER === 'your-email@gmail.com' || empty(SMTP_PASS)) {
        return false;
    }

    $timeout = 12;
    $host = SMTP_HOST;
    $port = (int)SMTP_PORT;
    $secure = defined('SMTP_SECURE') ? strtolower(SMTP_SECURE) : 'tls';

    $socketHost = ($secure === 'ssl') ? "ssl://{$host}" : $host;
    $socket = @fsockopen($socketHost, $port, $errno, $errstr, $timeout);

    if (!$socket) {
        error_log("SMTP connection failed to {$host}:{$port} - {$errstr} ({$errno})");
        return false;
    }

    stream_set_timeout($socket, $timeout);

    $readResponse = function() use ($socket) {
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $response;
    };

    $sendCommand = function(string $cmd) use ($socket) {
        fputs($socket, $cmd . "\r\n");
    };

    $greeting = $readResponse();
    if (substr($greeting, 0, 3) !== '220') {
        fclose($socket);
        return false;
    }

    $sendCommand("EHLO " . (gethostname() ?: 'localhost'));
    $readResponse();

    if ($secure === 'tls') {
        $sendCommand("STARTTLS");
        $tlsResp = $readResponse();
        if (substr($tlsResp, 0, 3) !== '220') {
            fclose($socket);
            return false;
        }
        $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
            $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
        }
        if (!@stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
            fclose($socket);
            return false;
        }
        $sendCommand("EHLO " . (gethostname() ?: 'localhost'));
        $readResponse();
    }

    // Authenticate
    if (!empty(SMTP_USER) && !empty(SMTP_PASS)) {
        $sendCommand("AUTH LOGIN");
        $authResp = $readResponse();
        if (substr($authResp, 0, 3) !== '334') {
            fclose($socket);
            return false;
        }
        $sendCommand(base64_encode(SMTP_USER));
        $uResp = $readResponse();
        if (substr($uResp, 0, 3) !== '334') {
            fclose($socket);
            return false;
        }
        $sendCommand(base64_encode(SMTP_PASS));
        $pResp = $readResponse();
        if (substr($pResp, 0, 3) !== '235') {
            error_log("SMTP Authentication failed: " . $pResp);
            fclose($socket);
            return false;
        }
    }

    $fromEmail = (defined('SMTP_FROM_EMAIL') && SMTP_FROM_EMAIL !== 'no-reply@nomrenom.vn') ? SMTP_FROM_EMAIL : SMTP_USER;
    $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'NomReNom';

    $sendCommand("MAIL FROM:<" . $fromEmail . ">");
    $mailResp = $readResponse();
    if (substr($mailResp, 0, 3) !== '250') {
        fclose($socket);
        return false;
    }

    $sendCommand("RCPT TO:<" . $to . ">");
    $rcptResp = $readResponse();
    if (substr($rcptResp, 0, 3) !== '250' && substr($rcptResp, 0, 3) !== '251') {
        fclose($socket);
        return false;
    }

    $sendCommand("DATA");
    $dataResp = $readResponse();
    if (substr($dataResp, 0, 3) !== '354') {
        fclose($socket);
        return false;
    }

    $headers = [];
    $headers[] = "Date: " . date('r');
    $headers[] = "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$to}>";
    $headers[] = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>";
    $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: text/html; charset=UTF-8";
    $headers[] = "Content-Transfer-Encoding: base64";
    $headers[] = "X-Mailer: NomReNom Food Ordering v" . (defined('APP_VERSION') ? APP_VERSION : '2.0');

    $emailData = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($htmlBody)) . "\r\n.\r\n";
    $sendCommand($emailData);

    $finalResp = $readResponse();
    $sendCommand("QUIT");
    fclose($socket);

    return (substr($finalResp, 0, 3) === '250');
}

/**
 * Dispatch an email via genuine SMTP or fallback to persistent log
 * @param string $to
 * @param string $toName
 * @param string $subject
 * @param string $htmlBody
 * @param string|null $actionUrl Optional action button link
 * @return bool
 */
function sendEmailNotification(string $to, string $toName, string $subject, string $htmlBody, ?string $actionUrl = null): bool {
    $timestamp = date('Y-m-d H:i:s');
    $logDir = dirname(MAIL_LOG_FILE);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }

    $delivered = false;
    $deliveryMethod = 'Logged to File';

    // 1. Check if real SMTP credentials are provided (not default placeholder)
    $hasSmtpConfigured = (defined('SMTP_USER') && SMTP_USER !== 'your-email@gmail.com' && !empty(SMTP_PASS) && SMTP_PASS !== 'your-app-password');

    if ($hasSmtpConfigured) {
        // Attempt socket SMTP transmission
        try {
            if (sendSmtpSocket($to, $toName, $subject, $htmlBody)) {
                $delivered = true;
                $deliveryMethod = 'Real SMTP TLS Server (' . SMTP_HOST . ')';
            }
        } catch (Exception $e) {
            error_log("Socket SMTP failed: " . $e->getMessage());
        }

        // Fallback to PHP mail() if socket failed
        if (!$delivered) {
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
            $headers .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
            $headers .= "X-Mailer: NomReNom Mailer v" . (defined('APP_VERSION') ? APP_VERSION : '2.0') . "\r\n";

            if (@mail($to, $subject, $htmlBody, $headers)) {
                $delivered = true;
                $deliveryMethod = 'PHP mail() function';
            }
        }
    }

    // Always append to local audit log for instant verification and trace
    $logEntry  = "=================================================================\n";
    $logEntry .= "[{$timestamp}] EMAIL DISPATCH REPORT\n";
    $logEntry .= "Status: " . ($delivered ? "SENT via {$deliveryMethod}" : "DISPATCHED / AUDIT RECORD ({$deliveryMethod})") . "\n";
    $logEntry .= "To: {$toName} <{$to}>\n";
    $logEntry .= "Subject: {$subject}\n";
    if ($actionUrl) {
        $logEntry .= "ACTION LINK (Token/URL): {$actionUrl}\n";
    }
    $logEntry .= "-----------------------------------------------------------------\n";
    $logEntry .= strip_tags(str_replace(['<br>', '<br/>', '<p>', '</p>'], ["\n", "\n", "\n", "\n"], $htmlBody)) . "\n";
    $logEntry .= "=================================================================\n\n";

    @file_put_contents(MAIL_LOG_FILE, $logEntry, FILE_APPEND);

    // Save latest link to session so users can inspect directly
    if ($actionUrl) {
        $_SESSION['last_email_action'] = [
            'to' => $to,
            'subject' => $subject,
            'url' => $actionUrl,
            'time' => $timestamp
        ];
    }

    return true;
}

/**
 * Send customer account activation email
 * @param string $email
 * @param string $fullName
 * @param string $token
 * @return bool
 */
function sendAccountVerificationEmail(string $email, string $fullName, string $token): bool {
    $verifyUrl = BASE_URL . '/verify_email.php?token=' . urlencode($token);
    $subject = 'NomReNom – Verify Your Account Email Address';

    $html = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #FAF7F2; padding: 24px; border-radius: 8px; border: 1px solid #E6DFD5;">
        <div style="text-align: center; margin-bottom: 20px;">
            <h1 style="color: #1B4332; margin: 0; font-size: 26px;">NomReNom</h1>
            <p style="color: #6C584C; font-size: 13px; margin: 4px 0 0 0;">Good Food, Better Prices, Less Waste.</p>
        </div>
        <div style="background: #FFFFFF; padding: 24px; border-radius: 6px; border: 1px solid #ECE7E1;">
            <h2 style="color: #1B4332; font-size: 18px; margin-top: 0;">Hello ' . htmlspecialchars($fullName) . ',</h2>
            <p style="color: #333333; line-height: 1.6;">Thank you for joining the NomReNom community! You just created an account to help rescue premium surplus baked goods from local artisan bakeries.</p>
            <p style="color: #333333; line-height: 1.6;">Please click the button below to verify your email address and activate your account:</p>
            <div style="text-align: center; margin: 28px 0;">
                <a href="' . $verifyUrl . '" style="background: #1B4332; color: #FFFFFF; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: bold; display: inline-block;">Verify Account Now</a>
            </div>
            <p style="color: #666666; font-size: 13px;">If the button does not work, copy and paste this verification URL into your browser:<br>
            <a href="' . $verifyUrl . '" style="color: #2D6A4F; word-break: break-all;">' . $verifyUrl . '</a></p>
            <p style="color: #888888; font-size: 12px; margin-top: 24px; border-top: 1px solid #EEEEEE; padding-top: 12px;">This activation link is valid for 24 hours. If you did not sign up for NomReNom, please disregard this email.</p>
        </div>
    </div>';

    return sendEmailNotification($email, $fullName, $subject, $html, $verifyUrl);
}

/**
 * Send reservation confirmation email
 * @param array $reservation
 * @param array $items
 * @param array $bakery
 * @param array $slot
 * @param string $recipientEmail
 * @param string $recipientName
 * @return bool
 */
function sendReservationConfirmationEmail(array $reservation, array $items, array $bakery, array $slot, string $recipientEmail, string $recipientName): bool {
    $code = $reservation['reservation_code'];
    $lookupUrl = BASE_URL . '/reservation_lookup.php?code=' . urlencode($code);
    $subject = "NomReNom – Reservation Confirmation [#{$code}]";

    $itemsHtml = '';
    foreach ($items as $item) {
        $itemsHtml .= '<tr>
            <td style="padding: 10px; border-bottom: 1px solid #EEE;">' . htmlspecialchars($item['box_name']) . '</td>
            <td style="padding: 10px; border-bottom: 1px solid #EEE; text-align: center;">x' . (int)$item['quantity'] . '</td>
            <td style="padding: 10px; border-bottom: 1px solid #EEE; text-align: right; font-weight: bold;">' . formatVnd($item['subtotal']) . '</td>
        </tr>';
    }

    $pickupDate = formatDateTime($slot['pickup_date'], 'M d, Y');
    $pickupWindow = formatTime($slot['start_time']) . ' - ' . formatTime($slot['end_time']);

    $html = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #FAF7F2; padding: 24px; border-radius: 8px;">
        <div style="text-align: center; margin-bottom: 20px;">
            <h1 style="color: #1B4332; margin: 0;">NomReNom</h1>
            <p style="color: #6C584C; font-size: 13px;">Good Food, Better Prices, Less Waste.</p>
        </div>
        <div style="background: #FFFFFF; padding: 24px; border-radius: 6px; border: 1px solid #E6DFD5;">
            <div style="background: #E8F5E9; border-left: 4px solid #2D6A4F; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px;">
                <h3 style="color: #1B4332; margin: 0 0 4px 0; font-size: 16px;">Reservation Confirmed & Recorded!</h3>
                <p style="margin: 0; color: #2E7D32; font-size: 14px;">Reservation Reference: <strong>' . htmlspecialchars($code) . '</strong></p>
            </div>

            <p>Hello <strong>' . htmlspecialchars($recipientName) . '</strong>, thank you for joining NomReNom in reducing food waste!</p>
            
            <h4 style="color: #1B4332; border-bottom: 2px solid #E6DFD5; padding-bottom: 6px; margin-top: 20px;">Pickup Details</h4>
            <table style="width: 100%; font-size: 14px; margin-bottom: 20px;">
                <tr><td style="padding: 4px 0; color: #666;">Bakery Partner:</td><td style="padding: 4px 0; font-weight: bold;">' . htmlspecialchars($bakery['name']) . '</td></tr>
                <tr><td style="padding: 4px 0; color: #666;">Pickup Address:</td><td style="padding: 4px 0;">' . htmlspecialchars($bakery['address']) . ', ' . htmlspecialchars($bakery['district']) . '</td></tr>
                <tr><td style="padding: 4px 0; color: #666;">Pickup Date:</td><td style="padding: 4px 0; font-weight: bold; color: #1B4332;">' . $pickupDate . '</td></tr>
                <tr><td style="padding: 4px 0; color: #666;">Time Window:</td><td style="padding: 4px 0; font-weight: bold; color: #1B4332;">' . $pickupWindow . '</td></tr>
                <tr><td style="padding: 4px 0; color: #666;">Payment Method:</td><td style="padding: 4px 0;">' . ($reservation['payment_method'] === 'Pickup' ? 'Pay at Pickup' : 'Online Payment (' . htmlspecialchars($reservation['payment_status']) . ')') . '</td></tr>
            </table>

            <h4 style="color: #1B4332; border-bottom: 2px solid #E6DFD5; padding-bottom: 6px;">Order Summary</h4>
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr style="background: #F4EFE6;">
                        <th style="padding: 8px 10px; text-align: left;">Food Box</th>
                        <th style="padding: 8px 10px; text-align: center;">Qty</th>
                        <th style="padding: 8px 10px; text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>' . $itemsHtml . '</tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" style="padding: 12px 10px; text-align: right; font-weight: bold;">Total Due:</td>
                        <td style="padding: 12px 10px; text-align: right; font-weight: bold; font-size: 16px; color: #1B4332;">' . formatVnd($reservation['total_amount']) . '</td>
                    </tr>
                </tfoot>
            </table>

            <div style="text-align: center; margin: 24px 0;">
                <a href="' . $lookupUrl . '" style="background: #2D6A4F; color: #FFF; text-decoration: none; padding: 10px 24px; border-radius: 6px; font-weight: bold; display: inline-block;">Track Reservation Status</a>
            </div>
            
            <p style="color: #666; font-size: 12px; line-height: 1.5;">* Important: When you arrive at the bakery, please present your <strong>Reservation Code (' . htmlspecialchars($code) . ')</strong> or phone number to the bakery staff.</p>
        </div>
    </div>';

    return sendEmailNotification($recipientEmail, $recipientName, $subject, $html, $lookupUrl);
}

/**
 * Send password reset email
 * @param string $email
 * @param string $fullName
 * @param string $token
 * @return bool
 */
function sendPasswordResetEmail(string $email, string $fullName, string $token): bool {
    $resetUrl = BASE_URL . '/reset_password.php?token=' . urlencode($token);
    $subject = 'NomReNom – Password Reset Instructions';

    $html = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #FAF7F2; padding: 24px; border-radius: 8px;">
        <div style="background: #FFFFFF; padding: 24px; border-radius: 6px; border: 1px solid #E6DFD5;">
            <h2 style="color: #1B4332; margin-top: 0;">Hello ' . htmlspecialchars($fullName) . ',</h2>
            <p>We received a request to reset your password for your NomReNom account.</p>
            <p>Please click the button below to set a new password (link expires in 60 minutes):</p>
            <div style="text-align: center; margin: 24px 0;">
                <a href="' . $resetUrl . '" style="background: #D4A373; color: #1B4332; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: bold; display: inline-block;">Reset My Password</a>
            </div>
            <p style="color: #888; font-size: 12px;">If you did not request a password reset, you can safely ignore this email.</p>
        </div>
    </div>';

    return sendEmailNotification($email, $fullName, $subject, $html, $resetUrl);
}

/**
 * Send welcome email to newly registered customer using their real email
 * @param string $email
 * @param string $fullName
 * @return bool
 */
function sendWelcomeRegistrationEmail(string $email, string $fullName): bool {
    $menuUrl = BASE_URL . '/boxes.php';
    $subject = 'Welcome to NomReNom – Your Account is Active!';

    $html = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #FAF7F2; padding: 24px; border-radius: 12px; border: 1px solid #E6DFD5;">
        <div style="text-align: center; margin-bottom: 24px;">
            <h1 style="color: #0F4C3A; margin: 0; font-size: 26px;">NomReNom</h1>
            <p style="color: #6C584C; font-size: 13px; margin: 4px 0 0 0;">Fresh Bakery Surplus Food Platform</p>
        </div>
        <div style="background: #FFFFFF; padding: 28px; border-radius: 8px; border: 1px solid #ECE7E1;">
            <h2 style="color: #0F4C3A; font-size: 20px; margin-top: 0;">Welcome to NomReNom, ' . htmlspecialchars($fullName) . '!</h2>
            <p style="color: #374151; line-height: 1.6; font-size: 14px;">Your customer account has been created successfully with your email address: <strong>' . htmlspecialchars($email) . '</strong>.</p>
            <p style="color: #374151; line-height: 1.6; font-size: 14px;">You can now order delicious surplus baked goods, artisan sourdough, and fresh meal boxes from local bakeries at up to 60% off while preventing food waste!</p>

            <div style="background: #F0FDF4; border-left: 4px solid #10B981; padding: 14px; margin: 20px 0; border-radius: 4px;">
                <strong style="color: #065F46; font-size: 14px; display: block; margin-bottom: 4px;">What You Can Do:</strong>
                <ul style="margin: 0; padding-left: 20px; color: #047857; font-size: 13px; line-height: 1.6;">
                    <li>Browse daily surplus boxes updated every morning.</li>
                    <li>Add items to your cart and customize quantities.</li>
                    <li>Choose store pickup or local campus/home delivery.</li>
                    <li>Track live order progress in real-time.</li>
                </ul>
            </div>

            <div style="text-align: center; margin: 28px 0 16px 0;">
                <a href="' . $menuUrl . '" style="background: #0F4C3A; color: #FFFFFF; text-decoration: none; padding: 13px 28px; border-radius: 6px; font-weight: bold; font-size: 14px; display: inline-block;">Browse Today\'s Food Menu</a>
            </div>

            <p style="color: #9CA3AF; font-size: 12px; margin-top: 24px; border-top: 1px solid #F3F4F6; padding-top: 12px; text-align: center;">
                Need help with an order? Contact our support team at support@nomrenom.vn or call 0901 234 567.
            </p>
        </div>
    </div>';

    return sendEmailNotification($email, $fullName, $subject, $html, $menuUrl);
}

/**
 * Send security notification email whenever a customer logs into their account
 * @param string $email
 * @param string $fullName
 * @param string $ipAddress
 * @param string $userAgent
 * @return bool
 */
function sendLoginNotificationEmail(string $email, string $fullName, string $ipAddress = '', string $userAgent = ''): bool {
    if (empty($ipAddress)) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    if (empty($userAgent)) {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Web Browser';
    }

    // Friendly device / browser parsing
    $browser = 'Web Browser';
    if (stripos($userAgent, 'Chrome') !== false && stripos($userAgent, 'Edg') === false) $browser = 'Google Chrome';
    elseif (stripos($userAgent, 'Firefox') !== false) $browser = 'Mozilla Firefox';
    elseif (stripos($userAgent, 'Safari') !== false && stripos($userAgent, 'Chrome') === false) $browser = 'Apple Safari';
    elseif (stripos($userAgent, 'Edg') !== false) $browser = 'Microsoft Edge';

    $os = 'Desktop / Mobile';
    if (stripos($userAgent, 'Windows') !== false) $os = 'Windows PC';
    elseif (stripos($userAgent, 'Macintosh') !== false) $os = 'Mac OS';
    elseif (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) $os = 'iOS Device';
    elseif (stripos($userAgent, 'Android') !== false) $os = 'Android Device';

    $deviceInfo = "{$browser} on {$os}";
    $loginTime = date('F j, Y \a\t g:i a');
    $resetUrl = BASE_URL . '/forgot_password.php';
    $subject = 'Security Alert: New Sign-In to your NomReNom Account';

    $html = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #FAF7F2; padding: 24px; border-radius: 12px; border: 1px solid #E6DFD5;">
        <div style="text-align: center; margin-bottom: 24px;">
            <h1 style="color: #0F4C3A; margin: 0; font-size: 26px;">NomReNom</h1>
            <p style="color: #6C584C; font-size: 13px; margin: 4px 0 0 0;">Fresh Bakery Surplus Food Platform</p>
        </div>
        <div style="background: #FFFFFF; padding: 28px; border-radius: 8px; border: 1px solid #ECE7E1;">
            <div style="background: #F0FDF4; border-left: 4px solid #10B981; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px;">
                <strong style="color: #065F46; font-size: 15px; display: block;">Successful Account Sign-In Detected</strong>
                <span style="color: #047857; font-size: 13px;">A new login was recorded for your customer account.</span>
            </div>

            <p style="color: #1F2937; line-height: 1.6; font-size: 15px;">Hello <strong>' . htmlspecialchars($fullName) . '</strong>,</p>
            <p style="color: #4B5563; line-height: 1.6; font-size: 14px;">We noticed a successful login to your NomReNom account (<strong>' . htmlspecialchars($email) . '</strong>):</p>

            <div style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 6px; padding: 16px; margin: 20px 0;">
                <table style="width: 100%; font-size: 13px; color: #374151; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 6px 0; color: #6B7280; width: 120px;"><strong>Date & Time:</strong></td>
                        <td style="padding: 6px 0; font-weight: 600; color: #111827;">' . $loginTime . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #6B7280;"><strong>Device & App:</strong></td>
                        <td style="padding: 6px 0; font-weight: 600; color: #111827;">' . htmlspecialchars($deviceInfo) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #6B7280;"><strong>IP Address:</strong></td>
                        <td style="padding: 6px 0; font-weight: 600; color: #111827;">' . htmlspecialchars($ipAddress) . '</td>
                    </tr>
                </table>
            </div>

            <p style="color: #4B5563; font-size: 13px; line-height: 1.6;">
                <strong>Was this you?</strong> If you just logged in to check your orders or browse the menu, no action is needed.
            </p>
            <p style="color: #DC2626; font-size: 13px; line-height: 1.6;">
                <strong>Don\'t recognise this login?</strong> If you did not sign in, someone else may have your password. We recommend resetting your password immediately:
            </p>

            <div style="text-align: center; margin: 24px 0 16px 0;">
                <a href="' . $resetUrl . '" style="background: #DC2626; color: #FFFFFF; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold; font-size: 14px; display: inline-block;">Reset Your Password</a>
            </div>

            <p style="color: #9CA3AF; font-size: 12px; margin-top: 24px; border-top: 1px solid #F3F4F6; padding-top: 12px; text-align: center;">
                This automated security notification was sent to ' . htmlspecialchars($email) . ' because you are a registered member of NomReNom.
            </p>
        </div>
    </div>';

    return sendEmailNotification($email, $fullName, $subject, $html, $resetUrl);
}
