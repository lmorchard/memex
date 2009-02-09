<?php slot::start('crumbs') ?>
    / <span>register</span>
<?php slot::end() ?>

<?php
    echo form::build('register', array('class'=>'signup'), @$errors, array(
        form::fieldset('login details', array('class'=>'login'), array(
            form::field('input',    'login_name',       'Login name'),
            form::field('input',    'email',            'Email address'),
            form::field('password', 'password',         'Password'),
            form::field('password', 'password_confirm', 'Password (confirm)'),
        )),
        form::fieldset('profile details', array(), array(
            form::field('input',    'screen_name',  'Screen name'),
            form::field('input',    'full_name',    'Full name'),
            form::field('textarea', 'bio',          'Bio / About you'),
        )),
        form::fieldset('finish', array(), array(
            form::captcha('captcha', 'Captcha'),
            form::field('submit', 'register', 'Register', array('value'=>'Register'))
        ))
    ));
?>
