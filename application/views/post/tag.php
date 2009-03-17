<?php slot::start('head') ?>
    <?php
        $feed_url = url::base() . 'feeds/atom/tag' . 
            ( !empty($tags) ? '/' . out::U(join(' ', $tags)) : '' ) ; 
    ?>
    <link rel="alternate" type="application/atom+xml" title="Atom feed" href="<?= out::H($feed_url) ?>"> 
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
            <a href="<?= url::base() . 'tag/' . out::U($tag) ?>"><?= out::H($tag) ?></a>
        <?php endforeach ?> (<?= $pagination['total'] ?>)
    <?php endif ?>
<?php slot::end() ?>

<?php if (empty($posts)): ?>

    <div class="message">
        <?php if (!$auth_profile || $screen_name != $auth_profile['screen_name']): ?>
            <h2>No items found.</h2>
        <?php else: ?>
            <?php if (!$tags): ?>
                <h2>You have no bookmarks, yet.</h2>
                <p>
                    Why not start by <a href="<?= url::base() . 'save' ?>">saving a new bookmark</a>?
                </p>
            <?php else: ?>
                <h2>You have no bookmarks tagged <?php foreach ($tags as $tag): ?><?= out::H($tag) ?><?php endforeach ?>.</h2>
            <?php endif ?>
        <?php endif ?>
    </div>

<?php else: ?>

    <?php View::factory('pagination_mini_control', $pagination)->render(true) ?>

    <ul class="posts">
        <?php foreach ($posts as $post): ?>
            <?php View::factory('post/post', array(
                'auth_profile' => $auth_profile,
                'post'         => $post
            ))->render(true) ?>
        <?php endforeach; ?>
    </ul>

    <?php View::factory('pagination_control', $pagination)->render(true) ?>

<?php endif ?>
