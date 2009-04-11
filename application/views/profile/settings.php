<?php
    $screen_name = $auth_profile['screen_name'];
?>
<?php slot::start('crumbs') ?>
    / profiles 
    / <a href="<?= url::base() . 'people/' . rawurlencode($screen_name) ?>"><?= html::specialchars($screen_name) ?></a>
    / <a href="<?= url::base() . 'profiles/' . rawurlencode($screen_name) . '/settings' ?>">settings</a>
<?php slot::end() ?>

<?php slot::start('infobar') ?>
    profile settings for <a href=""><?= html::specialchars($auth_profile['screen_name']) ?></a>
<?php slot::end() ?>

<?php
usort($sections, create_function(
    '$b,$a', '
        $a = @$a["priority"];
        $b = @$b["priority"];
        return ($a==$b)?0:(($a<$b)?-1:1);
    '
))
?>
<ul class="sections">
    <?php foreach ($sections as $section): ?>
        <li class="section">
            <h2><?= html::specialchars($section['title']) ?></h2>
            <dl>
                <?php foreach ($section['items'] as $item): ?>
                    <dt><a href="<?= url::base() . html::specialchars($item['url']) ?>"><?= html::specialchars($item['title']) ?></a></dt>
                    <dd><?= $item['description'] ?></dd>
                <?php endforeach ?>
            </dl>
        </li>
    <?php endforeach ?>
</ul>
