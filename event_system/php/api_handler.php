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

            // Compare MD5 password (change to password_hash() in production)
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
