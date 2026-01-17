<?php

/**
 * @var CodeIgniter\Pager\PagerRenderer $pager
 */
$pager->setSurroundCount(2);
?>
<nav aria-label="<?= esc(lang('Pager.pageNavigation')) ?>">
    <ul class="pagination justify-content-end">
        <?php if ($pager->hasPrevious()) : ?>
            <li class="page-item">
                <a class="page-link" href="<?= esc($pager->getFirst(), 'attr') ?>" aria-label="<?= esc(lang('Pager.first'), 'attr') ?>">
                    <span aria-hidden="true"><?= esc(lang('Pager.first')) ?></span>
                </a>
            </li>
            <li class="page-item">
                <a class="page-link" href="<?= esc($pager->getPreviousPage(), 'attr') ?>" aria-label="<?= esc(lang('Pager.previous'), 'attr') ?>">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        <?php endif ?>

        <?php foreach ($pager->links() as $link) : ?>
            <li class="page-item<?= $link['active'] ? ' active' : '' ?>">
                <a class="page-link" href="<?= esc($link['uri'], 'attr') ?>">
                    <?= esc($link['title']) ?>
                </a>
            </li>
        <?php endforeach ?>

        <?php if ($pager->hasNext()) : ?>
            <li class="page-item">
                <a class="page-link" href="<?= esc($pager->getNextPage(), 'attr') ?>" aria-label="<?= esc(lang('Pager.next'), 'attr') ?>">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
            <li class="page-item">
                <a class="page-link" href="<?= esc($pager->getLast(), 'attr') ?>" aria-label="<?= esc(lang('Pager.last'), 'attr') ?>">
                    <span aria-hidden="true"><?= esc(lang('Pager.last')) ?></span>
                </a>
            </li>
        <?php endif ?>
    </ul>
</nav>