<?php
    $screen_name = $auth_profile['screen_name'];
?>
<?php slot::start('crumbs') ?>
    / profiles 
    / <a href="<?= url::base() . 'people/' . out::U($screen_name) ?>"><?= out::H($screen_name) ?></a>
    / <a href="<?= url::base() . 'profiles/' . out::U($screen_name) . '/settings' ?>">settings</a>
<?php slot::end() ?>

<?php slot::start('infobar') ?>
    profile settings for <a href=""><?= out::H($auth_profile['screen_name']) ?></a>
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
            <h2><?= out::H($section['title']) ?></h2>
            <dl>
                <?php foreach ($section['items'] as $item): ?>
                    <dt><a href="<?= url::base() . out::H($item['url']) ?>"><?= out::H($item['title']) ?></a></dt>
                    <dd><?= $item['description'] ?></dd>
                <?php endforeach ?>
            </dl>
        </li>
    <?php endforeach ?>
</ul>
