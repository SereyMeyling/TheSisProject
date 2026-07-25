<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden - Access Denied</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Kantumruy+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <style>
        :root {
            --bg-color: #f7f5f0;
            --card-bg: #ffffff;
            --text-main: #2b2d42;
            --text-muted: #6c757d;
            --accent-red: #dc3545;
            --accent-soft-red: #fde8e8;
            --primary-btn: #198754;
            --primary-btn-hover: #146c43;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Inter', 'Kantumruy Pro', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .error-card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 40px 30px;
            max-width: 520px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.04);
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .icon-badge {
            width: 90px;
            height: 90px;
            background: var(--accent-soft-red);
            color: var(--accent-red);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            margin-bottom: 20px;
        }

        .error-code {
            font-size: 72px;
            font-weight: 800;
            line-height: 1;
            color: var(--accent-red);
            margin-bottom: 8px;
            letter-spacing: -2px;
        }

        .error-subtitle {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 12px;
        }

        .error-message {
            font-size: 15px;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .btn-action-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-dashboard {
            background-color: var(--primary-btn);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-dashboard:hover {
            background-color: var(--primary-btn-hover);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .btn-back {
            background-color: #f1f3f5;
            color: #495057;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background-color: #e9ecef;
            color: #212529;
        }

        .hospital-branding {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f1f3f5;
            font-size: 13px;
            color: #adb5bd;
        }
    </style>
</head>
<body>

    <div class="error-card">
        <!-- Shield Lock Icon -->
        <div class="icon-badge">
            <i class="fas fa-user-shield"></i>
        </div>

        <!-- 403 Header Code -->
        <div class="error-code">403</div>

        <!-- Subtitle & Message -->
        <div class="error-subtitle">Access Denied / ការចូលប្រើប្រាស់ត្រូវបានបដិសេធ</div>
        <p class="error-message">
            You do not have permission to access this page.<br>
            <span class="small">អ្នកគ្មានសិទ្ធិចូលប្រើប្រាស់ទំព័រនេះទេ។ សូមទាក់ទងអ្នកគ្រប់គ្រងប្រព័ន្ធ ប្រសិនបើអ្នកត្រូវការសិទ្ធិចូលប្រើ។</span>
        </p>

        <!-- Navigation Buttons -->
        <div class="btn-action-group">
            <a href="javascript:history.back()" class="btn-back">
                <i class="fas fa-arrow-left"></i> ត្រឡប់ក្រោយ (Go Back)
            </a>
            <a href="{{ route('home') }}" class="btn-dashboard">
                <i class="fas fa-home"></i> ផ្ទាំងគ្រប់គ្រង (Dashboard)
            </a>
        </div>

        <!-- System Branding -->
        <div class="hospital-branding">
            Hospital Management System &bull; Access Control
        </div>
    </div>

</body>
</html>
