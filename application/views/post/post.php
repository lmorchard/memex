<?php
    $auth_screen_name = AuthProfiles::get_profile('screen_name');
    $u_auth_screen_name = rawurlencode($auth_screen_name);
    $auth_post = ($post->profile_id == AuthProfiles::get_profile('id'));
    $profile_home_url = url::base() . 'people/' . rawurlencode($post->screen_name);
?>
<li class="post" id="post-<?= $post->hash ?>">
    <h4 class="title">
        <a href="<?= html::specialchars($post->url) ?>"><?= html::specialchars($post->title) ?></a>
    </h4>
    <?php if (!empty($post->notes)): ?>
        <p class="notes"><?= html::specialchars($post->notes) ?></p>
    <?php endif ?>
    <div class="meta">
        <ul class="tags">
            <?php foreach ($post->tags_parsed as $tag): ?>
                <?php
                    // Assemble tag classes based on rough tag namespaces.
                    $tag_classes = array('tag');
                    $tmp   = explode('=', $tag);
                    $parts = explode(':', $tmp[0]);
                    if (count($parts) > 1) {
                        $tag_classes[] = " tag_{$parts[0]} tag_{$parts[0]}_{$parts[1]}";
                    }
                ?>
                <li class="<?=html::specialchars(join(' ', $tag_classes))?>">
                    <a href="<?= $profile_home_url . '/' . html::specialchars(rawurlencode($tag)) ?>"><?= html::specialchars($tag) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
        <span class="author"><a href="<?= $profile_home_url ?>"><?= html::specialchars($post->screen_name) ?></a></span>
        <span class="date"><a class="view" href="<?= url::base() . 'posts/' . html::specialchars($post->uuid) ?>"><?= html::specialchars($post->user_date) ?></a></span>
        <ul class="commands">
            <?php if ($auth_post): ?>
                <li class="first"><a class="edit" href="<?= url::base() . 'posts/' . html::specialchars($post->uuid) . ';edit' ?>">edit</a></li>
                <li><a class="delete" href="<?= url::base() . 'posts/' . html::specialchars($post->uuid) . ';delete' ?>">delete</a></li>
            <?php else: ?>
                <li class="first"><a class="copy" href="<?= url::base() . 'posts/' . html::specialchars($post->uuid) . ';copy' ?>">copy</a></li>
            <?php endif ?>
        </ul>
    </div>

    <?php if (false): ?>
        <pre><?= var_export($post->toArray()) ?></pre>
    <?php endif ?>

</li>
