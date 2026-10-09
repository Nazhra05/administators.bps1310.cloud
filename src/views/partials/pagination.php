<?php
/**
 * Reusable Pagination Partial
 * 
 * @var array{
 *     page: int,
 *     per_page: int,
 *     total_items: int,
 *     total_pages: int,
 *     from: int,
 *     to: int
 * } $pagination
 * @var string $baseUrl
 */

$page = (int)($pagination['page'] ?? 1);
$perPage = (int)($pagination['per_page'] ?? 10);
$totalItems = (int)($pagination['total_items'] ?? 0);
$totalPages = (int)($pagination['total_pages'] ?? 1);
$from = (int)($pagination['from'] ?? 0);
$to = (int)($pagination['to'] ?? 0);
$baseUrl = $baseUrl ?? '';

// Helper to construct query link preserving all existing GET params
$buildUrl = function(int $targetPage, ?int $targetPerPage = null) use ($baseUrl): string {
    $params = $_GET;
    $params['page'] = $targetPage;
    if ($targetPerPage !== null) {
        $params['per_page'] = $targetPerPage;
    }
    return e(url($baseUrl . '?' . http_build_query($params)));
};
?>

<div class="pagination-container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-top: 20px; padding-top: 16px; border-top: 1px solid #f1f5f9; font-size: 0.875rem; color: #64748b;">
    <!-- Items counter & Per-Page Selector -->
    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
        <div>
            <?php if ($totalItems > 0): ?>
                Menampilkan <strong style="color: #0f172a;"><?= $from ?></strong> - <strong style="color: #0f172a;"><?= $to ?></strong> dari <strong style="color: #0f172a;"><?= $totalItems ?></strong> data
            <?php else: ?>
                Tidak ada data untuk ditampilkan
            <?php endif; ?>
        </div>

        <form method="GET" action="<?= e(url($baseUrl)) ?>" style="display: inline-flex; align-items: center; gap: 6px; margin: 0;">
            <?php foreach ($_GET as $k => $v): ?>
                <?php if ($k !== 'per_page' && $k !== 'page' && !is_array($v)): ?>
                    <input type="hidden" name="<?= e($k) ?>" value="<?= e((string)$v) ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <label for="per_page_select" style="font-size: 0.825rem; color: #475569;">Per halaman:</label>
            <select id="per_page_select" name="per_page" onchange="this.form.submit()"
                    style="padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.825rem; background: white; cursor: pointer; color: #334155;">
                <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>10</option>
                <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25</option>
                <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50</option>
            </select>
        </form>
    </div>

    <!-- Navigation Buttons -->
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Navigasi Halaman" style="display: flex; align-items: center; gap: 4px;">
            <!-- First Page -->
            <?php if ($page > 1): ?>
                <a href="<?= $buildUrl(1) ?>" title="Halaman Pertama"
                   style="display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 32px; padding: 0 8px; border: 1px solid #cbd5e1; border-radius: 6px; color: #334155; text-decoration: none; font-weight: 500; background: white;">
                    &laquo;
                </a>
                <a href="<?= $buildUrl($page - 1) ?>" title="Sebelumnya"
                   style="display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 32px; padding: 0 8px; border: 1px solid #cbd5e1; border-radius: 6px; color: #334155; text-decoration: none; font-weight: 500; background: white;">
                    &lsaquo;
                </a>
            <?php endif; ?>

            <!-- Page Numbers (Sliding Window of 5) -->
            <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                if ($endPage - $startPage < 4) {
                    if ($startPage === 1) {
                        $endPage = min($totalPages, $startPage + 4);
                    } elseif ($endPage === $totalPages) {
                        $startPage = max(1, $endPage - 4);
                    }
                }
            ?>

            <?php if ($startPage > 1): ?>
                <span style="padding: 0 4px; color: #94a3b8;">...</span>
            <?php endif; ?>

            <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                <?php if ($p === $page): ?>
                    <span aria-current="page"
                          style="display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 32px; padding: 0 8px; border: 1px solid #2563eb; border-radius: 6px; background: #2563eb; color: white; font-weight: 700;">
                        <?= $p ?>
                    </span>
                <?php else: ?>
                    <a href="<?= $buildUrl($p) ?>"
                       style="display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 32px; padding: 0 8px; border: 1px solid #cbd5e1; border-radius: 6px; color: #334155; text-decoration: none; font-weight: 500; background: white;">
                        <?= $p ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($endPage < $totalPages): ?>
                <span style="padding: 0 4px; color: #94a3b8;">...</span>
            <?php endif; ?>

            <!-- Next & Last Page -->
            <?php if ($page < $totalPages): ?>
                <a href="<?= $buildUrl($page + 1) ?>" title="Selanjutnya"
                   style="display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 32px; padding: 0 8px; border: 1px solid #cbd5e1; border-radius: 6px; color: #334155; text-decoration: none; font-weight: 500; background: white;">
                    &rsaquo;
                </a>
                <a href="<?= $buildUrl($totalPages) ?>" title="Halaman Terakhir"
                   style="display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 32px; padding: 0 8px; border: 1px solid #cbd5e1; border-radius: 6px; color: #334155; text-decoration: none; font-weight: 500; background: white;">
                    &raquo;
                </a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
