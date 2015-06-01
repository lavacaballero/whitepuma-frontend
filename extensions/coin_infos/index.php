<?php
    /**
     * Platform Extension: Coin infos/index
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
        $title_append = "Coin infos";
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
        $title_append = "Coin infos";
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
        <script type="text/javascript"          src="<?=$root_url?>/lib/jquery.exptextarea.js"></script>
        <script type="text/javascript">$(document).ready(function() { $('.expandible_textarea').expandingTextArea(); });</script>

        <!-- TableSorter -->
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery-tablesorter/jquery.tablesorter.min.js"></script>
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery-tablesorter/jquery.metadata.js"></script>
        <link rel="stylesheet" type="text/css"  href="<?=$config->commons_url?>/lib/jquery-tablesorter/themes/blue/style.css">
        <script type="text/javascript">
            // http://stackoverflow.com/questions/10984609/jquery-tablesorter-sorting-comma-separated-numbers
            jQuery.tablesorter.addParser({
                id: "fancyNumber",
                is: function(s) {
                    return /^[0-9]?[0-9,\.]*$/.test(s);
                },
                format: function(s) {
                    return jQuery.tablesorter.formatFloat( s.replace(/,/g,'') );
                },
                type: "numeric"
            });
        </script>

        <style type="text/css">
            tr.disabled td, tr.disabled td a { color: maroon !important; }
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
        <h3 style="margin-top: 0; font-style: italic;">Coin infos</h3>

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

        <? if( $is_admin ) include "$root_url/contents/index.platform_stats.multi.inc"; ?>

        <h1 class="ui-widget-header message_box ui-corner-all">
            Coin infos
        </h1>

        <div class="ui-state-highlight message_box ui-corner-all">
            <span class="fa fa-info-circle"></span>
            Transaction fees are applied <u>to the recipient upon receiving</u> (not to the sender). They are registered as transactions to a
            special system account per every coin.
            Withdrawal fees are set as minimums for every coin daemon. We do not control them, so depending
            on the amount of coins being taken out and the inputs inside the wallet, those fees may be lower or even higher.
        </div>

        <div class="table_wrapper">
            <table class="tablesorter" width="100%" cellpadding="2" cellspacing="1" border="0">
                <thead>
                    <tr>
                        <th>Coin</th>
                        <th class="{sorter: false}">Version info</th>
                        <th class="{sorter: false}">Min Tx size</th>
                        <th class="{sorter: false}">Tx fee</th>
                        <th class="{sorter: false}">Withdrawal fee</th>
                        <? if( $is_admin ): ?>
                            <? if( ! empty($config->db_tables["tip_batches"]) ): ?>
                                <th class="{sorter: 'fancyNumber'}">Direct deliveries</th>
                                <th class="{sorter: 'fancyNumber'}">Rain drops</th>
                            <? endif; ?>
                            <th class="{sorter: 'fancyNumber'}">Total transactions</th>
                            <th class="{sorter: 'fancyNumber'}">Volume (coins)</th>
                            <th class="{sorter: false}">&nbsp;</th>
                            <th class="{sorter: 'fancyNumber'}">Volume (USD)</th>
                            <th>Last transaction date</th>
                        <? endif; ?>
                    </tr>
                </thead>
                <tbody>

                    <? $coins = array_keys($config->tipping_providers_database[$config->current_tipping_provider_keyname]["per_coin_data"]); sort($coins); ?>
                    <? $total_directs_count = $total_drops_count = $total_usd_volume = 0; ?>
                    <? foreach($coins as $coin_name): ?>
                        <? $coin_data = $config->tipping_providers_database[$config->current_tipping_provider_keyname]["per_coin_data"][$coin_name]; ?>
                        <tr class="<?= $coin_data["coin_disabled"] ? "disabled" : "" ?>">
                            <td>
                                <a href="<?= $coin_data["official_url"] ?>" target="_blank"><?= $coin_name ?></a>
                                <? if($coin_data["coin_disabled"]): ?>
                                    <span class="fa fa-eye-slash" title="Coin disabled"></span>
                                <? endif; ?>
                            </td>
                            <td><?= $coin_data["wallet_daemon_info"] ?></td>
                            <td align="right"><?= $coin_data["min_transaction_amount"] ?></td>
                            <td align="center"><?= empty($coin_data["transaction_fee"]) ? "&mdash;" : $coin_data["transaction_fee"] ?></td>
                            <td align="right"><?= $coin_data["system_transaction_fee"] ?></td>
                            <?
                                if( $is_admin )
                                {
                                    $tip_count     =
                                    $rain_drops    =
                                    $direct_tips   =
                                    $volume        =
                                    $last_tip_date = "&mdash;";
                                    $query = "
                                        select
                                            count(op_id) as tip_count,
                                            max(date_processed) as last_tip_date,
                                            sum(coins) as volume,
                                            ( select count(op_id) from ".$config->db_tables["log"]."
                                              where state = 'OK' and
                                              coin_name   = '$coin_name' and
                                              entry_type  = 'tip_batch' ) as rain_drops,
                                            ( select count(op_id) from ".$config->db_tables["log"]."
                                              where state =  'OK' and
                                              coin_name   =  '$coin_name' and
                                              entry_type  <> 'tip_batch' ) as direct_tips,
                                            ( select price from {$config->db_tables['coin_prices']}
                                              where coin_name = '$coin_name'
                                              order by `date` desc limit 1 ) as coin_price
                                        from ".$config->db_tables["log"]."
                                        where state = 'OK' and
                                        coin_name = '$coin_name'
                                    ";
                                    $res    = mysql_query($query);
                                    if( mysql_num_rows($res) > 0 )
                                    {
                                        $row = mysql_fetch_object($res);
                                        if( ! empty($row->volume) )
                                        {
                                            $total_directs_count += $row->direct_tips;
                                            $total_drops_count   += $row->rain_drops;
                                            $volume = number_format($row->volume, 8);
                                            $last_tip_date = $row->last_tip_date;
                                            if( ! empty($row->tip_count) )   $tip_count   = number_format($row->tip_count);
                                            if( ! empty($row->rain_drops) )  $rain_drops  = number_format($row->rain_drops);
                                            if( ! empty($row->direct_tips) ) $direct_tips = number_format($row->direct_tips);
                                        } # end if
                                        $usd_volume = $row->coin_price * $row->volume;
                                        $total_usd_volume += $usd_volume;
                                    } # end if
                                    mysql_free_result( $res );
                                    ?>
                                    <? if( ! empty($config->db_tables["tip_batches"]) ): ?>
                                        <td align="right"><?= $direct_tips ?></td>
                                        <td align="right"><?= $rain_drops ?></td>
                                    <? endif; ?>
                                    <td align="right"><?= $tip_count ?></td>
                                    <td align="right"><?= $volume ?></td>
                                    <td align="left"><?= strtoupper($coin_data["coin_sign"]) ?></td>
                                    <td align="right"><?= number_format($usd_volume, 8) ?></td>
                                    <td><?= $last_tip_date ?></td>
                                    <?
                                } # end if
                            ?>
                        </tr>

                    <? endforeach; ?>

                </tbody>
                <tfoot>
                    <tr align="right">
                        <td colspan="5">Totals</td>
                        <? if( ! empty($config->db_tables["tip_batches"]) ): ?>
                            <td><?= number_format($total_directs_count) ?></td>
                            <td><?= number_format($total_drops_count) ?></td>
                        <? endif; ?>
                        <td><?= number_format($total_directs_count + $total_drops_count) ?></td>
                        <td colspan="2">&nbsp;</td>
                        <td align="right"><?= number_format($total_usd_volume, 8) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </body>
</html>
