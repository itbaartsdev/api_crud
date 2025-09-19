<?php 
// Pastikan menggunakan koneksi database local
if (file_exists('conf/koneksi.php')) {
    include 'conf/koneksi.php';
} else if (file_exists('../conf/koneksi.php')) {
    include '../conf/koneksi.php';
} else {
    // Fallback untuk structure yang berbeda
    include '../../conf/koneksi.php';
}
 ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?=$apk;?> - Modern Database Generator</title>
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" href="azzam/app-assets/images/favicon/apple-touch-icon-152x152.png">
    <link rel="shortcut icon" type="image/x-icon" href="azzam/app-assets/images/favicon/favicon-32x32.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Materialize CSS -->
    <link rel="stylesheet" type="text/css" href="azzam/app-assets/vendors/data-tables/css/jquery.dataTables.min.css">
    
    <!-- Custom Modern CSS -->
    <link rel="stylesheet" type="text/css" href="azzam/app-assets/css/custom/modern-ui.css">
    
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        /* Modern Header - Simplified */
        .modern-header {
            background: white;
            border-bottom: 1px solid #e0e0e0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 0;
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 70px;
        }
        
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #2d3748;
            font-weight: 700;
            font-size: 1.25rem;
        }
        
        /* Clean Brand Styling */
        .brand-icon {
            width: 36px;
            height: 36px;
            background: #667eea;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }
        
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .nav-btn {
            padding: 8px 16px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
        }
        
        .nav-btn:hover {
            background: #5a67d8;
            transform: translateY(-1px);
        }
        
        /* Main Content */
        .main-content {
            margin-top: 70px;
            min-height: calc(100vh - 70px);
            padding: 20px 0;
        }
        
        .container {
            max-width: 100%;
            margin: 0;
            width: 100%;
        }
        
        /* Clean Simple Cards Layout */
        .simple-cards {
            display: grid;
            grid-template-columns: 70% 30%;
            gap: 10px;
            width: 100%;
            margin: 0;
            padding: 0 10px;
        }
        
        .simple-card {
            background: #f8f8f8;
            border-radius: 15px;
            padding: 25px;
            cursor: default;
            transition: all 0.2s ease;
            min-height: 480px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
        }
        
        .simple-card:hover {
            background: #f0f0f0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .card-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            margin: 0;
            line-height: 1.2;
        }
        
        /* Form Card Styles */
        .form-card {
            background: #f8f8f8;
        }
        
        .form-card:hover {
            background: #f0f0f0;
        }
        
        .form-inputs {
            flex: 1;
            overflow-y: auto;
        }
        
        /* Empty State Styling */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            text-align: center;
            min-height: 300px;
        }
        
        .empty-state i {
            opacity: 0.5;
        }
        
        /* Tambah Button Styling */
        .tambah-btn:hover {
            background: #45a049 !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
        }
        
        /* Hide modal wrapper for embedded form */
        .form-inputs .modal {
            display: block !important;
            position: static !important;
            background: transparent !important;
            box-shadow: none !important;
            margin: 0 !important;
            max-height: none !important;
            width: 100% !important;
        }
        
        .form-inputs .modern-form {
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
            backdrop-filter: none !important;
        }
        
        .form-inputs .form-header {
            display: none !important;
        }
        
        .form-inputs .form-footer {
            background: transparent !important;
            border: none !important;
            border-radius: 0 !important;
        }
        
        /* Table Card Styles */
        .table-card {
            background: #f8f8f8;
        }
        
        .table-card:hover {
            background: #f0f0f0;
        }
        
        .table-content {
            flex: 1;
            overflow-y: auto;
        }
        
        .table-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .table-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
            margin-bottom: 8px;
        }
        
        .table-item:hover {
            background: #f8f9fa;
            border-color: #2196F3;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .table-info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }
        
        .table-info i {
            color: #2196F3;
            font-size: 20px;
        }
        
        .table-name {
            font-weight: 500;
            color: #333;
            font-size: 16px;
        }
        
        .table-actions {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .edit-btn {
            background: #4CAF50;
            color: white;
        }
        
        .edit-btn:hover {
            background: #45a049;
            transform: scale(1.1);
        }
        
        .delete-btn {
            background: #f44336;
            color: white;
        }
        
        .delete-btn:hover {
            background: #da190b;
            transform: scale(1.1);
        }
        
        .view-btn {
            background: #2196F3;
            color: white;
        }
        
        .view-btn:hover {
            background: #1976D2;
            transform: scale(1.1);
        }
        
        .action-btn i {
            font-size: 16px;
        }
        
        .no-tables {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .no-tables i {
            font-size: 48px;
            margin-bottom: 16px;
            color: #ccc;
        }
        
        .no-tables p {
            margin: 0 0 12px 0;
            font-size: 18px;
        }
        
        .create-hint {
            margin-top: 8px;
        }
        
        .create-hint small {
            color: #999;
            font-size: 14px;
        }
        
        /* Enhanced Responsive Design */
        @media (max-width: 480px) {
            .header-content {
                padding: 0 12px;
            }
            
            .brand {
                font-size: 1rem;
            }
            
            .brand-icon {
                width: 32px;
                height: 32px;
                font-size: 16px;
            }
            
            .nav-actions {
                gap: 8px;
            }
            
            .nav-btn {
                padding: 6px 12px;
                font-size: 12px;
            }
            
            .simple-cards {
                grid-template-columns: 1fr;
                gap: 8px;
                padding: 0 5px;
            }
            
            .simple-card {
                padding: 15px;
                min-height: 350px;
                border-radius: 15px;
            }
            
            .card-title {
                font-size: 1.3rem;
            }
            
            .main-content {
                padding: 8px 0;
            }
            
            .table-item {
                padding: 10px;
                flex-direction: column;
                gap: 10px;
            }
            
            .table-info {
                justify-content: center;
                text-align: center;
            }
            
            .table-actions {
                justify-content: center;
            }
            
            .table-name {
                font-size: 14px;
            }
            
            .tambah-btn {
                padding: 6px 12px !important;
                font-size: 12px !important;
            }
            
            .empty-state {
                padding: 20px 10px !important;
                min-height: 200px !important;
            }
        }
        
        @media (min-width: 481px) and (max-width: 768px) {
            .header-content {
                padding: 0 16px;
            }
            
            .brand {
                font-size: 1.1rem;
            }
            
            .simple-cards {
                grid-template-columns: 1fr;
                gap: 10px;
                padding: 0 5px;
            }
            
            .simple-card {
                padding: 20px;
                min-height: 400px;
            }
            
            .card-title {
                font-size: 1.5rem;
            }
            
            .main-content {
                padding: 10px 0;
            }
            
            .table-item {
                padding: 12px;
                flex-direction: row;
                gap: 12px;
            }
            
            .table-info {
                justify-content: flex-start;
            }
            
            .table-actions {
                justify-content: flex-end;
            }
        }
        
        @media (min-width: 769px) and (max-width: 1024px) {
            .simple-cards {
                grid-template-columns: 65% 35%;
                gap: 15px;
                padding: 0 10px;
            }
            
            .simple-card {
                padding: 25px;
                min-height: 450px;
            }
        }
        
        /* Edit Mode Responsive Styles */
        .edit-form-container {
            padding: 0;
        }
        
        .field-header {
            display: grid;
            grid-template-columns: 2fr 2fr 1.5fr 1fr 1.5fr auto;
            gap: clamp(8px, 2vw, 16px);
            background: #333;
            color: white;
            padding: clamp(8px, 2vw, 12px);
            font-size: clamp(10px, 2vw, 12px);
            font-weight: 600;
            border-radius: 6px 6px 0 0;
            overflow-x: auto;
            min-width: 600px;
        }
        
        .field-row {
            display: grid;
            grid-template-columns: 2fr 2fr 1.5fr 1fr 1.5fr auto;
            gap: clamp(8px, 2vw, 16px);
            padding: clamp(6px, 1.5vw, 10px);
            background: white;
            border-bottom: 1px solid #eee;
            align-items: center;
            overflow-x: auto;
            min-width: 600px;
        }
        
        .field-row.primary-key {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
        }
        
        .field-input {
            padding: clamp(4px, 1vw, 8px) clamp(6px, 1.5vw, 10px);
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: clamp(11px, 2vw, 13px);
            width: 100%;
            box-sizing: border-box;
            min-height: 32px;
        }
        
        .field-input:focus {
            outline: none;
            border-color: #2196F3;
            box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.2);
        }
        
        /* Mobile edit form adjustments */
        @media (max-width: 768px) {
            .field-header {
                display: none;
            }
            
            .field-row {
                grid-template-columns: 1fr;
                gap: 8px;
                min-width: auto;
                padding: 12px;
                margin-bottom: 16px;
                border-radius: 8px;
                border: 1px solid #ddd;
            }
            
            .field-row > div {
                margin-bottom: 8px;
            }
            
            .field-row > div::before {
                content: attr(data-label) ": ";
                font-weight: 600;
                font-size: 0.75rem;
                color: #2196F3;
                display: block;
                margin-bottom: 4px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .field-row > div:last-child::before {
                display: none;
            }
        }
        
        .remove-btn {
            background: #f44336;
            color: white;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .remove-btn:hover {
            background: #da190b;
        }
        
        .remove-btn i {
            font-size: 14px;
        }
        
        .lock-icon {
            color: #666;
            font-size: 18px;
        }
        
        .add-field-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            margin: 15px auto;
            display: block;
            font-size: 14px;
        }
        
        .add-field-btn:hover {
            background: #45a049;
        }
        
        .add-field-btn i {
            font-size: 16px;
            vertical-align: middle;
            margin-right: 5px;
        }
        
        /* Modal Styles */
        .modal {
            max-width: 90% !important;
            width: 800px !important;
            max-height: 90% !important;
            border-radius: 20px !important;
            overflow: visible !important;
        }
        
        .modal-content {
            padding: 0 !important;
        }
        
        .modal-footer {
            border-radius: 0 0 20px 20px !important;
        }
    </style>
</head>

<body>
    <!-- Modern Header -->
    <header class="modern-header">
        <div class="header-content">
            <a href="index.php" class="brand">
                <div class="brand-icon">
                    <i class="material-icons">storage</i>
                </div>
                <span>Azzam Generator</span>
            </a>
            
            <div class="nav-actions">
                <a href="#form" class="nav-btn modal-trigger">
                    <i class="material-icons" style="font-size: 18px;">add</i>
                    Create Table
                </a>
                <a href="index.php" class="nav-btn">
                    <i class="material-icons" style="font-size: 18px;">exit_to_app</i>
                    Exit
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">