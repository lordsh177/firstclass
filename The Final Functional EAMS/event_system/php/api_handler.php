<?php
/**
 * Refactored api_handler.php (WITH QR CODE LOGIC RESTORED)
 * Unified MySQLi usage, consolidated actions, PHPMailer optional.
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_config.php';
date_default_timezone_set('Asia/Manila');

// Optional PHPMailer autoload if installed
$hasPHPMailer = false;
if (file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')) {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    $hasPHPMailer = true;
}

function jsonResponse($success, $message = '', $data = null) {
    $resp = ['success' => (bool)$success, 'message' => $message];
    if ($data !== null) $resp['data'] = $data;
    echo json_encode($resp);
    exit;
}

function post($key, $default = null) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

// Ensure $conn exists and is mysqli
if (!isset($conn) || !($conn instanceof mysqli)) {
    jsonResponse(false, 'Database connection not found or misconfigured.');
}

$action = strtolower(post('action', ''));

// Helper: send email
function sendEmail($to, $subject, $body) {
    global $hasPHPMailer;
    if ($hasPHPMailer) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'lomopog.clark777@gmail.com';
            $mail->Password   = 'rpyyrvluzmmvducu'; 
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('lomopog.clark777@gmail.com', 'Event Management System');
            $mail->addAddress($to);

            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer error: " . $e->getMessage());
        }
    }

    return @mail($to, $subject, $body);
}



/* ============================================================
   PRIMARY ACTION SWITCH
   ============================================================ */
switch ($action) {


/* ============================================================
   LOGIN
   ============================================================ */
case 'login':
    $email = post('email', '');
    $password = post('password', '');

    if ($email === '' || $password === '') {
        jsonResponse(false, 'Email and password are required.');
    }

    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $user = $res->fetch_assoc();
        if (md5($password) === $user['password']) {
            jsonResponse(true, 'Login successful.', [
                'id'   => $user['id'],
                'name' => $user['name'],
                'role' => $user['role']
            ]);
        } else {
            jsonResponse(false, 'Incorrect password.');
        }
    } else {
        jsonResponse(false, 'No account found with that email.');
    }
    break;



/* ============================================================
   USERS CRUD
   ============================================================ */
case 'create_user':
    $name = post('name', '');
    $email = post('email', '');
    $contact = post('contact', '');
    $gender = post('gender', 'Male');
    $address = post('address', '');
    $state = post('state', '');
    $country = post('country', '');
    $role = post('role', 'user');

    if ($name === '' || $email === '' || $contact === '') {
        jsonResponse(false, 'Name, email, and contact are required.');
    }

    $defaultPass = md5('password123');

    // Generate unique REG ID
    $reg_id = '';
    do {
        $reg_id = 'REG' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $chk = $conn->prepare("SELECT id FROM users WHERE reg_id = ?");
        $chk->bind_param('s', $reg_id);
        $chk->execute();
        $exists = $chk->get_result()->num_rows > 0;
    } while ($exists);

    // INSERT user record first
    $stmt = $conn->prepare("
        INSERT INTO users (reg_id, name, email, contact, gender, address, state, country, password, role)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('ssssssssss', $reg_id, $name, $email, $contact, $gender, $address, $state, $country, $defaultPass, $role);

    if (!$stmt->execute()) {
        jsonResponse(false, 'Failed to create user.');
    }

    $user_id = $stmt->insert_id;

    /* ============================================================
       RESTORED QR CODE GENERATION
       ============================================================ */
    try {
        // Load QR Library
        $qrLibPath = __DIR__ . '/phpqrcode/qrlib.php';
        if (!file_exists($qrLibPath)) {
            jsonResponse(false, 'QR library missing at: ' . $qrLibPath);
        }
        require_once $qrLibPath;

        // Ensure qrcodes folder exists
        $qrDir = __DIR__ . '/../qrcodes/';
        if (!file_exists($qrDir)) mkdir($qrDir, 0777, true);

        // QR output path
        $qrFile = $qrDir . $reg_id . '.png';

        // TEMP QR generation
        $tempFile = tempnam(sys_get_temp_dir(), 'qr_');
        QRcode::png($reg_id, $tempFile, QR_ECLEVEL_L, 6);

        // Load base QR image
        $qrImg = imagecreatefrompng($tempFile);
        $w = imagesx($qrImg);
        $h = imagesy($qrImg);

        // Add space for name text
        $fontHeight = 20;
        $finalHeight = $h + $fontHeight + 10;

        $canvas = imagecreatetruecolor($w, $finalHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $black = imagecolorallocate($canvas, 0, 0, 0);

        // Fill background
        imagefilledrectangle($canvas, 0, 0, $w, $finalHeight, $white);

        // Draw QR image
        imagecopy($canvas, $qrImg, 0, 0, 0, 0, $w, $h);

        // Add user name under QR
        $font = 5;
        $textWidth = imagefontwidth($font) * strlen($name);
        $x = ($w - $textWidth) / 2;
        imagestring($canvas, $font, $x, $h + 5, $name, $black);

        // Save final QR file
        imagepng($canvas, $qrFile);

        // Clean up
        imagedestroy($qrImg);
        imagedestroy($canvas);
        unlink($tempFile);

        // Save relative path to DB
        $qrPath = 'qrcodes/' . $reg_id . '.png';
        $u = $conn->prepare("UPDATE users SET qr_code = ? WHERE id = ?");
        $u->bind_param('si', $qrPath, $user_id);
        $u->execute();

        // Audit log
        $conn->query("INSERT INTO audit_logs (user, action) VALUES ('Admin', 'Created user $reg_id with QR code')");

    } catch (Throwable $e) {
        jsonResponse(false, 'QR generation failed: ' . $e->getMessage());
    }

    jsonResponse(true, 'User created successfully with QR.', [
        'id'     => $user_id,
        'reg_id' => $reg_id,
        'qr'     => 'qrcodes/' . $reg_id . '.png'
    ]);
    break;

    case 'update_user':
        $id = post('id', '');
        $name = post('name', '');
        $email = post('email', '');
        $contact = post('contact', '');
        $gender = post('gender', 'Male');
        $address = post('address', '');
        $state = post('state', '');
        $country = post('country', '');
        $role = post('role', 'user');

        if ($id === '' || $name === '' || $email === '' || $contact === '') {
            jsonResponse(false, 'Missing required fields.');
        }

        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, contact=?, gender=?, address=?, state=?, country=?, role=? WHERE id=?");
        $stmt->bind_param('ssssssssi', $name, $email, $contact, $gender, $address, $state, $country, $role, $id);
        if ($stmt->execute()) {
            $conn->query("INSERT INTO audit_logs (user, action) VALUES ('Admin', 'Updated user $id')");
            jsonResponse(true, 'User updated successfully.');
        } else {
            jsonResponse(false, 'Failed to update user.');
        }
        break;

    case 'get_users':
        $result = $conn->query("SELECT id, reg_id, name, email, contact, gender, address, state, country, role FROM users");
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        jsonResponse(true, '', $rows);
        break;

    case 'get_user':
        $id = post('id', '');
        if ($id === '') jsonResponse(false, 'Missing id.');
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        jsonResponse(true, '', $row ?: []);
        break;

    case 'delete_user':
        $id = post('id', '');
        if ($id === '') jsonResponse(false, 'Missing id.');
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $conn->query("INSERT INTO audit_logs (user, action) VALUES ('Admin', 'Deleted User $id')");
            jsonResponse(true, 'User deleted successfully.');
        } else {
            jsonResponse(false, 'Failed to delete user.');
        }
        break;


    /* ============================================================
       EVENTS CRUD
       ============================================================ */
    case 'create_event':
    $name = post('name', '');
    $date = post('date', '');
    $location = post('location', '');
    $description = post('description', '');

    if ($name === '' || $date === '' || $location === '') {
        jsonResponse(false, 'Please fill required fields.');
    }

    $stmt = $conn->prepare("INSERT INTO events (name, date, location, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $name, $date, $location, $description);

    if ($stmt->execute()) {
        $event_id = $stmt->insert_id;

        // Get all users with registered emails
        $users = $conn->query("SELECT name, email FROM users WHERE email IS NOT NULL AND email <> ''");

        if ($users && $users->num_rows > 0) {

            // For each user, send an individual email
            while ($u = $users->fetch_assoc()) {
                $subject = "New Event Registered: {$name}";

                $body =
                    "Hello {$u['name']}," . "\n\n" .
                    "A new event has been registered:\n\n" .
                    "Event: {$name}\n" .
                    "Date: {$date}\n" .
                    "Location: {$location}\n\n" .
                    "{$description}\n\n" .
                    "-- Event Management System";

                // Send using the SAME working email system 
                // (the one that works for forgot-password)
                sendEmail($u['email'], $subject, $body);
            }
        }

        // Log event
        $conn->query("INSERT INTO audit_logs (user, action) VALUES ('System', 'Created new event: $name and sent email notifications.')");

        jsonResponse(true, 'Event registered successfully and email notifications sent!', [
            'id' => $event_id
        ]);
    } else {
        jsonResponse(false, 'Failed to register event.');
    }
    break;

    case 'get_events':
        $result = $conn->query("SELECT id, name, description, location, date FROM events ORDER BY date DESC");
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        jsonResponse(true, '', $rows);
        break;

    case 'get_event':
        $id = post('id', '');
        if ($id === '') jsonResponse(false, 'Missing id.');
        $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        jsonResponse(true, '', $stmt->get_result()->fetch_assoc());
        break;

    case 'update_event':
        $id = post('id', '');
        $name = post('name', '');
        $date = post('date', '');
        $location = post('location', '');
        $description = post('description', '');

        if ($id === '' || $name === '' || $date === '' || $location === '') {
            jsonResponse(false, 'Missing required fields.');
        }

        $stmt = $conn->prepare("UPDATE events SET name=?, date=?, location=?, description=? WHERE id=?");
        $stmt->bind_param('ssssi', $name, $date, $location, $description, $id);
        if ($stmt->execute()) {
            $conn->query("INSERT INTO audit_logs (user, action) VALUES ('Admin', 'Updated event: $name')");
            jsonResponse(true, 'Event updated successfully.');
        } else {
            jsonResponse(false, 'Failed to update event.');
        }
        break;

    case 'delete_event':
        $id = post('id', '');
        if ($id === '') jsonResponse(false, 'Missing id.');
        $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $conn->query("INSERT INTO audit_logs (user, action) VALUES ('Admin', 'Deleted event id: $id')");
            jsonResponse(true, 'Event deleted successfully.');
        } else {
            jsonResponse(false, 'Failed to delete event.');
        }
        break;


    /* ============================================================
       ATTENDANCE
       ============================================================ */
    case 'mark_attendance':
        $reg_id = post('reg_id', '');
        $event_id = post('event_id', '');

        if ($reg_id === '' || $event_id === '') jsonResponse(false, 'Missing registration or event.');

        // find user id
        $stmt = $conn->prepare("SELECT id FROM users WHERE reg_id = ?");
        $stmt->bind_param('s', $reg_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res || $res->num_rows == 0) jsonResponse(false, 'Registration not found.');

        $user = $res->fetch_assoc();
        $user_id = $user['id'];

        // cooldown check (last action)
        $cooldownQ = $conn->prepare("
            SELECT id, check_in, check_out
            FROM attendance
            WHERE user_id = ? AND event_id = ?
            ORDER BY id DESC LIMIT 1
        ");
        $cooldownQ->bind_param('ii', $user_id, $event_id);
        $cooldownQ->execute();
        $last = $cooldownQ->get_result()->fetch_assoc();

        if (!$last) {
            $ins = $conn->prepare("INSERT INTO attendance (user_id, event_id, check_in) VALUES (?, ?, NOW())");
            $ins->bind_param('ii', $user_id, $event_id);
            $ins->execute();
            jsonResponse(true, 'Checked In Successfully!');
        } else {
            if (empty($last['check_out'])) {
                $upd = $conn->prepare("UPDATE attendance SET check_out = NOW() WHERE id = ?");
                $upd->bind_param('i', $last['id']);
                $upd->execute();
                jsonResponse(true, 'Checked Out Successfully!');
            } else {
                jsonResponse(false, 'Already checked in and checked out for this event.');
            }
        }
        break;

    case 'get_attendance':
        $result = $conn->query("
            SELECT a.id, a.reg_id, u.name AS user_name, e.name AS event_name, a.check_in, a.check_out
            FROM attendance a
            JOIN users u ON a.user_id = u.id
            JOIN events e ON a.event_id = e.id
            ORDER BY a.id DESC
        ");
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        jsonResponse(true, '', $rows);
        break;

    case 'get_history':
        $result = $conn->query("
            SELECT a.id, u.name AS user_name, e.name AS event_name, a.check_in, a.check_out
            FROM attendance a
            JOIN users u ON a.user_id = u.id
            JOIN events e ON a.event_id = e.id
            ORDER BY a.check_in DESC
        ");
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        jsonResponse(true, '', $rows);
        break;

    case 'get_user_history':
    $user_id = post('user_id', '');

    if ($user_id === '') {
        jsonResponse(false, 'Missing user ID.');
    }

    $stmt = $conn->prepare("
        SELECT a.id, u.name AS user_name, e.name AS event_name, a.check_in, a.check_out
        FROM attendance a
        JOIN events e ON a.event_id = e.id
        JOIN users u ON a.user_id = u.id
        WHERE a.user_id = ?
        ORDER BY a.check_in DESC
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();

    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    jsonResponse(true, '', $rows);
    break;


    case 'get_participants':
        $result = $conn->query("
            SELECT a.id, u.name, e.name AS event_name,
                CASE WHEN a.check_in IS NOT NULL THEN 'Checked-In' ELSE 'Registered' END AS status,
                a.check_in AS timestamp
            FROM attendance a
            JOIN users u ON a.user_id = u.id
            JOIN events e ON a.event_id = e.id
        ");
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        jsonResponse(true, '', $rows);
        break;

    case 'generate_report':
        $from = post('date_from', '');
        $to = post('date_to', '');

        if ($from === '' || $to === '') jsonResponse(false, 'Choose a valid date range.');

        $stmt = $conn->prepare("
            SELECT e.name AS event_name,
                COUNT(DISTINCT a.user_id) AS total_participants,
                SUM(CASE WHEN a.check_in IS NOT NULL THEN 1 ELSE 0 END) AS checked_in,
                SUM(CASE WHEN a.check_out IS NOT NULL THEN 1 ELSE 0 END) AS checked_out
            FROM events e
            LEFT JOIN attendance a ON e.id = a.event_id
            WHERE e.date BETWEEN ? AND ?
            GROUP BY e.id
        ");
        $stmt->bind_param('ss', $from, $to);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        jsonResponse(true, '', $rows);
        break;

    case 'get_report_details':
        $from = post('date_from', '');
        $to = post('date_to', '');
        if ($from === '' || $to === '') jsonResponse(false, 'Missing date range.');

        $eventsStmt = $conn->prepare("SELECT id, name, date, location, description FROM events WHERE date BETWEEN ? AND ? ORDER BY date ASC");
        $eventsStmt->bind_param('ss', $from, $to);
        $eventsStmt->execute();
        $events = $eventsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $data = [];
        foreach ($events as $ev) {
            $evId = (int)$ev['id'];
            $partStmt = $conn->prepare("
                SELECT u.reg_id, u.name, u.email, u.contact, a.check_in, a.check_out
                FROM attendance a
                JOIN users u ON a.user_id = u.id
                WHERE a.event_id = ?
                ORDER BY u.name ASC
            ");
            $partStmt->bind_param('i', $evId);
            $partStmt->execute();
            $participants = $partStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $data[] = ['event' => $ev, 'participants' => $participants];
        }

        jsonResponse(true, '', $data);
        break;

    case 'get_audit_logs':
        $result = $conn->query("SELECT id, user, action, timestamp FROM audit_logs ORDER BY timestamp DESC");
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        jsonResponse(true, '', $rows);
        break;


    /* ============================================================
       PASSWORD RECOVERY (Option A: password_resets table)
       ============================================================ */
    case 'send_email_code':
        $email = post('email', '');
        if ($email === '') jsonResponse(false, 'Email is required.');

        // check if user exists
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res || $res->num_rows === 0) jsonResponse(false, 'No account found with that email.');

        // generate code & expiry
        $code = strval(mt_rand(100000, 999999));
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $ins = $conn->prepare("INSERT INTO password_resets (email, code, expires_at) VALUES (?, ?, ?)");
        $ins->bind_param('sss', $email, $code, $expires);
        $ins->execute();

        // send code via email
        $subject = "Your Password Recovery Code";
        $message = "Your password recovery code is: {$code}\nThis code expires in 10 minutes.";
        $sent = sendEmail($email, $subject, $message);

        if ($sent) {
            jsonResponse(true, 'A recovery code has been sent to your email.');
        } else {
            jsonResponse(false, 'Failed to send recovery email. Check mail config.');
        }
        break;

    case 'verify_recovery_code':
        $code = post('code', '');
        if ($code === '') jsonResponse(false, 'Enter your recovery code.');

        $stmt = $conn->prepare("SELECT email FROM password_resets WHERE code = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res || $res->num_rows === 0) jsonResponse(false, 'Invalid or expired recovery code.');

        $row = $res->fetch_assoc();
        jsonResponse(true, 'Code verified.', ['email' => $row['email']]);
        break;

    case 'password_update':
        $email = post('email', '');
        $password = post('password', '');
        if ($email === '' || $password === '') jsonResponse(false, 'Missing required information.');

        // NOTE: system uses MD5 currently for compatibility. Migrate to password_hash() when possible.
        $hashed = md5($password);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param('ss', $hashed, $email);
        $stmt->execute();

        // delete password resets
        $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $del->bind_param('s', $email);
        $del->execute();

        jsonResponse(true, 'Password updated successfully.');
        break;

    case 'change_password':

    $user_id = post('user_id', '');
    $old = post('old_password', '');
    $new = post('new_password', '');
    $confirm = post('confirm_password', '');

    if ($user_id === '' || $old === '' || $new === '' || $confirm === '') {
        jsonResponse(false, 'All password fields are required.');
    }

    if ($new !== $confirm) {
        jsonResponse(false, 'New passwords do not match.');
    }

    // get stored hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if (!$user) {
        jsonResponse(false, 'User not found.');
    }

    // Compare MD5 (your system currently uses MD5)
    if (md5($old) !== $user['password']) {
        jsonResponse(false, 'Old password is incorrect.');
    }

    // Save new password
    $newHash = md5($new);
    $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update->bind_param("si", $newHash, $user_id);
    $update->execute();

    jsonResponse(true, 'Password updated successfully.');
    break;
    
    default:
        jsonResponse(false, 'Invalid request.');
        break;
}

?>
