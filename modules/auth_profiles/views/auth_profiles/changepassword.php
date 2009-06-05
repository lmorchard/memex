<?php if (!empty($password_changed)): ?>
    <p>Password changed. <?=html::anchor('login', 'Login again?')?></p>
<?php elseif (!empty($invalid_reset_token)): ?>
    <p>Invalid password reset token. <?=html::anchor('forgotpassword', 'Try again?')?></p>
<?php else: ?>
    <?php form::$errors = @$form_errors ?>
    <?= 
    form::build(null, array('class'=>'changepassword'), array(
        form::field('hidden', 'password_reset_token'),
        form::fieldset('change password', null, array(
            form::field('password', 'old_password', 'old password'),
            form::field('password', 'new_password', 'new password'),
            form::field('password', 'new_password_confirm', 'new password (confirm)'),
            form::field('submit', 'change', null, array('value'=>'change password'))
        ))
    )) 
    ?>
<?php endif ?>
