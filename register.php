<?php
require_once 'includes/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_type = $_POST['user_type'] ?? '';

    if ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('ss', $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = 'That username or email is already in use.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password, user_type) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssss', $username, $email, $hashed_password, $user_type);

            if ($stmt->execute()) {
                $success = 'Account created successfully. You can sign in now.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ProductSecure</title>
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
            <span style="--beam-height: 260px; --beam-delay: 0s;"></span>
            <span style="--beam-height: 360px; --beam-delay: .5s;"></span>
            <span style="--beam-height: 240px; --beam-delay: 1.1s;"></span>
            <span style="--beam-height: 500px; --beam-delay: .8s;"></span>
            <span style="--beam-height: 300px; --beam-delay: 1.6s;"></span>
            <span style="--beam-height: 530px; --beam-delay: .3s;"></span>
            <span style="--beam-height: 260px; --beam-delay: 1.4s;"></span>
            <span style="--beam-height: 420px; --beam-delay: 1s;"></span>
            <span style="--beam-height: 290px; --beam-delay: 1.8s;"></span>
        </div>

        <a href="index.php" class="auth-floating-brand">
            <span class="brand-mark"><i class="fas fa-layer-group"></i></span>
            <span>
                <strong>ProductSecure</strong>
                <span>Neon onboarding flow</span>
            </span>
        </a>

        <div class="auth-card-wrap" style="width:min(100%, 680px);">
            <section class="auth-card auth-card-wide">
                <div class="auth-switch">
                    <a href="index.php">Login</a>
                    <a href="register.php" class="active">Register</a>
                </div>

                <div class="auth-copy">
                    <h1>Create Account</h1>
                    <p>Build your account with the same premium look and start working inside a cleaner QR verification workspace.</p>
                    <div class="auth-badges">
                        <span class="auth-badge"><i class="fas fa-qrcode"></i> Product identity</span>
                        <span class="auth-badge"><i class="fas fa-chart-line"></i> Analytics ready</span>
                        <span class="auth-badge"><i class="fas fa-link"></i> Ledger backed</span>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error" style="margin-bottom: 18px;">
                        <i class="fas fa-circle-exclamation"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="auth-success-banner" style="margin-bottom: 18px;">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <div class="form-row">
                        <div class="auth-field">
                            <span class="auth-field-title">Username</span>
                            <div class="auth-input-shell">
                                <input type="text" name="username" placeholder="Choose username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>

                        <div class="auth-field">
                            <span class="auth-field-title">Email</span>
                            <div class="auth-input-shell">
                                <input type="email" name="email" placeholder="Enter email address" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                <i class="fas fa-envelope"></i>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="auth-field">
                            <span class="auth-field-title">Password</span>
                            <div class="auth-input-shell">
                                <input type="password" name="password" placeholder="At least 6 characters" required>
                                <i class="fas fa-lock"></i>
                            </div>
                        </div>

                        <div class="auth-field">
                            <span class="auth-field-title">Confirm Password</span>
                            <div class="auth-input-shell">
                                <input type="password" name="confirm_password" placeholder="Repeat password" required>
                                <i class="fas fa-shield-heart"></i>
                            </div>
                        </div>
                    </div>

                    <div class="auth-field">
                        <span class="auth-field-title">Select Role</span>
                        <div class="auth-input-shell">
                            <select name="user_type" required>
                                <option value="">Choose your role</option>
                                <option value="manufacturer" <?php echo (($_POST['user_type'] ?? '') === 'manufacturer') ? 'selected' : ''; ?>>Manufacturer</option>
                                <option value="distributor" <?php echo (($_POST['user_type'] ?? '') === 'distributor') ? 'selected' : ''; ?>>Distributor</option>
                                <option value="consumer" <?php echo (($_POST['user_type'] ?? '') === 'consumer') ? 'selected' : ''; ?>>Consumer</option>
                            </select>
                            <i class="fas fa-user-tag"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn auth-submit btn-block">
                        <span>Create Account</span>
                    </button>
                </form>

                <div class="auth-note-grid">
                    <div class="auth-note-card">
                        <strong>Manufacturer</strong>
                        <p>Create QR identities, organize product batches, and track verification readiness.</p>
                    </div>
                    <div class="auth-note-card">
                        <strong>Distributor or Consumer</strong>
                        <p>Focus on fast lookup, smart scan results, and blockchain-backed authenticity checks.</p>
                    </div>
                </div>

                <div class="auth-links">
                    <span>Already have an account? <a href="index.php">Login</a></span>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
