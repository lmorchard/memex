<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
    $action_name     = Router::$method;
    $controller_name = Router::$controller;
?>
<html xmlns="http://www.w3.org/1999/xhtml"> 
    <head>  
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
        <title>memex</title>
        <link href="<?= url::base() . 'css/nostalgia/main.css' ?>" media="screen" rel="stylesheet" type="text/css" />
        <link href="<?= url::base() . 'css/nostalgia/' . $controller_name . '.css' ?>" media="screen" rel="stylesheet" type="text/css" />
        <?= slot::output('head') ?>
    </head> 
    <body id="<?= 'ctrl_' . $controller_name . '_act_' . $action_name ?>" 
            class="<?= 'ctrl_' . $controller_name ?> <?= 'act_' . $action_name ?> <?= 'ctrl_' . $controller_name . '_act_' . $action_name ?>">
        <div id="page" class="<?= (slot::exists('sidebar') != '') ? 'with_sidebar' : '' ?>">

            <div id="header">

                <div class="logo"><span></span></div>

                <div class="crumbs">
                    <span class="title"><a href="/">memex</a></span>
                    <?= slot::output('crumbs') ?>
                </div>

                <div class="main">
                </div>

                <div class="sub">

                    <div class="auth">
                        <ul class="nav">
                        </ul>
                    </div>

                </div>

            </div>

            <div id="infobar">
                <?= slot::output('infobar') ?>
            </div>

            <div id="middle">
                <div id="content">
                    <?php if (!empty($message)): ?>
                        <p class="message"><?= html::specialchars($message) ?></p>
                    <?php endif ?>
                    <?php echo $content ?>
                </div>
                <?php if ( slot::exists('sidebar') ): ?>
                    <div id="sidebar"><?php slot::output('sidebar') ?></div>
                <?php endif ?>
            </div>

            <div id="footer">
                <ul class="nav">
                    <li class="first"><a href="/">memex</a></li>
                    <?= slot::output('footer_nav') ?>
                </ul>

                <a class="license" rel="license" href="http://creativecommons.org/licenses/by-sa/3.0/" title="This work is licensed under a Creative Commons Attribution-Share Alike 3.0 Unported License"><img alt="Creative Commons License" src="http://i.creativecommons.org/l/by-sa/3.0/80x15.png" /></a>

            </div>

        </div>

        <script src="<?= url::base() . 'js/mootools-1.2.1-core-yc.js' ?>" type="text/javascript"></script>
        <script src="<?= url::base() . 'js/mootools-1.2-more.js' ?>" type="text/javascript"></script>
        <script src="<?= url::base() . 'js/memex/utils.js' ?>" type="text/javascript"></script>
        <script src="<?= url::base() . 'js/memex/main.js' ?>" type="text/javascript"></script>
        <script src="<?= url::base() . 'js/memex/nostalgia/main.js' ?>" type="text/javascript"></script>
        <script src="<?= url::base() . 'js/memex/nostalgia/'.$controller_name.'.js' ?>" type="text/javascript"></script>

    </body>
</html>
