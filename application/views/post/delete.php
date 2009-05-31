<h2>Delete this?</h2>

<ul class="posts">
    <?php View::factory('post/post', array(
        'post' => $post
    ))->render(true) ?>
</ul>

<?php
echo form::build(url::current(), array('class'=>'delete'), array(
    form::fieldset('delete post', array('class'=>'delete'), array(
        form::field('submit', 'delete',  null, array('value'=>'delete')),
        form::field('submit', 'cancel',  null, array('value'=>'cancel'))
    ))
));
?>
