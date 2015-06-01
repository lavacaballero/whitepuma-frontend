<?php
    if($admin_impersonization_in_effect)
    {
        $jquery_ui_theme = $config->jquery_ui_theme_for_admin_impersonation;
        $title_append = $account->name . "'s Home";
    }
    else
    {
        if( $session_from_cookie ) $jquery_ui_theme = $config->current_coin_data["jquery_ui_theme_for_alternate_login"];
        else                       $jquery_ui_theme = $config->current_coin_data["jquery_ui_theme"];
        $title_append = "Your user home";
    } # end if
    if( ! $config->engine_enabled ) $jquery_ui_theme = $config->engine_disabled_ui_theme;
?>
<html>
    <head>
        <title><?=$config->app_display_longname?> - Re-authorization needed!</title>
        <meta name="viewport"                   content="width=device-width" />
        <meta http-equiv="X-UA-Compatible"      content="IE=Edge" />
        <link rel="icon"                        href="<?= $config->favicon ?>">
        <link rel="shortcut icon"               href="<?= $config->favicon ?>">
        <script                                 src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
        <link rel="stylesheet" type="text/css"  href="//code.jquery.com/ui/1.10.4/themes/<?=$jquery_ui_theme?>/jquery-ui.css">
        <script                                 src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
        <link rel="stylesheet" type="text/css"  href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery.blockUI.js"></script>
        <link rel="stylesheet" type="text/css"  href="misc/styles.css?v=<?=$config->scripts_and_styles_version?>">
        <style type="text/css">
            <?= $config->current_coin_data["body_font_definition"] ?>
            <?= $config->current_coin_data["ui_font_definition"] ?>
            .coin_signed:after { content: ' <?=$config->current_coin_data["coin_name_plural"]?>'; }
        </style>
        <script type="text/javascript"          src="misc/functions.js?v=<?=$config->scripts_and_styles_version?>"></script>
    </head>
    <body>
        <div id="fb-root"></div>
        <script>
          window.fbAsyncInit = function() {
              FB.init({
                appId      : '<?= $config->facebook_app_id ?>',
                version    : 'v2.0',
                status     : true,
                cookie     : true,
                xfbml      : true  // parse XFBML
              });
              FB.Canvas.setAutoGrow();
          };

          (function(d, s, id){
             var js, fjs = d.getElementsByTagName(s)[0];
             if (d.getElementById(id)) {return;}
             js = d.createElement(s); js.id = id;
             js.src = "//connect.facebook.net/en_US/sdk.js";
             fjs.parentNode.insertBefore(js, fjs);
           }(document, 'script', 'facebook-jssdk'));
        </script>

        <h1>We couldn't get a token to access your profile!</h1>

        <p>Possible cause: <span style="color: maroon;"><?=$e->getMessage();?></span></p>

        <p>Please <a href="<?=$_SERVER["PHP_SELF"]."?wasuuup=".md5(mt_rand(1,65535))?>" style="font-weight: bold;">try reloading this page</a>.
        If the problem persists, please re-authorize our app so we can try again
        or <span class="pseudo_link" onclick="$('#alternate_login').dialog('open');">use alternate login</span>.</p>

        <? include "contents/alternate_login_form.inc"; ?>

        <div align="center">
            <fb:login-button size="xlarge" scope="<?=$config->facebook_auth_scope?>" show_faces="false" onlogin="location.reload();">
                Reconnect now
            </fb:login-button>
        </div>
    </body>
</html>
