<ul class="posts">
    <?php View::factory('post/post', array(
        'auth_profile' => $auth_profile,
        'post'         => $post
    ))->render(true) ?>
</ul>
