<?php slot::start('crumbs') ?>
    / docs / <a href="<?= url::base() . url::current() ?>"><?= $doc_path ?></a>
<?php slot::end() ?>

<div class="doc_content">
<?= $doc_content ?>
</div>
