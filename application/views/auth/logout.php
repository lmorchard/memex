<?php
form::$data   = $form_data;
form::$errors = $form_errors;

slot::set('head_title', ' / logout');
?>

<?php slot::start('crumbs') ?> / <span>logout</span> <?php slot::end() ?>

<p>
Logged out. <a href="<?= url::base() . 'login' ?>">Login again?</a>
</p>
