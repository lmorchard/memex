<?php slot::start('crumbs') ?>
/ installation
<?php slot::end() ?>

<div class="doc_content">

    <?php if (empty($config_not_writable)): ?>
        <div>
            <h2>Hello world!</h2>
            <p>Memex has not yet been configured and fully installed. This 
            page will help you get up and running.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <ul class="errors">
                <?php foreach ($errors as $error): ?>
                    <li><p><?=$error?></p></li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>

        <?= 
        form::build(url::current(), array(), array(
            form::fieldset('Memex database details', array(), array(
                '<p>'. join("\n", array(
                    'These fields describe the MySQL user account and database name '.
                    'to be used for Memex data.'
                )).'</p>',
                form::field('input',  'host',      'Host'),
                form::field('input',  'port',      'Port'),
                form::field('input',  'database',  'Database name'),
                form::field('input',  'username',  'User name'),
                form::field('input',  'password',  'Password'),
            )),
            form::fieldset('MySQL admin user', array(), array(
                '<p>'. join("\n", array(
                    'This should be a user with the ability to create the above '.
                    'database table and, if necessary, the user account for this '.
                    'application.'
                )).'</p>',
                form::field('input',  'admin_username',  'User name'),
                form::field('input',  'admin_password',  'Password'),
            )),
            form::fieldset('Site details', array(), array(
                '<p>'. join("\n", array(
                    'You can customize some basic aspects of Memex with these fields, '.
                    'though the defaults listed here are likely to work fine.'
                )).'</p>',
                form::field('input',  'site_title', 'Site title'),
                form::field('input',  'base_url',   'Base URL')
            )),
            form::fieldset('Finished', array(), array(
                form::field('submit', 'configure', null, array('value'=>'configure')),
            ))
        ))
        ?>

    <?php else: ?>
        <div>
            <h2>Almost there...</h2>
            <p>The database was created, but a config file was unable to be 
            written.  You're going to need to create this file by hand:</p>
        </div>
        <?=
        form::build(url::current(), array(), array(
            form::fieldset('Memex config', array(), array(
                form::field('input',    'filename', 'Filename', array('value'=>$config_fn)),
                form::field('textarea', 'source',   'Source', array('value'=>$config_src))
            ))
        ))
        ?>
        <div>
            <p>
            Once you've created this file, continue on to <?=html::anchor('install/step2', 'step 2')?>.
            </p>
        </div>

    <?php endif ?>
</div>
