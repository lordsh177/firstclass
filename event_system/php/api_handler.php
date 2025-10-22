<?php
header('Content-Type: application/json');
require_once 'db_config.php';

$action = $_POST['action'] ?? '';

switch ($action) {
    // --- LOGIN ---
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
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful!',
                    'name' => $user['name']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No account found with that email.']);
        }
        exit;

    // --- USER CRUD ---
    case 'create_user':
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $contact = $_POST['contact'] ?? '';
        $gender = $_POST['gender'] ?? 'Male';
        $address = $_POST['address'] ?? '';
        $state = $_POST['state'] ?? '';
        $country = $_POST['country'] ?? '';
        $password = md5('password123'); // Default password; update form to include password input

        if (empty($name) || empty($email) || empty($contact)) {
            echo json_encode(['success' => false, 'message' => 'Name, email, and contact are required.']);
            exit;
        }

        // Generate unique reg_id
        do {
            $reg_id = 'REG' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE reg_id = ?");
            $check_stmt->bind_param("s", $reg_id);
            $check_stmt->execute();
            $exists = $check_stmt->get_result()->num_rows > 0;
        } while ($exists);

        $stmt = $conn->prepare("INSERT INTO users (reg_id, name, email, contact, gender, address, state, country, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $reg_id, $name, $email, $contact, $gender, $address, $state, $country, $password);
        if ($stmt->execute()) {
            // Log audit
            $conn->query("INSERT INTO audit_logs (user, action) VALUES ('$name', 'Created user with reg_id $reg_id')");
            echo json_encode(['success' => true, 'message' => 'User created successfully. Registration ID: ' . $reg_id, 'reg_id' => $reg_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create user.']);
        }
        exit;

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
            echo json_encode(['success' => false, 'message' => 'ID, name, email, and contact are required.']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, contact=?, gender=?, address=?, state=?, country=? WHERE id=?");
        $stmt->bind_param("sssssssi", $name, $email, $contact, $gender, $address, $state, $country, $id);
        if ($stmt->execute()) {
            $conn->query("INSERT INTO audit_logs (user, action) VALUES ('$name', 'Updated user')");
            echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update user.']);
        }
        exit;

    case 'get_users':
    $result = $conn->query("SELECT id, reg_id, name, email, contact, gender, address, state, country FROM users");
    if ($result) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $users]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database query failed.']);
    }
    exit;
    case 'get_user':
        $id = $_POST['id'] ?? '';
        $stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $user]);
        exit;

    case 'delete_user':
        $id = $_POST['id'] ?? '';
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $conn->query("INSERT INTO audit_logs (user, action) VALUES ('Admin', 'Deleted user ID $id')");
            echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete user.']);
        }
        exit;

    // --- EVENT CRUD ---
    case 'create_event':
        $name = $_POST['name'] ?? '';
        $date = $_POST['date'] ?? '';
        $location = $_POST['location'] ?? '';
        $description = $_POST['description'] ?? '';

        if (empty($name) || empty($date) || empty($location)) {
            echo json_encode(['success' => false, 'message' => 'Name, date, and location are required.']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO events (name, date, location, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $date, $location, $description);
        if ($stmt->execute()) {
            $conn->query("INSERT INTO audit_logs (user, action) VALUES ('Admin', 'Created event: $name')");
            echo json_encode(['success' => true, 'message' => 'Event created successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create event.']);
        }
        exit;

    case 'update_event':
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $date = $_POST['date'] ?? '';
        $location = $_POST['location'] ?? '';
        $description = $_POST['description'] ?? '';

        if (empty($id) || empty($name) || empty($date) || empty($location)) {
            echo json_encode(['success' => false, 'message' => 'ID, name, date, and location are required.']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE events SET name=?, date=?, location=?, description=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $date, $location, $description, $id);
        if ($stmt->execute()) {
            $conn->query("INSERT INTO audit_logs (user, action) VALUES ('Admin', 'Updated event: $name')");
            echo json_encode(['success' => true, 'message' => 'Event updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update event.']);
        }
        exit;

    case 'get_events':
        $result = $conn->query("SELECT * FROM events");
        $events = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $events]);
        exit;

    case 'get_event':
        $id = $_POST['id'] ?? '';
        $stmt = $conn->prepare("SELECT * FROM events WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $event = $stmt->get_result()->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $event]);
        exit;

    case 'delete_event':
        $id = $_POST['id'] ?? '';
        $stmt = $conn->prepare("DELETE FROM events WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $conn->query("INSERT INTO audit_logs (user, action) VALUES ('Admin', 'Deleted event ID $id')");
            echo json_encode(['success' => true, 'message' => 'Event deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete event.']);
        }
        exit;

    // --- ATTENDANCE ---
    case 'mark_attendance':
        $reg_id = $_POST['reg_id'] ?? '';
        $event_id = $_POST['event_id'] ?? '';
        $type = $_POST['type'] ?? ''; // 'in' or 'out'

        if (empty($reg_id) || empty($event_id) || !in_array($type, ['in', 'out'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data.']);
            exit;
        }

        // Fetch user_id by reg_id
        $stmt = $conn->prepare("SELECT id FROM users WHERE reg_id=? LIMIT 1");
        $stmt->bind_param("s", $reg_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found with this registration ID.']);
            exit;
        }
        $user_id = $user['id'];

        if ($type === 'in') {
            $stmt = $conn->prepare("INSERT INTO attendance (user_id, event_id, reg_id, check_in) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE check_in=NOW()");
        } else {
            $stmt = $conn->prepare("UPDATE attendance SET check_out=NOW() WHERE user_id=? AND event_id=? AND check_out IS NULL");
        }
        $stmt->bind_param("iis", $user_id, $event_id, $reg_id);
        if ($stmt->execute()) {
            $conn->query("INSERT INTO audit_logs (user, action) VALUES ('User $user_id', 'Marked $type for event $event_id')");
            echo json_encode(['success' => true, 'message' => ucfirst($type) . ' marked successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark attendance.']);
        }
        exit;

    case 'get_attendance':
        $result = $conn->query("SELECT a.id, u.name AS user_name, e.name AS event_name, a.check_in, a.check_out FROM attendance a JOIN users u ON a.user_id=u.id JOIN events e ON a.event_id=e.id");
        $attendance = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $attendance]);
        exit;

    // --- AUDIT LOGS ---
    case 'get_audit_logs':
        $result = $conn->query("SELECT * FROM audit_logs ORDER BY timestamp DESC");
        $logs = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $logs]);
        exit;

    // --- PARTICIPANTS ---
    case 'get_participants':
        $result = $conn->query("SELECT a.id, u.name, e.name AS event_name, CASE WHEN a.check_in IS NOT NULL THEN 'Checked In' ELSE 'Registered' END AS status, a.check_in AS timestamp FROM attendance a JOIN users u ON a.user_id=u.id JOIN events e ON a.event_id=e.id");
        $participants = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $participants]);
        exit;

    // --- REPORTS ---
    case 'generate_report':
        $from = $_POST['date_from'] ?? '';
        $to = $_POST['date_to'] ?? '';
        if (empty($from) || empty($to)) {
            echo json_encode(['success' => false, 'message' => 'Date range required.']);
            exit;
        }
        $stmt = $conn->prepare("SELECT e.name AS event_name, COUNT(DISTINCT a.user_id) AS total_participants, SUM(CASE WHEN a.check_in IS NOT NULL THEN 1 ELSE 0 END) AS checked_in, SUM(CASE WHEN a.check_out IS NOT NULL THEN 1 ELSE 0 END) AS checked_out FROM events e LEFT JOIN attendance a ON e.id=a.event_id WHERE e.date BETWEEN ? AND ? GROUP BY e.id");
        $stmt->bind_param("ss", $from, $to);
        $stmt->execute();
        $reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $reports]);
        exit;

    // --- HISTORY ---
    case 'get_history':
        $result = $conn->query("SELECT a.id, u.name AS user_name, e.name AS event_name, a.check_in, a.check_out FROM attendance a JOIN users u ON a.user_id=u.id JOIN events e ON a.event_id=e.id ORDER BY a.check_in DESC");
        $history = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $history]);
        exit;

    // --- FORGOT PASSWORD (PLACEHOLDERS) ---
    case 'send_email_code':
        $email = $_POST['email'];
        echo json_encode(['success' => true, 'message' => "Recovery code sent to $email"]);
        exit;

    case 'send_phone_code':
        $email = $_POST['email'];
        echo json_encode(['success' => true, 'message' => "Recovery code sent via phone for $email"]);
        exit;

    case 'verify_recovery_code':
        $code = $_POST['code'];
        if ($code === '123456') {
            echo json_encode(['success' => true, 'message' => 'Code verified! You can now reset your password.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid recovery code.']);
        }
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit;
}
?>