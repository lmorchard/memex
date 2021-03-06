<?php
    $auth_screen_name = AuthProfiles::get_profile('screen_name');
    $u_auth_screen_name = rawurlencode($auth_screen_name);
?>
<?php slot::start('head') ?>
    <?php
        $feed_url = url::base() . 'feeds/atom/tag' . 
            ( !empty($tags) ? '/' . rawurlencode(join(' ', $tags)) : '' ) ; 
    ?>
    <link rel="alternate" type="application/atom+xml" title="Atom feed" href="<?= html::specialchars($feed_url) ?>"> 
<?php slot::end() ?>

<?php slot::start('crumbs') ?>
    <?php if (empty($tags)): ?>
    / <a href="<?= url::base() ?>">recent</a>
    <?php else: ?>
        / tag / <a href="<?= url::full_current() ?>"><?= join(' + ', $tags) ?></a>
    <?php endif ?>
<?php slot::end() ?>

<?php slot::start('infobar') ?>
    <?php if (empty($tags)): ?>
        Recent items (<?= $pagination['total'] ?>)
    <?php else: ?>
        Recent items tagged <?php foreach ($tags as $tag): ?>
            <a href="<?= url::base() . 'tag/' . rawurlencode($tag) ?>"><?= html::specialchars($tag) ?></a>
        <?php endforeach ?> (<?= $pagination['total'] ?>)
    <?php endif ?>
<?php slot::end() ?>

<?php if (empty($posts)): ?>

    <div class="message">
        <?php if ($screen_name != $auth_screen_name): ?>
            <h2>No items found.</h2>
        <?php else: ?>
            <?php if (!$tags): ?>
                <h2>You have no bookmarks, yet.</h2>
                <p>
                    Why not start by <a href="<?= url::base() . 'save' ?>">saving a new bookmark</a>?
                </p>
            <?php else: ?>
                <h2>You have no bookmarks tagged <?php foreach ($tags as $tag): ?><?= html::specialchars($tag) ?><?php endforeach ?>.</h2>
            <?php endif ?>
        <?php endif ?>
    </div>

<?php else: ?>

    <?php View::factory('pagination_mini_control', $pagination)->render(true) ?>

    <ul class="posts">
        <?php foreach ($posts as $post): ?>
            <?php View::factory('post/post', array(
                'post' => $post
            ))->render(true) ?>
        <?php endforeach; ?>
    </ul>

    <?php View::factory('pagination_control', $pagination)->render(true) ?>

<?php endif ?>
