<?php
    /**
     * Platform Extension: Websites / Index
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
    include "$root_url/models/account_extensions.php";
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
    } # end if
    if( ! $config->engine_enabled ) $jquery_ui_theme = $config->engine_disabled_ui_theme;

    if( ! is_resource($config->db_handler) ) db_connect();

    $title_append = $config->buttons_buttonizer_heading_caption . " enabled websites";
    header("Content-Type: text/html; charset=utf-8");
?>
<html xmlns:fb="http://ogp.me/ns/fb#">
    <head>
        <title><?=$config->app_display_longname?> - <?=$title_append?></title>
        <meta name="viewport"                       content="width=device-width" />
        <meta http-equiv="X-UA-Compatible"          content="IE=Edge" />
        <link rel="icon"                            href="<?= $config->favicon ?>">
        <link rel="shortcut icon"                   href="<?= $config->favicon ?>">
        <meta property="og:title"                   content="<?=$config->app_display_longname?>" />
        <meta property="og:image"                   content="<?=$config->facebook_canvas_image?>" />

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
        <script                                     src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
        <link rel="stylesheet" type="text/css"      href="//code.jquery.com/ui/1.10.4/themes/<?=$jquery_ui_theme?>/jquery-ui.css">
        <script                                     src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
        <link rel="stylesheet" type="text/css"      href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
        <script type="text/javascript"              src="<?=$config->commons_url?>/lib/jquery.blockUI.js"></script>
        <link rel="stylesheet" type="text/css"      href="<?=$root_url?>/misc/styles.css?v=<?=$config->scripts_and_styles_version?>">
        <style type="text/css">
            <?= $config->user_home_body_font_definition ?>
            <?= $config->user_home_ui_font_definition ?>
            .coin_signed:after { content: ' <?=$config->current_coin_data["coin_name_plural"]?>'; }
        </style>
        <script type="text/javascript"              src="<?=$root_url?>/misc/functions.js?v=<?=$config->scripts_and_styles_version?>"></script>
        <script type="text/javascript"              src="<?=$config->commons_url?>/lib/jquery.timeago.js"></script>
        <script type="text/javascript"              src="<?=$config->commons_url?>/lib/jquery.form.min.js"></script>
        <script type="text/javascript"              src="<?=$config->commons_url?>/lib/jquery.scrollTo-min.js"></script>
        <link rel="stylesheet" type="text/css"      href="<?=$config->commons_url?>/lib/jquery-lightbox/jquery.lightbox.css">
        <script type="text/javascript"              src="<?=$config->commons_url?>/lib/jquery-lightbox/jquery.lightbox.js"></script>
        <script type="text/javascript">
            $(function()
            {
                // UI stuff
                $('a.buttonized, button').button();
                $('.lightbox').lightbox({
                    fileLoadingImage: root_url + 'lib/jquery-lightbox/loading.gif',
                    fileBottomNavCloseImage: root_url + 'lib/jquery-lightbox/closelabel.gif',
                });
                $('.tabs').tabs();
                $('.tablesorter').tablesorter();
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
        <script type="text/javascript"              src="<?=$config->commons_url?>/lib/jquery.exptextarea.js"></script>
        <script type="text/javascript">$(document).ready(function() { $('.expandible_textarea').expandingTextArea(); });</script>

        <!-- TableSorter -->
        <script type="text/javascript"              src="<?=$config->commons_url?>/lib/jquery-tablesorter/jquery.tablesorter.min.js"></script>
        <script type="text/javascript"              src="<?=$config->commons_url?>/lib/jquery-tablesorter/jquery.metadata.js"></script>
        <link rel="stylesheet" type="text/css"      href="<?=$config->commons_url?>/lib/jquery-tablesorter/themes/blue/style.css">

        <!-- Local -->
        <style type="text/css">
            #category_list a                    { display: inline-block; padding: 5px; margin: 5px; }
            #website_list  .item                { display: inline-block; width: 200px; padding: 10px; margin: 10px; vertical-align: top;
                                                  text-decoration: none; text-align: center; }
            #website_list  .item span           { display: block; margin-bottom: 5px; }
            #website_list  .item .icon img      { height: 128px; }
            #website_list  .item .name          { font-weight: bold; }
            #website_list  .item .description   { font-style: italic; }
            #website_list  .item .details span  { display: inline-block; }
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

        <div id="category_list" class="ui-widget-header message_box ui-corner-all">

            <?
                $query = "select category, count(public_key) as website_count
                          from " . $config->db_tables["websites"] . "
                          where public_key not like 'lj.%'
                          and   state = 'enabled' group by category";
                $res = mysql_query($query);
                $all_count = 0;
                $links     = array();
                while( $row = mysql_fetch_object($res) )
                {
                    $links[] = "<a class='ui-state-default ".($_GET["category"] == $row->category ? "ui-state-active" : "")." ui-corner-all' href='enabled_websites_list.php?category=$row->category'>$row->category ($row->website_count)</a>\n";
                    $all_count += $row->website_count;
                } # end while
            ?>

            <a class="ui-state-default <? if( empty($_GET["category"]) ) echo "ui-state-active"; ?> ui-corner-all"
               href="enabled_websites_list.php">All websites (<?=$all_count?>)</a>

            <? foreach($links as $this_link) echo $this_link; ?>

        </div>

        <div id="website_list">

            <?
                $query = "select * from " . $config->db_tables["websites"] . "
                          where public_key not like 'lj.%'
                          and   state = 'enabled'
                          ".( empty($_GET["category"]) ? "" : "and category = '$_GET[category]'")."
                          order by name asc";
                $res = mysql_query($query);
                while( $row = mysql_fetch_object($res) )
                {
                    if( empty($row->icon_url) ) $row->icon_url = $config->buttons_default_website_logo;
                    ?>

                    <div class="item">
                        <a href="<?=$row->main_url?>" target="_blank">
                            <span class="icon"><img src="<?=$row->icon_url?>"></span>
                            <span class="name"><?=$row->name?></span>
                        </a>
                        <span class="description"><?=$row->description?></span>
                        <span class="details">
                            <span>Joined: <?=time_elapsed_string($row->creation_date) ?></span>
                            <? if($row->allow_leeching) echo "<span><b>Allows leeching!</b></span>"; ?>
                        </span>
                    </div>

                    <?
                } # end while
            ?>
        </div>


    </body>
</html>
