<?php
    foreach (array('first', 'previous', 'next', 'last') as $pos) {
        ${"url_$pos"} = url::full_current(true, array('page' => ${$pos})); 
    }
?>
<ul class="pagination_mini">

    <li class="previous">
        <?php if (isset($previous)): ?>
            <a href="<?= $url_previous ?>">previous</a>
        <?php else: ?>
            <span class="disabled">previous</span>
        <?php endif; ?>
    </li>

    <li class="next">
        <?php if (isset($next)): ?>
            <a href="<?= $url_next ?>">next</a>
        <?php else: ?>
            <span class="disabled">next</span>
        <?php endif; ?>
    </li>

    <li class="page">
    page <?= $page_number ?> of <?= $last ?>
    </li>

</ul>
