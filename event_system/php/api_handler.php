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

        $stmt = $conn->prepare("
            INSERT INTO users (reg_id, name, email, contact, gender, address, state, country, password)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssssssss", $reg_id, $name, $email, $contact, $gender, $address, $state, $country, $password);

        if ($stmt->execute()) {
            $conn->query("INSERT INTO audit_logs (user, action) VALUES ('$name', 'Created user $reg_id')");
            echo json_encode(['success' => true, 'message' => 'User created successfully', 'reg_id' => $reg_id]);
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

    case 'create_event':
        $name = $_POST['name'];
        $date = $_POST['date'];
        $location = $_POST['location'];
        $description = $_POST['description'];

        if (empty($name) || empty($date) || empty($location)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO events (name, date, location, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $date, $location, $description);

        if ($stmt->execute()) {
            $conn->query("INSERT INTO audit_logs (user, action) VALUES ('Admin', 'Created event $name')");
            echo json_encode(['success' => true, 'message' => 'Event created successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create event.']);
        }
        exit;


    case 'get_events':
        $result = $conn->query("SELECT * FROM events ORDER BY date DESC");
        echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
        exit;


    case 'update_event':
        $id = $_POST['id'];
        $name = $_POST['name'];
        $date = $_POST['date'];
        $location = $_POST['location'];
        $description = $_POST['description'];

        $stmt = $conn->prepare("UPDATE events SET name=?, date=?, location=?, description=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $date, $location, $description, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Event updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update event.']);
        }
        exit;


    case 'delete_event':
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM events WHERE id=?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Event deleted.']);
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
    $type = $_POST['type'];

    // Get user from registration id
    $userQ = $conn->query("SELECT id FROM users WHERE reg_id='$reg_id'");
    if($userQ->num_rows == 0){
        echo json_encode(["success"=>false,"message"=>"Registration not found."]);
        exit;
    }
    $user = $userQ->fetch_assoc();
    $user_id = $user['id'];

    // Check if attendance exists already
    $check = $conn->query("SELECT * FROM attendance WHERE user_id='$user_id' AND event_id='$event_id'");

    // PROCESS CHECK-IN
    if($type === "in"){
        if($check->num_rows > 0){
            echo json_encode(["success"=>false,"message"=>"Already checked in."]);
            exit;
        }

        $conn->query("INSERT INTO attendance (user_id, event_id, check_in) VALUES ('$user_id', '$event_id', NOW())");
        echo json_encode(["success"=>true,"message"=>"Checked In Successfully!"]);
        exit;
    }

    // PROCESS CHECK-OUT
    if($type === "out"){
        if($check->num_rows == 0){
            echo json_encode(["success"=>false,"message"=>"User has not checked in!"]);
            exit;
        }

        $row = $check->fetch_assoc();

        if(!empty($row['check_out'])){
            echo json_encode(["success"=>false,"message"=>"Already checked out!"]);
            exit;
        }

        $conn->query("UPDATE attendance SET check_out = NOW() WHERE id='{$row['id']}'");
        echo json_encode(["success"=>true,"message"=>"Checked Out Successfully!"]);
        exit;
    }

break;


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


    /* ============================================================
       DEFAULT (Invalid Action)
       ============================================================ */
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit;
}

?>
