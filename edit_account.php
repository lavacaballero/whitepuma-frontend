<?php
    /**
     * Edit account page - for custom accounting
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

    $root_url = ".";
    if( ! is_file("config.php") ) die("ERROR: config file not found.");
    include "config.php";
    include "functions.php";
    include "models/tipping_provider.php";
    include "models/account.php";
    session_start();

    include "facebook-php-sdk/facebook.php";
    $fb_params = array(
        'appId'              => $config->facebook_app_id,
        'secret'             => $config->facebook_app_secret,
        'fileUpload'         => false,
        'allowSignedRequest' => true
    );
    $facebook = new Facebook($fb_params);

    $errors = $messages = array();

    $id_account     = "";
    ########################################
    if( ! empty($_GET["facebook_connect"]) )
    ########################################
    {
        # Facebook login
        $cookie_name         = $config->session_vars_prefix . $config->cookie_session_identifier;
        $session_from_cookie = false;
        if( empty($_COOKIE[$cookie_name]) ) throw_fake_401();

        $id_account = decryptRJ256($config->tokens_encryption_key, $_COOKIE[$cookie_name]);
        if( empty($id_account) ) throw_fake_401();

        $account = new account($id_account);
        if( ! $account->exists ) throw_fake_401();

        try
        {
            $fb_access_token = $facebook->getAccessToken();
            $facebook->setExtendedAccessToken();
            $fb_access_token = $facebook->getAccessToken();

            $fb_user_id = $facebook->getUser();
            $tmp_account = new account($fb_user_id);
            if( $tmp_account->exists && $tmp_account->id_account != $account->id_account )
            {
                $errors[] = "You already have your Facebook account connected with another UniDash account:<br>
                             Id: {$tmp_account->id_account} &bull;
                             Name: {$tmp_account->name} &bull;
                             Created: {$tmp_account->date_created}<br>.
                             You cannot connect a Facebook account with more than one UniDash account at the same time.";
            }
            else
            {
                $user_profile                        = $facebook->api("/me");
                $account->facebook_user_access_token = $fb_access_token;
                $account->timezone                   = $user_profile["timezone"];
                $account->last_update                =
                $account->last_activity              = date("Y-m-d H:i:s");

                if( empty($account->email) )
                {
                    $account->email = $user_profile["email"];
                    $query = "select * from {$config->db_tables["account"]}
                              where id_account <> '{$account->id_account}'
                              and   (email = '{$user_profile["email"]}' or alternate_email = '{$user_profile["email"]}')";
                    $res = mysql_query($query);
                    if( mysql_num_rows($res) == 0 ) $account->email = $user_profile["email"];
                    mysql_free_result($res);
                } # end if

                $account->save();

                $query = "
                    update ".$config->db_tables["account"]."
                    set facebook_id = '$fb_user_id'
                    where id_account  = '{$account->id_account}'
                ";
                mysql_query($query);

                $messages[] = "Account connected with Facebook User ID $fb_user_id.";
            } # end if
        }
        catch(Exception $e)
        {
            $errors[] = "Can't get login account from Facebook:<br>
                         ".$e->getMessage()."<br>
                         Please try again. If the problem persists, please take a screenshot of this page
                         and send it to us on a support request at our
                         <a href='{$config->website_pages["support"]}'>Help &amp; support forum</a>.";
        } // end try...catch
    } # end if

    $user_id = empty($id_account) ? get_online_user_id() : $id_account;
    if( empty($user_id) )
    {
        include $config->contents["welcome"];
        die();
    } # end if

    $account  = new account($user_id);
    $is_admin = isset($config->sysadmins[$account->id_account]);
    # header( "X-Admin: $user_id // $account->id_account ~ " . $config->sysadmins[$account->id_account] );
    if( $is_admin ) $admin_level = $config->sysadmins[$account->id_account];

    $_SESSION[$config->session_vars_prefix."current_coin_name"] = "_none_";
    header("Content-Type: text/html; charset=utf-8");

    $jquery_ui_theme = $config->user_home_jquery_ui_theme;
    $title_append = "Edit your account";

    if( $_REQUEST["mode"] == "save" )
    {
        if( empty($account->facebook_id) && empty($_POST["name"]) )  $errors[] = "Please specify your name.";
        if( empty($account->facebook_id) && empty($_POST["email"]) ) $errors[] = "Please provide a valid email address.";

        $name      = stripslashes(trim($_POST["name"]));
        $email     = stripslashes(trim($_POST["email"]));
        $alt_email = stripslashes(trim($_POST["alternate_email"]));
        $password  = stripslashes(trim($_POST["password"]));
        $password2 = stripslashes(trim($_POST["password2"]));

        if( empty($account->facebook_id) && empty($name) )  $errors[] = "Please specify your name.";
        if( empty($account->facebook_id) && empty($email) ) $errors[] = "Please provide a valid email address.";
        if( $email == $alt_email )                          $errors[] = "Please specify a different alternate email.";

        if( empty($account->facebook_id) && ! filter_var($email, FILTER_VALIDATE_EMAIL) )
            $errors[] = "Please type a valid primary email address.";

        if( ! empty($alt_email) && ! filter_var($alt_email, FILTER_VALIDATE_EMAIL) )
            $errors[] = "Please type a valid alternate email address.";

        if( $password != $password2 )
            $errors[] = "Provided passwords don't match. Please retype them.";


        if( empty($account->facebook_id) && ! empty($email) )
        {
            $query = "
                select * from ".$config->db_tables["account"]."
                where id_account <> '$account->id_account'
                and (email = '".addslashes($email)."' or alternate_email = '".addslashes($email)."')
            ";
            $res   = mysql_query($query);
            if( mysql_num_rows($res) > 0 )
                $errors[] = "The primary email you specified is already set to another account. Please use another primary email address.";
        } # end if

        if( ! empty($alt_email) )
        {
            $query = "
                select * from ".$config->db_tables["account"]."
                where id_account <> '$account->id_account'
                and (email = '".addslashes($alt_email)."' or alternate_email = '".addslashes($alt_email)."')
            ";
            $res   = mysql_query($query);
            if( mysql_num_rows($res) > 0 )
                $errors[] = "The alternate email you specified is set on another account. Please use another primary email address.";
        } # end if

        if( empty($errors) )
        {
            if( !( $name != $account->name || $email != $account->email || $alt_email != $account->alternate_email || $password != "" ) )
            {
                $messages[] = "No changes have been detected.";
            }
            else
            {
                $password_changed = ! empty($password);

                $limit        = date("Y-m-d H:i:s", strtotime("now + 30 minutes"));
                $token        = encryptRJ256($config->tokens_encryption_key, "$account->id_account\t$name\t$email\t$alt_email\t$password\t$limit");
                $ip           = $_SERVER['REMOTE_ADDR'];
                $hostname     = gethostbyaddr($_SERVER['REMOTE_ADDR']);
                $fecha_envio  = date("Y-m-d H:i:s");
                $mail_from    = "$config->mail_sender_name <$config->mail_sender_address>";
                $mail_to      = "$name <$email>";
                $url          = $config->website_pages["toolbox"] . "?mode=accept_account_changes&token=" . urlencode($token);

                $mail_subject = $config->app_display_shortname . " - Account modification request at $config->app_display_shortname";
                $mail_body = "We have received an account modification request on your behalf at $config->app_display_longname.\r\n"
                           . "\r\n"
                           . "Your name: $account->name --> $name\r\n"
                           . "Your email: ".(empty($account->email) ? "none" : $account->email)." --> ".(empty($email) ? "none" : $email)."\r\n"
                           . "Your alternate email: ".(empty($account->alternate_email) ? "none" : $account->alternate_email)." --> ".(empty($alt_email) ? "none" : $alt_email)."\r\n"
                           . "Password changed? ".($password_changed ? "YES" : "no")."\r\n"
                           . "\r\n"
                           . "If you've placed this request, please follow the next link to confirm it:\r\n"
                           . "$url\r\n"
                           . "\r\n"
                           . "If you didn't place this request, just disregard this email.\r\n"
                           . "\r\n"
                           . "Feel free to contact us if you have any doubt by following the next link:\r\n"
                           . $config->website_pages["support"] . "\r\n"
                           . "\r\n"
                           . "Regards,\r\n"
                           . $config->app_display_longname . "'s Team\r\n"
                           . "\r\n"
                           . "-----------------------------\r\n"
                           . "Date/Time:   $fecha_envio\r\n"
                           . "IP:          $ip\r\n"
                           . "Host:        $hostname\r\n"
                           . "Browser:     ".$_SERVER["HTTP_USER_AGENT"]."\r\n"
                           ;
                @mail(
                    $mail_to, $mail_subject, $mail_body,
                    "From: ".$mail_from . "\r\n" .
                    "MIME-Version: 1.0\r\n" .
                    "Content-Type: text/plain; charset=utf-8\r\n"
                );
                $messages[] = "An email was sent to the previous email address(es) on the account. Please check your inbox and spambox for further instructions.";
            } # end if
        } # end if
    } # end if
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
            $(function()
            {
                // UI stuff
                $('a.buttonized, button').button();
                $('.lightbox').lightbox({scaleImages: true, xScale: 2, yScale: 2});
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

            h1 button .ui-button-text, .ui-widget-header button .ui-button-text { font-size: 10pt; font-weight: normal; }
            @media all and (max-width: 5000px) and (min-width: 481px)
            {
                .field { text-align: left; display: inline-block; margin: 10px; width: 44%; vertical-align: top; }
            }
            @media all and (max-width: 480px) and (min-width: 100px)
            {
                .field { text-align: left; display: block; width: auto; vertical-align: top; }
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
                    location.href = '<?="index.php?switch_coin="?>'+coin_name+'<?= "&wasuuup=".md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>&tab=' + active;
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
                <a class="buttonized" href="<?="index.php?show=multicoin_dashboard&wasuuup=".md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>">
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
        <h3 style="margin-top: 0; font-style: italic;">Editing your account</h3>

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

        <form name="account_edit" id="account_edit" method="post" action="<?= $_SERVER["PHP_SELF"] ?>?wasuuup=<?=md5(mt_rand(1,65535))?>">
            <input type="hidden" name="mode" value="save">

            <h2 class="ui-widget-header ui-corner-all" style="padding: 10px; margin-top: 0;">Edit your account details</h2>

            <? if( ! empty($errors) ): ?>
                <div class="ui-state-error message_box ui-corner-all">
                    <? foreach($errors as $this_error): ?>
                        <span class="ui-icon embedded ui-icon-alert"></span>
                        <?= $this_error ?>
                    <? endforeach; ?>
                </div>
            <? endif; ?>

            <? if( ! empty($messages) ): ?>
                <div class="ui-state-highlight message_box ui-corner-all">
                    <? foreach($messages as $this_message): ?>
                        <span class="ui-icon embedded ui-icon-info"></span>
                        <?= $this_message ?>
                    <? endforeach; ?>
                </div>
            <? endif; ?>

            <? if( ! empty($account->facebook_id)): ?>
                <div class="ui-state-highlight ui-corner-all" style="clear: both;">
                    <span class="ui-icon embedded ui-icon-info"></span>
                    Your account is connected to your Facebook profile. You can't edit your name or your primary email.
                    For your safety, please consider adding an alternate email and specifying a password so you can get directly into
                    your dashboard if you loose your Facebook account.
                </div>
            <? endif; ?>

            <div align="center" style="margin-top: 10px;">
                <div class="field">
                    Your account id:
                    <div style="margin-left: 25px;">
                        <input type="text" disabled name="dummy" style="width: 100%;" value="<?=$account->id_account?>">
                    </div>
                </div>
                <div class="field">
                    Member since:
                    <div style="margin-left: 25px;">
                        <input type="text" disabled name="dummy" style="width: 100%;" value="<?=$account->date_created?>">
                    </div>
                </div>
                <div class="field">
                    Last activity registered:
                    <div style="margin-left: 25px;">
                        <input type="text" disabled name="dummy" style="width: 100%;" value="<?=time_elapsed_string($account->last_activity)?>">
                    </div>
                </div>
                <div class="field">
                    Your name:
                    <div style="margin-left: 25px;">
                        <input type="text" name="name" <? if( ! empty($account->facebook_id) ) echo "disabled"; ?> style="width: 100%;" value="<?= empty($name) ? htmlspecialchars($account->name) : htmlspecialchars($name) ?>">
                    </div>
                </div>
                <div class="field">
                    Your email:
                    <div style="margin-left: 25px;">
                        <input type="text" name="email" <? if( ! empty($account->facebook_id) ) echo "disabled"; ?> style="width: 100%;" value="<?= empty($email) ? $account->email : htmlspecialchars($email) ?>">
                    </div>
                </div>
                <div class="field">
                    (Optional) Alternate email:
                    <div style="margin-left: 25px;">
                        <input type="text" name="alternate_email" style="width: 100%;" value="<?= empty($alt_email) ? $account->alternate_email : htmlspecialchars($alt_email)?>">
                    </div>
                </div>
                <div class="field">
                    <? if( empty($account->facebook_id) ): ?>
                        (Optional) Type a new password:
                    <? else: ?>
                        (Optional) New alternate password::
                    <? endif; ?>
                    <div style="margin-left: 25px;">
                        <input type="password" name="password" style="width: 100%;">
                    </div>
                </div>
                <div class="field">
                    Retype the new password:
                    <div style="margin-left: 25px;">
                        <input type="password" name="password2" style="width: 100%;">
                    </div>
                </div>
            </div>
            <div style="clear: both; text-align: center;">
                <button type="submit" style="width: 100px;">Submit</button>
            </div>
        </form>

        <? if( empty($account->facebook_id) ): ?>


            <!-- =================== -->
            <!-- Facebook connection -->
            <!-- =================== -->

            <h2 class="ui-widget-header ui-corner-all" style="padding: 10px; margin-top: 0;">
                <span class="fa-stack fa-lg">
                    <i class="fa fa-square-o fa-stack-2x"></i>
                    <i class="fa fa-facebook fa-stack-1x"></i>
                </span>
                Connect with Facebook
            </h2>

            <div align="center">

                <div class="ui-state-highlight message_box ui-corner-all">
                    <span class="fa fa-warning"></span>
                    Important: once connected, you can't disconnect it. Your name and primary email fields will be locked and updated from Facebook.
                </div>

                <p align="center">
                    <fb:login-button size="xlarge" width="100%" scope="<?=$config->facebook_auth_scope?>"
                        onlogin="location.href='<?=$_SERVER["PHP_SELF"]?>?facebook_connect=true&wasuuup=' + parseInt(Math.random() * 1000000000000000)">
                        Connect now
                    </fb:login-button>
                </p>

            </div>

        <? endif; ?>

        <? load_extensions("edit_account_addition"); ?>

    </body>
</html>
