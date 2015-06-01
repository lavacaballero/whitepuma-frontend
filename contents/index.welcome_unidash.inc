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

        <meta name="google-site-verification"   content="AGYOsj3wDjW80DXxwBFEDN5luUmPn8HJPj_5SLnpTT0" />
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
        <script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery.form.min.js"></script>
        <style type="text/css">
            #supported_coins ul   { margin: 0; padding: 0; }
            #supported_coins li   { display: inline-block; margin: 5px; padding: 5px; background-color: #BBDDFF; border: 1px solid #42A0FF; border-radius: 5px; }
            #supported_coins li a { color: navy; }

            @media all and (max-width: 5000px) and (min-width: 801px)
            {
                .connect_segment { display: inline-block; width: 48%; padding: 5px; text-align: left; vertical-align: top;
                                   margin-bottom: 20px; min-height: 220px; }
                .register .field { text-align: left; display: inline-block; margin: 10px; width: 21%; }
            }
            @media all and (max-width: 800px) and (min-width: 100px)
            {
                .connect_segment { display: block; width: auto; padding: 5px; text-align: left; vertical-align: top;
                                   margin-bottom: 20px; }
                .register .field { text-align: left; display: inline-block; margin: 10px; width: 44%; }
            }
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

        <!-- [+] Info -->
        <div>

            <img src="<?= $config->facebook_canvas_image ?>" style="float: left; margin-right: 10px; margin-bottom: 10px; width: 85px; height: 85px;">

            <h1>Welcome to <?=$config->app_display_longname?>!</h1>

            <p>Now you can tip your favorite content makers and pay for services/purchase goods with <?=$config->contents_supported_coin_names_text?>!
            You only need to create an account on our platform or connect our app with your any supported Social Network.
            Once joined, you'll be granted <?= count($config->tipping_providers_database["wpnet_fbtipbot"]["per_coin_data"]) == 1 ? "a wallet address" : "a wallet address <u>per each supported coin</u>" ?>,
            in which you can deposit any amount of coins you want to spend.</p>

            <p>If you have a blog, a website or you contribute on a
            <a href="<?=$config->buttons_about_widget_link?>" target="_blank"><?=$config->buttons_buttonizer_rootname?> enabled</a>
            website or forum, or you just want <u>to share a link to receive coins</u>,
            Just go to the "Websites &amp; buttons" page once logged in and <u>create your Piggy Bank</u>,
            make a button and <u>get the invocation code</u>!</p>

        </div>
        <!-- [-] Info -->

        <div class="error_to_throw">
            <? if( ! empty($error_to_throw) ): ?>
                <!-- Coming from functions.php::get_online_user_id() -->
                <div class="ui-state-error message_box ui-corner-all">
                    <span class="fa fa-warning"></span>
                    <?= $error_to_throw ?>
                </div>
            <? endif; ?>
        </div>

        <? if( ! empty($config->engine_global_message) ) { ?>
            <div class="ui-state-error message_box ui-corner-all" style="font-size: 14pt; text-align: center;">
                <span class="ui-icon embedded ui-icon-info"></span>
                <?= $config->engine_global_message ?>
            </div>
        <? } # end if ?>

        <div style="text-align: center;">

            <div class="connect_segment facebook_connect ui-widget-content ui-corner-all">
                <h2 class="ui-widget-header ui-corner-all" style="padding: 5px; margin-top: 0;">Connect with Facebook</h2>
                <div class="message_box ui-state-highlight ui-corner-all" style="text-align: center; font-size: 11pt; color: maroon;">
                    <span class="ui-icon embedded ui-icon-info"></span>
                    If you already have an <?=$config->app_single_word_name?> account
                    with your email or Twitter and want to link it with Facebook,
                    <b>login first with your email or Twitter</b>, then <b>edit your account</b>
                    and link it from there <u>or you'll get a new unlinked account</u>.
                </div>
                <div align="center">
                    <fb:login-button size="large" width="100%" scope="<?=$config->facebook_auth_scope?>"
                        onlogin="if(location.href.indexOf('welcome.php') < 0 && location.href.indexOf('mode=logout') < 0) location.reload(); else location.href='<?=$root_url?>/?wasuuup=' + parseInt(Math.random() * 1000000000000000);">
                        Connect/login now
                    </fb:login-button>
                </div>
            </div><!-- /.facebook_connect -->

            <div class="connect_segment standard_login ui-widget-content ui-corner-all">
                <div id="standard_login_result" style="display: none;"></div>
                <form name="standard_login_form" id="standard_login_form" method="post" action="<?=$root_url?>/toolbox.php?wasuuup=<?=md5(mt_rand(1,65535))?>">
                    <input type="hidden" name="mode" value="do_standard_login">
                    <h2 class="ui-widget-header ui-corner-all" style="padding: 5px; margin-top: 0;">Login with your email</h2>
                    <table width="100%" cellpadding="5" cellspacing="0" border="0">
                        <tr align="left" valign="top">
                            <td width="50%">
                                Your base or alternate email:
                                <div style="margin-left: 25px;">
                                    <input type="text" name="email" style="width: 100%;">
                                </div>
                            </td>
                            <td width="50%">
                                Your password:
                                <div style="margin-left: 25px;">
                                    <input type="password" name="password" style="width: 100%;">
                                </div>
                            </td>
                        </tr>
                    </table>

                    <div style="text-align: center;">
                        <br><button type="submit" style="width: 50%;">Login</button>
                    </div>
                </form>

                Forgotten password? Enter your email above and <span class="pseudo_link" onclick="$('#standard_login_form input[name=mode]').val('request_standard_password_reset'); $('#standard_login_form').submit();">request a password reset</span>.

                <script type="text/javascript">
                    ///////////////////////////////////////
                    function process_login_result(response)
                    ///////////////////////////////////////
                    {
                        if( response != 'OK' )
                        {
                            alert( 'There has been a problem:\n\n'
                                 + response + '\n\n'
                                 + 'Please try again. If you need assistance, please click the "Help & support" link on the page and post a support request.'
                                 );
                            $('#alternate_login').unblock();
                            return;
                        } // end function

                        if( $('#standard_login_form input[name=mode]').val() == 'request_standard_password_reset' )
                        {
                            alert( 'An email has been sent to the address you\'ve specified. Please check your inbox -and your spam box- and follow instructions.\n\n'
                                 + 'If you can\'t get the email after a few minutes, please click the "Help & support" link on the page and post a support request.'
                                 );
                            return;
                        } // end if

                        top.location.href = 'index.php?wasuuup='+(Math.random() * 1000000000000000);
                    } // end function

                    ////////////////////////////
                    $(document).ready(function()
                    ////////////////////////////
                    {
                        $('#standard_login_form').ajaxForm({
                            target:        '#standard_login_result',
                            success:       process_login_result
                        }); // end ajaxForm
                    }); // end function
                </script>
            </div><!-- /.standard_login -->

            <? load_extensions("welcome_page_login_area", $root_url); ?>

        </div>

        <div class="register ui-widget-content ui-corner-all" style="padding: 5px;">
            <div id="register_form_result" style="display: none;"></div>
            <form name="register_form" id="register_form" method="post" action="toolbox.php?wasuuup=<?=md5(mt_rand(1,65535))?>">
                <input type="hidden" name="mode" value="do_register">
                <h2 class="ui-widget-header ui-corner-all" style="padding: 5px; margin-top: 0;">Create your account</h2>
                <div align="center" style="margin-top: 10px;">
                    <div class="field">
                        Your name:
                        <div style="margin-left: 25px;">
                            <input type="text" name="name" style="width: 100%;">
                        </div>
                    </div>
                    <div class="field">
                        Your email:
                        <div style="margin-left: 25px;">
                            <input type="text" name="email" style="width: 100%;">
                        </div>
                    </div>
                    <div class="field">
                        Type a password:
                        <div style="margin-left: 25px;">
                            <input type="password" name="password" style="width: 100%;">
                        </div>
                    </div>
                    <div class="field">
                        Reype the password:
                        <div style="margin-left: 25px;">
                            <input type="password" name="password2" style="width: 100%;">
                        </div>
                    </div>
                </div>
                <div style="clear: both;">
                    By submitting this form, you agree to our <a href="<?=$config->website_pages["terms_of_service"]?>" target="_blank">Terms of Service</a>.
                    <button type="submit" style="width: 100px;">Submit</button>
                </div>
            </form>
            <script type="text/javascript">
                //////////////////////////////////////////
                function process_register_result(response)
                //////////////////////////////////////////
                {
                    if( response != 'OK' )
                    {
                        alert( 'There has been a problem:\n\n'
                             + response + '\n\n'
                             + 'Please try again. If you need assistance, please click the "Help & support" link on the page and post a support request.'
                             );
                        $('#alternate_login').unblock();
                        return;
                    } // end function

                    alert( 'An email has been sent to the address you\'ve specified. Please check your inbox -and your spam box- and follow instructions.\n\n'
                         + 'If you can\'t get the email after a few minutes, please try submitting the form again.\n'
                         + 'If the problem persists, click the "Help & support" link on the page and post a support request.'
                         );
                    return;
                } // end function

                ////////////////////////////
                $(document).ready(function()
                ////////////////////////////
                {
                    $('#register_form').ajaxForm({
                        target:        '#register_form_result',
                        success:       process_register_result
                    }); // end ajaxForm
                }); // end function
            </script>
        </div>

        <div id="supported_coins_content" style="clear: both;">
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

    </body>
</html>
