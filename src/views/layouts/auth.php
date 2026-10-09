<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Autentikasi') ?> - BPS Kabupaten Solok Selatan</title>
    <link rel="stylesheet" href="<?= e(asset('assets/dashboard.css')) ?>">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
            background: #f4f8fc;
        }
        .auth-card {
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 10px 25px -5px rgba(0, 102, 179, 0.1);
            border: 1px solid #e1edf7;
            padding: 2.25rem;
            width: 100%;
            max-width: 440px;
        }
        .auth-header {
            text-align: center;
            margin-bottom: 1.75rem;
        }
        .auth-logo {
            width: 56px;
            height: 56px;
            object-fit: contain;
            margin-bottom: 0.75rem;
        }
        .auth-header h1 {
            font-size: 1.5rem;
            color: #005a9c;
            margin-bottom: 0.25rem;
        }
        .auth-header p {
            color: #64748b;
            font-size: 0.875rem;
        }
    </style>
    <?php if (!empty($extraCss)): ?>
        <style><?= $extraCss ?></style>
    <?php endif; ?>
</head>
<body>
    <div class="auth-card">
        <div class="auth-header">
            <img src="<?= e(asset('assets/logo_bps.jpg')) ?>" alt="Logo BPS" class="auth-logo">
            <h1><?= e($headerTitle ?? 'Portal Admin') ?></h1>
            <p><?= e($headerSubtitle ?? 'BPS Kabupaten Solok Selatan') ?></p>
        </div>

        <?php include __DIR__ . '/../partials/flash.php'; ?>

        <?= $content ?? '' ?>
    </div>

    <?php if (!empty($extraJs)): ?>
        <script nonce="<?= e(csp_nonce()) ?>"><?= $extraJs ?></script>
    <?php endif; ?>
</body>
</html>
