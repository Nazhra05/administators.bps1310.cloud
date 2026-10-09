<?php
declare(strict_types=1);

$successMsg = flash('success');
$errorMsg = flash('error');
$warningMsg = flash('warning');
$infoMsg = flash('info');
?>

<?php if ($successMsg): ?>
    <div class="alert alert-success alert-auto-dismiss" role="alert">
        <span>✅ <?= e($successMsg) ?></span>
        <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
    </div>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div class="alert alert-error" role="alert">
        <span>⚠️ <?= e($errorMsg) ?></span>
        <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
    </div>
<?php endif; ?>

<?php if ($warningMsg): ?>
    <div class="alert alert-warning" role="alert">
        <span>🔔 <?= e($warningMsg) ?></span>
        <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
    </div>
<?php endif; ?>

<?php if ($infoMsg): ?>
    <div class="alert alert-info alert-auto-dismiss" role="alert">
        <span>ℹ️ <?= e($infoMsg) ?></span>
        <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
    </div>
<?php endif; ?>
