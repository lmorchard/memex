<?php
    $auth_post = ($auth_profile && 
        $post['profile_id'] == $auth_profile['id']);
    $profile_home_url = url::base() . 'people/' . out::U($post['screen_name']);
?>
<li class="post" id="post-<?= $post['hash'] ?>">
    <h4 class="title">
        <a href="<?= out::H($post['url']) ?>"><?= out::H($post['title']) ?></a>
    </h4>
    <?php if (!empty($post['notes'])): ?>
        <p class="notes"><?= out::H($post['notes']) ?></p>
    <?php endif ?>
    <div class="meta">
        <ul class="tags">
            <?php foreach ($post['tags_parsed'] as $tag): ?>
                <li class="tag"><a href="<?= $profile_home_url . '/' . out::H(rawurlencode($tag)) ?>"><?= out::H($tag) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <span class="author"><a href="<?= $profile_home_url ?>"><?= out::H($post['screen_name']) ?></a></span>
        <span class="date"><a class="view" href="<?= url::base() . 'posts/' . out::H($post['uuid']) ?>"><?= out::H($post['user_date']) ?></a></span>
        <ul class="commands">
            <?php if ($auth_post): ?>
                <li class="first"><a class="edit" href="<?= url::base() . 'posts/' . out::H($post['uuid']) . ';edit' ?>">edit</a></li>
                <li><a class="delete" href="<?= url::base() . 'posts/' . out::H($post['uuid']) . ';delete' ?>">delete</a></li>
            <?php else: ?>
                <li class="first"><a class="copy" href="<?= url::base() . 'posts/' . out::H($post['uuid']) . ';copy' ?>">copy</a></li>
            <?php endif ?>
        </ul>
    </div>

    <?php if (false): ?>
        <pre><?= var_export($post->toArray()) ?></pre>
    <?php endif ?>

</li>
