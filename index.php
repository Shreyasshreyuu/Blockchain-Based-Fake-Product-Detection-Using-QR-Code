<?php
require_once 'includes/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            header('Location: dashboard.php');
            exit();
        }

        $error = 'Password does not match our records.';
    } else {
        $error = 'We could not find an account with that username or email.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ProductSecure</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="auth-body">
    <div class="auth-scene">
        <div class="auth-floor-glow"></div>
        <div class="auth-floor-grid"></div>
        <div class="auth-orb auth-orb-one"></div>
        <div class="auth-orb auth-orb-two"></div>

        <div class="auth-beams" aria-hidden="true">
            <span style="--beam-height: 280px; --beam-delay: 0s;"></span>
            <span style="--beam-height: 410px; --beam-delay: .4s;"></span>
            <span style="--beam-height: 220px; --beam-delay: 1.1s;"></span>
            <span style="--beam-height: 480px; --beam-delay: .8s;"></span>
            <span style="--beam-height: 260px; --beam-delay: 1.6s;"></span>
            <span style="--beam-height: 520px; --beam-delay: .3s;"></span>
            <span style="--beam-height: 245px; --beam-delay: 1.4s;"></span>
            <span style="--beam-height: 430px; --beam-delay: .9s;"></span>
            <span style="--beam-height: 290px; --beam-delay: 1.9s;"></span>
        </div>

        <a href="index.php" class="auth-floating-brand">
            <span class="brand-mark"><i class="fas fa-shield-halved"></i></span>
            <span>
                <strong>ProductSecure</strong>
                <span>Authenticity intelligence</span>
            </span>
        </a>

        <div class="auth-card-wrap">
            <section class="auth-card">
                <div class="auth-switch">
                    <a href="index.php" class="active">Login</a>
                    <a href="register.php">Register</a>
                </div>

                <div class="auth-copy">
                    <h1>Login</h1>
                    <p>Enter your credentials to access the redesigned verification dashboard with QR creation, smart scan, and blockchain insights.</p>
                    <div class="auth-badges">
                        <span class="auth-badge"><i class="fas fa-fingerprint"></i> Secure access</span>
                        <span class="auth-badge"><i class="fas fa-bolt"></i> Live verification</span>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error" style="margin-bottom: 18px;">
                        <i class="fas fa-circle-exclamation"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <div class="auth-field">
                        <span class="auth-field-title">Username or Email</span>
                        <div class="auth-input-shell">
                            <input type="text" name="username" placeholder="Enter username or email" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>

                    <div class="auth-field">
                        <span class="auth-field-title">Password</span>
                        <div class="auth-input-shell">
                            <input type="password" name="password" placeholder="Enter password" required>
                            <i class="fas fa-lock"></i>
                        </div>
                    </div>

                    <div class="auth-secondary-row">
                        <span class="helper-note">Demo login: <strong style="color:#fff;">admin / admin123</strong></span>
                        <a href="forgot-password.php" class="auth-helper-text" style="font-weight:700;text-decoration:none;">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn auth-submit btn-block">
                        <span>Login</span>
                    </button>
                </form>

                <div class="auth-links">
                    <span>Don’t have an account? <a href="register.php">Register</a></span><br>
                    <span>Manufacturer, distributor, and consumer ready.</span>
                </div>

                <div class="auth-mini-features">
                    <div class="auth-mini-feature">
                        <strong>QR</strong>
                        <span>Identity creation</span>
                    </div>
                    <div class="auth-mini-feature">
                        <strong>Scan</strong>
                        <span>Instant trust check</span>
                    </div>
                    <div class="auth-mini-feature">
                        <strong>Ledger</strong>
                        <span>Secure audit trail</span>
                    </div>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
