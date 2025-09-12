<div class="container py-4">
    <?php if ($m = flash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= h($m) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($m = flash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= h($m) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
</div>