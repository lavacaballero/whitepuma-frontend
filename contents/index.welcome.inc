<?php
    header("Content-Type: text/html; charset=utf-8");

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
    if( empty($jquery_ui_theme) ) $jquery_ui_theme = $config->user_home_jquery_ui_theme;
?>
<html>
    <head>
        <title><?=$config->app_display_longname?> - Welcome</title>
        <meta name="viewport" content="width=device-width" />
        <meta http-equiv="X-UA-Compatible"      content="IE=Edge" />
        <link rel="icon"                        href="<?= $config->favicon ?>">
        <link rel="shortcut icon"               href="<?= $config->favicon ?>">
        <meta property="og:title"               content="<?=$config->app_display_longname?>" />
        <meta property="og:image"               content="<?=$config->facebook_canvas_image?>" />

        <? if( ! empty($config->google_analytics_id) ): ?>
            <!-- Google Analytics -->
            <script>
              (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
              (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
              m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
              })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
              ga('create', '<?=$config->google_analytics_id?>', 'auto');
              ga('send', 'pageview');
            </script>
        <? endif; ?>

        <script                                 src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
        <link rel="stylesheet" type="text/css"  href="//code.jquery.com/ui/1.10.4/themes/<?=$jquery_ui_theme?>/jquery-ui.css">
        <script                                 src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
        <link rel="stylesheet" type="text/css"  href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery.blockUI.js"></script>
        <link rel="stylesheet" type="text/css"  href="<?=$root_url?>/misc/styles.css?v=<?=$config->scripts_and_styles_version?>">
        <style type="text/css">
            <?= $config->current_coin_data["body_font_definition"] ?>
            <?= $config->current_coin_data["ui_font_definition"] ?>
            .coin_signed:after { content: ' <?=$config->current_coin_data["coin_name_plural"]?>'; }
        </style>
        <script type="text/javascript"          src="<?=$root_url?>/misc/functions.js?v=<?=$config->scripts_and_styles_version?>"></script>
        <meta name="google-site-verification"   content="AGYOsj3wDjW80DXxwBFEDN5luUmPn8HJPj_5SLnpTT0" />
        <style type="text/css">
            #supported_coins ul   { margin: 0; padding: 0; }
            #supported_coins li   { display: inline-block; margin: 5px; padding: 5px; background-color: #ebc776; border: 1px solid #ce832f; border-radius: 5px; }
            #supported_coins li a { color: #2e1509; }
        </style>
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

        <img src="<?= $config->facebook_canvas_image ?>" style="float: left; margin-right: 10px; margin-bottom: 10px; width: 110px; height: 110px;">

        <h1>Welcome to <?=$config->app_display_longname?>!</h1>

        <p>Tipping on Facebook works by posting a command over the posts and comments submitted in our <a href="<?=$root_url?>/extensions/pulse/" target="_blank">Community Pulse</a>.
        When you tag the user you want to tip with <?=$config->contents_supported_coin_names_text?>,
        they the coins immediately on their wallet and both get notified.</p>

        <p>All the backend service is provided by <a href="<?=$config->website_pages["root_url"]?>" target="_blank"><?=$config->app_display_longname?></a>.</p>

        <div class="links_bar ui-widget-content ui-corner-all" style="text-align: right; clear: both;">
            <div style="float: left;">
                <fb:like size="large" href="<?= $config->fb_like_button_link ?>" layout="button_count" action="like" width="100%" show_faces="true" share="true"></fb:like>
                <a href="https://twitter.com/share" class="twitter-share-button" data-url="<?= $config->twitter_tweet_button_link ?>" data-via="whitepuma_net"
                   data-text="<?=$config->twitter_tweet_button_text?>">Tweet</a>
                <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>
            </div>

            <a                 href="<?="$root_url/"?>changelog.php"><span class="ui-icon embedded ui-icon-note"></span>Changelog</a>
            <a target="_blank" href="<?=$config->website_pages["about"]?>"><span class="ui-icon embedded ui-icon-info"></span>About...</a>
            <a target="_top"   href="<?= $config->facebook_app_page ?>"><span class="ui-icon embedded ui-icon-document"></span>News page</a>
            <?if( ! empty($config->facebook_app_group) ): ?>
                <a target="_top" href="<?= $config->facebook_app_group ?>"><span class="ui-icon embedded ui-icon-heart"></span>Tipping Group</a>
            <? endif; ?>
            <a target="_blank" href="<?=$config->website_pages["terms_of_service"]?>"><span class="ui-icon embedded ui-icon-script"></span>TOS</a>
            <a target="_blank" href="<?=$config->website_pages["privacy_policy"]?>"><span class="ui-icon embedded ui-icon-key"></span>Privacy Policy</a>
            <a target="_blank" href="<?=$config->website_pages["faq"]?>"><span class="ui-icon embedded ui-icon-star"></span>FAQ</a>
            <a target="_blank" href="<?=$config->website_pages["support"]?>"><span class="ui-icon embedded ui-icon-help"></span>Help &amp; Support</a>
            &nbsp;
        </div><!-- /.links_bar -->

        <div class="error_to_throw">
            <? if( ! empty($error_to_throw) ): ?>
                <!-- Coming from functions.php::get_online_user_id() -->
                <div class="ui-state-error message_box ui-corner-all">
                    <span class="fa fa-warning"></span>
                    <?= $error_to_throw ?>
                </div>
            <? endif; ?>
        </div>

        <h2>Getting started</h2>

        <p>
            You need to authorize this app by hitting the "Connect" button below so your Facebook profile becomes connected.
            Once in, you'll be granted <?= count($config->tipping_providers_database["wpnet_fbtipbot"]["per_coin_data"]) == 1 ? "a wallet address" : "a wallet address per each supported coin" ?>,
            in which you can deposit any amount of <?=$config->contents_supported_coin_names_text?> you want to tip.
        </p>

        <p>
            Our dashboard shows you the latest submissions on our Pulse, and you're free to post contents or tipping commands either
            over the composition tool or over the comments of an existing post. You can opt to send the post to one of our integrated
            Facebook Group to increase engagement.
        </p>

        <div class="message_box" align="center">
            <fb:login-button size="xlarge" width="100%" scope="<?=$config->facebook_auth_scope?>" show_faces="true" onlogin="location.reload();">
                Connect now
            </fb:login-button>
        </div>

        <div class="message_box ui-state-highlight ui-corner-all" style="text-align: center; font-size: 12pt;">
            <span class="ui-icon embedded ui-icon-info"></span>
            Already joined? Some possible problem getting the dashboard. Please <a href="<?=$_SERVER["PHP_SELF"]."?wasuuup=".md5(mt_rand(1,65535))?>" style="font-weight: bold;">try reloading this page</a>
            or use alternate login below.
        </div>

        <? include "contents/alternate_login_form.inc"; ?>

        <div id="supported_coins_content" style="<? if( count($config->tipping_providers_database["wpnet_fbtipbot"]["per_coin_data"]) == 1 ) echo "display: none"; else echo "clear: both"; ?>">
            <h2>Supported coins</h2>
            <div id="supported_coins">
                <ul>
                    <? $coins_list = $config->tipping_providers_database["wpnet_fbtipbot"]["per_coin_data"]; ksort($coins_list); foreach($coins_list as $coin_key => $coin_data): ?>
                        <li><a href="<?= $coin_data["official_url"] ?>" target="_blank"><?= $coin_key ?></a></li>
                    <? endforeach; ?>
                </ul>
            </div>
        </div><!-- /#supported_coins_content -->

        <p><b>Important:</b> We <u>do not buy or sell or trade</u> any coin. You must own some and have them transferred here.</p>

        <h2>Integrated Facebook groups</h2>

        <p>If you want to interact with other users in our network, you can join any of the next Facebook Groups:</p>

        <div id="supported_groups">
            <ul>
                <?
                    if( ! is_resource($config->db_handler) ) db_connect();
                    $object_list = array();
                    foreach($config->facebook_monitor_objects as $key => $object)
                    {
                        $name = $object["name"];
                        $user_count = $object["type"] == "group" ? get_flag_value("group_counts:$key") : 0;
                        $object_list[$name] = array(
                            "url"        => $object["url"],
                            "name"       => $object["name"],
                            "user_count" => $user_count
                        );
                    } # end foreach
                    ksort($object_list);
                ?>
                <? foreach($object_list as $key => $object): ?>
                    <? $style = (substr($object["name"], 0, 1) != ">") ? "" : "font-weight: bold;"; ?>
                    <? $object["name"] = str_replace(">", "", $object["name"]); ?>
                    <? if( empty($object["url"]) ) continue; ?>
                    <li>
                        <a style="<?=$style?>" href="<?= $object["url"] ?>" target="_blank"><?= $object["name"] ?></a>
                        <?= empty($object["user_count"]) ? "" : "[".$object["user_count"]." members]" ?>
                    </li>
                <? endforeach; ?>
                <? if( ! empty($config->website_pages["group_addition_form"]) ): ?>
                    <li>
                        <span class="ui-state-highlight ui-corner-all" style="display: inline-block;">
                            <span class="ui-icon embedded ui-icon-alert"></span>
                            Do you want a group to be added to this list? <a href="<?= $config->website_pages["group_addition_form"] ?>" target="_blank">Click here!</a> &nbsp;
                        </span>
                    </li>
                <? endif; ?>
            </ul>
        </div><!-- /#welcome_contents -->

    </body>
</html>
