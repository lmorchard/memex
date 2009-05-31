<ul class="posts">
    <?php View::factory('post/post', array(
        'post' => $post
    ))->render(true) ?>
</ul>
