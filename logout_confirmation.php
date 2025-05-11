<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Logout</title>
    <link rel="stylesheet" href="assets/css/stylesidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        .logout-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .logout-content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            max-width: 400px;
            width: 90%;
            text-align: center;
            animation: fadeIn 0.3s ease-out;
        }
        
        .logout-icon {
            font-size: 50px;
            color: #e74c3c;
            margin-bottom: 20px;
        }
        
        .logout-title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }
        
        .logout-message {
            margin-bottom: 25px;
            color: #555;
            line-height: 1.5;
        }
        
        .logout-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .logout-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .logout-confirm {
            background-color: #e74c3c;
            color: white;
        }
        
        .logout-confirm:hover {
            background-color: #c0392b;
        }
        
        .logout-cancel {
            background-color: #3498db;
            color: white;
        }
        
        .logout-cancel:hover {
            background-color: #2980b9;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="logout-modal">
        <div class="logout-content">
            <div class="logout-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <div class="logout-title">
                Konfirmasi Logout
            </div>
            <div class="logout-message">
                Apakah Anda yakin ingin keluar dari sistem? Pastikan semua perubahan telah disimpan.
            </div>
            <div class="logout-buttons">
                <form method="post" action="logout.php" style="margin: 0; padding: 0;">
                    <button type="submit" name="confirm_logout" class="logout-btn logout-confirm">
                        <i class="fas fa-check"></i> Ya, Logout
                    </button>
                </form>
                <form method="post" action="logout.php" style="margin: 0; padding: 0;">
                    <button type="submit" name="cancel_logout" class="logout-btn logout-cancel">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>