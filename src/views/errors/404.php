<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? '404 - Halaman Tidak Ditemukan') ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 1.5rem;
        }
        .card {
            background: #ffffff;
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.08);
            max-width: 480px;
            text-align: center;
        }
        .badge {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        h1 {
            font-size: 1.75rem;
            margin: 0 0 0.75rem;
            color: #0f172a;
        }
        p {
            margin: 0 0 1.5rem;
            color: #64748b;
            line-height: 1.5;
        }
        .btn {
            display: inline-block;
            padding: 0.625rem 1.25rem;
            background: #0284c7;
            color: #ffffff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.15s ease;
        }
        .btn:hover {
            background: #0369a1;
        }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge"><?= e($badge ?? '404 Error') ?></span>
        <h1><?= e($title ?? 'Halaman Tidak Ditemukan') ?></h1>
        <p><?= e($message ?? 'Halaman yang Anda tuju tidak ditemukan atau telah dipindahkan.') ?></p>
        <a href="<?= e(url('/')) ?>" class="btn">Kembali ke Beranda</a>
    </div>
</body>
</html>
