<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Dashboard') ?> - BPS Kabupaten Solok Selatan</title>
    <link rel="stylesheet" href="<?= e(asset('assets/dashboard.css')) ?>">
    <?php if (!empty($extraCss)): ?>
        <style><?= $extraCss ?></style>
    <?php endif; ?>
</head>
<body>
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <main class="main" id="mainContent">
        <?php include __DIR__ . '/../partials/navbar.php'; ?>
        <?php include __DIR__ . '/../partials/flash.php'; ?>

        <div class="content-body">
            <?= $content ?? '' ?>
        </div>
    </main>

    <script src="<?= e(asset('assets/dashboard.js')) ?>"></script>
    <?php if (!empty($extraJs)): ?>
        <script><?= $extraJs ?></script>
    <?php endif; ?>
</body>
</html>
