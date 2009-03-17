<?php slot::start('crumbs') ?>
/ installation
<?php slot::end() ?>

<div class="doc_content">

<?php slot::start('prose') ?>

## Hello world!

This software has not yet been configured and fully installed, and so is not 
yet ready for use.  

This page will help walk you through getting Memex up and running on your 
server.

### MySQL Database Details

<?php slot::end_filter('Markdown') ?>

<?php
echo form::build(url::current(), array(), @$errors, array(
    html::ul(array(
        form::field('input',  'host',      'host',     array('value'=>'127.0.0.1')),
        form::field('input',  'database',  'database', array('value'=>'memex')),
        form::field('input',  'username',  'username', array('value'=>'memex')),
        form::field('input',  'password',  'password', array('value'=>'memex')),
        form::field('submit', 'configure', null,       array('value'=>'submit')),
    )
)));
?>

<h3>Environment Tests</h3>

<div id="tests">
    <?php $failed = FALSE ?>

    <?php slot::start('tests') ?>
    <p>The following tests have been run to determine if Kohana will work in your 
    environment. If any of the tests have failed, consult the 
    <a href="http://docs.kohanaphp.com/installation">documentation</a> for more 
    information on how to correct the problem.</p>

    <table cellspacing="0">
        <tr>
            <th>PHP Version</th>
            <?php if (version_compare(PHP_VERSION, '5.2', '>=')): ?>
            <td class="pass"><?php echo PHP_VERSION ?></td>
            <?php else: $failed = TRUE ?>
            <td class="fail">Kohana requires PHP 5.2 or newer, this version is <?php echo PHP_VERSION ?>.</td>
            <?php endif ?>
        </tr>
        <tr>
            <th>PCRE UTF-8</th>
            <?php if ( ! @preg_match('/^.$/u', 'ñ')): $failed = TRUE ?>
            <td class="fail"><a href="http://php.net/pcre">PCRE</a> has not been compiled with UTF-8 support.</td>
            <?php elseif ( ! @preg_match('/^\pL$/u', 'ñ')): $failed = TRUE ?>
            <td class="fail"><a href="http://php.net/pcre">PCRE</a> has not been compiled with Unicode property support.</td>
            <?php else: ?>
            <td class="pass">Pass</td>
            <?php endif ?>
        </tr>
        <tr>
            <th>Reflection Enabled</th>
            <?php if (class_exists('ReflectionClass')): ?>
            <td class="pass">Pass</td>
            <?php else: $failed = TRUE ?>
            <td class="fail">PHP <a href="http://www.php.net/reflection">reflection</a> is either not loaded or not compiled in.</td>
            <?php endif ?>
        </tr>
        <tr>
            <th>Filters Enabled</th>
            <?php if (function_exists('filter_list')): ?>
            <td class="pass">Pass</td>
            <?php else: $failed = TRUE ?>
            <td class="fail">The <a href="http://www.php.net/filter">filter</a> extension is either not loaded or not compiled in.</td>
            <?php endif ?>
        </tr>
        <tr>
            <th>Iconv Extension Loaded</th>
            <?php if (extension_loaded('iconv')): ?>
            <td class="pass">Pass</td>
            <?php else: $failed = TRUE ?>
            <td class="fail">The <a href="http://php.net/iconv">iconv</a> extension is not loaded.</td>
            <?php endif ?>
        <tr>
            <?php if (extension_loaded('mbstring')): ?>
            <th>Mbstring Not Overloaded</th>
            <?php if (ini_get('mbstring.func_overload') & MB_OVERLOAD_STRING): $failed = TRUE ?>
            <td class="fail">The <a href="http://php.net/mbstring">mbstring</a> extension is overloading PHP's native string functions.</td>
            <?php else: ?>
            <td class="pass">Pass</td>
            <?php endif ?>
            </tr>
        <?php endif ?>
        </tr>
        <tr>
        <th>URI Determination</th>
        <?php if (isset($_SERVER['REQUEST_URI']) OR isset($_SERVER['PHP_SELF'])): ?>
        <td class="pass">Pass</td>
        <?php else: $failed = TRUE ?>
        <td class="fail">Neither <code>$_SERVER['REQUEST_URI']</code> or <code>$_SERVER['PHP_SELF']</code> is available.</td>
        <?php endif ?>
        </tr>
    </table>
    <?php slot::end() ?>

    <?php if ($failed === TRUE): ?>
    <?php slot::output('tests') ?>
    <?php endif ?>

    <div id="results">
        <?php if ($failed === TRUE): ?>
        <p class="fail">Kohana may not work correctly with your environment.</p>
        <?php else: ?>
        <p class="pass">Your environment passed all basic requirements.</p>
        <?php endif ?>
    </div>

</div>

</div>
