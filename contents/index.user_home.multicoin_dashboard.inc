<?php
    $_SESSION[$config->session_vars_prefix."current_coin_name"] = "_none_";
    header("Content-Type: text/html; charset=utf-8");
    
    if($admin_impersonization_in_effect)
    {
        $jquery_ui_theme = $config->jquery_ui_theme_for_admin_impersonation;
        $title_append = $account->name . "'s Home";
    }
    else
    {
        $jquery_ui_theme = $config->user_home_jquery_ui_theme;
        $title_append = "Your user home";
    } # end if
    if( ! $config->engine_enabled ) $jquery_ui_theme = $config->engine_disabled_ui_theme;
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
        
        <script                                 src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
        <link rel="stylesheet" type="text/css"  href="//code.jquery.com/ui/1.10.4/themes/<?=$jquery_ui_theme?>/jquery-ui.css">
        <script                                 src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
        <link rel="stylesheet" type="text/css"  href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery.blockUI.js"></script>
        <link rel="stylesheet" type="text/css"  href="misc/styles.css?v=<?=$config->scripts_and_styles_version?>">
        <style type="text/css">
            <?= $config->user_home_body_font_definition ?>
            <?= $config->user_home_ui_font_definition ?>
            .coin_signed:after { content: ' <?=$config->current_coin_data["coin_name_plural"]?>'; }
        </style>
        <script type="text/javascript"          src="misc/functions.js?v=<?=$config->scripts_and_styles_version?>"></script>
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery.timeago.js"></script>
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery.form.min.js"></script>
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery.scrollTo-min.js"></script>
        <link rel="stylesheet" type="text/css"  href="<?=$config->commons_url?>/lib/jquery-lightbox/jquery.lightbox.css">
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery-lightbox/jquery.lightbox.js"></script>
        <script type="text/javascript">
            var indicators_loaded         = 0;
            var indicators_to_load        = 0;
            
            ///////////////////////
            function get_balances()
            ///////////////////////
            {
                // Get addresses/balances
                $('.coin_item:not(.disabled)').each(function()
                {
                    var $this_element = $(this);
                    var url           = 'toolbox.php?mode=get_address_and_balance&coin_name='+escape($this_element.attr('coin_name'))+'&wasuuup=' + Math.round(Math.random() * 999999999999) + '<? if($admin_impersonization_in_effect) echo "&for_id_account=".$account->id_account; ?>';
                    
                    $.get(url, function(response)
                    {
                        indicators_loaded++;
                        
                        if( response.indexOf('OK') < 0 )
                        {
                            $this_element.find('.content_container .main_section').html(response);
                            $this_element.find('.content.balance').text('N/A');
                            $this_element.find('.indicator').removeClass('ui-widget-content').addClass('ui-state-error');
                            return;
                        } // end if
                        
                        var parts = response.split(':');
                        $this_element.find('.content.balance').text(parts[2]);
                        if( parts[4] ) $this_element.find('.balance_extra').text(parts[4]);
                        if( parseFloat(parts[2]) > 0 ) $this_element.find('.indicator').removeClass('ui-state-active').addClass('ui-state-default');
                        if( $this_element.attr('wallet_address') == '' )
                        {
                            $this_element.find('.content.address').attr('wallet_address', parts[1]);
                            $this_element.find('.address_caption.empty').hide();
                            $this_element.find('.address_caption.filled').show();
                            $this_element.find('.content.qrcode')
                                .attr('href', 'toolbox/get_qrcode/'+parts[1]+'')
                                .addClass('lightbox')
                                .attr('title', $this_element.find('.content.address').attr('title') + parts[1]);
                            $this_element.find('.content.adress').lightbox({scaleImages: true, xScale: 2, yScale: 2});
                        } // end if
                        
                    }); // end function
                }); // end each .multi_indicators tr
            } // end function
            
            ////////////
            $(function()
            ////////////
            {
                // UI stuff
                $('a.buttonized, button').button();
                $('.lightbox').lightbox({scaleImages: true, xScale: 2, yScale: 2});
                $(document).tooltip();
                // get_balances();
            });
        </script>
        <style type="text/css">
            h1 button .ui-button-text, .ui-widget-header button .ui-button-text { font-size: 10pt; font-weight: normal; }
            .coin_selectors        { text-align: center; }
            .coin_item             { text-align: left; display: inline-block; width: 165px; height: 180px; overflow: hidden; margin-bottom: 5px; vertical-align: top; }
            .content.address       { font-size: 8pt !important; padding: 2px; cursor: pointer; padding: 5px !important; display: inline-block !important; }
            
            .indicators                   { margin-bottom: 0; padding: 5px 0 0 5px; }
            .indicators .indicator        { padding: 5px; }
            .indicators .indicator .title { margin-left: -10px; margin-top: -10px; }
        </style>
        
        <style type="text/css">
            
            /* ############ */
            /* Opslog stuff */
            /* ############ */
            
            .opslog_entry { display: inline-block; margin: 5px; max-width: 250px; vertical-align: top;
                            background-color: whitesmoke; border-radius: 5px;
                            border: 1px solid silver;
                            -webkit-box-shadow: 2px 2px 2px 0px rgba(50, 50, 50, 0.75);
                            -moz-box-shadow:    2px 2px 2px 0px rgba(50, 50, 50, 0.75); 
                            box-shadow:         2px 2px 2px 0px rgba(50, 50, 50, 0.75); }
            
            .opslog_entry           { color: black; }
            .opslog_entry a:link    { color: blue; }
            .opslog_entry a:visited { color: purple; }
            
            .opslog_coin_section                        { white-space: nowrap; text-align: center; border-bottom: 1px solid silver; line-height: 24px; }
            .opslog_coin_section .opslog_arrow          ,
            .opslog_coin_section .opslog_coin_image     ,
            .opslog_coin_section .opslog_amount         { display: inline-block; padding: 2px; vertical-align: middle; }
            .opslog_coin_section .opslog_coin_image img { width: 24px; height: 24px; }
            .opslog_coin_section .opslog_amount         { font-size: 11pt; font-weight: normal; } 
            
            .opslog_details_section { padding: 2px; font-size: 10pt; border-bottom: 1px solid silver; }
            .opslog_message_section { padding: 2px; font-size: 9pt; max-height: 52px; overflow: hidden; font-style: italic; }
            
            /* Colors */
            .opslog_entry[optype="incoming"]               { background-color: #C0FFC0; }
            .opslog_entry[optype="incoming"] .opslog_arrow { color: #005800; }
            .opslog_entry[optype="outgoing"]               { background-color: #FFC0C0; }
            .opslog_entry[optype="outgoing"] .opslog_arrow { color: #580000; }
            
            .opslog_entry[state="ERROR"]               { background-color: #FFFFC0; }
            .opslog_entry[state="ERROR"] .opslog_arrow { color: #000000; }
        </style>
        
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
        
        <? if( ! empty($config->user_home_header_additions) ) echo $config->user_home_header_additions; ?>
        <? load_extensions("multicoin_home_head"); ?>
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
        
        <img src="<?=$config->facebook_canvas_image?>" border="0" height="64" alt="Logo" style="float: left; margin-right: 10px;">
        <h1 style="margin-bottom: 0;">
            <?=$config->app_display_longname?> v<?=$config->app_version?>
            <a href="<?=$_SERVER["PHP_SELF"]."?wasuuup=".md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>" class="buttonized">
                <span class="fa fa-refresh"></span>
                Reload
            </a>
            <? if( ! empty($config->facebook_canvas_page) ): ?>
                <a nohref="<?=$_SERVER["PHP_SELF"]."?wasuuup=".md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>" class="buttonized"
                   onclick="$('#main_sections, #instructions').toggle()">
                    <span class="fa fa-life-saver"></span>
                    Instructions
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
        <h3 style="margin-top: 0; font-style: italic;">Logged in as <?=$account->name?></h3>
        
        <? if( ! $config->engine_enabled ) { ?>
            <div class="ui-state-active message_box ui-corner-all" style="font-size: 14pt; text-align: center;">
                <span class="ui-icon embedded ui-icon-info"></span>
                <?= $config->engine_disabled_message ?>
            </div>
        <? } # end if ?>
        
        <? if( ! empty($config->engine_home_message) ) { ?>
            <div class="ui-state-error message_box ui-corner-all" style="font-size: 14pt; text-align: center;">
                <span class="ui-icon embedded ui-icon-info"></span>
                <?= $config->engine_home_message ?>
            </div>
        <? } # end if ?>
        
        <? if( ! empty($config->engine_global_message) ) { ?>
            <div class="ui-state-error message_box ui-corner-all" style="font-size: 14pt; text-align: center;">
                <span class="ui-icon embedded ui-icon-info"></span>
                <?= $config->engine_global_message ?>
            </div>
        <? } # end if ?>
        
        <? if($admin_impersonization_in_effect) { ?>
            <div class="ui-state-error message_box ui-corner-all" style="font-size: 18pt; text-align: center;">
                <span class="ui-icon embedded ui-icon-info"></span>
                You're watching <? if($is_admin) echo "[$admin_level]"; ?> <?=$account->name?>'s profile in admin impersonation mode.
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
        
        <!-- [-] Trailing stuff -->
        
        <? if( $_REQUEST["coming_from"] || $account->date_created >= date("Y-m-d H:is", strtotime("now - 2 days")) ): ?>
            
            <div class="message_box ui-state-hover ui-corner-all" style="font-size: 12pt;">
                <span class="ui-icon embedded ui-icon-info"></span>
                <?=$config->multicoin_home_welcome?>
            </div>
            <script type="text/javascript"> $(document).ready(function() { get_balances(); }); </script>
            
        <? else: ?>
            
            <script type="text/javascript"> $(document).ready(function() { get_balances(); }); </script>
            <!--
            <script type="text/javascript"> $(document).ready(function() { $('.coin_selectors .coin_item .spinner').replaceWith('<span class="spinner fa fa-info-circle" title="Click on the \'Refresh balances now\' button at the top to load this info."></span>'); }); </script>
            <div class="ui-state-error message_box ui-corner-all" style="font-size: 14pt; text-align: center;">
                <span class="fa fa-warning"></span>
                <b>Important:</b> balances are no longer refreshed automatically on load.
                <button class="smaller" style="font-size: 12pt;" onclick="$('.coin_selectors .coin_item .spinner').replaceWith('<img class=\'spinner\' src=\'img/progress_16x16_gray.gif\' border=\'0\' width=\'16\' height=\'16\'>'); get_balances(); $(this).closest('div').hide(); return false;">
                    Refresh balances now
                </button>
            </div>
            -->
            
        <? endif; ?>
        
        <div id="main_sections">
            
            <div id="opslog_notification_area">
                <?
                    $flag_id            = "last_dashboard_visit:$account->id_account";
                    $current_flag_value = get_flag_value($flag_id);
                    if( empty($current_flag_value) ) $last_dashboard_visit = $account->last_update;
                    else                             $last_dashboard_visit = $current_flag_value;
                    $new_cookie_value = date("Y-m-d H:i:s");
                    set_flag_value($flag_id, $new_cookie_value);
                    
                    $query = "
                        select
                            entry_type, from_handler, entry_id,
                            from_id_account, message, to_id_account,
                            coin_name, coins,
                            date_processed, state
                        from ".$config->db_tables["log"]."
                        where date_processed >= '$last_dashboard_visit'
                        and   ( from_id_account = '$account->id_account' or to_id_account = '$account->id_account' )
                        order by date_processed asc
                    ";
                    $res = mysql_query($query);
                    if( mysql_num_rows($res) > 0): ?>
                        
                        <div class="opslog_notifications ui-widget-content ui-corner-all" style="padding: 5px; margin-bottom: 10px;">
                            
                            <h2 class="ui-widget-header ui-corner-all" style="margin: 0 0 10px 0; padding: 5px;">
                                <button onclick="$('.opslog_notifications_list').toggle(); $(this).find('.visible, .hidden').toggle()"
                                        title="Toggle list on/off" style="height: 24px;">
                                    <span class="visible"><span class="fa fa-caret-down"></span></span>
                                    <span class="hidden" style="display: none"><span class="fa fa-caret-right"></span></span>
                                </button>
                                Recent activity on your account (since <?= time_elapsed_string($last_dashboard_visit) ?>)
                            </h2>
                            
                            <div class="opslog_notifications_list">
                                
                                <? while( $row = mysql_fetch_object($res) ): ?>
                                    <? $optype = $row->from_id_account == $account->id_account ? "outgoing" : "incoming"; ?>
                                    
                                    <div class="opslog_entry" state="<?=$row->state?>" optype="<?=$optype?>">
                                        <div class="opslog_coin_section">
                                            <div class="opslog_arrow">
                                                <? if($row->state != "OK"): ?>
                                                    <span class="fa fa-times fa-lg"></span>
                                                <? else: ?>
                                                    <? if($optype == "outgoing"): ?>
                                                        <span class="fa fa-arrow-left fa-lg"></span>
                                                    <? else: ?>
                                                        <span class="fa fa-arrow-right fa-lg"></span>
                                                    <? endif; ?>
                                                <? endif; ?>
                                            </div>
                                            <div class="opslog_coin_image">
                                                <img src="<?= $config->current_tipping_provider_data["per_coin_data"][$row->coin_name]["coin_image"] ?>" alt="<?= $row->coin_name ?>">
                                            </div>
                                            <div class="opslog_amount">
                                                <?= rounded_trimming($row->coins, 8, true) ?>
                                                <?= $config->current_tipping_provider_data["per_coin_data"][$row->coin_name]["coin_sign"] ?>
                                            </div>
                                        </div>
                                        <div class="opslog_details_section">
                                            <div class="opslog_recipient">
                                                <? if($optype == "outgoing"): ?>
                                                    <? $tmp = new account($row->to_id_account); ?>
                                                    To:
                                                    <? if( empty($tmp->facebook_id) ): ?>
                                                        <?= $tmp->name ?>
                                                    <? else: ?>
                                                        <a href="https://www.facebook.com/<?=$tmp->facebook_id?>"><?= $tmp->name ?></a>
                                                    <? endif; ?>
                                                <? else: ?>
                                                    <? $tmp = new account($row->from_id_account); ?>
                                                    From: <a href="https://www.facebook.com/<?=$tmp->facebook_id?>"><?= $tmp->name ?></a>
                                                <? endif; ?>
                                                <?= "<span style='display: inline-block' title='{$row->date_processed}'>" . time_elapsed_string($row->date_processed) . "</span>"; ?>
                                            </div>
                                        </div>
                                        <div class="opslog_message_section">
                                            <? if($row->entry_type == "tip_batch") echo "<span style='font-weight: bold; font-style: normal;'>[From rain] </span>"; ?>
                                            <?=$row->message?>
                                        </div>
                                    </div>
                                    
                                <? endwhile; ?>
                            </div>
                            
                        </div><!-- /.opslog_notifications -->
                        
                <? endif; ?>
            </div><!-- /#opslog_notification_area -->
            
            <? load_extensions("multicoin_home_above_indicators"); ?>
            
            <div class="coin_selectors">
                
                <? load_extensions("multicoin_dashboard_coin_indicators_pre"); ?>
                
                <? $coins_for_selector = array_keys($config->current_tipping_provider_data["per_coin_data"]); asort($coins_for_selector); ?>
                <? foreach( $coins_for_selector as $coin_name ): ?>
                    <? $wallet_address = $account->get_wallet_address_for_coin($coin_name); ?>
                    
                    <div class="coin_item <? if($config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_disabled"]) echo "disabled"; ?>" 
                         coin_name="<?=$coin_name?>" wallet_address="<?= $wallet_address ?>">
                        
                        <div class="indicators">
                            <div class="indicator ui-state-active <? if($config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_disabled"]) echo "ui-state-disabled"; ?> ui-corner-all" style="display: block; width: auto;">
                                
                                <div class="title ui-state-active <? if($config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_disabled"]) echo "ui-state-error"; ?> ui-corner-all" style="text-align: right; line-height: 32px;">
                                    
                                    <img src="<?= $config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_image"] ?>" 
                                         border="0" width="32" height="32" alt="<?= $coin_name ?>" style="float: left;">
                                    
                                    <span style="display: inline; font-weight: normal; font-size: 10pt;">
                                        <?=$coin_name?>&nbsp;
                                    </span>
                                    
                                    <br clear="all">
                                </div>
                                
                                <div class="content_container">
                                    
                                    <div class="main_section" style="margin: 10px 0;">
                                        <? if( ! empty($wallet_address) ): ?>
                                            <div style="margin-bottom: 5px;">
                                                <div class="balance_in_coin">
                                                    <span class="content balance" style="font-size: 10pt; display: inline;">
                                                        <? if($config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_disabled"]): ?>
                                                            N/A
                                                        <? else: ?>
                                                            <img class="spinner" src="img/progress_16x16_gray.gif" border="0" width="16" height="16">
                                                        <? endif; ?>
                                                        
                                                    </span>
                                                    <span style="font-size: 10pt; display: inline; font-weight: normal;">
                                                        <?= strtoupper($config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_sign"]) ?>
                                                    </span>
                                                </div>
                                                <? if($config->current_tipping_provider_data["per_coin_data"][$coin_name]["exclude_from_global_balance"]): ?>
                                                    <div style="font-size: 9pt; font-weight: normal; color: gray;">
                                                        <span class="balance_extra excluded"></span>
                                                        <span class="fa fa-info-circle pseudo_link" style="text-decoration: none;" title="Excluded from the total portfolio estimated value."></span>
                                                    </div>
                                                <? else: ?>
                                                    <div class="balance_extra" style="font-size: 9pt; font-weight: normal;"></div>
                                                <? endif; ?>
                                            </div>
                                            <a class="content address ui-widget-content ui-corner-all"
                                               onmouseover="$(this).toggleClass('ui-state-hover')" onmouseout="$(this).toggleClass('ui-state-hover')"
                                               wallet_address="<?= $wallet_address ?>"
                                               title="Your <?=$coin_name?> deposit address: "
                                               onclick="prompt('This is your <?=$coin_name?> deposit address:', $(this).attr('wallet_address')); return false;">
                                               Address
                                            </a>
                                            <a class="content lightbox address ui-widget-content ui-corner-all"
                                               onmouseover="$(this).toggleClass('ui-state-hover')" onmouseout="$(this).toggleClass('ui-state-hover')"
                                               wallet_address="<?= $wallet_address ?>" href="toolbox/get_qrcode/<?= $wallet_address ?>" 
                                               title="Your <?=$coin_name?> deposit address: <?= $wallet_address ?>">
                                                QR Code
                                            </a>
                                        <? else: ?>
                                            <? if($config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_disabled"]): ?>
                                                <div style="margin-bottom: 5px;">
                                                    <div class="address_caption empty"><i><b><?=$coin_name?></b><br>is currently disabled! Please try again later.</i></div>
                                                </div>
                                            <? else: ?>
                                                <div style="margin-bottom: 5px;">
                                                    <div class="address_caption empty"><i><img class="spinner" src="img/progress_16x16_gray.gif" border="0" width="16" height="16"> Getting your <br>address...</i></div>
                                                    <div class="address_caption filled" style="display: none"><b><?=$coin_name?></b><br>address generated.</div>
                                                </div>
                                                
                                                <a class="content address ui-widget-content ui-corner-all"
                                                   onmouseover="$(this).toggleClass('ui-state-hover')" onmouseout="$(this).toggleClass('ui-state-hover')"
                                                   wallet_address="<?= $wallet_address ?>"
                                                   title="Your <?=$coin_name?> deposit address: "
                                                   onclick="prompt('This is your <?=$coin_name?> deposit address:', $(this).attr('wallet_address')); return false;">
                                                   Address
                                                </a>
                                                <a class="content address qrcode ui-widget-content ui-corner-all"
                                                   onmouseover="$(this).toggleClass('ui-state-hover')" onmouseout="$(this).toggleClass('ui-state-hover')"
                                                   wallet_address="<?= $wallet_address ?>"
                                                   title="Your <?=$coin_name?> deposit address: ">
                                                   QR code
                                                </a>
                                            <? endif; ?>
                                        <? endif; ?>
                                    </div>
                                    
                                    <div style="margin-bottom: 10px;">
                                        <a class="message_box ui-state-default ui-corner-all" style="margin: 0; padding: 5px; text-decoration: none;" 
                                           onmouseover="$(this).toggleClass('ui-state-hover')" onmouseout="$(this).toggleClass('ui-state-hover')"
                                           title="Click to enter your <?=$coin_name?> dashboard"
                                           href="<?=$_SERVER["PHP_SELF"]?>?switch_coin=<?=urlencode($coin_name)?>&wasuuup=<?=md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>">
                                           Dashboard
                                           <span class="ui-icon embedded ui-icon-play"></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    
                <? endforeach; ?>
                
            </div><!-- /.coin_selectors -->
            
        </div><!-- /#main_sections -->
        
        <div id="instructions" style="display: none;">
            <? $coin_name_instructions_override = "<u>coin_handler</u>"; ?>
            <? include $config->contents["instructions"]; ?>
            <div align="center">
                <button style="width: 30%;" onclick="$('#main_sections, #instructions').toggle(); $.scrollTo(0, 'fast');">
                    <span class="fa fa-mail-reply"></span>
                    Return to coin selectors
                </button>
            </div>
        </div>
        
    </body> 
</html>
