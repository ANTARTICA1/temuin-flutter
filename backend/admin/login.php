<?php
session_start();
include_once '../api/config/database.php';

header("Content-Type: text/html; charset=UTF-8");

if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $database = new Database();
        $db = $database->getConnection();

        $query = "SELECT id, name, password, role FROM users WHERE email = :email AND role = 'admin' LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $row['password'])) {
                $_SESSION['admin_id'] = $row['id'];
                $_SESSION['admin_name'] = $row['name'];
                header("Location: index.php");
                exit;
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Akun tidak ditemukan atau Anda bukan admin!";
        }
    } else {
        $error = "Mohon isi email dan password!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Temuin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { 
            background: #f0f2f5; 
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card { 
            max-width: 420px; 
            margin: auto; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
            overflow: hidden;
            border: none;
        }
        .login-header { 
            background: linear-gradient(135deg, #6C5CE7 0%, #4834D4 100%); 
            color: white; 
            padding: 40px 20px 30px; 
            text-align: center; 
        }
        .login-header .icon-circle {
            width: 70px;
            height: 70px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 32px;
        }
        .form-control {
            border-radius: 12px;
            padding: 12px 15px;
            border: 1px solid #e1e5eb;
            background-color: #f8f9fa;
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.15);
            border-color: #6C5CE7;
            background-color: #ffffff;
        }
        .input-group-text {
            border-radius: 12px;
            border-color: #e1e5eb;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6C5CE7 0%, #4834D4 100%); 
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 92, 231, 0.3);
            background: linear-gradient(135deg, #5A4BD1 0%, #3B2BB5 100%); 
        }
        .form-label {
            font-weight: 500;
            color: #495057;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card login-card">
            <div class="login-header">
                <div class="icon-circle">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h3 class="mb-1 fw-bold">Admin Portal</h3>
                <p class="mb-0 text-white-50 small">Masuk ke sistem kontrol Temuin</p>
            </div>
            <div class="card-body p-4 p-md-5">
                <?php if($error): ?>
                    <div class="alert alert-danger d-flex align-items-center rounded-3 p-3 mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?= htmlspecialchars($error) ?></div>
                    </div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="mb-4">
                        <label class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-envelope text-muted"></i>
                            </span>
                            <input type="email" name="email" class="form-control border-start-0 px-2" required placeholder="Masukan email">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-lock text-muted"></i>
                            </span>
                            <input type="password" name="password" class="form-control border-start-0 px-2" required placeholder="Masukan Password">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-2">Masuk ke Dashboard</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
