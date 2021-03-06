<?php if (!empty($password_reset_token_set)): ?>
    <p>
        Check the email address registered for this account for a link to change 
        your password.
    </p>
<?php else: ?>
    <?php form::$errors = @$form_errors ?>
    <?= 
    form::build(null, array('class'=>'forgotpassword'), array(
        form::fieldset('forgot password', null, array(
            "<p>Supply either of these pieces of information to recover your password:</p>",
            form::field('input', 'login_name', 'login name'),
            form::field('input', 'email', 'email address'),
            form::field('submit', 'forgot', null, array('value'=>'forgot password'))
        ))
    )) 
    ?>
<?php endif ?>
