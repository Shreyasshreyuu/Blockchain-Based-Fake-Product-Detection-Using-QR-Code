<?php
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";
$result = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['qr_image'])) {
    $file = $_FILES['qr_image'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Upload failed. Error code: " . $file['error'];
    } else {
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            $error = "Invalid file type. Please upload an image.";
        } 
        // Validate file size (max 5MB)
        elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = "File too large. Maximum size is 5MB.";
        } else {
            // Move uploaded file
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $filename = time() . '_' . basename($file['name']);
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Here you would need a QR decoding library in PHP
                // For now, we'll redirect to scan page with the file path
                header("Location: scan-qr.php?scan_file=" . urlencode($filepath));
                exit();
            } else {
                $error = "Failed to save uploaded file.";
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
    <title>Upload QR Code - ProductSecure</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .upload-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
        }
        h2 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        p {
            color: #666;
            margin-bottom: 30px;
        }
        .upload-area {
            border: 3px dashed #667eea;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        .upload-area:hover {
            background: #f8f9fa;
            border-color: #764ba2;
        }
        .upload-area i {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 15px;
        }
        .upload-area input {
            display: none;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            transition: transform 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            margin-top: 10px;
        }
        .error {
            background: #fee;
            color: #c00;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .file-info {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            display: none;
        }
        .preview {
            max-width: 100%;
            max-height: 200px;
            margin-top: 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="upload-card">
        <h2><i class="fas fa-cloud-upload-alt"></i> Upload QR Code Image</h2>
        <p>Select an image containing a QR code from your device</p>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                <i class="fas fa-cloud-upload-alt"></i>
                <p><strong>Click to upload</strong> or drag and drop</p>
                <p>Supported formats: JPG, PNG, GIF, BMP (Max 5MB)</p>
                <input type="file" name="qr_image" id="fileInput" accept="image/*" required>
            </div>
            
            <div class="file-info" id="fileInfo">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <i class="fas fa-file-image" style="color: #4CAF50;"></i>
                    <span id="fileName"></span>
                </div>
                <img id="preview" class="preview" alt="Preview">
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-search"></i> Scan QR Code
            </button>
        </form>
        
        <a href="scan-qr.php" class="btn btn-secondary" style="display: block; text-align: center; text-decoration: none;">
            <i class="fas fa-arrow-left"></i> Back to Scanner
        </a>
    </div>
    
    <script>
    document.getElementById('fileInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Show file info
            document.getElementById('fileInfo').style.display = 'block';
            document.getElementById('fileName').textContent = file.name;
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
    </script>
</body>
</html>