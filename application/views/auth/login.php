<?php slot::start('crumbs') ?>
    / <span>login</span>
<?php slot::end() ?>

<?php
    echo form::build('login', array('class'=>'login'), @$errors, array(
        form::field('hidden', 'jump', ''),
        form::fieldset('login details', array('class'=>'login'), array(
            form::field('input',    'login_name',       'Login name'),
            form::field('password', 'password',         'Password'),
            form::field('submit',   'login',  null, array('value'=>'login'))
        ))
    ));
?>