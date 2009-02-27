<?php
    foreach (array('first', 'previous', 'next', 'last') as $pos) {
        ${"url_$pos"} = url::current(true, array('page' => ${$pos})); 
    }

    function pagination_page($current, $n) { ?>
        <li class="page">
          <?php if ($current != $n): ?>
            <a href="<?= url::current(true, array('page' => $n)); ?>"><?= $n ?></a>
          <?php else: ?>
            <span class="current"><?= $n ?></span>
          <?php endif; ?>
        </li>
    <?php }
?>
<div class="pagination">

    <ul class="pages">

        <li class="previous">
            <?php if (isset($previous)): ?>
              <a href="<?= $url_previous ?>">previous</a>
            <?php else: ?>
              <span class="disabled">previous</span>
            <?php endif; ?>
        </li>

        <?php if ($page_number < 7): ?>
            <?php /* 1 2 3 4 5 6 7 ... 99 100 */ ?>
            <?php for ($n=1; $n <= min(7, $last); $n++): ?>
                <?php pagination_page($page_number, $n) ?>
            <?php endfor ?>
            <?php if ($last > 7): ?>
                <li class="separator">...</li>
                <?php for ($n=($last - 1); $n<=$last; $n++): ?>
                    <?php pagination_page($page_number, $n) ?>
                <?php endfor ?>
            <?php endif ?>
        <?php else: ?>
            <?php for ($n=1; $n <= min(2, $last); $n++): ?>
                <?php pagination_page($page_number, $n) ?>
            <?php endfor ?>
            <?php if ($page_number < ($last - 5)): ?>
                <?php /* 1 2 ... 4 5 6 7 8 9 10 ... 99 100 */ ?>
                <li class="separator">...</li>
                <?php for ($n= $page_number-3 ; $n <= min($page_number+3, $last); $n++): ?>
                    <?php pagination_page($page_number, $n) ?>
                <?php endfor ?>
                <li class="separator">...</li>
                <?php for ($n=($last - 1); $n<=$last; $n++): ?>
                    <?php pagination_page($page_number, $n) ?>
                <?php endfor ?>
            <?php else: ?>
                <?php /* 1 2 ... 94 95 96 97 98 99 100 */ ?>
                <li class="separator">...</li>
                <?php for ($n=max(3,$last - 6); $n<=$last; $n++): ?>
                    <?php pagination_page($page_number, $n) ?>
                <?php endfor ?>
            <?php endif ?>
        <?php endif ?>

        <li class="next">
            <?php if (isset($next)): ?>
              <a href="<?= $url_next ?>">next</a>
            <?php else: ?>
              <span class="disabled">next</span>
            <?php endif; ?>
        </li>
    </ul>

    <div class="page_position">
        <?= (($page_number-1) * $page_size) + 1 ?> - 
        <?= min( (($page_number)   * $page_size), $total ) ?>
        of <?= $total ?>
    </div>

    <div class="page_size">
        show
        <ul class="page_size_choices">
            <?php
                $sizes = array(5, 10, 25, 50, 100, 200);
                $first = true;
                foreach ($sizes as $size) {
                    ?>
                        <?php if ($size == $page_size): ?>
                            <li class="selected <?= ($first) ? 'first' : '' ?>">
                                <span><?= $size ?></span>
                            </li>
                        <?php else: ?>
                            <li class="<?= ($first) ? 'first' : '' ?>">
                                <a href="<?= url::current(false, array('set_page_size' => $size)) ?>"><?= $size ?></a>
                            </li>
                        <?php endif ?>
                    <?php
                    $first = false;        
                } 
            ?>
        </ul>
        items per page
    </div>

</div>
