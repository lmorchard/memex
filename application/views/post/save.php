<?php
    $screen_name = AuthProfiles::get_profile('screen_name');
    $profile_home_url = url::base() . 'people/' . rawurlencode($screen_name);
?>

<?php slot::start('crumbs') ?>
    / people / <a href="<?= $profile_home_url ?>"><?= html::specialchars($screen_name) ?></a>
<?php slot::end() ?>

<?=slot::get('form_before')?>
<?php
echo form::build('save', array('class'=>'save'), array(
    slot::get('form_start'),
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
    )),
    slot::get('form_end')
));
?>
<?=slot::get('form_after')?>
