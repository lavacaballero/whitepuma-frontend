<?php
    header("Content-Type: text/html; charset=utf-8");

    # header("Content-Type: text/html; charset=utf-8");
    # header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
    # header('Pragma: no-cache'); // HTTP 1.0.
    # header('Expires: 0'); // Proxies.

    # header('Last-Modified: ' . gmdate('D, d M Y H:i:s', (time() - $config->cache_headers_ttl)).' GMT');
    # header("max-age=$config->cache_headers_ttl");
    # header('Expires: ' . gmdate('D, d M Y H:i:s', (time()+$config->cache_headers_ttl)).' GMT');

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
<html xmlns:fb="http://ogp.me/ns/fb#">
    <head>

        <title><?=$config->app_display_longname?> - <?=$title_append?></title>
        <meta property="og:title"               content="<?=$config->app_display_longname?>" />
        <meta property="og:image"               content="<?=$config->facebook_canvas_image?>" />
        <meta name="viewport"                   content="width=device-width" />
        <meta http-equiv="X-UA-Compatible"      content="IE=Edge" />
        <link rel="icon"                        href="<?= $config->favicon ?>">
        <link rel="shortcut icon"               href="<?= $config->favicon ?>">

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
        <link rel="stylesheet" type="text/css"  href="misc/styles.css?v=<?=$config->scripts_and_styles_version?>">
        <style type="text/css">
            <?= $config->current_coin_data["body_font_definition"] ?>
            <?= $config->current_coin_data["ui_font_definition"] ?>
            .coin_signed:after { content: ' <?=$config->current_coin_data["coin_name_plural"]?>'; }
        </style>
        <script type="text/javascript"          src="misc/functions.js?v=<?=$config->scripts_and_styles_version?>"></script>
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery-tablesorter/jquery.tablesorter.min.js"></script>
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery-tablesorter/jquery.metadata.js"></script>
        <link rel="stylesheet" type="text/css"  href="<?=$config->commons_url?>/lib/jquery-tablesorter/themes/blue/style.css">
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery.timeago.js"></script>
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery.form.min.js"></script>
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery.scrollTo-min.js"></script>
        <script type="text/javascript">
            $(function()
            {
                var active_tab = parseInt('<?=$_REQUEST["tab"]?>');
                if( active_tab )
                    $( "#tabs" ).tabs({active: active_tab});
                else
                    $( "#tabs" ).tabs();
                $('.tablesorter').tablesorter();
                $('.timeago').timeago();
                $('a.buttonized, button').button();
                $(document).tooltip();
            });
        </script>
        <style type="text/css">
            #log_helper pre { font-family: 'Lucida Console', 'Courier New', Courier, monospace; font-size: 8pt;
                              line-height: 8pt; color: silver; background-color: black; margin: 0; padding: 4pt; }
            #logs_container button .ui-button-text { padding: .1em .2em .1em .1em; }
            h1 button .ui-button-text, .ui-widget-header button .ui-button-text { font-size: 10pt; font-weight: normal; }

            .tablesorter tr:hover td { background-color: greenyellow; }

            #unclaimed table tr.ok      td { background-color: #C0FFC0; } #unclaimed table tr.ok:hover      td { background-color: #00FF00; }
            #unclaimed table tr.ko      td { background-color: #FFC0C0; } #unclaimed table tr.ko:hover      td { background-color: #FF8080; }
            #unclaimed table tr.check   td { background-color: #FFFFC0; } #unclaimed table tr.check:hover   td { background-color: #FFF200; }
            #unclaimed table tr.not_yet td { background-color: #F0F0F0; } #unclaimed table tr.not_yet:hover td { background-color: #E0E0E0; }

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
        <link rel="stylesheet" type="text/css"  href="<?=$config->commons_url?>/lib/jquery-lightbox/jquery.lightbox.css">
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery-lightbox/jquery.lightbox.js"></script>
        <script type="text/javascript"> $(document).ready( function() { $('.lightbox').lightbox({scaleImages: true, xScale: 2, yScale: 2}); }); </script>

        <!-- FB stuff -->
        <script type="text/javascript">
            /////////////////////////////////
            function invoke_requests_dialog()
            /////////////////////////////////
            {
                FB.ui({method: 'apprequests',
                    message: '<?= addslashes($config->app_requests_message)?>'
                }, requests_dialog_callback);
            } // end function

            ///////////////////////////////////
            function requests_dialog_callback()
            ///////////////////////////////////
            {
                // alert('Thank you for spreading the word!');
            } // end function
        </script>

        <!-- Record navs -->
        <script type="text/javascript">
            /////////////////////////////////////////
            function unclaimed_nav( offset, initing )
            /////////////////////////////////////////
            {
                var params = {
                    'mode':            'unclaimed_listing',
                    'coin_name':       '<?=$config->current_coin_name?>',
                    'unclaimed_offset': offset,
                    'unclaimed_order':  '<?=$unclaimed_order?>'
                }
                pagednav('unclaimed', '#unclaimed_inner', params);
                if( typeof initing == 'undefined' ) return false;
            } // end function

            //////////////////////////////////////
            function opslog_nav( offset, initing )
            //////////////////////////////////////
            {
                var params = {
                    'mode':         'user_ops_log_listing',
                    'coin_name':    '<?=$config->current_coin_name?>',
                    'opslog_offset': offset,
                    'opslog_order':  '<?=$opslog_order?>'
                }
                pagednav('ops_log', '#ops_log_inner', params);
                if( typeof initing == 'undefined' ) return false;
            } // end function

            //////////////////////////////////////////////
            function pagednav( table, target_div, params )
            //////////////////////////////////////////////
            {
                var url = root_url + 'toolbox.php?<? if($admin_impersonization_in_effect) echo "view_profile_id=".$account->id_account."&"; ?>wasuuup=' + Math.round(Math.random() * 999999999999);
                $(target_div).block(blockUI_default_params);
                $.get(url + ' ' + target_div, params, function(response)
                {
                    $(target_div).html(response);
                    $(target_div).unblock();
                    $(target_div + ' .tablesorter').tablesorter();
                    $(target_div + ' .timeago').timeago();
                    $(target_div + ' .nav_buttons button').button();
                });
            } // end function

            ////////////////////////////
            $(document).ready(function()
            ////////////////////////////
            {
                opslog_nav(0, true);
                unclaimed_nav(0, true);
            }); // end document.ready
        </script>

        <!-- Wallet transactions -->
        <script type="text/javascript">
            $(document).ready(function()
            {
                var url = root_url + 'toolbox.php?mode=fetch_wallet_transaction_counts&wasuuup=' + parseInt(Math.random() * 1000000000000000);
                $.getJSON(url, function(data)
                {
                    if( data.message != 'OK' )
                    {
                        $('.wallet_transactions_count').html('<span class="fa fa-warning" title="' + data.message + '"></span>');
                        $('.wallet_transactions_data').html('<span class="fa fa-warning"></span> ' + data.message);
                        return;
                    } // end if

                    for(var i in data.data)
                    {
                        $('.wallet_transactions_count.' + i).html(data.data[i]);
                        var url = root_url + 'toolbox.php?mode=fetch_wallet_transactions_table&category=' + i + '&wasuuup=' + parseInt(Math.random() * 1000000000000000);
                        var target_div = '.wallet_transactions_data.' + i;
                        $(target_div).load(url);
                    } // end for
                }); // end .getJSON
            }); // end function
        </script>

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
                    location.href = '<?=$_SERVER["PHP_SELF"]."?switch_coin="?>'+coin_name+'<?= "&wasuuup=".md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>&tab=' + active;
                } // end function
            </script>
            <div id="coin_switcher" class="ui-widget-header ui-corner-all">
                <select name="coin_selector" style="font-size: 16pt;" onchange="switch_coin(this.options[this.selectedIndex].value)">
                    <option value="">&lt;Switch coin&gt;</option>
                    <? $coins_for_selector = array_keys($config->current_tipping_provider_data["per_coin_data"]); asort($coins_for_selector); ?>
                    <? foreach( $coins_for_selector as $coin_name ): ?>
                        <option <? if($coin_name == $config->current_coin_name) echo "disabled" ?> value="<?= $coin_name ?>"><? if($coin_name == $config->current_coin_name) echo "►" ?><?= $coin_name ?></option>
                    <? endforeach; ?>
                </select>
                <?= $config->contents_below_coin_switcher ?>
            </div>
        <? endif; ?>

        <img src="<?=$config->current_coin_data["coin_image"]?>" border="0" height="64" alt="Logo" style="float: left; margin-right: 10px;">
        <? if($config->user_home_shows_by_default == "multicoin_dashboard"): ?>
            <div style="margin: 0; font-style: italic;">
                <?=$config->app_display_longname?> v<?=$config->app_version?>
            </div>
        <? endif; ?>
        <h1 style="margin: 0;">
            <? if($config->user_home_shows_by_default == "multicoin_dashboard"): ?>
                <?= $config->current_coin_name ?> dashboard
            <? else: ?>
                <?=$config->app_display_longname?> v<?=$config->app_version?> ~ Dashboard
            <? endif; ?>
            <a href="<?=$_SERVER["PHP_SELF"]."?wasuuup=".md5(mt_rand(1,65535))?>" class="buttonized"
               onclick="var active = $('#tabs').tabs('option', 'active'); location.href = '<?=$_SERVER["PHP_SELF"]."?wasuuup=".md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>&tab=' + active; return false;">
                <span class="fa fa-refresh"></span>
                Reload
            </a>
            <? if($config->user_home_shows_by_default == "multicoin_dashboard"): ?>
                <a class="buttonized" href="<?=$_SERVER["PHP_SELF"]."?show=multicoin_dashboard&wasuuup=".md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>">
                    <span clasS="fa fa-home"></span>
                    Home
                </a>
            <? endif; ?>
            <? if( $is_admin ): ?>
                <a class="buttonized" href="<?="index.php?admin_mode=user_central&wasuuup=".md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>">
                    <span class="fa fa-users"></span>
                    User Admin
                </a>
                <? if( stristr($config->admin_tab_functions_disabled, "groups") === false ): ?>
                    <a class="buttonized" href="<?="index.php?admin_mode=group_central&wasuuup=".md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>">
                        <span class="fa fa-comments"></span>
                        Groups Admin
                    </a>
                <? endif; ?>
                <? if( stristr($config->admin_tab_functions_disabled, "logs") === false ): ?>
                    <a class="buttonized" href="<?="index.php?admin_mode=logs_central&wasuuup=".md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>">
                        <span class="fa fa-file-text-o"></span>
                        Logs Viewer
                    </a>
                <? endif; ?>
            <? endif; ?>
            <? load_extensions("heading_main_buttons"); ?>
        </h1>
        <h3 style="margin-top: 0; font-style: italic;"><?=$config->current_coin_data["wallet_daemon_info"]?></h3>

        <? if( ! empty($config->db_tables["tip_batches"])): ?>

            <!-- ############ -->
            <!-- Rain details -->
            <!-- ############ -->

            <script type="text/javascript">
                /////////////////////////////////////////////////////////
                function show_tip_rain_details( batch_id, where_to_show )
                /////////////////////////////////////////////////////////
                {
                    var url = 'toolbox.php'
                            + '?mode=show_specific_rain_drops'
                            + '&batch_id=' + batch_id
                            + '&wasuuup=' + (Math.random() * 1000000000000000)
                            ;

                    var target_place = typeof where_to_show == 'undefined'
                                     ? '#tip_rain_details'
                                     : where_to_show;

                    $(target_place + ' .contents').load(url, function()
                    {
                        $(target_place + ' .title').text('Rain ' + batch_id + ' alphabetized drops list');
                        $(target_place).show();
                        $.scrollTo(target_place, 'fast');
                    }); // end .load
                } // end function
            </script>
            <div id="tip_rain_details_top" class="tip_rain_details" style="display: none;">

                <div class="ui-widget-content message_box ui-corner-all" style="padding: 5px;">
                    <h2 class="ui-widget-header message_box ui-corner-all" style="margin-top: 0;">
                        <span class="fa fa-times fa-border pull-right" title="Close rain details" style="cursor: pointer;" onclick="$(this).closest('.tip_rain_details').hide()"></span>
                        <span class="title"></span>
                    </h2>
                    <div class="contents"></div>
                </div>

                <? if( ! empty($_GET["show_rain"])): ?>
                    <script type="text/javascript"> $(document).ready(function() { show_tip_rain_details('<?=$_GET["show_rain"]?>', '#tip_rain_details_top'); }); </script>
                <? endif; ?>

            </div><!-- /#tip_rain_details -->

        <? endif; ?>

        <? if( ! $config->engine_enabled ) { ?>
            <div class="ui-state-active message_box ui-corner-all" style="font-size: 14pt; text-align: center;">
                <span class="ui-icon embedded ui-icon-info"></span>
                <?= $config->engine_disabled_message ?>
            </div>
        <? } # end if ?>

        <? if( ! empty($config->engine_global_message) ) { ?>
            <div class="ui-state-error message_box ui-corner-all" style="font-size: 14pt; text-align: center;">
                <span class="ui-icon embedded ui-icon-info"></span>
                <?= $config->engine_global_message ?>
            </div>
        <? } # end if ?>

        <div class="links_bar ui-widget-content ui-corner-all" style="text-align: right;">
            <? if(! $admin_impersonization_in_effect) { ?>
                <div style="float: left;">
                    <fb:like size="large" href="<?= $config->fb_like_button_link ?>" layout="button_count" action="like" width="100%" show_faces="true" share="true"></fb:like>
                    <a href="https://twitter.com/share" class="twitter-share-button" data-url="<?= $config->twitter_tweet_button_link ?>" data-via="whitepuma_net"
                       data-text="<?=$config->twitter_tweet_button_text?>">Tweet</a>
                    <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>
                </div>
            <? } # end if ?>
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

        <? if( $config->current_coin_data["coin_disabled"] ) { ?>
            <div class="ui-state-active message_box ui-corner-all" style="font-size: 14pt; text-align: center;">
                <span class="ui-icon embedded ui-icon-info"></span>
                <?= $config->current_coin_data["coin_disabled_message"] ?>
            </div>
        <? } # end if ?>

        <? if( $config->current_coin_data["coin_trailing_message"] ) { ?>
            <div class="ui-state-active message_box ui-corner-all" style="font-size: 14pt; text-align: center;">
                <span class="ui-icon embedded ui-icon-info"></span>
                <?= $config->current_coin_data["coin_trailing_message"] ?>
            </div>
        <? } # end if ?>

        <? if($admin_impersonization_in_effect) { ?>
            <div class="ui-state-error message_box ui-corner-all" style="font-size: 18pt; text-align: center;">
                <span class="ui-icon embedded ui-icon-info"></span>
                You're watching <? if($is_admin) echo "[$admin_level]"; ?> <?=$account->name?>'s profile in admin impersonation mode.
            </div>
        <? } # end if ?>

        <? load_extensions("coin_dashboard_pre_contents"); ?>

        <? $balance = $account->get_balance(); ?>

        <div class="indicators ui-widget-header">
            <div class="indicator ui-widget-content ui-corner-all">
                <div class="title ui-state-active ui-corner-all">Account id</div>
                <div class="content">
                    <?=$account->id_account?>
                </div>
            </div>
            <div class="indicator ui-widget-content ui-corner-all">
                <div class="title ui-state-active ui-corner-all">Balance</div>
                <div class="content numeric">
                    <span class="coin_signed"><?= number_format_crypto($balance) ?></span>
                </div>
            </div>

            <? load_extensions("coin_dashboard_indicator_after_balance"); ?>

            <div class="indicator ui-widget-content ui-corner-all">
                <div class="title ui-state-active ui-corner-all">Incoming</div>
                <div class="wallet_transactions_count tips_in content numeric">
                    <img src="<?=$config->commons_url?>/img/progress_16x16_gray.gif">
                </div>
            </div>
            <div class="indicator ui-widget-content ui-corner-all">
                <div class="title ui-state-active ui-corner-all">Outgoing</div>
                <div class="wallet_transactions_count tips_out content numeric">
                    <img src="<?=$config->commons_url?>/img/progress_16x16_gray.gif">
                </div>
            </div>
            <div class="indicator ui-widget-content ui-corner-all">
                <div class="title ui-state-active ui-corner-all">Deposits</div>
                <div class="wallet_transactions_count deposits content numeric">
                    <img src="<?=$config->commons_url?>/img/progress_16x16_gray.gif">
                </div>
            </div>
            <div class="indicator ui-widget-content ui-corner-all">
                <div class="title ui-state-active ui-corner-all">Withdrawals</div>
                <div class="wallet_transactions_count withdrawals content numeric">
                    <img src="<?=$config->commons_url?>/img/progress_16x16_gray.gif">
                </div>
            </div>
            <div class="indicator ui-widget-content ui-corner-all">
                <div class="title ui-state-active ui-corner-all">All</div>
                <div class="wallet_transactions_count all_activity content numeric">
                    <img src="<?=$config->commons_url?>/img/progress_16x16_gray.gif">
                </div>
            </div>

            <? if( ! empty($config->app_requests_message) ): ?>

                <div class="indicator ui-widget-content ui-corner-all">
                    <div class="title ui-state-active ui-corner-all">Spread the word</div>
                    <div class="content">
                        <span class="ui-state-default ui-corner-all" onmouseover="$(this).addClass('ui-state-hover')" onmouseout="$(this).removeClass('ui-state-hover')"
                              style="display: inline-block; padding: 0 5px; cursor: pointer; font-size: 14pt;" onclick="invoke_requests_dialog();">Invite your friends!</span>
                    </div>
                </div>

            <? endif; ?>

            <? if( $admin_impersonization_in_effect ) { ?>
                <script type="text/javascript">
                    function get_wallet_address_as_admin()
                    {
                        var url = 'toolbox.php?mode=get_wallet_address&for_id_account=<?=$account->id_account?>&wasuuup=' + Math.round(Math.random() * 999999999999);
                        $('#span_walletaddress').block( blockUI_smallest_params );
                        $.get(url, function(response)
                        {
                            if( response.indexOf(/error/i) >= 0 )
                            {
                                alert( response + '\n\n'
                                     + 'Please try again. If you need assistance, please reach the\n'
                                     + 'dev team to get it manually.'
                                     );
                                $('#span_walletaddress').unblock();
                                return;
                            } // end if

                            $('#span_walletaddress').text(response).unblock();
                        }); // end .get
                    } // end function
                </script>
            <? } # end if ?>

            <div class="indicator smaller full_sized ui-widget-content ui-corner-all" style="text-align: center;">
                <div class="content">
                    Your wallet address:
                    <span id="span_walletaddress"><?= empty($account->wallet_address) ? "Not yet assigned!" : $account->wallet_address ?></span>
                    <? if(empty($account->wallet_address)) { ?>
                        <? if( ! $admin_impersonization_in_effect ) { ?>
                            <button <? if( ! $config->engine_enabled ) echo "disabled"; ?> onclick="get_wallet_address();">
                                <span class="ui-icon embedded ui-icon-arrowreturnthick-1-s"></span>
                                Get address...
                            </button>
                        <? } else { ?>
                            <button <? if( ! $config->engine_enabled ) echo "disabled"; ?> onclick="get_wallet_address_as_admin();">
                                <span class="ui-icon embedded ui-icon-arrowreturnthick-1-s"></span>
                                Get as user...
                            </button>
                        <? } # end if ?>
                    <? } else { ?>
                        <a title="Your <?=$config->current_coin_name?> wallet address: <?=$account->wallet_address?>" class="lightbox ui-state-default ui-corner-all" href="toolbox/get_qrcode/<?=$account->wallet_address?>"
                           style="display: inline-block; padding: 5px 10px 6px 10px; text-decoration: none; font-size: 10pt; position: relative; top: 2px;"
                           onmouseover="$(this).toggleClass('ui-state-hover')" onmouseout="$(this).toggleClass('ui-state-hover')">
                            <span class="ui-icon embedded ui-icon-newwin"></span>
                            QR Code
                        </a>&nbsp;
                        <button title="<?=$account->wallet_address?>"
                           onclick="prompt('This is your <?=$config->current_coin_name?> wallet address:', '<?=$account->wallet_address?>'); return false;">
                            <span class="ui-icon embedded ui-icon-copy"></span>
                            Copy
                        </button>&nbsp;
                    <? } # end if ?>
                    <? if( ! $admin_impersonization_in_effect ) { ?>
                        <? # if( ! $config->current_coin_data["coin_disabled"] ) { ?>
                            <? if( empty($account->email) && empty($account->alternate_email) ): ?>
                                <? $message = empty($config->custom_account_creation_prefix)
                                            ? "Please specify your emergency info."
                                            : "Please edit your account and specify a main/alternate email and password."; ?>
                                <button <? if( ! $config->engine_enabled ) echo "disabled"; ?> onclick="alert('You have no email set on your account! You will not be able to withdraw any coin until you setup a main/alternate email. <?=$message?>')">
                                    <span class="ui-icon embedded ui-icon-arrowreturnthick-1-w"></span>
                                    Withdraw...
                                </button>
                            <? else: ?>
                                <button <? if( ! $config->engine_enabled ) echo "disabled"; ?> onclick="show_withdrawal_request_form()">
                                    <span class="ui-icon embedded ui-icon-arrowreturnthick-1-w"></span>
                                    Withdraw...
                                </button>
                            <? endif; ?>
                        <? # } # end if ?>
                    <? } # end if ?>

                    <? if( empty($config->custom_account_creation_prefix) ): ?>
                        <!-- [+] Alternate info -->
                            <? if( empty($account->alternate_email) || empty($account->alternate_password) ) { ?>
                                <? if( ! $admin_impersonization_in_effect ) { ?>
                                    <div class="ui-state-error message_box ui-corner-all">
                                        <span class="ui-icon embedded ui-icon-alert"></span>
                                        <span class="pseudo_link" onclick="show_alternate_info_editor()">
                                            <b>You haven't set your emergency login info. Please click here to set it up!</b>
                                        </span>
                                    </div>
                                <? } else { ?>
                                    <div class="ui-state-error message_box ui-corner-all">
                                        <span class="ui-icon embedded ui-icon-alert"></span>
                                        <span>
                                            <b>The user haven't set its emergency login info!</b>
                                        </span>
                                    </div>
                                <? } # end if ?>
                            <? } else { ?>
                                <? if( ! $admin_impersonization_in_effect ) { ?>
                                    <button <? if( ! $config->engine_enabled ) echo "disabled"; ?> onclick="show_alternate_info_editor()">
                                        <span class="ui-icon embedded ui-icon-pencil"></span>
                                        Emergency info...
                                    </button>
                                <? } # end if ?>
                            <? } # end if ?>
                        <!-- [-] Alternate info -->
                    <? endif; ?>

                </div>
            </div>
        </div><!-- /.indicators -->

        <? include "contents/index.user_home.withdraw_request_dialog.inc"; ?>
        <? if( empty($config->custom_account_creation_prefix) ) include "contents/index.user_home.edit_alt_info_dialog.inc"; ?>

        <? $min_tip        = $config->current_coin_data["min_transaction_amount"]; ?>
        <? $min_fee        = $config->current_coin_data["transaction_fee"]; ?>
        <? $min_withdraw   = $config->current_coin_data["min_withdraw_amount"]; ?>
        <? $network_tx_fee = $config->current_coin_data["system_transaction_fee"]; ?>
        <? $withdraw_fee   = $config->current_coin_data["withdraw_fee"]; ?>

        <? if( $is_admin && count($config->current_tipping_provider_data["per_coin_data"]) == 1 )
            include "contents/index.platform_stats.single.inc"; ?>

        <? load_extensions("coin_dashboard_below_user_stats"); ?>

        <div id="tabs">

            <? $tab_names = explode(",", $config->current_coin_data["dashboard_tab_names"]); ?>
            <ul>
                <li><a href="#instructions"><?= $tab_names[0] ?></a></li>
                <li><a href="#tips_received"><?= $tab_names[1] ?></a></li>
                <li><a href="#tips_sent"><?= $tab_names[2] ?></a></li>
                <li><a href="#deposits"><?= $tab_names[3] ?></a></li>
                <li><a href="#withdrawals"><?= $tab_names[4] ?></a></li>
                <li><a href="#all_activity"><?= $tab_names[5] ?></a></li>
                <li><a href="#unclaimed"><?= $tab_names[6] ?></a></li>
                <? if( ! empty($config->db_tables["tip_batches"])): ?>
                    <li><a href="#tip_rains"><?= $tab_names[8] ?></a></li>
                <? endif; ?>
                <li><a href="#ops_log"><?= $tab_names[7] ?></a></li>
            </ul>

            <div id="instructions">
                <? include $config->contents["instructions"]; ?>
            </div><!-- /#instructions -->

            <div id="tips_received" class="ui-tabs ui-tabs-hide">
                <div class="wallet_transactions_data tips_in contents">
                    <span class="fa fa-spinner fa-spin"></span> Loading...
                </div>
            </div><!-- #/tips_received -->

            <div id="tips_sent" class="ui-tabs ui-tabs-hide">
                <div class="ui-state-highlight message_box ui-corner-all" style="text-align: center;">
                    <span class="ui-state-active ui-corner-all" style="display: inline-blocK; padding: 0 5px;">
                        Min transaction amount: <b><span class="coin_signed"><?= $min_tip ?></span></b>
                    </span>
                    <? if( ! empty($min_fee)) { ?>
                        <span class="ui-state-active ui-corner-all" style="display: inline-blocK; padding: 0 5px;">
                            Transaction Fee:
                            <b><span class="coin_signed"><?= $min_fee ?></span></b>
                            (charged on reception)
                        </span>
                    <? } # end if ?>
                </div>
                <div class="wallet_transactions_data tips_out contents">
                    <span class="fa fa-spinner fa-spin"></span> Loading...
                </div>
            </div><!-- #/tips_sent -->

            <div id="deposits" class="ui-tabs ui-tabs-hide">
                <div class="wallet_transactions_data deposits contents">
                    <span class="fa fa-spinner fa-spin"></span> Loading...
                </div>
            </div><!-- #/deposits -->

            <div id="withdrawals" class="ui-tabs ui-tabs-hide">
                <div class="ui-state-highlight message_box ui-corner-all" style="text-align: center;">
                    <span class="ui-state-active ui-corner-all" style="display: inline-blocK; padding: 0 5px;">
                        Min withdrawal: <b><span class="coin_signed"><?= $min_withdraw?></span></b>
                    </span>
                    <? if( ! empty($network_tx_fee)) { ?>
                        <span class="ui-state-active ui-corner-all" style="display: inline-blocK; padding: 0 5px;">
                            Network withdrawal fee: <b><span class="coin_signed"><?= $network_tx_fee?></span></b>
                            (for miners)
                        </span>
                    <? } # end if ?>
                    <? if( ! empty($withdraw_fee)) { ?>
                        <span class="ui-state-active ui-corner-all" style="display: inline-blocK; padding: 0 5px;">
                            Platform Provider (<?=$config->current_tipping_provider_data["shortname"]?>) Fee:
                            <b><span class="coin_signed"><?= $withdraw_fee?></span></b>
                            (to help pay servers)
                        </span>
                    <? } # end if ?>
                    <button onclick="show_withdrawal_request_form(); $.scrollTo('#withdraw_request_form', 'fast'); return false;">
                        <span class="ui-icon embedded ui-icon-arrowreturnthick-1-w"></span>
                        Withdraw...
                    </button>
                </div>
                <div class="wallet_transactions_data withdrawals contents">
                    <span class="fa fa-spinner fa-spin"></span> Loading...
                </div>
            </div><!-- #/withdrawals -->

            <div id="all_activity" class="ui-tabs ui-tabs-hide">
                <div class="wallet_transactions_data all_activity contents">
                    <span class="fa fa-spinner fa-spin"></span> Loading...
                </div>
            </div><!-- #/all_activity -->

            <div id="ops_log" class="ui-tabs ui-tabs-hide">
                <div id="ops_log_inner">
                    <span class="fa fa-spinner fa-spin"></span> Loading...
                </div>
            </div><!-- #/ops_log -->

            <div id="unclaimed" class="ui-tabs ui-tabs-hide">
                <div id="unclaimed_inner">
                    <span class="fa fa-spinner fa-spin"></span> Loading...
                </div>
            </div><!-- /#unclaimed -->

            <? if( ! empty($config->db_tables["tip_batches"])): ?>
                <div id="tip_rains" class="ui-tabs ui-tabs-hide">
                    <? include "contents/index.tip_rains.inc"; ?>
                </div><!-- /#tip_rains -->
            <? endif; ?>

        </div><!-- /#tabs -->

    </body>
</html>
