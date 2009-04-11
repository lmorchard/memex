<?php
    $profile_home_url = url::base() . 'people/' . rawurlencode($screen_name);
?>

<?php slot::start('head') ?>
    <?php
        $feed_url = url::base() . 'feeds/atom/people/' . rawurlencode($screen_name) . 
            ( !empty($tags) ? '/' . rawurlencode(join(' ', $tags)) : '' ) ; 
    ?>
    <link rel="alternate" type="application/atom+xml" title="Atom feed" href="<?= $feed_url ?>"> 
<?php slot::end() ?>

<?php slot::start('crumbs') ?>
    / people / <a href="<?= $profile_home_url ?>"><?= html::specialchars($screen_name) ?></a>
    <?php if ($tags): ?>
        / <a href="<?= url::base() . url::current() ?>"><?= html::specialchars(join(' + ', $tags)) ?></a>
    <?php endif ?>
<?php slot::end() ?>

<?php slot::start('infobar') ?>
    <?php
        $whose_items = ($auth_profile && $screen_name == $auth_profile['screen_name']) ?
            'your items' : $screen_name . "'s items";
    ?>
    <?php if (!$tags): ?>
        All <?= $whose_items ?> (<?= $pagination['total'] ?>)
    <?php else: ?>
        <?= $whose_items ?> tagged <?php foreach ($tags as $tag): ?>
            <a href="<?= $profile_home_url . '/' . rawurlencode($tag) ?>"><?= html::specialchars($tag) ?></a>
        <?php endforeach ?> (<?= $pagination['total'] ?>)
    <?php endif ?>
<?php slot::end() ?>

<?php if (!empty($tag_counts)): ?>
    <?php slot::start('sidebar') ?>
        <div class="top_tags">
            <h4>top tags</h4>
            <ul>
                <?php foreach ($tag_counts as $tag_ct): ?>
                    <li>
                        <span class="count"><?= html::specialchars($tag_ct['count']) ?></span>
                        <a href="<?= $profile_home_url . '/' . rawurlencode($tag_ct['tag']) ?>" class="tag"><?= html::specialchars($tag_ct['tag']) ?></a>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php slot::end() ?>
<?php endif ?>

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
                <h2>You have no bookmarks tagged <?php foreach ($tags as $tag): ?><?= html::specialchars($tag) ?><?php endforeach ?>.</h2>
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
