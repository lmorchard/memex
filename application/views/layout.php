<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
    $action_name     = Router::$method;
    $controller_name = Router::$controller;
?>
<html xmlns="http://www.w3.org/1999/xhtml"> 

    <head>  
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
        <title>memex<?= slot::get('head_title') ?></title>
        <?=html::stylesheet(array(
            'css/main.css', 
            'css/nostalgia/main.css',
            'css/nostalgia/' . $controller_name . '.css'
        ))?>
        <?= slot::get('head_end') ?>
    </head> 

    <body id="<?= 'ctrl_' . $controller_name . '_act_' . $action_name ?>" 
            class="<?= 'ctrl_' . $controller_name ?> <?= 'act_' . $action_name ?> <?= 'ctrl_' . $controller_name . '_act_' . $action_name ?>">
        <div id="page" class="<?= (slot::exists('sidebar') != '') ? 'with_sidebar' : '' ?>">

            <div id="header">

                <div class="logo"><span></span></div>

                <div class="crumbs">
                    <span class="title"><a href="/">memex</a></span>
                    <?= slot::get('crumbs') ?>
                </div>

                <div class="main">
                    <?php if (null != $auth_profile): ?>
                        <ul class="nav">
                            <li class="first"><a href="<?= url::base() . 'people/' . rawurlencode($auth_profile['screen_name']) ?>">your bookmarks</a></li> 
                            <li><a href="<?= url::base() . 'save' ?>">save new</a></li>
                        </ul>
                    <?php endif ?>
                </div>

                <div class="sub">

                    <div class="auth">
                        <ul class="nav">
                            <?php if (null === $auth_profile): ?>
                                <li class="first"><a href="<?= url::base() . 'login' ?>">login</a></li>
                                <li><a href="<?= url::base() . 'register' ?>">register</a></li>
                            <?php else: ?>
                                <li class="first">logged in as <a href="<?= url::base() . 'people/' . rawurlencode($auth_profile['screen_name']) ?>"><?= html::specialchars($auth_profile['screen_name']) ?></a></li>
                                <li><a href="<?= url::base() . 'profiles/' . rawurlencode($auth_profile['screen_name']) . '/settings' ?>">settings</a></li>
                                <li><a href="<?= url::base() . 'logout' ?>">logout</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>

                </div>

            </div>

            <div id="infobar">
                <?= slot::get('infobar') ?>
            </div>

            <div id="middle">
                <div id="content">
                    <?php if (!empty($message)): ?>
                        <p class="message"><?= html::specialchars($message) ?></p>
                    <?php endif ?>
                    <?php echo $content ?>
                </div>
                <?php if ( slot::exists('sidebar') ): ?>
                    <div id="sidebar"><?php slot::get('sidebar') ?></div>
                <?php endif ?>
            </div>

            <div id="footer">
                <ul class="nav">
                    <li class="first"><a href="/">memex</a></li>
                    <li><a href="<?= url::base() . 'docs/README' ?>">about</a></li>
                    <li><a href="<?= url::base() . 'docs/TODO' ?>">todo</a></li>
                    <li><a href="<?= url::base() . 'docs/FAQ' ?>">faq</a></li>
                    <?= slot::get('footer_nav') ?>
                </ul>

                <a class="license" rel="license" href="http://creativecommons.org/licenses/by-sa/3.0/" title="This work is licensed under a Creative Commons Attribution-Share Alike 3.0 Unported License"><img alt="Creative Commons License" src="http://i.creativecommons.org/l/by-sa/3.0/80x15.png" /></a>

            </div>

        </div>

        <script type="text/javascript">
            if (typeof window.Memex == 'undefined') window.Memex = {};
            Memex.Config = {
                global: {
                    debug: true,
                    base_url: <?= json_encode(url::base()) ?>
                },
                'Memex.Main' : {
                },
                EOF: null
            };
        </script>

        <?=html::script(array(
            'js/mootools-1.2.1-core-yc.js',
            'js/mootools-1.2-more.js',
            'js/memex/utils.js',
            'js/memex/main.js',
            'js/memex/nostalgia/main.js',
            'js/memex/nostalgia/'.$controller_name.'.js'
        ))?>

        <?=slot::get('body_end')?>

    </body>
</html>
