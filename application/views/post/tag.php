<?php slot::start('head') ?>
    <?php
        $feed_url = url::base() . 'feeds/atom/'
        /*$url(
            array(
                'format' => 'atom', 
                'screen_name' => $screen_name,
                'tags' => ($tags) ? join(' ', $tags) : ''
            ), 
            ($tags) ? 'feeds_post_tag' : 'feeds_site_home'
        );*/
    ?>
    <link rel="alternate" type="application/atom+xml" title="Atom feed" href="<?= out::H($feed_url) ?>"> 
<?php slot::end() ?>

<?php slot::start('crumbs') ?>
    <?php if (empty($tags)): ?>
    / <a href="<?= url::base() ?>">recent</a>
    <?php else: ?>
        / tag / <a href="<?= url::current() ?>"><?= join(' + ', $tags) ?></a>
    <?php endif ?>
<?php slot::end() ?>

<?php slot::start('infobar') ?>
    <?php if (empty($tags)): ?>
        Recent items (<?= $posts_count ?>)
    <?php else: ?>
        Recent items tagged <?php foreach ($tags as $tag): ?>
            <a href="<?= url::base() . 'tag/' . out::U($tag) ?>"><?= out::H($tag) ?></a>
        <?php endforeach ?> (<?= $posts_count ?>)
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

    <!-- $paginationControl(
        $paginator, 'Sliding', 'pagination_mini_control.phtml'
    ); -->

    <ul class="posts">
        <?php foreach ($posts as $post): ?>
            <?php View::factory('post/post', array(
                'profile'      => $profile,
                'auth_profile' => $auth_profile,
                'post'         => $post
            ))->render(true) ?>
        <?php endforeach; ?>
    </ul>

    <!-- $paginationControl(
        $paginator, 'Sliding', 'pagination_control.phtml'
    ); -->

<?php endif ?>
