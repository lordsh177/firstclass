<?php
/**
 * ============================================================
 * Event Attendance Management System - API Handler
 * ============================================================
 * Handles all AJAX/API actions for:
 * - Login
 * - Users CRUD
 * - Events CRUD
 * - Attendance (check-in/out)
 * - History, Participants, Reports, Audit Logs
 * ============================================================
 */

header('Content-Type: application/json');
require_once 'db_config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer (make sure you have the PHPMailer folder)
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

$action = $_POST['action'] ?? '';

switch ($action) {

    /* ============================================================
       LOGIN
       ============================================================ */
    case 'login':
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
            exit;
        }

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (md5($password) === $user['password']) {
                echo json_encode(['success' => true, 'message' => 'Login successful!', 'name' => $user['name']]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No account found with that email.']);
        }
        exit;


    /* ============================================================
       USERS (CREATE / UPDATE / GET / DELETE)
       ============================================================ */

    // CREATE USER
case 'create_user':
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $gender = $_POST['gender'] ?? 'Male';
    $address = $_POST['address'] ?? '';
    $state = $_POST['state'] ?? '';
    $country = $_POST['country'] ?? '';
    $password = md5('password123');

    if (empty($name) || empty($email) || empty($contact)) {
        echo json_encode(['success' => false, 'message' => 'Name, email, and contact are required.']);
        exit;
    }

    // Generate unique registration ID
    do {
        $reg_id = 'REG' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE reg_id = ?");
        $check_stmt->bind_param("s", $reg_id);
        $check_stmt->execute();
        $exists = ($check_stmt->get_result()->num_rows > 0);
    } while ($exists);

    // Insert user first
    $stmt = $conn->prepare("
        INSERT INTO users (reg_id, name, email, contact, gender, address, state, country, password)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssssssss", $reg_id, $name, $email, $contact, $gender, $address, $state, $country, $password);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;

        try {
            // ✅ Load QR library
            $qrLibPath = __DIR__ . '/phpqrcode/qrlib.php';
            if (!file_exists($qrLibPath)) {
                echo json_encode(['success' => false, 'message' => 'QR library not found at: ' . $qrLibPath]);
                exit;
            }
            require_once($qrLibPath);

            // ✅ Ensure QR code folder exists
            $qrDir = dirname(__DIR__) . '/qrcodes/';
            if (!file_exists($qrDir)) {
                if (!mkdir($qrDir, 0777, true)) {
                    echo json_encode(['success' => false, 'message' => 'Cannot create QR directory: ' . $qrDir]);
                    exit;
                }
            }

            // ✅ File path
            $qrFile = $qrDir . $reg_id . '.png';

            // ✅ Generate base QR code (encoded reg_id)
            $qrTempFile = tempnam(sys_get_temp_dir(), 'qr_');
            QRcode::png($reg_id, $qrTempFile, QR_ECLEVEL_L, 6);

            // ✅ Load generated QR image
            $qrImage = imagecreatefrompng($qrTempFile);
            $width = imagesx($qrImage);
            $height = imagesy($qrImage);

            // ✅ Create a new image with space for the user's name
            $fontHeight = 20;
            $newHeight = $height + $fontHeight + 10; // extra padding
            $finalImage = imagecreatetruecolor($width, $newHeight);

            // White background
            $white = imagecolorallocate($finalImage, 255, 255, 255);
            imagefilledrectangle($finalImage, 0, 0, $width, $newHeight, $white);

            // Copy QR into final image
            imagecopy($finalImage, $qrImage, 0, 0, 0, 0, $width, $height);

            // Draw user's name under QR
            $black = imagecolorallocate($finalImage, 0, 0, 0);
            $font = 5; // built-in GD font
            $textWidth = imagefontwidth($font) * strlen($name);
            $textX = ($width - $textWidth) / 2; // center text
            imagestring($finalImage, $font, $textX, $height + 5, $name, $black);

            // Save final QR image
            imagepng($finalImage, $qrFile);

            // Cleanup
            imagedestroy($qrImage);
            imagedestroy($finalImage);
            unlink($qrTempFile);

            // ✅ Save QR path to database
            $qrPath = 'qrcodes/' . $reg_id . '.png';
            $updateQR = $conn->prepare("UPDATE users SET qr_code = ? WHERE id = ?");
            $updateQR->bind_param("si", $qrPath, $user_id);
            $updateQR->execute();

            // ✅ Log the action
            $conn->query("INSERT INTO audit_logs (user, action) VALUES ('$name', 'Created user $reg_id with QR code')");

            echo json_encode([
                'success' => true,
                'message' => 'User created successfully with QR code.',
                'reg_id' => $reg_id,
                'qr_code' => $qrPath
            ]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'QR generation failed: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create user.']);
    }
    exit;





    // UPDATE USER
    case 'update_user':
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $contact = $_POST['contact'] ?? '';
        $gender = $_POST['gender'] ?? 'Male';
        $address = $_POST['address'] ?? '';
        $state = $_POST['state'] ?? '';
        $country = $_POST['country'] ?? '';

        if (empty($id) || empty($name) || empty($email) || empty($contact)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
            exit;
        }

        $stmt = $conn->prepare("
            UPDATE users 
            SET name=?, email=?, contact=?, gender=?, address=?, state=?, country=? 
            WHERE id=?
        ");
        $stmt->bind_param("sssssssi", $name, $email, $contact, $gender, $address, $state, $country, $id);

        if ($stmt->execute()) {
            $conn->query("INSERT INTO audit_logs (user, action) VALUES ('$name', 'Updated user')");
            echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update user.']);
        }
        exit;


    // GET ALL USERS
    case 'get_users':
        $result = $conn->query("SELECT id, reg_id, name, email, contact, gender, address, state, country FROM users");
        echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
        exit;


    // GET SINGLE USER
    case 'get_user':
        $id = $_POST['id'];
        $stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_assoc()]);
        exit;


    // DELETE USER
    case 'delete_user':
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $conn->query("INSERT INTO audit_logs (user, action) VALUES ('Admin', 'Deleted User $id')");
            echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete user.']);
        }
        exit;


    /* ============================================================
       EVENTS (CREATE / READ / UPDATE / DELETE)
       ============================================================ */

   /************************************
 * CREATE EVENT
 ************************************/
case "create_event":
    $name = $_POST['name'] ?? '';
    $date = $_POST['date'] ?? '';
    $location = $_POST['location'] ?? '';
    $description = $_POST['description'] ?? '';

    if (empty($name) || empty($date) || empty($location)) {
        echo json_encode(["success" => false, "message" => "Please fill required fields."]);
        exit;
    }

    // Insert the new event
    $stmt = $conn->prepare("INSERT INTO events (name, date, location, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $date, $location, $description);

    if ($stmt->execute()) {
        $event_id = $stmt->insert_id;

        // -------------------------------------------------
        // ✅ Notify all users by EMAIL (single BCC)
        // -------------------------------------------------
        $users = $conn->query("SELECT name, email FROM users WHERE email IS NOT NULL AND email <> ''");
        if ($users && $users->num_rows > 0) {
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';     // Gmail SMTP server
                $mail->SMTPAuth   = true;
                $mail->Username   = 'lomopog.clark777@gmail.com';      // 🔹 Replace with your Gmail
                $mail->Password   = 'rpyy rvlu zmmv ducu';         // 🔹 Replace with your App Password
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                // Sender info
                $mail->setFrom('YOUR_EMAIL@gmail.com', 'Event Management System');
                $mail->addReplyTo('YOUR_EMAIL@gmail.com', 'Event Management System');

                // Add all users as BCC (hidden recipients)
                while ($u = $users->fetch_assoc()) {
                    $mail->addBCC($u['email'], $u['name']);
                }

                // Email content
                $mail->isHTML(true);
                $mail->Subject = "New Event Registered: $name";
                $mail->Body = "
                    Hello,<br><br>
                    A new event has been registered:<br><br>
                    <b>Event:</b> $name<br>
                    <b>Date:</b> $date<br>
                    <b>Location:</b> $location<br><br>
                    $description<br><br>
                    Please log in to your account for more details.<br><br>
                    -- Event Management System
                ";

                $mail->send();
            } catch (Exception $e) {
                error_log('Mailer Error: ' . $mail->ErrorInfo);
            }
        }

        // -------------------------------------------------
        // ✅ Log the action
        // -------------------------------------------------
        $conn->query("INSERT INTO audit_logs (user, action) VALUES ('System', 'Created new event: $name and sent email notifications.')");

        echo json_encode([
            "success" => true,
            "message" => "Event registered successfully and email notifications sent!"
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to register event."]);
    }

    exit;



/************************************
 * GET ALL EVENTS
 ************************************/
case 'get_events':
    $result = $conn->query("SELECT id, name, description, location, date FROM events");
    echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
    exit;


/************************************
 * GET SINGLE EVENT
 ************************************/
case 'get_event':
    $id = $_POST['id'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM events WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_assoc()]);
    exit;


/************************************
 * UPDATE EVENT
 ************************************/
case 'update_event':
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $date = $_POST['date'] ?? '';
    $location = $_POST['location'] ?? '';
    $description = $_POST['description'] ?? '';

    if (empty($id) || empty($name) || empty($date) || empty($location)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE events 
        SET name=?, date=?, location=?, description=?
        WHERE id=?
    ");
    $stmt->bind_param("ssssi", $name, $date, $location, $description, $id);

    if ($stmt->execute()) {
        $conn->query("INSERT INTO audit_logs (user, action) VALUES ('Admin', 'Updated event: $name')");
        echo json_encode(['success' => true, 'message' => 'Event updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update event.']);
    }
    exit;


/************************************
 * DELETE EVENT
 ************************************/
case 'delete_event':
    $id = $_POST['id'] ?? '';

    $stmt = $conn->prepare("DELETE FROM events WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $conn->query("INSERT INTO audit_logs (user, action) VALUES ('Admin', 'Deleted event id: $id')");
        echo json_encode(['success' => true, 'message' => 'Event deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete event.']);
    }
    exit;


    /* ============================================================
       ATTENDANCE (Check-In / Check-Out)
       ============================================================ */

case "mark_attendance":
    $reg_id = $_POST['reg_id'];
    $event_id = $_POST['event_id'];

    // Get user from registration id
    $userQ = $conn->query("SELECT id FROM users WHERE reg_id='$reg_id'");
    if ($userQ->num_rows == 0) {
        echo json_encode(["success" => false, "message" => "Registration not found."]);
        exit;
    }
    $user = $userQ->fetch_assoc();
    $user_id = $user['id'];

    // Check last attendance action (for cooldown)
    $cooldownQ = $conn->query("
        SELECT 
            GREATEST(
                IFNULL(UNIX_TIMESTAMP(check_in), 0),
                IFNULL(UNIX_TIMESTAMP(check_out), 0)
            ) AS last_action
        FROM attendance
        WHERE user_id='$user_id' AND event_id='$event_id'
        ORDER BY id DESC LIMIT 1
    ");
    if ($cooldownQ && $cooldownQ->num_rows > 0) {
        $last = $cooldownQ->fetch_assoc();
        if (!empty($last['last_action'])) {
            $elapsed = time() - $last['last_action'];
            if ($elapsed < 30) { // 30 seconds = 1 minute
                $remaining = ceil((30 - $elapsed) / 60);
                echo json_encode([
                    "success" => false,
                    "message" => "Please wait {$remaining} more minute(s) before scanning again."
                ]);
                exit;
            }
        }
    }

    // Check if attendance record already exists for this event
    $check = $conn->query("SELECT * FROM attendance WHERE user_id='$user_id' AND event_id='$event_id' LIMIT 1");

    // If no record, then CHECK-IN
    if ($check->num_rows == 0) {
        $conn->query("INSERT INTO attendance (user_id, event_id, check_in) VALUES ('$user_id', '$event_id', NOW())");
        echo json_encode(["success" => true, "message" => "Checked In Successfully!"]);
        exit;
    }

    // If record exists, handle CHECK-OUT logic
    $row = $check->fetch_assoc();

    if (empty($row['check_out'])) {
        // User is currently checked in → mark as checked out
        $conn->query("UPDATE attendance SET check_out = NOW() WHERE id='{$row['id']}'");
        echo json_encode(["success" => true, "message" => "Checked Out Successfully!"]);
        exit;
    } else {
        // Already checked out → just notify
        echo json_encode(["success" => false, "message" => "Already checked in and checked out for this event."]);
        exit;
    }





    /* ============================================================
       VIEW ATTENDANCE / HISTORY / PARTICIPANTS / REPORTS
       ============================================================ */

    case 'get_attendance':
        $result = $conn->query("
            SELECT a.id, a.reg_id, u.name AS user_name, e.name AS event_name, a.check_in, a.check_out
            FROM attendance a
            JOIN users u ON a.user_id=u.id
            JOIN events e ON a.event_id=e.id
            ORDER BY a.id DESC
        ");
        echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
        exit;


    case 'get_history':
        $result = $conn->query("
            SELECT a.id, u.name AS user_name, e.name AS event_name, a.check_in, a.check_out
            FROM attendance a
            JOIN users u ON a.user_id=u.id
            JOIN events e ON a.event_id=e.id
            ORDER BY a.check_in DESC
        ");
        echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
        exit;


    case 'get_participants':
        $result = $conn->query("
            SELECT a.id, u.name, e.name AS event_name,
                CASE WHEN a.check_in IS NOT NULL THEN 'Checked-In' ELSE 'Registered' END AS status,
                a.check_in AS timestamp
            FROM attendance a
            JOIN users u ON a.user_id=u.id
            JOIN events e ON a.event_id=e.id
        ");
        echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
        exit;


    case 'generate_report':
        $from = $_POST['date_from'];
        $to = $_POST['date_to'];

        $stmt = $conn->prepare("
            SELECT e.name AS event_name,
                COUNT(DISTINCT a.user_id) AS total_participants,
                SUM(CASE WHEN a.check_in IS NOT NULL THEN 1 ELSE 0 END) AS checked_in,
                SUM(CASE WHEN a.check_out IS NOT NULL THEN 1 ELSE 0 END) AS checked_out
            FROM events e
            LEFT JOIN attendance a ON e.id=a.event_id
            WHERE e.date BETWEEN ? AND ?
            GROUP BY e.id
        ");
        $stmt->bind_param("ss", $from, $to);
        $stmt->execute();

        echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
        exit;


    /* ============================================================
       AUDIT LOGS
       ============================================================ */
    case 'get_audit_logs':
        $result = $conn->query("SELECT * FROM audit_logs ORDER BY timestamp DESC");
        echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
        exit;


    /* ============================================================
       PASSWORD RECOVERY (Placeholder)
       ============================================================ */
    case 'send_email_code':
        echo json_encode(['success' => true, 'message' => 'Code sent.']);
        exit;

    case 'verify_recovery_code':
        echo json_encode([
            'success' => ($_POST['code'] ?? '') === '123456',
            'message' => ($_POST['code'] ?? '') === '123456'
                ? 'Code verified.'
                : 'Invalid code.'
        ]);
        exit;

        // GET EVENTS
if ($action === "get_events") {
    $result = $conn->query("SELECT * FROM events");
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    echo json_encode(["success" => true, "data" => $events]);
    exit;
}

// CREATE EVENT
if ($action === "create_event") {
    $name = $_POST['name'];
    $location = $_POST['location'];
    $date = $_POST['date'];
    $description = $_POST['description'];

    $conn->query("INSERT INTO events (name, description, location, date)
                  VALUES ('$name','$description','$location','$date')");
    echo json_encode(["success" => true, "message" => "Event successfully added!"]);
    exit;
}

// DELETE EVENT
if ($action === "delete_event") {
    $id = $_POST['id'];
    $conn->query("DELETE FROM events WHERE id='$id'");
    echo json_encode(["success" => true, "message" => "Event deleted successfully!"]);
    exit;
}
if ($action == "get_event") {
    $id = $_POST["id"];
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(["success" => true, "data" => $stmt->fetch(PDO::FETCH_ASSOC)]);
}
if ($action == "update_event") {
    $stmt = $pdo->prepare("UPDATE events SET name=?, date=?, location=?, description=? WHERE id=?");
    $stmt->execute([
        $_POST["name"],
        $_POST["date"],
        $_POST["location"],
        $_POST["description"],
        $_POST["id"]
    ]);

    echo json_encode(["success" => true, "message" => "Event updated successfully!"]);
}


    /* ============================================================
       DEFAULT (Invalid Action)
       ============================================================ */
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit;
}

?>
