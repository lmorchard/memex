<?php
    $profile_home_url = url::base() . 'people/' . out::U($screen_name);
?>

<?php slot::start('crumbs') ?>
    / people / <a href="<?= $profile_home_url ?>"><?= out::H($auth_profile['screen_name']) ?></a>
<?php slot::end() ?>

<?php
echo form::build('save', array('class'=>'save'), @$errors, array(
    form::field('hidden', 'jump', ''),
    form::fieldset($submethod . ' post', array('class'=>'save'), array_merge( 
        array(
            form::field('input',    'url',     'url'),
        ),
        (!$have_url) ? array() : array(
            form::field('input',    'title',   'title'),
            form::field('textarea', 'notes',   'notes'),
            form::field('input',    'tags',    'tags'),
            form::field('checkbox', 'private', 'do not share', array('value'=>'private')),
        ),
        array(
            form::field('submit',   'save',    null, array('value'=>'save')),
            form::field('submit',   'cancel',  null, array('value'=>'cancel'))
        )
    ))
));
?>
