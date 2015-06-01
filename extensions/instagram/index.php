<?php
    /**
     * Platform Extension: Instagram index page (info)
     *
     * @package    WhitePuma OpenSource Platform
     * @subpackage Frontend
     * @copyright  2014 Alejandro Caballero
     * @author     Alejandro Caballero - acaballero@lavasoftworks.com
     * @license    GNU-GPL v3 (http://www.gnu.org/licenses/gpl.html)
     *
     * This program is free software: you can redistribute it and/or modify
     * it under the terms of the GNU General Public License as published by
     * the Free Software Foundation, either version 3 of the License, or
     * (at your option) any later version.
     *
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     * GNU General Public License for more details.
     * THE SOFTWARE.
     */

    $root_url = "../..";
    if( ! is_file("$root_url/config.php") ) die("ERROR: config file not found.");
    include "$root_url/config.php";
    include "$root_url/functions.php";
    include "$root_url/models/tipping_provider.php";
    include "$root_url/models/account.php";
    session_start();

    include "$root_url/facebook-php-sdk/facebook.php";
    $fb_params = array(
        'appId'              => $config->facebook_app_id,
        'secret'             => $config->facebook_app_secret,
        'fileUpload'         => false,
        'allowSignedRequest' => true
    );
    $facebook = new Facebook($fb_params);

    $user_id = get_online_user_id();
    if( empty($user_id) )
    {
        include "$root_url/" . $config->contents["welcome"];
        die();
    } # end if

    $account  = new account($user_id);
    $is_admin = isset($config->sysadmins[$account->id_account]);
    # header( "X-Admin: $user_id // $account->id_account ~ " . $config->sysadmins[$account->id_account] );
    if( $is_admin ) $admin_level = $config->sysadmins[$account->id_account];

    if( $config->user_home_shows_by_default == "multicoin_dashboard" )
    {
        $_SESSION[$config->session_vars_prefix."current_coin_name"] = "_none_";
    } # end if

    if($admin_impersonization_in_effect)
    {
        $jquery_ui_theme = $config->jquery_ui_theme_for_admin_impersonation;
        $title_append = "Instagram TipBot instructions";
    }
    else
    {
        if( $config->user_home_shows_by_default == "multicoin_dashboard" )
        {
            $jquery_ui_theme = $config->user_home_jquery_ui_theme;
        }
        else
        {
            if( $session_from_cookie ) $jquery_ui_theme = $config->current_coin_data["jquery_ui_theme_for_alternate_login"];
            else                       $jquery_ui_theme = $config->current_coin_data["jquery_ui_theme"];
        } # end if
        $title_append = "Instagram TipBot instructions";
    } # end if
    if( ! $config->engine_enabled ) $jquery_ui_theme = $config->engine_disabled_ui_theme;


    if( ! is_resource($config->db_handler) ) db_connect();

    header("Content-Type: text/html; charset=utf-8");
?>
<html xmlns:fb="http://ogp.me/ns/fb#">
    <head>
        <title><?=$config->app_display_longname?> - <?=$title_append?></title>
        <meta name="viewport"                   content="width=device-width" />
        <meta http-equiv="X-UA-Compatible"      content="IE=Edge" />
        <link rel="icon"                        href="<?= $config->favicon ?>">
        <link rel="shortcut icon"               href="<?= $config->favicon ?>">
        <meta property="og:title"               content="<?=$config->app_display_longname?> - <?=$title_append?>" />
        <meta property="og:image"               content="<?=$config->facebook_canvas_image?>" />
        <meta name="description"                content="Information about <?=$config->app_display_shortname?>'s Instagram TipBot" />

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

        <script type="text/javascript"> var root_url = '<?=$root_url?>/'; </script>
        <script                                 src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
        <link rel="stylesheet" type="text/css"  href="//code.jquery.com/ui/1.10.4/themes/<?=$jquery_ui_theme?>/jquery-ui.css">
        <script                                 src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
        <link rel="stylesheet" type="text/css"  href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery.blockUI.js"></script>
        <link rel="stylesheet" type="text/css"  href="<?=$root_url?>/misc/styles.css?v=<?=$config->scripts_and_styles_version?>">
        <style type="text/css">
            <?= $config->user_home_body_font_definition ?>
            <?= $config->user_home_ui_font_definition ?>
            .coin_signed:after { content: ' <?=$config->current_coin_data["coin_name_plural"]?>'; }
        </style>
        <script type="text/javascript"          src="<?=$root_url?>/misc/functions.js?v=<?=$config->scripts_and_styles_version?>"></script>
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery.timeago.js"></script>
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery.form.min.js"></script>
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery.scrollTo-min.js"></script>
        <link rel="stylesheet" type="text/css"  href="<?=$config->commons_url?>/lib/jquery-lightbox/jquery.lightbox.css">
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery-lightbox/jquery.lightbox.js"></script>
        <script type="text/javascript">
            $(function()
            {
                // UI stuff
                $('a.buttonized, button').button();
                $('.lightbox').lightbox();
                $('.tabs').tabs();
                $(document).tooltip();
            });
        </script>
        <style type="text/css">
            @media all and (max-width: 4000px) and (min-width: 640px)
            {
                #coin_switcher { float: right; padding: 5px; text-align: center; }
            }
            @media all and (max-width: 639px) and (min-width: 100px)
            {
                #coin_switcher { display: block; width: auto; margin-bottom: 10px; text-align: center; padding: 5px; }
                #coin_switcher select { width: 100%; }
            }
        </style>

        <!-- Expandible Textarea -->
        <style type="text/css">
            .expandible_textarea { overflow-x: auto; overflow-y: hidden; -moz-box-sizing: border-box; resize: none;
                                   height: 19px; max-height: 190px; padding-bottom: 2px; width: 100%;
                                   font-family: Arial, Helvetica, sans-serif; font-size: 10pt; }
        </style>
        <script type="text/javascript"          src="<?=$root_url?>/lib/jquery.exptextarea.js"></script>
        <script type="text/javascript">$(document).ready(function() { $('.expandible_textarea').expandingTextArea(); });</script>

        <style type="text/css">
            @media all and (max-width: 5000px) and (min-width: 481px)
            {
                .field { text-align: left; display: inline-block; margin: 10px; width: 44%; vertical-align: top; }
            }
            @media all and (max-width: 480px) and (min-width: 100px)
            {
                .field { text-align: left; display: block; width: auto; vertical-align: top; }
            }

            .multicol_field            { text-align: left; vertical-align: top; margin: 0 5px; }
            .multicol_field code       { padding: 5px; margin-top: 2px; }
            .multicol_field .coin_name { line-height: 24px; background-size: 22px; background-position: left center;
                                         background-repeat: no-repeat; padding-left: 24px; }
            @media all and (max-width: 5000px) and (min-width: 1025px)
            {
                .multicol_field { display: inline-block; width: 31%; }
            }
            @media all and (max-width: 1024px) and (min-width: 801px)
            {
                .multicol_field { display: inline-block; width: 30%; }
            }
            @media all and (max-width: 800px) and (min-width: 641px)
            {
                .multicol_field { display: inline-block; width: 29%; }
            }
            @media all and (max-width: 640px) and (min-width: 481px)
            {
                .multicol_field { display: inline-block; width: 45%; }
            }
            @media all and (max-width: 480px) and (min-width: 100px)
            {
                .multicol_field { text-align: left; display: block; width: auto; }
            }
        </style>
    </head>
    <body>

        <!-- [+] Trailing stuff -->

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

        <h1 class="ui-state-hover ui-corner-all" style="padding: 5px;">
            <span class="fa fa-user fa-border"></span>
            [<?=$account->id_account?>] <?=$account->name?>
            <span id="session_buttons">
                <a class="buttonized" href="<?="$root_url/index.php?mode=logout&wasuuup=".md5(mt_rand(1,65535))?>">
                    <span class="fa fa-sign-out"></span>
                    Reset/logout
                </a>
                <? if( ! empty($config->custom_account_creation_prefix) ): ?>
                    <a class="buttonized" href="<?="$root_url/edit_account.php?wasuuup=".md5(mt_rand(1,65535))?>">
                        <span class="fa fa-pencil"></span>
                        Edit
                    </a>
                <? endif; ?>
            </span>
        </h1>

        <? if( count($config->current_tipping_provider_data["per_coin_data"]) > 1 ): ?>
            <script type="text/javascript">
                function switch_coin(coin_name)
                {
                    var active = $('#tabs').tabs('option', 'active');
                    location.href = '<?="$root_url/index.php?switch_coin="?>'+coin_name+'<?= "&wasuuup=".md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>&tab=' + active;
                } // end function
            </script>
            <div id="coin_switcher" class="ui-widget-header ui-corner-all">
                <select name="coin_selector" style="font-size: 16pt;" onchange="switch_coin(this.options[this.selectedIndex].value)">
                    <option value="">&lt;Jump to coin:&gt;</option>
                    <? $coins_for_selector = array_keys($config->current_tipping_provider_data["per_coin_data"]); asort($coins_for_selector); ?>
                    <? foreach( $coins_for_selector as $coin_name ): ?>
                        <option value="<?= $coin_name ?>"><?= $coin_name ?></option>
                    <? endforeach; ?>
                </select>
                <?= $config->contents_below_coin_switcher ?>
            </div>
        <? endif; ?>

        <img src="<?=$config->facebook_canvas_image?>" border="0" height="64" alt="Logo" style="float: left; margin-right: 10px;">
        <h1 style="margin-bottom: 0;">
            <?=$config->app_display_longname?> v<?=$config->app_version?>
            <a href="<?=$_SERVER["PHP_SELF"]."?wasuuup=".md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>" class="buttonized">
                <span class="fa fa-refresh"></span>
                Reload
            </a>
            <? if($config->user_home_shows_by_default == "multicoin_dashboard"): ?>
                <a class="buttonized" href="<?="$root_url/index.php?show=multicoin_dashboard&wasuuup=".md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>">
                    <span clasS="fa fa-home"></span>
                    Home
                </a>
            <? else: ?>
                <a class="buttonized" href="<?="$root_url/index.php?wasuuup=".md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>">
                    <span clasS="fa fa-home"></span>
                    Dashboard
                </a>
            <? endif; ?>
            <? if( $is_admin ): ?>
                <a class="buttonized" href="<?="$root_url/index.php?admin_mode=user_central&wasuuup=".md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>">
                    <span class="fa fa-users"></span>
                    User Admin
                </a>
                <? if( stristr($config->admin_tab_functions_disabled, "groups") === false ): ?>
                    <a class="buttonized" href="<?="$root_url/index.php?admin_mode=group_central&wasuuup=".md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>">
                        <span class="fa fa-comments"></span>
                        Groups Admin
                    </a>
                <? endif; ?>
                <? if( stristr($config->admin_tab_functions_disabled, "logs") === false ): ?>
                    <a class="buttonized" href="<?="$root_url/index.php?admin_mode=logs_central&wasuuup=".md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>">
                        <span class="fa fa-file-text-o"></span>
                        Logs Viewer
                    </a>
                <? endif; ?>
            <? endif; ?>
            <? load_extensions("heading_main_buttons", $root_url); ?>
        </h1>
        <h3 style="margin-top: 0; font-style: italic;"><?=$title_append?></h3>

        <? if( ! empty($config->engine_global_message) ) { ?>
            <div class="ui-state-error message_box ui-corner-all" style="font-size: 14pt; text-align: center;">
                <span class="ui-icon embedded ui-icon-info"></span>
                <?= $config->engine_global_message ?>
            </div>
        <? } # end if ?>

        <div class="links_bar ui-widget-content ui-corner-all" style="text-align: right;">
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

        <!-- [-] Trailing stuff -->

        <!-- ============ -->
        <!-- Instructions -->
        <!-- ============ -->

        <h2 class="ui-widget-header message_box ui-corner-all">
            How does our Instagram TipBot work?
        </h2>

        <p>Our TipBot monitors comments sent over <b>media with the <u>#<?=$config->instagram_subscriptions_data["hashtag"]?></u> hashtag</b> in the description.
        By adding the hashtag to <u>new</u> media, you make it "tippable" and our bot will start listening for tip commmands over the comments.
        To tip someone, you use the same syntax used in all our tipping applications, but <b>you must prefix the hashtag to it</b>:</p>

        <code>
            <u>#<?=$config->instagram_subscriptions_data["hashtag"]?></u>
            give <b>amount</b> <b style="color: purple">coin_name</b>
            to <u style="display: inline-block;">@recipient1</u>
            <u style="display: inline-block;">@recipient2</u>
            <u style="display: inline-block;">@recipientN</u>
            <i>some message</i>
        </code>

        <p>The coins you specify will be tipped equally to all recipients, so if you tell the bot to send 10 <i>whatevercoins</i>
        and you mention three recipients, you'll be sending 10 coins to each one of them.</p>

        <h2>How to get started</h2>

        <ul>
            <li>
                <u>New <?=$config->app_single_word_name?> users coming from Instagram</u>:
                You already have your account. <u>You just need to add the <b>#<?=$config->instagram_subscriptions_data["hashtag"]?></b> hashtag</u>
                to your Instagram submissions to enable tipping over comments.
            </li>
            <li>
                <u>Existing <?=$config->app_single_word_name?> users without connection to our Instagram app</u>:
                You just need to <a href="#connect">connect your Instagram account</a> with <?=$config->app_single_word_name?>!
            </li>
        </ul>

        <h2>Limits exist!</h2>

        <ul>
            <li>Instagram <b>doesn't offer a messaging API</b>, but we can deliver notifications through our
            <a href="<?=$config->twitter_about_page?>" target="_blank">Twitter TipBot</a>!
            Just <a href="<?=$root_url?>/edit_account.php">edit your account</a> and
            <a href="<?=$root_url?>/extensions/twitter/">connect with your Twitter account</a>
            to start receiving incoming/outgoing tip notifications!</li>

            <li>Since we monitor tagged media items, they may grow quickly, causing an overhead in our tips monitor.
            For this reason, we've set a limit of <u><?=$config->instagram_item_lifespan?> days</u> for an item
            to receive tips. We will adjust this limit accordingly to prevent future issues.</li>

            <li style="display: none;">Instagram <b>restricts API calls</b> to 5000 per hour. We will need to adjust our tips monitor as tagged media starts to flow.<br>
            At this moment, our monitor is running <u>every <?=$config->instagram_monitor_heartbeat?> minutes</u> and reads
            <u>the latest <?=$config->instagram_read_items_limit?> items</u>, giving a single item a tipping lifespan
            of <u>about <?=$config->instagram_min_hours_per_item?> hours</u>.<br>
            Please follow our <a href="<?=$config->facebook_app_page?>">Facebook news page</a> to get updates upon this and other important topics.</li>
        </ul>

        <h2>Tipping a non-<?=$config->app_single_word_name?> user? No problem!</h2>

        <p>When your tip recipients aren't registered with <?=$config->app_display_shortname?>,
        an account is being created for them, but <b>they wont be notified.</b> you will need to
        explain them what you're doing and, if possible, provide them the link to our
        <a href="<?=$config->instagram_about_page?>" target="_blank">Instagram TipBot info page</a>. Once they
        authorize our App they'll be able to claim their tips.</p>

        <h2>Got a new account generated automatically? <u>Re-route it!</u></h2>

        <p>If you already are a <?=$config->app_single_word_name?> user, you haven't tied your Instagram account
        and someone tipped you, you may want to re-route your tips from your newly created account
        to your previously existing <?=$config->app_single_word_name?> account. This is a <u>free</u> service
        that will allow you to keep all your balances in one single place.
        You just need to <a href="<?=$root_url?>/edit_account.php?wasuuup=<?=md5(mt_rand(1, 65535))?>">edit your account</a>
        and specify the target account id on the respective area of the Twitter connection form.</p>

        <p>There's one disadvantage: you will need to manually send existing coins, and since your Instagram account
        is already tied to a new <?=$config->app_single_word_name?> account, you'll not be able to use Instagram to sign into
        your "good one" unless you unlink the new one and render it unusable.</p>

        <!-- ======================= -->
        <!-- Connect/disconnect form -->
        <!-- ======================= -->

        <a name="connect"></a>
        <? include "form.inc"; ?>

        <!-- ============================ -->
        <!-- Supported coins and syntaxes -->
        <!-- ============================ -->

        <h2>Supported coins and syntaxes</h2>

        <p>Our TipBot supports all of the <?=count($config->current_tipping_provider_data["per_coin_data"])?> coins supported by
        <?=$config->app_display_shortname?> and it uses <u>the same syntaxes</u> (singular and/or plural) <b>including easter eggs!</b>.
        You may notice that the coin handler is the coin name without the "coin" suffix
        -with some exceptions-, so it is easy for you to remember them.
        <b>Per-coin minimums</b> can be found <a href="<?=$config->website_pages["fees_and_coins_info"]?>" target="_blank">on this page</a>.</p>

        <div align="center">
            <? $coin_names = array_keys($config->current_tipping_provider_data["per_coin_data"]); sort($coin_names); ?>
            <? foreach($coin_names as $coin_name): ?>
                <? if( $config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_disabled"] ) continue; ?>

                <div class="multicol_field">
                    <div class="coin_name" style="background-image: url('<?= $config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_image"] ?>');"><?=$coin_name?>:</div>
                    <code>
                        <u>@<?=$config->twitter_screen_name?></u>
                        give amount <b style="color: purple"><?= strtolower($config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_name_singular"]) ?></b>
                        to @friend<br>
                        <? if( $config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_name_singular"]
                           !=  $config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_name_plural"] ): ?>
                            <u>@<?=$config->twitter_screen_name?></u>
                            give amount <b style="color: purple"><?= strtolower($config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_name_plural"]) ?></b>
                            to @friend<br>
                        <? endif; ?>
                    </code>
                </div>

            <? endforeach; ?>
        </div><br>

    </body>
</html>
