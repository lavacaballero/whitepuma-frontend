<?
    /**
     * Toolbox
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
     *
     * @returns string Requested info or error message in human-readable format.
     */

    $root_url = ".";
    if( ! is_file("config.php") ) die("ERROR: config file not found.");
    include "config.php";
    include "functions.php";
    include "models/tipping_provider.php";
    include "models/account.php";

    if( empty($_REQUEST["mode"]) )
    {
        header("Content-Type: text/plain; charset=utf-8");
        die("ERROR: this script has to be invoked under an operation mode, and it wasn't provided!");
    } # end if

    ############################################################
    if( $_REQUEST["mode"] == "request_standard_password_reset" )
    ############################################################
    {
        $alternate_email = trim($_REQUEST["email"]);

        if( empty($alternate_email) )
        {
            header("Content-Type: text/plain; charset=utf-8");
            die("ERROR: no email has been provided.");
        } # end if

        if( ! is_resource($config->db_handler) ) db_connect();
        $query = "select * from ".$config->db_tables["account"]." where email = '".addslashes($alternate_email)."' or alternate_email = '".addslashes($alternate_email)."'";
        $res   = mysql_query($query);

        if( mysql_num_rows($res) == 0 )
        {
            header("Content-Type: text/plain; charset=utf-8");
            die("ERROR: there is no account attached to the email you've provided.");
        } # end if

        $row = mysql_fetch_object($res);
        mysql_free_result($res);
        $account = new account($row);

        if( ! empty($account->facebook_id) && empty($account->alternate_password) )
        {
            header("Content-Type: text/plain; charset=utf-8");
            die("ERROR: your account is tied to your Facebook account and no alternate password has been set. You need to login through your Facebook account and set an alternate login method by hitting the 'Emergency info' button next to the wallet address on any coin dashboard.");
        } # end if

        $limit        = date("Y-m-d H:i:s", strtotime("now + 30 minutes"));
        $token        = encryptRJ256($config->tokens_encryption_key, "$account->id_account\t$limit");
        $ip           = $_SERVER['REMOTE_ADDR'];
        $hostname     = gethostbyaddr($_SERVER['REMOTE_ADDR']);
        $fecha_envio  = date("Y-m-d H:i:s");
        $mail_from    = "$config->mail_sender_name<$config->mail_sender_address>";
        $mail_to      = ( empty($account->email) ? "$account->name alternate email<$account->alternate_email>" : "$account->name<$account->email>");
        $url          = $config->website_pages["toolbox"] . "?mode=authorize_standard_password_reset&token=" . urlencode($token);

        $mail_subject = $config->app_display_shortname . " - Emergency recovery password reset";
        $mail_body = "We have received a password reset request for your account.\r\n"
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
            "Content-Type: text/plain; charset=utf-8\r\n" .
            ( empty($account->email) ? "" : (empty($account->alternate_email) ? "" : "Cc: $account->name alternate email<$account->alternate_email>\r\n") )
        );

        header("Content-Type: text/plain; charset=utf-8");
        die("OK");
    } # end if

    ##############################################################
    if( $_REQUEST["mode"] == "authorize_standard_password_reset" )
    ##############################################################
    {
        if( trim($_REQUEST["token"]) == "" )
        {
            header("Content-Type: text/plain; charset=utf-8");
            die("ERROR: no token specified.");
        } # end if

        $continue        = true;
        $decrypted_token = decryptRJ256($config->tokens_encryption_key, trim($_REQUEST["token"]));
        list($id_account, $limit) = explode("\t", $decrypted_token);

        if( preg_match('/[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/', $limit) == 0 )
        {
            $message_class  = "ui-state-error";
            $title          = "Invalid token";
            $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                            . "The token you've provided is invalid.<br>\n"
                            . "Please check the email we've sent to you.<br>"
                            . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
            $continue = false;
        } # end if

        if( $continue && date("Y-m-d H:i:s") > $limit )
        {
            $message_class  = "ui-state-error";
            $title          = "Token expired";
            $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                            . "The token you've provided alredy expired. Tokens have a life of 30 minutes.<br>\n"
                            . "Please request again the password reset and check your email.<br>"
                            . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
            $continue = false;
        } # end if

        if( $continue )
        {
            $account = new account($id_account);

            $new_alternate_password      = randomPassword();
            $account->alternate_password = md5($new_alternate_password);
            $account->last_activity      =
            $account->last_update        = date("Y-m-d H:i:s");
            $account->save();

            $ip           = $_SERVER['REMOTE_ADDR'];
            $hostname     = gethostbyaddr($_SERVER['REMOTE_ADDR']);
            $fecha_envio  = date("Y-m-d H:i:s");
            $mail_from    = "$config->mail_sender_name<$config->mail_sender_address>";
            $mail_to      = ( empty($account->email) ? "$account->name alternate email<$account->alternate_email>" : "$account->name<$account->email>");

            $mail_subject = $config->app_display_shortname . " - Emergency recovery login info";
            $mail_body = "Greetings! Here is your updated login info:\r\n"
                       . "\r\n"
                       . "Main email:          ".$account->email."\r\n"
                       . "Alternate email:     ".(empty($account->alternate_email) ? "none": $account->alternate_email)."\r\n"
                       # "Alternate password:  $new_alternate_password\r\n"
                       . "Alternate login URL: ".$config->website_pages["root_url"]."\r\n"
                       . "\r\n"
                       . "Please save this information for further reference.\r\n"
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
                "Content-Type: text/plain; charset=utf-8\r\n" .
                ( empty($account->email) ? "" : (empty($account->alternate_email) ? "" : "Cc: $account->name alternate email<$account->alternate_email>\r\n") )
            );

            $message_class  = "ui-state-highlight";
            $title          = "Account has been updated.";
            $message        = "<span class='ui-icon embedded ui-icon-info'></span>"
                            . "Your new password is: <b>$new_alternate_password</b><br>"
                            . "A notification has been been sent to your email account(s).<br>"
                            . "Now you can re-login to <a href='index.php'>your dashboard</a>.<br>"
                            . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
        } # end if

        ?><html>
            <head>
                <title><?=$config->app_display_longname?> - <?=$title?></title>
                <meta name="viewport" content="width=device-width" />
                <meta http-equiv="X-UA-Compatible"      content="IE=Edge" />
                <link rel="icon"                        href="<?= $config->favicon ?>">
                <link rel="shortcut icon"               href="<?= $config->favicon ?>">
                <link rel="stylesheet" type="text/css"  href="//code.jquery.com/ui/1.10.4/themes/<?=$config->current_coin_data["jquery_ui_theme"]?>/jquery-ui.css">
                <link rel="stylesheet" type="text/css"  href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
                <link rel="stylesheet" type="text/css"  href="misc/styles.css?v=<?=$config->scripts_and_styles_version?>">
                <style type="text/css">
                    <?= $config->current_coin_data["body_font_definition"] ?>
                    <?= $config->current_coin_data["ui_font_definition"] ?>
                    .coin_signed:after { content: ' <?=$config->current_coin_data["coin_name_plural"]?>'; }
                </style>
            </head>
            <body>
                <h1><?=$title?></h1>
                <div class="message_box <?=$message_class?> ui-corner-all"><?= $message ?></div>
            </body>
        </html><?
        die();
    } # end if

    #######################################################
    if( $_REQUEST["mode"] == "request_alt_password_reset" )
    #######################################################
    {
        $alternate_email = trim($_REQUEST["email"]);

        if( empty($alternate_email) )
        {
            header("Content-Type: text/plain; charset=utf-8");
            die("ERROR: no email has been provided.");
        } # end if

        if( ! is_resource($config->db_handler) ) db_connect();
        $query = "select * from ".$config->db_tables["account"]." where alternate_email = '".addslashes($alternate_email)."'";
        $res   = mysql_query($query);

        if( mysql_num_rows($res) == 0 )
        {
            header("Content-Type: text/plain; charset=utf-8");
            die("ERROR: there is no account attached to the email you've provided.");
        } # end if

        $row = mysql_fetch_object($res);
        mysql_free_result($res);
        $account = new account($row);

        $limit        = date("Y-m-d H:i:s", strtotime("now + 30 minutes"));
        $token        = encryptRJ256($config->tokens_encryption_key, "$account->id_account\t$limit");
        $ip           = $_SERVER['REMOTE_ADDR'];
        $hostname     = gethostbyaddr($_SERVER['REMOTE_ADDR']);
        $fecha_envio  = date("Y-m-d H:i:s");
        $mail_from    = "$config->mail_sender_name<$config->mail_sender_address>";
        $mail_to      = ( empty($account->email) ? "$account->name alternate email<$account->alternate_email>" : "$account->name<$account->email>");
        $url          = $config->website_pages["toolbox"] . "?mode=authorize_password_reset&token=" . urlencode($token);

        $mail_subject = $config->app_display_shortname . " - Emergency recovery password reset";
        $mail_body = "We have received an alternate password reset request for your account.\r\n"
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
            "Content-Type: text/plain; charset=utf-8\r\n" .
            ( empty($account->email) ? "" : (empty($account->alternate_email) ? "" : "Cc: $account->name alternate email<$account->alternate_email>\r\n") )
        );

        header("Content-Type: text/plain; charset=utf-8");
        die("OK");
    } # end if

    #####################################################
    if( $_REQUEST["mode"] == "authorize_password_reset" )
    #####################################################
    {
        if( trim($_REQUEST["token"]) == "" )
        {
            header("Content-Type: text/plain; charset=utf-8");
            die("ERROR: no token specified.");
        } # end if

        $continue        = true;
        $decrypted_token = decryptRJ256($config->tokens_encryption_key, trim($_REQUEST["token"]));
        list($id_account, $limit) = explode("\t", $decrypted_token);

        if( preg_match('/[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/', $limit) == 0 )
        {
            $message_class  = "ui-state-error";
            $title          = "Invalid token";
            $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                            . "The token you've provided is invalid.<br>\n"
                            . "Please check the email we've sent to you.<br>"
                            . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
            $continue = false;
        } # end if

        if( $continue && date("Y-m-d H:i:s") > $limit )
        {
            $message_class  = "ui-state-error";
            $title          = "Token expired";
            $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                            . "The token you've provided alredy expired. Tokens have a life of 30 minutes.<br>\n"
                            . "Please request again the password reset and check your email.<br>"
                            . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
            $continue = false;
        } # end if

        if( $continue )
        {
            $account = new account($id_account);

            $new_alternate_password      = randomPassword();
            $account->alternate_password = md5($new_alternate_password);
            $account->last_activity      =
            $account->last_update        = date("Y-m-d H:i:s");
            $account->save();

            $ip           = $_SERVER['REMOTE_ADDR'];
            $hostname     = gethostbyaddr($_SERVER['REMOTE_ADDR']);
            $fecha_envio  = date("Y-m-d H:i:s");
            $mail_from    = "$config->mail_sender_name<$config->mail_sender_address>";
            $mail_to      = ( empty($account->email) ? "$account->name alternate email<$account->alternate_email>" : "$account->name<$account->email>");

            $mail_subject = $config->app_display_shortname . " - Emergency recovery login info";
            $mail_body = "Greetings! Here is your updated emergency recovery login info:\r\n"
                       . "\r\n"
                       . "Alternate email:     ".$account->alternate_email."\r\n"
                       # "Alternate password:  $new_alternate_password\r\n"
                       . "Alternate login URL: ".$config->website_pages["root_url"]."\r\n"
                       . "\r\n"
                       . "Please save this information for further reference.\r\n"
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
                "Content-Type: text/plain; charset=utf-8\r\n" .
                ( empty($account->email) ? "" : (empty($account->alternate_email) ? "" : "Cc: $account->name alternate email<$account->alternate_email>\r\n") )
            );

            $message_class  = "ui-state-highlight";
            $title          = "Account has been updated.";
            $message        = "<span class='ui-icon embedded ui-icon-info'></span>"
                            . "Your new password is: <b>$new_alternate_password</b><br>"
                            . "A notification has been sent to your email account(s).<br>"
                            . "Now you can re-login to <a href='index.php'>your dashboard</a>.<br>"
                            . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
        } # end if

        ?><html>
            <head>
                <title><?=$config->app_display_longname?> - <?=$title?></title>
                <meta name="viewport" content="width=device-width" />
                <meta http-equiv="X-UA-Compatible"      content="IE=Edge" />
                <link rel="icon"                        href="<?= $config->favicon ?>">
                <link rel="shortcut icon"               href="<?= $config->favicon ?>">
                <link rel="stylesheet" type="text/css"  href="//code.jquery.com/ui/1.10.4/themes/<?=$config->current_coin_data["jquery_ui_theme"]?>/jquery-ui.css">
                <link rel="stylesheet" type="text/css"  href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
                <link rel="stylesheet" type="text/css"  href="misc/styles.css?v=<?=$config->scripts_and_styles_version?>">
                <style type="text/css">
                    <?= $config->current_coin_data["body_font_definition"] ?>
                    <?= $config->current_coin_data["ui_font_definition"] ?>
                    .coin_signed:after { content: ' <?=$config->current_coin_data["coin_name_plural"]?>'; }
                </style>
            </head>
            <body>
                <h1><?=$title?></h1>
                <div class="message_box <?=$message_class?> ui-corner-all"><?= $message ?></div>
            </body>
        </html><?
        die();
    } # end if

    ##############################################
    if( $_REQUEST["mode"] == "do_standard_login" )
    ##############################################
    {
        $email    = trim($_REQUEST["email"]);
        $password = trim($_REQUEST["password"]);

        if( ! is_resource($config->db_handler) ) db_connect();
        $query = "select * from ".$config->db_tables["account"]." where (email = '".addslashes($email)."' or alternate_email = '".addslashes($email)."') and alternate_password = '".md5($password)."'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 )
        {
            header("Content-Type: text/plain; charset=utf-8");
            die("ERROR: couldn't find that email/password combination.");
        } # end if
        $row = mysql_fetch_object($res);
        mysql_free_result($res);

        $account = new account($row);
        $account->ping();

        $cookie_name  = $config->session_vars_prefix . $config->cookie_session_identifier;
        $cookie_value = encryptRJ256($config->tokens_encryption_key, $account->id_account);
        setcookie($cookie_name,       $cookie_value, strtotime("now + 30 days"), "/", $config->cookie_domain);
        $_COOKIE[$cookie_name] = $cookie_value;

        header("Content-Type: text/plain; charset=utf-8");
        die("OK");
    } # end if

    ###############################################
    if( $_REQUEST["mode"] == "do_alternate_login" )
    ###############################################
    {
        $email    = trim($_REQUEST["email"]);
        $password = trim($_REQUEST["password"]);

        if( ! is_resource($config->db_handler) ) db_connect();
        $query = "select * from ".$config->db_tables["account"]." where alternate_email = '".addslashes($email)."' and alternate_password = '".md5($password)."'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 )
        {
            header("Content-Type: text/plain; charset=utf-8");
            die("ERROR: couldn't find that email/password combination.");
        } # end if
        $row = mysql_fetch_object($res);
        mysql_free_result($res);

        $account = new account($row);
        $account->ping();

        $cookie_name  = $config->session_vars_prefix . $config->cookie_session_identifier;
        $cookie_value = encryptRJ256($config->tokens_encryption_key, $account->id_account);
        setcookie($cookie_name,       $cookie_value, strtotime("now + 30 days"), "/", $config->cookie_domain);
        $_COOKIE[$cookie_name] = $cookie_value;

        header("Content-Type: text/plain; charset=utf-8");
        die("OK");
    } # end if

    ##################################################
    if( $_REQUEST["mode"] == "authorize_alt_changes" )
    ##################################################
    {
        if( trim($_REQUEST["token"]) == "" )
        {
            header("Content-Type: text/plain; charset=utf-8");
            die("ERROR: no token specified.");
        } # end if

        $continue        = true;
        $decrypted_token = decryptRJ256($config->tokens_encryption_key, trim($_REQUEST["token"]));
        list($id_account, $alternate_password, $limit) = explode("\t", $decrypted_token);

        if( preg_match('/[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/', $limit) == 0 )
        {
            $message_class  = "ui-state-error";
            $title          = "Invalid token";
            $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                            . "The token you've provided is invalid.<br>\n"
                            . "Please check the email we've sent to you.<br>"
                            . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
            $continue = false;
        } # end if

        if( $continue && date("Y-m-d H:i:s") > $limit )
        {
            $message_class  = "ui-state-error";
            $title          = "Token expired";
            $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                            . "The token you've provided alredy expired. Tokens have a life of 30 minutes.<br>\n"
                            . "Please login to the dashboard, resubmit your changes and recheck your email.<br>"
                            . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
            $continue = false;
        } # end if

        if( $continue )
        {
            $account = new account($id_account);
            if( stristr($account->alternate_password, "pending:") === false )
            {
                $message_class  = "ui-state-error";
                $title          = "Account has no pending change requests";
                $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                                . "This account has no pending change requests. This means you haven't requested changes on your alternate login method or you've reloaded the page after the change was accepted.<br>\n"
                                . "Please login to the dashboard, resubmit your changes and recheck your email.<br>"
                                . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                                ;
                $continue = false;
            } # end if
        } # end if

        if( $continue )
        {
            $account->alternate_password = str_replace("pending:", "", $account->alternate_password);
            $account->last_activity      =
            $account->last_update        = date("Y-m-d H:i:s");
            $account->save();

            $message_class  = "ui-state-highlight";
            $title          = "Account has been updated.";
            $message        = "<span class='ui-icon embedded ui-icon-info'></span>"
                            . "Now you may need to re-login to <a href='index.php'>your dashboard</a>.<br>"
                            . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
        } # end if

        ?><html>
            <head>
                <title><?=$config->app_display_longname?> - <?=$title?></title>
                <meta name="viewport"                   content="width=device-width" />
                <meta http-equiv="X-UA-Compatible"      content="IE=Edge" />
                <link rel="icon"                        href="<?= $config->favicon ?>">
                <link rel="shortcut icon"               href="<?= $config->favicon ?>">
                <link rel="stylesheet" type="text/css"  href="//code.jquery.com/ui/1.10.4/themes/<?=$config->current_coin_data["jquery_ui_theme"]?>/jquery-ui.css">
                <link rel="stylesheet" type="text/css"  href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
                <link rel="stylesheet" type="text/css"  href="misc/styles.css?v=<?=$config->scripts_and_styles_version?>">
                <style type="text/css">
                    <?= $config->current_coin_data["body_font_definition"] ?>
                    <?= $config->current_coin_data["ui_font_definition"] ?>
                    .coin_signed:after { content: ' <?=$config->current_coin_data["coin_name_plural"]?>'; }
                </style>
            </head>
            <body>
                <h1><?=$title?></h1>
                <div class="message_box <?=$message_class?> ui-corner-all"><?= $message ?></div>
            </body>
        </html><?
        die();

    } # end if

    ###################################################
    if( $_REQUEST["mode"] == "accept_account_changes" )
    ###################################################
    {
        if( trim($_REQUEST["token"]) == "" )
        {
            header("Content-Type: text/plain; charset=utf-8");
            die("ERROR: no token specified.");
        } # end if

        $continue        = true;
        $decrypted_token = decryptRJ256($config->tokens_encryption_key, trim($_REQUEST["token"]));
        list($id_account, $name, $email, $alt_email, $password, $limit) = explode("\t", $decrypted_token);

        if( preg_match('/[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/', $limit) == 0 )
        {
            $message_class  = "ui-state-error";
            $title          = "Invalid token";
            $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                            . "The token you've provided is invalid.<br>\n"
                            . "Please check the email we've sent to you.<br>"
                            . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
            $continue = false;
        } # end if

        if( $continue && date("Y-m-d H:i:s") > $limit )
        {
            $message_class  = "ui-state-error";
            $title          = "Token expired";
            $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                            . "The token you've provided alredy expired. Tokens have a life of 30 minutes.<br>\n"
                            . "Please login to the dashboard, resubmit your changes and recheck your email.<br>"
                            . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
            $continue = false;
        } # end if

        if( $continue )
        {
            $account = new account($id_account);
            $account->name               = $name;
            $account->email              = $email;
            $account->alternate_email    = $alt_email;
            $account->last_activity      =
            $account->last_update        = date("Y-m-d H:i:s");
            if( ! empty($password) ) $account->alternate_password = md5($password);
            $account->save();

            $message_class  = "ui-state-highlight";
            $title          = "Account has been updated.";
            $message        = "<span class='ui-icon embedded ui-icon-info'></span>"
                            . "If you have changed your password, don't forget to logout/reset your session at <a href='index.php?mode=logout'>your dashboard</a>.<br>"
                            . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
        } # end if

        ?><html>
            <head>
                <title><?=$config->app_display_longname?> - <?=$title?></title>
                <meta name="viewport"                   content="width=device-width" />
                <meta http-equiv="X-UA-Compatible"      content="IE=Edge" />
                <link rel="icon"                        href="<?= $config->favicon ?>">
                <link rel="shortcut icon"               href="<?= $config->favicon ?>">
                <link rel="stylesheet" type="text/css"  href="//code.jquery.com/ui/1.10.4/themes/<?=$config->current_coin_data["jquery_ui_theme"]?>/jquery-ui.css">
                <link rel="stylesheet" type="text/css"  href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
                <link rel="stylesheet" type="text/css"  href="misc/styles.css?v=<?=$config->scripts_and_styles_version?>">
                <style type="text/css">
                    <?= $config->current_coin_data["body_font_definition"] ?>
                    <?= $config->current_coin_data["ui_font_definition"] ?>
                    .coin_signed:after { content: ' <?=$config->current_coin_data["coin_name_plural"]?>'; }
                </style>
            </head>
            <body>
                <h1><?=$title?></h1>
                <div class="message_box <?=$message_class?> ui-corner-all"><?= $message ?></div>
            </body>
        </html><?
        die();

    } # end if

    ########################################
    if( $_REQUEST["mode"] == "do_register" )
    ########################################
    {
        if( empty($_POST["name"]) )      die("Please type your real name.");
        if( empty($_POST["email"]) )     die("Please type a valid email address you can check.");
        if( empty($_POST["password"]) )  die("Please type a password.");
        if( empty($_POST["password2"]) ) die("Please retype the password.");

        $name      = stripslashes(trim($_POST["name"]));
        $email     = stripslashes(trim($_POST["email"]));
        $password  = stripslashes(trim($_POST["password"]));
        $password2 = stripslashes(trim($_POST["password2"]));

        if( empty($name) )      die("Please type your real name.");
        if( empty($email) )     die("Please type a valid email address you can check.");
        if( empty($password) )  die("Please type a password.");
        if( empty($password2) ) die("Please retype the password.");

        if( ! filter_var($email, FILTER_VALIDATE_EMAIL) ) die("Please type a valid email address you can check.");
        if( $password != $password2 )                     die("Passwords don't match. Please retype them.");

        if( ! is_resource($config->db_handler) ) db_connect();
        $query = "select * from ".$config->db_tables["account"]." where email = '".addslashes($email)."' or alternate_email = '".addslashes($email)."'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) > 0 ) die("There is an account already registered to the email address you specified. Please use another.");

        if( $_REQUEST["submode"] == "direct_register" )
        {
            # Let's save the account
            $id_account                  = $config->custom_account_creation_prefix . uniqid(true);
            $account                     = new account();
            $account->id_account         = $id_account;
            $account->name               = $name;
            $account->email              = $email;
            $account->alternate_password = md5(mt_rand(1,65535));
            $account->tipping_provider   = $config->current_tipping_provider_keyname;
            $account->date_created       =
            $account->last_update        =
            $account->last_activity      = date("Y-m-d H:i:s");
            $account->save();

            # Let's open the session
            $cookie_name  = $config->session_vars_prefix . $config->cookie_session_identifier;
            $cookie_value = encryptRJ256($config->tokens_encryption_key, $account->id_account);
            setcookie($cookie_name,       $cookie_value, strtotime("now + 30 days"), "/", $config->cookie_domain);
            $_COOKIE[$cookie_name] = $cookie_value;

            die("OK");
        } # end if

        $limit        = date("Y-m-d H:i:s", strtotime("now + 30 minutes"));
        $token        = encryptRJ256($config->tokens_encryption_key, "$name\t$email\t$password\t$limit");
        $ip           = $_SERVER['REMOTE_ADDR'];
        $hostname     = gethostbyaddr($_SERVER['REMOTE_ADDR']);
        $fecha_envio  = date("Y-m-d H:i:s");
        $mail_from    = "$config->mail_sender_name<$config->mail_sender_address>";
        $mail_to      = "$name <$email>";
        $url          = $config->website_pages["toolbox"] . "?mode=activate_account&token=" . urlencode($token);

        $mail_subject = $config->app_display_shortname . " - Account creation request at $config->app_display_shortname";
        $mail_body = "We have received an account creation request on your behalf at $config->app_display_longname.\r\n"
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

        die("OK");
    } # end if

    #############################################
    if( $_REQUEST["mode"] == "activate_account" )
    #############################################
    {
        if( trim($_REQUEST["token"]) == "" )
        {
            header("Content-Type: text/plain; charset=utf-8");
            die("ERROR: no token specified.");
        } # end if

        $continue        = true;
        $decrypted_token = decryptRJ256($config->tokens_encryption_key, trim($_REQUEST["token"]));
        list($name, $email, $password, $limit) = explode("\t", $decrypted_token);

        if( preg_match('/[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/', $limit) == 0 )
        {
            $message_class  = "ui-state-error";
            $title          = "Invalid token";
            $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                            . "The token you've provided is invalid.<br>\n"
                            . "Please check the email we've sent to you.<br>"
                            . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
            $continue = false;
        } # end if

        if( $continue && date("Y-m-d H:i:s") > $limit )
        {
            $message_class  = "ui-state-error";
            $title          = "Token expired";
            $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                            . "The token you've provided alredy expired. Tokens have a life of 30 minutes.<br>\n"
                            . "Please login to the dashboard, resubmit your changes and recheck your email.<br>"
                            . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
            $continue = false;
        } # end if

        if( $continue )
        {
            if( ! is_resource($config->db_handler) ) db_connect();
            $query = "select * from ".$config->db_tables["account"]." where email = '".addslashes($email)."' or alternate_email = '".addslashes($email)."'";
            $res   = mysql_query($query);
            if( mysql_num_rows($res) > 0 )
            {
                $message_class  = "ui-state-error";
                $title          = "Account already registered";
                $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                                . "There is an account already registered to the email address you specified. Please use another.<br>\n"
                                . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                                ;
                $continue = false;
            } # end if
        } # end if

        if( $continue )
        {
            $id_account                  = $config->custom_account_creation_prefix . uniqid(true);
            $account                     = new account();
            $account->id_account         = $id_account;
            $account->name               = $name;
            $account->email              = $email;
            $account->alternate_password = md5($password);
            $account->tipping_provider   = $config->current_tipping_provider_keyname;
            $account->date_created       =
            $account->last_update        =
            $account->last_activity      = date("Y-m-d H:i:s");
            $account->save();

            $message_class  = "ui-state-highlight";
            $title          = "Account has been created!";
            $message        = "<span class='ui-icon embedded ui-icon-info'></span>"
                            . "Now you may login to <a href='index.php'>your dashboard</a>.<br>"
                            . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
        } # end if

        ?><html>
            <head>
                <title><?=$config->app_display_longname?> - <?=$title?></title>
                <meta name="viewport" content="width=device-width" />
                <meta http-equiv="X-UA-Compatible"      content="IE=Edge" />
                <link rel="icon"                        href="<?= $config->favicon ?>">
                <link rel="shortcut icon"               href="<?= $config->favicon ?>">
                <link rel="stylesheet" type="text/css"  href="//code.jquery.com/ui/1.10.4/themes/<?=$config->current_coin_data["jquery_ui_theme"]?>/jquery-ui.css">
                <link rel="stylesheet" type="text/css"  href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
                <link rel="stylesheet" type="text/css"  href="misc/styles.css?v=<?=$config->scripts_and_styles_version?>">
                <style type="text/css">
                    <?= $config->current_coin_data["body_font_definition"] ?>
                    <?= $config->current_coin_data["ui_font_definition"] ?>
                    .coin_signed:after { content: ' <?=$config->current_coin_data["coin_name_plural"]?>'; }
                </style>
            </head>
            <body>
                <h1><?=$title?></h1>
                <div class="message_box <?=$message_class?> ui-corner-all"><?= $message ?></div>
            </body>
        </html><?
        die();
    } # end if

    ##################################################
    if( $_REQUEST["mode"] == "disable_notifications" )
    ##################################################
    {
        if( trim($_REQUEST["token"]) == "" )
        {
            header("Content-Type: text/plain; charset=utf-8");
            die("ERROR: no token specified.");
        } # end if

        $continue        = true;
        $id_account      = decryptRJ256($config->tokens_encryption_key, trim($_REQUEST["token"]));

        if( ! is_resource($config->db_handler) ) db_connect();
        $query = "select * from ".$config->db_tables["account"]." where id_account = '$id_account'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 )
        {
            $message_class  = "ui-state-error";
            $title          = "Account doesn't exist!";
            $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                            . "The account id inside your token is not registered into our database.<br>\n"
                            . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
            $continue = false;
        } # end if

        if( $continue )
        {
            $account                        = new account($id_account);
            $account->receive_notifications = 'false';
            $account->last_update           =
            $account->last_activity         = date("Y-m-d H:i:s");
            $account->save();

            $message_class  = "ui-state-highlight";
            $title          = "Notifications disabled";
            $message        = "<span class='ui-icon embedded ui-icon-info'></span>"
                            . "You will no longer receive notifications from us.<br>"
                            . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
        } # end if

        ?><html>
            <head>
                <title><?=$config->app_display_longname?> - <?=$title?></title>
                <meta name="viewport" content="width=device-width" />
                <meta http-equiv="X-UA-Compatible"      content="IE=Edge" />
                <link rel="icon"                        href="<?= $config->favicon ?>">
                <link rel="shortcut icon"               href="<?= $config->favicon ?>">
                <link rel="stylesheet" type="text/css"  href="//code.jquery.com/ui/1.10.4/themes/<?=$config->current_coin_data["jquery_ui_theme"]?>/jquery-ui.css">
                <link rel="stylesheet" type="text/css"  href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
                <link rel="stylesheet" type="text/css"  href="misc/styles.css?v=<?=$config->scripts_and_styles_version?>">
                <style type="text/css">
                    <?= $config->current_coin_data["body_font_definition"] ?>
                    <?= $config->current_coin_data["ui_font_definition"] ?>
                </style>
            </head>
            <body>
                <h1><?=$title?></h1>
                <div class="message_box <?=$message_class?> ui-corner-all"><?= $message ?></div>
            </body>
        </html><?
        die();
    } # end if

    #########################################################
    if( $_REQUEST["mode"] == "authorize_account_extensions" )
    #########################################################
    {
        if( trim($_REQUEST["token"]) == "" )
        {
            header("Content-Type: text/plain; charset=utf-8");
            die("ERROR: no token specified.");
        } # end if

        $continue        = true;
        $decrypted_token = decryptRJ256($config->tokens_encryption_key, trim($_REQUEST["token"]));
        list($id_account, $previous_rerouting_data, $new_reroute_to, $limit) = explode("\t", $decrypted_token);

        if( preg_match('/[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/', $limit) == 0 )
        {
            $message_class  = "ui-state-error";
            $title          = "Invalid token";
            $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                            . "The token you've provided is invalid.<br>\n"
                            . "Please check the email we've sent to you.<br>"
                            . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
            $continue = false;
        } # end if

        if( $continue && date("Y-m-d H:i:s") > $limit )
        {
            $message_class  = "ui-state-error";
            $title          = "Token expired";
            $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                            . "The token you've provided alredy expired. Tokens have a life of 30 minutes.<br>\n"
                            . "Please login to the dashboard, resubmit your changes and recheck your email.<br>"
                            . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
            $continue = false;
        } # end if

        if( $continue )
        {
            $account = new account($id_account);
            if( ! $account->exists )
            {
                $message_class  = "ui-state-error";
                $title          = "Unexistent account.";
                $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                                . "This account does not exist in our database.<br>\n"
                                . "Please login to the dashboard, resubmit your changes and recheck your email.<br>"
                                . "If you need assistance, please contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                                ;
                $continue = false;
            } # end if
        } # end if

        if( $continue )
        {
            include "models/account_extensions.php";
            $account_extensions = new account_extensions($id_account);

            $account_extensions->reroute_to = $new_reroute_to;
            if( ! $account_extensions->exists ) $account_extensions->id_account = $id_account;
            $account_extensions->save();

            $notification = empty($previous_rerouting_data)
                          ? "Account extended settings saved. All future transactions for {$account->id_account} are " . (empty($new_reroute_to) ? "going to be kept." : "going to be sent to $new_reroute_to.")
                          : "Account extended settings saved. All future transactions previously sent to $previous_rerouting_data are " . (empty($new_reroute_to) ? "going to be kept." : "going to be sent to $new_reroute_to.")
                          ;

            $message_class  = "ui-state-highlight";
            $title          = "Account extensions updated.";
            $message        = "<span class='ui-icon embedded ui-icon-info'></span>"
                            . "$notification"
                            ;
        } # end if
        ?><html>
            <head>
                <title><?=$config->app_display_longname?> - <?=$title?></title>
                <meta name="viewport"                   content="width=device-width" />
                <meta http-equiv="X-UA-Compatible"      content="IE=Edge" />
                <link rel="icon"                        href="<?= $config->favicon ?>">
                <link rel="shortcut icon"               href="<?= $config->favicon ?>">
                <link rel="stylesheet" type="text/css"  href="//code.jquery.com/ui/1.10.4/themes/<?=$config->current_coin_data["jquery_ui_theme"]?>/jquery-ui.css">
                <link rel="stylesheet" type="text/css"  href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
                <link rel="stylesheet" type="text/css"  href="misc/styles.css?v=<?=$config->scripts_and_styles_version?>">
                <style type="text/css">
                    <?= $config->current_coin_data["body_font_definition"] ?>
                    <?= $config->current_coin_data["ui_font_definition"] ?>
                    .coin_signed:after { content: ' <?=$config->current_coin_data["coin_name_plural"]?>'; }
                </style>
            </head>
            <body>
                <h1><?=$title?></h1>
                <div class="message_box <?=$message_class?> ui-corner-all"><?= $message ?></div>
            </body>
        </html><?
        die();
    } # end if

    ###########################################################
    if( $_REQUEST["mode"] == "get_id_account_from_fb_account" )
    ###########################################################
    {
        if( empty($_GET["id_account"]) ) die("ERROR:INVALID_ID_ACCOUNT");

        $id_account = decryptRJ256($config->interapps_exchange_key, stripslashes($_GET["id_account"]));
        $tmp = new account($id_account);
        if( ! $tmp->exists ) die("ERROR:UNEXISTENT_ACCOUNT:$id_account");
        die("OK:".encryptRJ256($config->interapps_exchange_key, $tmp->id_account));
    } # end if

    include "facebook-php-sdk/facebook.php";
    $fb_params = array(
        'appId'              => $config->facebook_app_id,
        'secret'             => $config->facebook_app_secret,
        'fileUpload'         => false,
        'allowSignedRequest' => true
    );
    $facebook = new Facebook($fb_params);

    $user_id = get_online_user_id(true);
    if( empty($user_id) )
    {
        header("Content-Type: text/plain; charset=utf-8");
        die("ERROR: can't get your user id! Please login!");
    } # end if

    $account = new account($user_id);
    if( ! $account->exists )
    {
        header("Content-Type: text/plain; charset=utf-8");
        die("ERROR: Account doesn't exist in DB! (App not authorized? Please authorize it!)");
    } # end if

    $is_admin = isset($config->sysadmins[$account->id_account]);

    ###############################################
    if( $_REQUEST["mode"] == "get_wallet_address" )
    ###############################################
    {
        if( ! $config->engine_enabled )
        {
            header("Content-Type: text/plain; charset=utf-8");
            die("ERROR: Engine is currently disabled! " . $config->engine_disabled_message);
        } # end if

        if( $is_admin && ! empty($_REQUEST["for_id_account"]) )
        {
            $account = new account($_REQUEST["for_id_account"]);
            if( ! $account->exists )
            {
                header("Content-Type: text/plain; charset=utf-8");
                die( "ERROR: Account id " . $_REQUEST["for_id_account"] . " doesn't exist." );
            } # end if
        } # end if

        $tipping_provider = new tipping_provider( $config->current_coin_data["api_url"],
                                                  $config->current_tipping_provider_data["public_key"],
                                                  $config->current_tipping_provider_data["secret_key"] );
        $res = $tipping_provider->get_address($account->id_account);
        if( $res->message == "ERROR:UNREGISTERED_ACCOUNT" )
        {
            $res = $tipping_provider->register($account->id_account);
            if( $res->message != "OK" )
            {
                header("Content-Type: text/plain; charset=utf-8");
                die("ERROR: Wallet unexistent, can't register account! Mesage: " . $res->message);
            } # end if
        } # end if

        header("Content-Type: text/plain; charset=utf-8");
        if( $res->message == "OK" )
        {
            if( ! is_wallet_address($res->data) )
            {
                header("Content-Type: text/plain; charset=utf-8");
                die("ERROR: Wallet address '$res->data' doesn't seem valid!");
            } # end if
            if( empty($account->wallet_address) )
            {
                $account->wallet_address = $res->data;
                $account->last_update = date("Y-m-d H:i:s");
                $account->save();
            } # end if
            die($res->data);
        } # end if

        $extra_info = ( is_object($res->extra_info) || is_array($res->extra_info) )
                    ? print_r($res->extra_info, true)
                    : $res->extra_info
                    ;

        die( "ERROR: Can't get wallet address! Response from provider is: $res->message."
           . ( empty($res->extra_info) ? "" : "\nExtra info: $extra_info" )
           );
    } # end if

    ####################################################
    if( $_REQUEST["mode"] == "get_address_and_balance" )
    ####################################################
    {
        header("Content-Type: text/plain; charset=utf-8");
        if( ! $config->engine_enabled )
            die("ERROR: Engine is currently disabled! " . $config->engine_disabled_message);

        if( empty($_REQUEST["coin_name"]) )
            die( "ERROR: Coin name not specified!" );

        $tipping_provider_keyname = get_tipping_provider_keyname_by_coin(trim($_REQUEST["coin_name"]));
        if( empty($tipping_provider_keyname) )
            die( "ERROR: Specified coin is not supported." );

        $config->current_tipping_provider_keyname = $tipping_provider_keyname;
        $config->current_coin_name                = $_REQUEST["coin_name"];
        $config->current_tipping_provider_data    = $config->tipping_providers_database[$config->current_tipping_provider_keyname];
        $config->current_coin_data                = $config->current_tipping_provider_data["per_coin_data"][$config->current_coin_name];

        if( $is_admin && ! empty($_REQUEST["for_id_account"]) )
        {
            $account = new account($_REQUEST["for_id_account"]);
            if( ! $account->exists )
            {
                header("Content-Type: text/plain; charset=utf-8");
                die( "ERROR: Account id " . $_REQUEST["for_id_account"] . " doesn't exist." );
            } # end if
        }
        else
        {
            $id_account = $account->id_account;
            $account    = new account($id_account);
        } # end if

        # Wallet address check
        if( empty($account->wallet_address) )
        {
            $tipping_provider = new tipping_provider( $config->current_coin_data["api_url"],
                                                      $config->current_tipping_provider_data["public_key"],
                                                      $config->current_tipping_provider_data["secret_key"] );
            $res = $tipping_provider->get_address($account->id_account);
            if( $res->message == "ERROR:UNREGISTERED_ACCOUNT" )
            {
                $res = $tipping_provider->register($account->id_account);
                if( $res->message != "OK" )
                    die("ERROR: Wallet unexistent, can't register account! Mesage: " . $res->message);
            } # end if

            if( $res->message == "OK" )
            {
                if( ! is_wallet_address($res->data) )
                    die("ERROR: Wallet address '$res->data' doesn't seem valid!");

                $account->wallet_address = $res->data;
                $account->last_update = date("Y-m-d H:i:s");
                $account->save();
                die("OK:".$account->wallet_address.":0");
            }
            else
            {
                $extra_info = ( is_object($res->extra_info) || is_array($res->extra_info) )
                            ? print_r($res->extra_info, true)
                            : $res->extra_info
                            ;
                die( "ERROR: Can't get wallet address! Response from provider is: $res->message."
                   . ( empty($res->extra_info) ? "" : "\nExtra info: $extra_info" )
                   );
            } # end if
        } # end if

        # Balance check
        $balance = $account->get_balance();
        if( ! is_numeric($balance) )
            die("ERROR: Can't get wallet balance. Please try again later.");

        # $to_return = "OK:".$account->wallet_address.":".number_format_crypto_condensed($balance, 1).":".$config->current_coin_name.":".number_format($balance, 8, ".", "");
        $to_return = "OK:".$account->wallet_address.":".number_format_crypto_condensed($balance, 1).":".$config->current_coin_name;
        load_extensions("tooolbox_get_address_and_balance_pre_render");
        $to_return .= ":".number_format($balance, 8, ".", "");
        die($to_return);
    } # end if

    ################################################
    if( $_REQUEST["mode"] == "save_alternate_info" )
    ################################################
    {
        $alternate_email     = trim($_REQUEST["alternate_email"]);
        $alternate_password  = trim($_REQUEST["alternate_password"]);
        $alternate_password2 = trim($_REQUEST["alternate_password2"]);

        if( empty($alternate_email) || empty($alternate_password) || empty($alternate_password2) )
        {
            header("Content-Type: text/html; charset=utf-8");
            die("
                <div class='ui-state-error message_box ui-corner-all' style='font-size: 14pt;'>
                    <span class='ui-icon embedded ui-icon-alert'></span>
                    Please specify an alternate email and a password and re-submit the form.
                </div>
            ");
        } # end if

        if( $alternate_email == $account->email )
        {
            header("Content-Type: text/html; charset=utf-8");
            die("
                <div class='ui-state-error message_box ui-corner-all' style='font-size: 14pt;'>
                    <span class='ui-icon embedded ui-icon-alert'></span>
                    Please specify an email address different to the one you have as main (if you have connected with Facebook, your email address can't be changed on this side.).
                </div>
            ");
        } # end if

        if( $alternate_password != $alternate_password2 )
        {
            header("Content-Type: text/html; charset=utf-8");
            die("
                <div class='ui-state-error message_box ui-corner-all' style='font-size: 14pt;'>
                    <span class='ui-icon embedded ui-icon-alert'></span>
                    Sepcified passwords don't match. Please re-type them again.
                </div>
            ");
        } # end if

        $query = "select * from ".$config->db_tables["account"]." where id_account != '$account->id_account' and alternate_email = '$alternate_email'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) > 0 )
        {
            header("Content-Type: text/html; charset=utf-8");
            die("
                <div class='ui-state-error message_box ui-corner-all' style='font-size: 14pt;'>
                    <span class='ui-icon embedded ui-icon-alert'></span>
                    There is another account with that alternate email specified. Please type another.
                </div>
            ");
        } # end if
        mysql_free_result($res);

        $query = "select * from ".$config->db_tables["account"]." where id_account != '$account->id_account' and email = '$alternate_email'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) > 0 )
        {
            header("Content-Type: text/html; charset=utf-8");
            die("
                <div class='ui-state-error message_box ui-corner-all' style='font-size: 14pt;'>
                    <span class='ui-icon embedded ui-icon-alert'></span>
                    You can't take over someone else's account.
                </div>
            ");
        } # end if
        mysql_free_result($res);

        $account->alternate_email    = $alternate_email;
        $account->alternate_password = "pending:" . md5($alternate_password);
        $account->last_activity      =
        $account->last_update        = date("Y-m-d H:i:s");
        $account->save();

        $limit        = date("Y-m-d H:i:s", strtotime("now + 30 minutes"));
        $token        = encryptRJ256($config->tokens_encryption_key, "$account->id_account\t$alternate_password\t$limit");
        $ip           = $_SERVER['REMOTE_ADDR'];
        $hostname     = gethostbyaddr($_SERVER['REMOTE_ADDR']);
        $fecha_envio  = date("Y-m-d H:i:s");
        $mail_from    = "$config->mail_sender_name<$config->mail_sender_address>";
        $mail_to      = empty($account->email) ? "$account->name alternate email<$account->alternate_email>" : "$account->name<$account->email>";
        $url          = $config->website_pages["toolbox"] . "?mode=authorize_alt_changes&token=" . urlencode($token);

        $mail_subject = $config->app_display_shortname . " - Emergency recovery login info";
        $mail_body = "Greetings! Here is your updated emergency recovery login info:\r\n"
                   . "\r\n"
                   . "Alternate email:     $alternate_email\r\n"
                   # "Alternate password:  $alternate_password\r\n"
                   . "Alternate login URL: ".$config->website_pages["root_url"]."\r\n"
                   . "\r\n"
                   . "If you've placed this request, please follow the next link to confirm it:\r\n"
                   . "$url\r\n"
                   . "\r\n"
                   . "Please save this information for further reference.\r\n"
                   . "\r\n"
                   . "If you didn't place this request, please take proper measures with\r\n"
                   . "your Facebook account: change the password, change the email, etc.\r\n"
                   . "and disregard this email.\r\n"
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
            "Content-Type: text/plain; charset=utf-8\r\n" .
            ( empty($account->email) ? "" : (empty($account->alternate_email) ? "" : "Cc: $account->name alternate email<$account->alternate_email>\r\n") )
        );

        header("Content-Type: text/html; charset=utf-8");
        die("
            <div class='ui-state-hover message_box ui-corner-all' style='font-size: 14pt;'>
                <span class='ui-icon embedded ui-icon-info'></span>
                Information saved. Please check any of your email addresses and follow instructions
                to authorize this change.
            </div>
        ");
    } # end if

    ########################################################
    if( $_REQUEST["mode"] == "lookup_users" && ! $is_admin )
    ########################################################
    {
        header("Content-Type: text/plain; charset=utf-8");
        die("Access denied.");
    }
    ##########################################################
    elseif( $_REQUEST["mode"] == "lookup_users" && $is_admin )
    ##########################################################
    {
        if( empty($_REQUEST["q"]) )
        {
            header("Content-Type: text/html; charset=utf-8");
            die('
                <div class="ui-state-hightlight ui-corner-all" style="padding: 5px;">
                    <span class="ui-icon embedded ui-icon-info"></span>
                    Specify something to lookup
                </div>
            ');
        } # end if

        $cols  = array();
        $query = "describe ".$config->db_tables["account"];
        $res   = mysql_query($query);
        while( $row = mysql_fetch_object($res) )
            if($row->Field != "facebook_user_access_token" && $row->Field != "alternate_password" && $row->Field != "tipping_provider")
                if( $row->Field == "receive_notifications" )
                    $cols[] = "Notif?";
                else
                    $cols[] = $row->Field;

        $query = "
            select * from ".$config->db_tables["account"]." where
               id_account like  '%$_REQUEST[q]%'
            or facebook_id like '%$_REQUEST[q]%'
            or name like        '%$_REQUEST[q]%'
            or email like       '%$_REQUEST[q]%'
            order by name asc
            limit 100
        ";
        $res = mysql_query($query);
        if( mysql_num_rows($res) == 0 )
        {
            mysql_free_result($res);
            header("Content-Type: text/html; charset=utf-8");
            die('
                <div class="ui-state-hightlight ui-corner-all" style="padding: 5px;">
                    <span class="ui-icon embedded ui-icon-info"></span>
                    There is nothing to match the specified query.
                </div>
            ');
        } # end if

        echo "
            <table class=\"tablesorter\" width=\"100%\" cellpadding=\"2\" cellspacing=\"1\" border=\"0\">
                <thead>
                    <tr>
                        ";
                            foreach($cols as $col) echo "<td>" . ucwords(str_replace("_", " ", $col)) . "</td>\n";
                        echo "
                    </tr>
                </thead>
                <tbody>
                    ";
                    /** @var account */
                    while( $row = mysql_fetch_object($res) )
                    {
                        echo "
                            <tr>
                            ";
                                foreach($row as $key => $val)
                                    if($key != "facebook_user_access_token" && $key != "alternate_password" && $key != "tipping_provider")
                                        if(empty($val)) echo "<td>&ndash;</td>\n";
                                        else            echo "<td>$val</td>\n";
                            echo "
                            </tr>
                        ";
                    } # end if
        echo "
                </tbody>
            </table>
        ";
        mysql_free_result($res);
        die();
    } # end if

    ####################################################
    if( $_REQUEST["mode"] == "show_log" && ! $is_admin )
    ####################################################
    {
        header("Content-Type: text/plain; charset=utf-8");
        die("Access denied.");
    }
    ######################################################
    elseif( $_REQUEST["mode"] == "show_log" && $is_admin )
    ######################################################
    {
        header("Content-Type: text-html; charset=iso-8859-1");
        if( ! is_file("logs/" . $_REQUEST["file"]) )
        {
            echo "Logfile '$_REQUEST[file]' not found.";
        }
        else
        {
            include_once "lib/cli_helper_class.php";
            $log_contents = file_get_contents("logs/" . $_REQUEST["file"]);
            $log_contents = trim($log_contents);
            $log_contents = cli::to_html($log_contents);
            $log_contents = str_replace("<br>", "", $log_contents);
            echo "<pre style='background-color: black; color: silver;'>$log_contents<br><br></pre>";
        } # end if
        die();
    } # end if

    /**
    * Tip rain creation
    *
    * @param string target_group            Target group Facebook id
    * @param string batch_title
    * @param number recipient_amount
    * @param string recipient_type          any, tippers, non_tippers
    * @param string using_bot_account       yes, no
    * @param number coins_per_recipient_min
    * @param number coins_per_recipient_max
    ############################################ */
    if( $_REQUEST["mode"] == "create_tip_rain" )
    ############################################
    {
        # set_time_limit( 600 );
        foreach($_REQUEST as $key => $val)
            $_REQUEST[$key] = trim(stripslashes($val));

        # [+] Validations
        {
            if( ($_REQUEST["target_group"]) == "" )
            {
                header("Content-Type: text/html; charset=utf-8");
                die('
                    <div class="ui-state-hightlight message_box ui-corner-all" style="padding: 5px;">
                        <span class="ui-icon embedded ui-icon-info"></span>
                        Error: Please select a target group.
                    </div>
                ');
            } # end if

            $group_found = false;
            $group_key   = "";
            foreach( $config->facebook_monitor_objects as $key => $object )
            {
                if( $object["id"] == $_REQUEST["target_group"] )
                {
                    $group_key   = $key;
                    $group_found = true;
                    break;
                } # end if
            } # end foreach
            if( ! $group_found )
            {
                header("Content-Type: text/html; charset=utf-8");
                die('
                    <div class="ui-state-error message_box ui-corner-all" style="padding: 5px;">
                        <span class="ui-icon embedded ui-icon-alert"></span>
                        Error: The specified group is not supported.
                    </div>
                ');
            } # end if

            # Let's assign the bot corresponding the group
            if( ! empty($group_key) )
            {
                $config->tippingbot_id_acount     = $config->facebook_monitor_objects[$group_key]["tippingbot_id_acount"];
                $config->tippingbot_fb_id_account = $config->facebook_monitor_objects[$group_key]["tippingbot_fb_id_account"];
            } # end if

            if( $config->current_coin_data["coin_disabled"] )
            {
                header("Content-Type: text/html; charset=utf-8");
                die('
                    <div class="ui-state-error message_box ui-corner-all" style="padding: 5px;">
                        <span class="ui-icon embedded ui-icon-alert"></span>
                        Error: '.$config->current_coin_data["coin_disabled_message"].'
                    </div>
                ');
            } # end if

            if( ($_REQUEST["batch_title"]) == "" )
            {
                header("Content-Type: text/html; charset=utf-8");
                die('
                    <div class="ui-state-error message_box ui-corner-all" style="padding: 5px;">
                        <span class="ui-icon embedded ui-icon-alert"></span>
                        Error: Please type a brief description for your rain.
                    </div>
                ');
            } # end if

            if( abs($_REQUEST["recipient_amount"]) < 1 )
            {
                header("Content-Type: text/html; charset=utf-8");
                die('
                    <div class="ui-state-error message_box ui-corner-all" style="padding: 5px;">
                        <span class="ui-icon embedded ui-icon-alert"></span>
                        Error: Please specify a valid amount of recipients.
                    </div>
                ');
            } # end if

            if( ! in_array(($_REQUEST["recipient_type"]), array("any", "tipper", "non_tipper")) )
            {
                header("Content-Type: text/html; charset=utf-8");
                die('
                    <div class="ui-state-error message_box ui-corner-all" style="padding: 5px;">
                        <span class="ui-icon embedded ui-icon-alert"></span>
                        Error: Please specify a valid recipient kind.
                    </div>
                ');
            } # end if

            if( $_REQUEST["using_bot_account"] && ! $is_admin )
            {
                header("Content-Type: text/html; charset=utf-8");
                die('
                    <div class="ui-state-error message_box ui-corner-all" style="padding: 5px;">
                        <span class="ui-icon embedded ui-icon-alert"></span>
                        Error: Only admins can access this option.
                    </div>
                ');
            } # end if

            if( ! is_numeric(($_REQUEST["coins_per_recipient_min"])) && ! is_numeric(($_REQUEST["coins_per_recipient_max"])) )
            {
                header("Content-Type: text/html; charset=utf-8");
                die('
                    <div class="ui-state-error message_box ui-corner-all" style="padding: 5px;">
                        <span class="ui-icon embedded ui-icon-alert"></span>
                        Error: Please specify valid min and max coins to rain.
                    </div>
                ');
            } # end if

            $_REQUEST["coins_per_recipient_min"] = round($_REQUEST["coins_per_recipient_min"], 8);
            $_REQUEST["coins_per_recipient_max"] = round($_REQUEST["coins_per_recipient_max"], 8);

            if( ($_REQUEST["coins_per_recipient_min"]) > ($_REQUEST["coins_per_recipient_max"]) )
            {
                header("Content-Type: text/html; charset=utf-8");
                die('
                    <div class="ui-state-error message_box ui-corner-all" style="padding: 5px;">
                        <span class="ui-icon embedded ui-icon-alert"></span>
                        Error: Max tip amount must be greater than or equal min tip amount.
                    </div>
                ');
            } # end if

            if( ($_REQUEST["coins_per_recipient_min"]) < ($config->current_coin_data["min_transaction_amount"]) )
            {
                header("Content-Type: text/html; charset=utf-8");
                die('
                    <div class="ui-state-error message_box ui-corner-all" style="padding: 5px;">
                        <span class="ui-icon embedded ui-icon-alert"></span>
                        Error: Min tip must be '.round_crypto($config->current_coin_data["min_transaction_amount"], 8).' '.$config->current_coin_data["coin_sign"].'
                    </div>
                ');
            } # end if

            if( abs($_REQUEST["recipient_amount"]) == 1 &&
                ($_REQUEST["coins_per_recipient_min"]) == ($config->current_coin_data["min_transaction_amount"]) )
            {
                header("Content-Type: text/html; charset=utf-8");
                die('
                    <div class="ui-state-error message_box ui-corner-all" style="padding: 5px;">
                        <span class="ui-icon embedded ui-icon-alert"></span>
                        Your minimums are too low. Please increase the tip size and/or the amount of users to tip.
                    </div>
                ');
            } # end if

            if( $_REQUEST["using_bot_account"] && $is_admin )
            {
                if( $config->tippingbot_id_acount == "none" )
                {
                    header("Content-Type: text/html; charset=utf-8");
                    die('
                        <div class="ui-state-error message_box ui-corner-all" style="padding: 5px;">
                            <span class="ui-icon embedded ui-icon-alert"></span>
                            Error: There is no bot account assigned to this group!
                        </div>
                    ');
                } # end if
                $bot_account = new account($config->tippingbot_id_acount);
                $bot_balance = $bot_account->get_balance();
                if( ! is_numeric($bot_balance) )
                {
                    header("Content-Type: text/html; charset=utf-8");
                    die('
                        <div class="ui-state-error message_box ui-corner-all" style="padding: 5px;">
                            <span class="ui-icon embedded ui-icon-alert"></span>
                            Error: Couldn\'t get bot\'s pool balance. Please try again.
                        </div>
                    ');
                } # end if
                if( ($_REQUEST["recipient_amount"] * $_REQUEST["coins_per_recipient_max"]) > $bot_balance )
                {
                    header("Content-Type: text/html; charset=utf-8");
                    die('
                        <div class="ui-state-error message_box ui-corner-all" style="padding: 5px;">
                            <span class="ui-icon embedded ui-icon-alert"></span>
                            Error: Bot\'s balance can\'t bear the rain. Please lower the recipients or the max tip size.
                        </div>
                    ');
                } # end if
            }
            else
            {
                $balance = $account->get_balance();
                if( ! is_numeric($balance) )
                {
                    header("Content-Type: text/html; charset=utf-8");
                    die('
                        <div class="ui-state-error message_box ui-corner-all" style="padding: 5px;">
                            <span class="ui-icon embedded ui-icon-alert"></span>
                            Error: Couldn\'t get your balance. Please try again.
                        </div>
                    ');
                } # end if
                if( ($_REQUEST["recipient_amount"] * $_REQUEST["coins_per_recipient_max"]) > $balance )
                {
                    header("Content-Type: text/html; charset=utf-8");
                    die('
                        <div class="ui-state-error message_box ui-corner-all" style="padding: 5px;">
                            <span class="ui-icon embedded ui-icon-alert"></span>
                            Error: Your balance can\'t bear the rain. Please lower the recipients or the max tip size.
                        </div>
                    ');
                } # end if
            } # end if
        }
        # [-] Validations

        $using_bot_account = $_REQUEST["using_bot_account"] == "true" ? 1 : 0;

        # Let's check if the rain wasn't already placed
        $query = "
            select * from ".$config->db_tables["tip_batches"]." where
            batch_title             = '".addslashes(($_REQUEST["batch_title"]))."' and
            target_group_id         = '".addslashes(($_REQUEST["target_group"]))."' and
            creator_facebook_id     = '".addslashes($account->facebook_id)."' and
            coin_name               = '".$config->current_coin_name."' and
            using_bot_account       = '$using_bot_account' and
            recipient_amount        = '".addslashes(($_REQUEST["recipient_amount"]))."' and
            recipient_type          = '".addslashes(($_REQUEST["recipient_type"]))."' and
            coins_per_recipient_min = '".addslashes(($_REQUEST["coins_per_recipient_min"]))."' and
            coins_per_recipient_max = '".addslashes(($_REQUEST["coins_per_recipient_max"]))."' and
            state in ('forging', 'active')
        ";
        $res = mysql_query($query);
        if( mysql_num_rows($res) > 0 )
        {
            mysql_free_result( $res );
            header("Content-Type: text/html; charset=utf-8");
            die('
                <div class="ui-state-hightlight message_box ui-corner-all" style="padding: 5px;">
                    <span class="ui-icon embedded ui-icon-info"></span>
                    Error: your rain is already being created. Please close the input form
                    and let the background process finish. If you can\'t see it after a
                    couple of minutes in the \'Active rains\' list, please contact us
                    so we check it out.
                </div>
            ');
        } # end if
        mysql_free_result( $res );

        # Let's get the amount of users for the coin
        $query = "
            select
                {$config->db_tables["account"]}.facebook_id,
                {$config->db_tables["account"]}.name
            from
                {$config->db_tables["account_wallets"]},
                {$config->db_tables["account"]}
            where
                {$config->db_tables["account_wallets"]}.id_account = {$config->db_tables["account"]}.id_account and
                {$config->db_tables["account_wallets"]}.coin_name = '{$config->current_coin_name}'
        ";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 )
        {
            header("Content-Type: text/html; charset=utf-8");
            die('
                <div class="ui-state-error message_box ui-corner-all" style="padding: 5px;">
                    <span class="ui-icon embedded ui-icon-info"></span>
                    Error: The coin has no users! This is normal for new coins. Please wait for
                    a couple of days for users to get wallet addresses before making a rain.
                </div>
            ');
        } # end if

        $member_list = array();
        while( $row = mysql_fetch_object($res) )
            $member_list[] = (object) array("id" => $row->facebook_id, "name" => $row->name );
        mysql_free_result( $res );

        header("Content-Type: text/html; charset=utf-8");

        # Let's insert the batch record
        $batch_id            = uniqid(true);
        $batch_creation_date = date("Y-m-d H:i:s");
        $query = "
            insert into {$config->db_tables["tip_batches"]} set
                batch_id                = '$batch_id',
                batch_title             = '".addslashes(($_REQUEST["batch_title"]))."',
                target_group_id         = '".addslashes(($_REQUEST["target_group"]))."',
                creator_facebook_id     = '".addslashes($account->facebook_id)."',
                creator_name            = '".addslashes($account->name)."',
                coin_name               = '".$config->current_coin_name."',
                using_bot_account       = '$using_bot_account',
                recipient_amount        = '".addslashes(($_REQUEST["recipient_amount"]))."',
                recipient_type          = '".addslashes(($_REQUEST["recipient_type"]))."',
                coins_per_recipient_min = '".addslashes(($_REQUEST["coins_per_recipient_min"]))."',
                coins_per_recipient_max = '".addslashes(($_REQUEST["coins_per_recipient_max"]))."',
                state                   = 'forging',
                date_created            = '$batch_creation_date'
        ";
        mysql_query( $query );

        $member_list_count = count($member_list);
        $picked_members = array();

        $picked_member_count = 0;
        $remaining_members_in_list = count($member_list);
        $recipient_amount = ($_REQUEST["recipient_amount"]);
        $picked_members_list = array();
        while( true )
        {
            $member_index = array_rand($member_list);
            $picked_member_facebook_id = $member_list[$member_index]->id;
            $picked_member_name        = $member_list[$member_index]->name;
            # echo "\n<br>&bull; $picked_member_name being checked... ";
            # echo "<!-- "; for($c = 1; $c <= 10; $c++) echo md5(mt_rand(1,65535)); echo "-->\n";
            unset($member_list[$member_index]);
            $remaining_members_in_list--;
            if( $remaining_members_in_list <= 0 ) break;

            if($picked_member_facebook_id == $config->tippingbot_fb_id_account) continue;
            if($picked_member_facebook_id == $account->facebook_id ) continue;

            if( ($_REQUEST["recipient_type"]) == "tipper" )
            {
                $query2 = "select distinct to_facebook_id from ".$config->db_tables["log"]."
                           where from_facebook_id = '$picked_member_facebook_id'
                           and state = 'OK'
                           and coin_name = '".$config->current_coin_name."' limit 2";
                $res2 = mysql_query($query2);
                $res2_count = mysql_num_rows($res2);
                mysql_free_result( $res2 );
                if( $res2_count < 2 ) continue;
            }
            else # if( trim($_REQUEST["recipient_type"]) == "non_tipper" )
            {
                $non_tipper_account = new account($picked_member_facebook_id);
                if( ! $non_tipper_account->exists ) continue;
                if( empty($non_tipper_account->wallet_address) ) continue;
                if( $non_tipper_account->date_created == $non_tipper_account->last_activity ) continue;
            } # end if

            $min = ($_REQUEST["coins_per_recipient_min"]);
            $max = ($_REQUEST["coins_per_recipient_max"]);
            if( count_fractions($min) == 0 && count_fractions($max) == 0 )
                $coin_amount = mt_rand($min, $max);
            else
                $coin_amount = random_with_fractions($min, $max);
            $query = "
                insert into ".$config->db_tables["tip_batch_submissions"]." set
                    batch_id              = '$batch_id',
                    recipient_facebook_id = '$picked_member_facebook_id',
                    recipient_name        = '".addslashes($picked_member_name)."',
                    coin_name             = '".$config->current_coin_name."',
                    coin_amount           = '$coin_amount',
                    date_created          = '$batch_creation_date'
            ";
            if ( mysql_query( $query ) )
            {
                $picked_member_count++;
                $picked_members_list[] = "► $picked_member_name: "."$coin_amount ".$config->current_coin_data["coin_name_plural"];
            } # end if

            # echo "&bull; $picked_member_name added! [$picked_member_count/$remaining_members_in_list]<br>\n";
            # echo "<!-- "; for($c = 1; $c <= 10; $c++) echo md5(mt_rand(1,65535)); echo "-->\n";
            # flush();

            if( $picked_member_count >= $recipient_amount ) break;
        } # end while

        # Let's update the rain so it gets started ASAP
        $query = "
            update ".$config->db_tables["tip_batches"]." set
                recipient_amount        = '$picked_member_count',
                state                   = 'active'
            where
                batch_id                = '$batch_id'
        ";
        mysql_query( $query );

        # Let's post a notification on the target group
        # if( $is_admin )
        {
            $group_message_status = "";
            $group_fb_id          = $_REQUEST["target_group"];
            /*
            $current_user_token   = $facebook->getAccessToken();
            $bot_account          = new account($config->tippingbot_id_acount);
            if( empty($bot_account->facebook_user_access_token) )
            {
                $group_message_status = "
                    <div class='ui-state-error message_box ui-corner-all' style='padding: 5px;'>
                        <span class='ui-icon embedded ui-icon-info'></span>
                        Important: Bot's access token is empty! Can't post message to target group!<br>
                        You will need to post about your rain in the group.<br>
                        Please be nice and inform the group's admins about this error.
                    </div>
                ";
            } # end if
            try
            {
                $facebook->setAccessToken($bot_account->facebook_user_access_token);
                $facebook->setExtendedAccessToken();
                $access_token = $facebook->getAccessToken();
            }
            catch(Exception $e)
            {
                $group_message_status = "
                    <div class='ui-state-error message_box ui-corner-all' style='padding: 5px;'>
                        <span class='ui-icon embedded ui-icon-info'></span>
                        Can't load bot's access token for posting notification to the group! Facebook says: " . $e->getMessage() . ".<br>
                        Please be nice and inform the group's admins about this error.
                    </div>
                ";
            } # end try...catch
            */

            asort($picked_members_list);
            $group_message = "For $picked_member_count ".$config->current_coin_data["coin_fan_name_plural"]." with a ".
                           ( $min != $max
                           ? "random drop size between $min and $max ".$config->current_coin_data["coin_name_plural"].".\n"
                           : "drop size of $min ".$config->current_coin_data["coin_name_plural"].".\n"
                           )
                           . ""
                           ;
            $message_params = array(
            #   "access_token" => $access_token, // see: https://developers.facebook.com/docs/facebook-login/access-tokens/
            #   "message"      => $group_message,
                "link"         => $config->facebook_canvas_page."?tab=7&show_rain=$batch_id".(count($config->current_tipping_provider_data["per_coin_data"]) == 1 ? "" : "&switch_coin=".$config->current_coin_name),
            #   "link"         => $config->website_pages["about"],
                "picture"      => $config->current_coin_data["tip_rain_link_image"],
                "name"         => stripslashes(trim($_REQUEST["batch_title"])),
                "caption"      => $config->app_root_domain,
                "description"  => $group_message
            );

            load_extensions("rain_maker_pre_notification", $root_url);

            $post_comment = true;
            try
            {
                $access_token = $facebook->getAccessToken();
                $facebook->setExtendedAccessToken();

                $ret = $facebook->api("/$group_fb_id/feed", 'POST', $message_params);
                list($gid, $pid) = explode("_", $ret["id"]);
                $group_message_status = "
                    <div class='ui-state-highlight message_box ui-corner-all' style='padding: 5px;'>
                        <span class='ui-icon embedded ui-icon-info'></span>
                        Message successfully posted to the group! Please follow this link to view it:<br>
                        <a href='https://www.facebook.com/$pid' target='_blank'>https://www.facebook.com/$pid</a>
                    </div>
                ";
            }
            catch(Exception $e)
            {
                $post_comment = false;
                $group_message_status = "
                    <div class='ui-state-error message_box ui-corner-all' style='padding: 5px;'>
                        <span class='ui-icon embedded ui-icon-info'></span>
                        Can't post notification to the group! Facebook says: " . $e->getMessage() . ".<br>
                        You need to authorize the app to post on your name before making it rain.<br>
                        Please check the rains list and manually paste the permalink on the group to let the users know about this one.
                    </div>
                ";
            } # end try...catch

            /*
            if( $post_comment )
            {
                $group_message = "I've created this rain for $picked_member_count ".$config->current_coin_data["coin_fan_name_plural"]." with a ".
                               ( $min != $max
                               ? "random drop size between ".$config->current_coin_data["coin_sign"]."$min and ".$config->current_coin_data["coin_sign"]."$max.\n"
                               : "drop size of ".$config->current_coin_data["coin_sign"]."$min.\n"
                               )
                               . "If you're included in the list below, please check your dashboard later on to look if your drop has fallen:\n"
                               . join("\n", $picked_members_list)
                               ;
                $comment_params = array(
                #   "access_token" => $access_token, // see: https://developers.facebook.com/docs/facebook-login/access-tokens/
                    "message"      => $group_message
                );
                try
                {
                    $ret = $facebook->api("/$pid/comments", 'POST', $comment_params);
                    $group_message_status = "
                        <div class='ui-state-highlight message_box ui-corner-all' style='padding: 5px;'>
                            <span class='ui-icon embedded ui-icon-info'></span>
                            Message successfully posted to the group! Please follow this link to view it:<br>
                            https://www.facebook.com/$pid
                        </div>
                    ";
                }
                catch(Exception $e)
                {
                    $group_message_status = "
                        <div class='ui-state-error message_box ui-corner-all' style='padding: 5px;'>
                            <span class='ui-icon embedded ui-icon-info'></span>
                            Notification was posted on the group, but couldn't post the recipient list!
                            Facebook says: " . $e->getMessage() . ".<br>
                            Please be nice and inform the group's admins about this error.
                        </div>
                    ";
                } # end try...catch
            } # end if
            */

            /*
            $facebook->destroySession();
            $facebook->setAccessToken($current_user_token);
            $facebook->setExtendedAccessToken();
            $access_token = $facebook->getAccessToken();
            */
        } # end if

        # For posting as user after the rain is submitted
        /*
        $group_message_status = "
            <div id='rain_group_id'>".$_REQUEST["target_group"]."</div>
            <div id='rain_message_data'>".json_encode($message_params)."</div>
            <div id='rain_comment_data'>".json_encode($comment_params)."</div>
        ";
        */

        # Let's notify the creator
        die('
            <div class="ui-state-highlight message_box ui-corner-all" style="padding: 5px;">
                <span class="ui-icon embedded ui-icon-info"></span>
                Your rain has been successfully created. '.$picked_member_count.' out of '.$member_list_count.' recipients added with a
                '.( $min != $max
                  ? 'random tip between '.$min.' and '.$max.' '.$config->current_coin_data["coin_name_plural"].'.<br>'
                  : 'fixed tip of '.$min.' '.$config->current_coin_data["coin_name_plural"]
                  ).'.<br>
                Please give it a couple of minutes
                to appear in the \'Active rains\' list. If it doesn\'t show up after a while,
                please contact us so we check what happened.
            </div>
        ' . $group_message_status);
    } # end if

    ###################################################
    if( $_REQUEST["mode"] == "show_active_rains_list" )
    ###################################################
    {
        $query = "select count(batch_id) as count from " . $config->db_tables["tip_batches"] . " where coin_name = '".$config->current_coin_name."'";
        $res   = mysql_query($query);
        $row   = mysql_fetch_object($res);
        mysql_free_result($res);
        $all_rains_count = $row->count;

        $limit = date("Y-m-d H:i:s", strtotime("now - 7 days"));
        $query = "
            select * from ".$config->db_tables["tip_batches"]." where
            coin_name = '".$config->current_coin_name."' and
            date_created >= '$limit'
            order by date_created desc
        ";
        $res = mysql_query($query);
        $recent_rains_count = mysql_num_rows($res);

        if( mysql_num_rows($res) == 0 )
        {
            mysql_free_result($res);
            header("Content-Type: text/html; charset=utf-8");
            die('
                <div class="ui-state-highlight ui-corner-all" style="padding: 5px;">
                    <span class="ui-icon embedded ui-icon-info"></span>
                    Sorry, but there are no rains active or waiting to fall.
                </div>
            ');
        } # end if

        header("Content-Type: text/html; charset=utf-8");
        echo "<div id='incoming_tip_rain_counts' style='display: none;'>$recent_rains_count out of $all_rains_count</div>\n";
        while($row = mysql_fetch_object($res))
        {
            switch($row->state)
            {
                case "forging":   $ui_state = "ui-state-default";  break;
                case "active":    $ui_state = "ui-state-active";   break;
                case "finished":  $ui_state = "ui-widget-content"; break;
                case "cancelled": $ui_state = "ui-state-error";    break;
            } # end switch
            switch($row->recipient_type)
            {
                case "any":        $recipient_type = "random ".$config->current_coin_data["coin_fan_name_plural"]."";     break;
                case "tipper":     $recipient_type = "tipping ".$config->current_coin_data["coin_fan_name_plural"].""; break;
                case "non_tipper": $recipient_type = "lucky ".$config->current_coin_data["coin_fan_name_plural"]."";   break;
            } # end switch
            $drop_size = $row->coins_per_recipient_min == $row->coins_per_recipient_max
                       ? "of " . $row->coins_per_recipient_min . " " . $config->current_coin_data["coin_name_plural"]
                       : "between " . $row->coins_per_recipient_min . " and " . $row->coins_per_recipient_max . " " . $config->current_coin_data["coin_name_plural"];
            $group_name      = $row->target_group_id;
            $using_bots_pool = $row->using_bot_account ? "using the bot's pool" : "";
            foreach($config->facebook_monitor_objects as $key => $object)
            {
                if($object["id"] == $row->target_group_id)
                {
                    $group_name = $object["name"];
                    break;
                } # end if
            } # end foreach
            $started = $row->date_started == "0000-00-00 00:00:00"
                     ? "&lt;Not started&gt;"
                     : time_elapsed_string($row->date_started);
            $finished = $row->date_finished == "0000-00-00 00:00:00"
                      ? "&lt;Not yet finished&gt;"
                      : time_elapsed_string($row->date_finished);
            $query2 = "
                select * from ".$config->db_tables["tip_batch_submissions"]." where
                    batch_id = '$row->batch_id' and
                    recipient_facebook_id = '$account->facebook_id'
            ";
            $current_user_info = "";
            $creator_name = $account->facebook_id == $row->creator_facebook_id
                          ? "you"
                          : "<a class='pseudo_link' href='https://www.facebook.com/$row->creator_facebook_id' target='_blank'>$row->creator_name</a>";
            if( $account->facebook_id != $row->creator_facebook_id )
            {
                $res2 = mysql_query($query2);
                if( mysql_num_rows($res2) == 0 )
                {
                    $current_user_info = "<div class='ui-widget-content ui-corner-all'>Sorry... you haven't been included here ;(</div>";
                }
                else
                {
                    $row2 = mysql_fetch_object($res2);
                    if( $row2->state == "pending" )
                        $current_user_info = "
                            <div class='ui-state-highlight ui-corner-all'>
                                You've been picked to receive a drop of ".$row2->coin_amount." ".$config->current_coin_data["coin_name_plural"]."!
                                Please wait for your turn!
                            </div>
                        ";
                    elseif( $row2->state == "sent" )
                        $current_user_info = "
                        <div class='ui-state-highlight ui-corner-all'>
                            You've already received a drop of ".$row2->coin_amount." ".$config->current_coin_data["coin_name_plural"]."
                            ".time_elapsed_string($row2->date_processed)."!
                        </div>
                        ";
                    elseif( $row2->state == "failed" )
                        $current_user_info = "
                            <div class='ui-state-error ui-corner-all'>
                                You've been picked to receive a drop of ".$row2->coin_amount." ".$config->current_coin_data["coin_name_plural"].",
                                which was attempted to be delivered ".time_elapsed_string($row2->date_processed)."
                                but it wasn't because of the next error: $row2->api_message.
                            </div>
                        ";
                } # end if
                mysql_free_result($res2);
            } # end if
            if( $row->state == "cancelled" )
            {
                $query3 = "
                    select
                        state, count(recipient_facebook_id) as count
                        from ".$config->db_tables["tip_batch_submissions"]."
                        where batch_id = '$row->batch_id'
                        group by state
                ";
                $res3 = mysql_query($query3);
                $states = array();
                while($row3 = mysql_fetch_object($res3)) $states[] = ucwords($row3->state) . ": " . $row3->count;
                $row->cancellation_message .= " [" . implode(", ", $states) . "]";
            } # end if
            $rain_coin_totals = "";
            if( $row->state != 'forging' )
            {
                $query3 = "
                    select
                        state, sum(coin_amount) as total_coins
                        from ".$config->db_tables["tip_batch_submissions"]."
                        where batch_id = '$row->batch_id'
                        group by state
                ";
                $res3 = mysql_query($query3);
                $res3 = mysql_query($query3);
                $coin_totals = array();
                $grand_total = 0;
                while($row3 = mysql_fetch_object($res3))
                {
                    $coin_totals[]  = ucwords($row3->state) . ": " . $row3->total_coins. " ".$config->current_coin_data["coin_name_plural"];
                    $grand_total   += $row3->total_coins;
                } # end while
                $rain_coin_totals = "<div>" . implode(", ", $coin_totals) . " Out of ".$grand_total." ".$config->current_coin_data["coin_name_plural"]."</div>";
            } # end if
            echo "
                <div class='rain_entry $ui_state ui-corner-all'>
                    <div class='ui-widget-header ui-corner-all'>
                        <b>$row->batch_title</b>
                        <button class='smaller' title='View this rain recipient list' onclick='show_tip_rain_details(\"$row->batch_id\")'><span class='ui-icon embedded ui-icon-search'></span></button>
                        <a title='Permalink to this rain drops list' target='_top' href='$config->facebook_canvas_page?switch_coin=".urlencode($config->current_coin_name)."&show_rain={$row->batch_id}#tabs'>[Permalink]</a>
                    </div>
                    <div>
                        Created ".time_elapsed_string($row->date_created)." by $creator_name $using_bots_pool
                        for $row->recipient_amount $recipient_type of $group_name with a drop size $drop_size.
                    </div>
                    $rain_coin_totals
                    $current_user_info
                    <hr>
                    <div>Current state: ".ucwords($row->state).(empty($row->cancellation_message) ? "" : " &mdash; ".$row->cancellation_message).".</div>
                    ".($row->state != "forging" ? "<span class='ui-widget-content ui-corner-all'>Started $started</span>&nbsp;"   : "")."
                    ".($row->state != "forging" ? "<span class='ui-widget-content ui-corner-all'>Finished $finished</span>" : "")."
                </div>
            ";
        } # end while
        mysql_free_result($res);
        die();
    } # end if

    ######################################################
    if( $_REQUEST["mode"] == "show_last_rain_drops_list" )
    ######################################################
    {
        $now  =  date("Y-m-d H:i:s");
        $limit = date("Y-m-d H:i:s", strtotime("now - 2 minutes"));
        $query = "
            select
                ".$config->db_tables["tip_batch_submissions"].".*,
                ".$config->db_tables["tip_batches"].".batch_title  as batch_title,
                ".$config->db_tables["tip_batches"].".creator_name as batch_creator_name,
                ".$config->db_tables["tip_batches"].".state        as batch_state
            from
                ".$config->db_tables["tip_batch_submissions"].",
                ".$config->db_tables["tip_batches"]."
            where
                ".$config->db_tables["tip_batch_submissions"].".coin_name = '".$config->current_coin_name."' and
                ".$config->db_tables["tip_batch_submissions"].".batch_id  = ".$config->db_tables["tip_batches"].".batch_id and
                ".$config->db_tables["tip_batch_submissions"].".date_created < '$now' and (
                    ".$config->db_tables["tip_batch_submissions"].".date_processed = '0000-00-00 00:00:00' or
                    ".$config->db_tables["tip_batch_submissions"].".date_processed >= '$limit'
                )
            order by
                date_created desc,
                date_processed desc,
                recipient_name asc
            limit " . ($config->tip_rain_submissions_per_minute * 2) . "
        ";
        # echo "<pre>$query</pre>";
        $res = mysql_query($query);
        if( mysql_num_rows($res) == 0 )
        {
            mysql_free_result($res);
            header("Content-Type: text/html; charset=utf-8");
            die('
                <div class="ui-state-highlight ui-corner-all" style="padding: 5px;">
                    <span class="ui-icon embedded ui-icon-info"></span>
                    Sorry, but there are no drops recently fallen or awaiting to fall.
                </div>
            ');
        } # end if

        while( $row = mysql_fetch_object($res) )
        {
            switch($row->batch_state)
            {
                case "forging":   $ui_state = "ui-state-default";  break;
                case "active":    $ui_state = "ui-state-active";   break;
                case "finished":  $ui_state = "ui-widget-content"; break;
                case "cancelled": $ui_state = "ui-state-error";    break;
            } # end switch
            $delivery_date = $pending_since = "";
            if( $row->state == "sent" )
            {
                $delivery_date = "<b>Sent " . time_elapsed_string($row->date_processed) . "</b>";
            }
            elseif( $row->state == "failed" )
            {
                $ui_state      = "ui-state-error";
                $delivery_date = "Attempted to be sent " . time_elapsed_string($row->date_processed) . " "
                               . "but got error: $row->api_message";
            }
            else
            {
                $pending_since = "[$row->state since ".time_elapsed_string($row->date_created)."]";
            } # end if
            echo "
                <div class='drop_entry $ui_state ui-corner-all'>
                    <div>$row->batch_title by $row->batch_creator_name ($row->batch_state)</div>
                    <div>
                        ".$row->coin_amount." ".$config->current_coin_data["coin_name_plural"]." &#x25ba;
                        <a href='https://www.facebook.com/$row->recipient_facebook_id' target='_blank'>$row->recipient_name</a>
                        $pending_since
                        $delivery_date
                    </div>
                </div>
            ";
        } # end while
        mysql_free_result($res);
        die();
    } # end if

    #####################################################
    if( $_REQUEST["mode"] == "show_specific_rain_drops" )
    #####################################################
    {

        if( trim($_REQUEST["batch_id"]) == "" )
        {
            header("Content-Type: text/html; charset=utf-8");
            die('
                <div class="ui-state-error ui-corner-all" style="padding: 5px;">
                    <span class="ui-icon embedded ui-icon-alert"></span>
                    You must provide a rain id to browse drop recipients.
                </div>
            ');
        } # end if

        $query = "
            select * from ".$config->db_tables["tip_batches"]." where batch_id = '".addslashes(trim($_REQUEST["batch_id"]))."'
        ";
        $res = mysql_query($query);
        if( mysql_num_rows($res) == 0 )
        {
            mysql_free_result($res);
            header("Content-Type: text/html; charset=utf-8");
            die('
                <div class="ui-state-error ui-corner-all" style="padding: 5px;">
                    <span class="ui-icon embedded ui-icon-alert"></span>
                    The specified rain wasn\'t found in the database.
                </div>
            ');
        } # end if
        mysql_free_result($res);

        $query = "
            select
                *
            from
                ".$config->db_tables["tip_batch_submissions"]."
            where
                batch_id = '".addslashes(trim($_REQUEST["batch_id"]))."'
            order by
                recipient_name asc
        ";
        # echo "<pre>$query</pre>";
        $res = mysql_query($query);
        if( mysql_num_rows($res) == 0 )
        {
            mysql_free_result($res);
            header("Content-Type: text/html; charset=utf-8");
            die('
                <div class="ui-state-highlight ui-corner-all" style="padding: 5px;">
                    <span class="ui-icon embedded ui-icon-info"></span>
                    This rain has no drop recipients!
                </div>
            ');
        } # end if

        while( $row = mysql_fetch_object($res) )
        {
            switch($row->batch_state)
            {
                case "forging":   $ui_state = "ui-state-default";  break;
                case "active":    $ui_state = "ui-state-active";   break;
                case "finished":  $ui_state = "ui-widget-content"; break;
                case "cancelled": $ui_state = "ui-state-error";    break;
            } # end switch
            $delivery_date = $pending_since = "";
            if( $row->state == "sent" )
            {
                $delivery_date = "<b>Sent " . time_elapsed_string($row->date_processed) . "</b>";
            }
            elseif( $row->state == "failed" )
            {
                $ui_state      = "ui-state-error";
                $delivery_date = "Attempted to be sent " . time_elapsed_string($row->date_processed) . " "
                               . "but got error: $row->api_message";
            }
            else
            {
                $pending_since = "[$row->state since ".time_elapsed_string($row->date_created)."]";
            } # end if
            echo "
                <div class='drop_entry $ui_state ui-corner-all'>
                    ".$row->coin_amount." ".$config->current_coin_data["coin_name_plural"]." &#x25ba;
                    <a href='https://www.facebook.com/$row->recipient_facebook_id' target='_blank'>$row->recipient_name</a>
                    $pending_since
                    $delivery_date
                </div>
            ";
        } # end while
        mysql_free_result($res);
        die();
    } # end if

    ######################################
    if( $_REQUEST["mode"] == "add_group" )
    ######################################
    {
        if( ! $is_admin )
        {
            header("Content-Type: text/plain; charset=utf-8");
            die("Access denied.");
        } # end if

        foreach($config->facebook_monitor_objects as $key => $data)
        {
            if( $data["id"] == $_POST["id"] )
            {
                header("Content-Type: text/plain; charset=utf-8");
                die("
                    <div class='ui-state-error message_box ui-corner-all'>
                        <span class='ui-icon embedded ui-icon-alert'></span>
                        The group has already been defined.
                    </div>
                ");
            } # end if
        } # end foreach


        $contents = trim(file_get_contents("groups.dat"));

        $contents .= "\n"
                  .  date("Y-m-d")                      . "\t"
                  .  $_POST["handler"]                  . "\t"
                  .  $_POST["name"]                     . "\t"
                  .  "group"                            . "\t"
                  .  "feed"                             . "\t"
                  .  $_POST["url"]                      . "\t"
                  .  $_POST["id"]                       . "\t"
                  .  $_POST["tippingbot_id_acount"]     . "\t"
                  .  $_POST["tippingbot_fb_id_account"] . "\n"
                  ;
        copy("groups.dat", "groups.dat.bak");
        file_put_contents("groups.dat", $contents);
        die("
            <div class='ui-state-highlight message_box ui-corner-all'>
                <span class='ui-icon embedded ui-icon-info'></span>
                Groups data file has been saved. The group will be included in the next run.<br>
                Wait for a couple of minutes and post a tip on the group to make sure it has catched up.
                If not, look on the logs section and check if a log has been generated.<br>
                <b>Do not forget to announce the addition on the news page and on the group!</b><br>
                Please refresh the groups list.
            </div>
        ");
    } # end if

    #########################################
    if( $_REQUEST["mode"] == "delete_group" )
    #########################################
    {
        if( ! $is_admin )
        {
            header("Content-Type: text/plain; charset=utf-8");
            die("Access denied.");
        } # end if

        $found = false;
        foreach($config->facebook_monitor_objects as $key => $data)
        {
            if( $data["key"] == $_REQUEST["handler"] )
            {
                $found = true;
                break;
            } # end if
        } # end foreach

        if( ! $found )
        {
            header("Content-Type: text/plain; charset=utf-8");
            die("
                <div class='ui-state-error message_box ui-corner-all'>
                    <span class='ui-icon embedded ui-icon-alert'></span>
                    There is no group defined with that handler.
                </div>
            ");
        } # end if

        unset($config->facebook_monitor_objects[$_REQUEST["handler"]]);
        $contents = "";

        foreach($config->facebook_monitor_objects as $key => $data)
        {
            $contents .= $data["since"]                     . "\t"
                      .  $key                               . "\t"
                      .  $data["name"]                      . "\t"
                      .  $data["type"]                      . "\t"
                      .  $data["edge"]                      . "\t"
                      .  $data["url"]                       . "\t"
                      .  $data["id"]                        . "\t"
                      .  $data["tippingbot_id_acount"]      . "\t"
                      .  $data["tippingbot_fb_id_account"]  . "\n"
                      ;
        } # end foreach

        copy("groups.dat", "groups.dat.bak");
        file_put_contents("groups.dat", $contents);
        die("
            <div class='ui-state-highlight message_box ui-corner-all'>
                <span class='ui-icon embedded ui-icon-info'></span>
                Groups data file has been saved. The group '$_REQUEST[handler]' will no longer be monitored.
            </div>
        ");
    } # end if

    #######################################
    if( $_REQUEST["mode"] == "get_qrcode" )
    #######################################
    {
        if( empty($_REQUEST["address"]) ) die("Invalid address");
        include "lib/phpqrcode/qrlib.php";
        header("Pragma: cache");
        header('Expires: ' . gmdate('D, d M Y H:i:s', (time()+(86400*7))).' GMT');
        header("Cache-Control: max-age=".(time()+(86400*7)));
        QRcode::png($_REQUEST["address"]);
        die();
    } # end if

    ####################################################
    if( $_REQUEST["mode"] == "save_account_extensions" )
    ####################################################
    {
        header("Content-Type: text/plain; charset=utf-8");
        include "models/account_extensions.php";
        if( empty($_REQUEST["extension_data"]) ) die("You're not submitted any data to extend your account.");

        $new_reroute_to     = stripslashes($_REQUEST["extension_data"]["reroute_to"]);
        if( $account->id_account == $new_reroute_to ) die("Please specify an account id different than the current one.");

        $tmp = new account($new_reroute_to);
        if( ! $tmp->exists ) die("Please provide an existing {$config->app_display_shortname} account id.");

        $account_extensions = new account_extensions($account->id_account);

        $previous_rerouting_data = $account_extensions->reroute_to;
        if( $previous_rerouting_data == $new_reroute_to ) die("No changes saved.");
        if( empty($account->email) && empty($account->alternate_email) )
        {
            # No email. We save the change.
            $tmp = new account($new_reroute_to);
            if( ! empty($new_reroute_to) && ! $tmp->exists ) die("Error: the specified account id doesn't exist.");

            $account_extensions->reroute_to = $new_reroute_to;
            if( ! $account_extensions->exists ) $account_extensions->id_account = $account->id_account;
            $account_extensions->save();

            $notification = empty($previous_rerouting_data)
                          ? "Account extended settings saved. All future transactions for {$account->id_account} are " . (empty($new_reroute_to) ? "going to be kept." : "going to be sent to $new_reroute_to.")
                          : "Account extended settings saved. All future transactions previously sent to $previous_rerouting_data are " . (empty($new_reroute_to) ? "going to be kept." : "going to be sent to $new_reroute_to.")
                          ;
            load_extensions("save_account_extensions_notification", $root_url);
            die($notification);
        } # end if

        # The account has an email, so let's build up a token and send it.
        $limit        = date("Y-m-d H:i:s", strtotime("now + 30 minutes"));
        $token        = encryptRJ256($config->tokens_encryption_key, "$account->id_account\t$previous_rerouting_data\t$new_reroute_to\t$limit");
        $ip           = $_SERVER['REMOTE_ADDR'];
        $hostname     = gethostbyaddr($_SERVER['REMOTE_ADDR']);
        $fecha_envio  = date("Y-m-d H:i:s");
        $mail_from    = "$config->mail_sender_name<$config->mail_sender_address>";
        $mail_to      = ( empty($account->email) ? "$account->name alternate email<$account->alternate_email>" : "$account->name<$account->email>");
        $url          = $config->website_pages["toolbox"] . "?mode=authorize_account_extensions&token=" . urlencode($token);

        $mail_subject = $config->app_display_shortname . " - Account extensions change request";
        $mail_body = "We have received a request to change extended data for your account:\r\n"
                   . "\r\n"
                   . "Old transaction re-routing account: ".(empty($previous_rerouting_data) ? "none" : $previous_rerouting_data)."\r\n"
                   . "New transaction re-routing account: ".(empty($new_reroute_to)          ? "none" : $new_reroute_to)."\r\n"
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
            "Content-Type: text/plain; charset=utf-8\r\n" .
            ( empty($account->email) ? "" : (empty($account->alternate_email) ? "" : "Cc: $account->name alternate email<$account->alternate_email>\r\n") )
        );
        die("An e-mail has been sent to you. Please look for it and follow instructions to authorize your changes before one hour or it will expire.");

    } # end if

    ##################################################################
    if( $is_admin && $_REQUEST["mode"] == "throw_groups_admin_table" )
    ##################################################################
    {
        ?>

        <div class="ui-state-highlight message_box ui-corner-all">
            <span class="ui-icon embedded ui-icon-info"></span>
            Member counts are cached on an hourly basis to speed up the load of this table.
        </div>

        <table class="tablesorter" width="100%" cellpadding="2" cellspacing="1" border="0">
            <thead>
                <tr>
                    <th class="{sorter: false}">Since</th>
                    <th class="{sorter: false}">Handler</th>
                    <th class="{sorter: false}">Name</th>
                    <th class="{sorter: false}">Type</th>
                    <th class="{sorter: false}">Edge</th>
                    <th class="{sorter: false}">URL</th>
                    <th class="{sorter: false}">Id</th>
                    <th class="{sorter: false}">Tipbot Id</th>
                    <th class="{sorter: false}">Tipbot FB Id</th>
                    <th class="{sorter: false}">User count</th>
                    <th class="{sorter: false}">Actions</th>
                </tr>
            </thead>
            <tbody>
                <? $total_reach = 0; ?>
                <? foreach($config->facebook_monitor_objects as $key => $data): ?>
                    <tr group_handler="<?=$key?>">
                        <td nowrap><?=empty($data["since"]) ? "N/A": $data["since"]?></td>
                        <td><?=$key?></td>
                        <td><?=htmlspecialchars($data["name"])?></td>
                        <td><?=$data["type"]?></td>
                        <td><?=$data["edge"]?></td>
                        <td><a href="<?=$data["url"]?>" target="_blank"><?=$data["url"]?></a></td>
                        <td><?=$data["id"]?></td>
                        <td><?=$data["tippingbot_id_acount"]?></td>
                        <td><?=$data["tippingbot_fb_id_account"]?></td>
                        <td align="right">
                            <?
                                $this_group_count = get_flag_value("group_counts:$key");
                                if( is_numeric($this_group_count) )
                                {
                                    echo number_format($this_group_count);
                                    $total_reach += $this_group_count;
                                }
                                else
                                {
                                    echo $this_group_count;
                                } # emd if
                            ?>
                        </td>
                        <td nowrap>
                            <button onclick="delete_group('<?=$key?>')">
                                <span class="ui-icon embedded ui-icon-trash"></span>
                                Delete
                            </button>
                        </td>
                    </tr>
                <? endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td align="right" colspan="9" style="font-weight: bold;">Total reach:</td>
                    <td align="right" style="font-weight: bold;"><?= number_format($total_reach) ?></td>
                    <td>&nbsp;</td>
                </tr>
            </tfoot>
        </table>

        <?
        die();
    } # end if

    ################################################################
    if( $is_admin && $_REQUEST["mode"] == "throw_monitor_bot_logs" )
    ################################################################
    {
        $files = glob("logs/*.log");
        if( count($files) == 0 )
        {
            ?>
            <div class="ui-state-hightlight ui-corner-all" style="padding: 5px;">
                <span class="ui-icon embedded ui-icon-info"></span>
                There are no logs available for viewing.
            </div>
            <?
        }
        else
        {
            # Grouping
            $last_group = "";
            foreach($files as $file)
            {
                $file_group = preg_replace('/\-[0-9]*\.log$/', "", $file);
                $filename = basename($file);
                ?>

                <? if( $file_group != $last_group ) { ?>
                    <? if($last_group != "") echo "</div><!-- div[group=$file_group] -->\n\n"; ?>
                    <div class="ui-widget-content message_box ui-corner-all" group="<?=$file_group?>" style="-moz-column-count: 2; -moz-column-gap: 10px; -webkit-column-count: 2; -webkit-column-gap: 10px;">
                <? } # end if ?>

                <a class="buttonized" href="toolbox.php?mode=show_log&file=<?=$filename?>&wasuuup=<?= md5(mt_rand(1, 65535)) ?>"
                   target="_blank" style="display: inline-block; margin: 5px; font-weight: normal;">
                    <span class="ui-icon embedded ui-icon-script"></span>
                    <?= str_replace(array("_","-",".log"), " ", $filename) ?>
                    &bull; <?= date("Y-m-d H:i:s", filemtime($file))?>
                    &bull; <?= number_format(filesize($file) / 1024) ?> KiB
                </a>

                <?
                $last_group = $file_group;
            } # end foreach
            ?></div><!-- /div[group=$file_group] --><? echo "\n\n";
        } # end if
        die();
    } # end if

    #######################################################
    if( $is_admin && $_REQUEST["mode"] == "decrypt_token" )
    #######################################################
    {
        header("Content-Type: text/plain; charset=utf-8");
        if( empty($_REQUEST["key"]) )   die("ERROR: No key provided");
        if( empty($_REQUEST["token"]) ) die("ERROR: No token provided");
        die( decryptRJ256($_REQUEST["key"], $_REQUEST["token"]) );
    } # end if

    #################################################
    if( $_REQUEST["mode"] == "user_ops_log_listing" )
    #################################################
    {
        header("Content-Type: text/html; charset=utf-8");
        include "$root_url/contents/index.user_home.opslog.inc";
        die();
    } # end if

    ##############################################
    if( $_REQUEST["mode"] == "unclaimed_listing" )
    ##############################################
    {
        header("Content-Type: text/html; charset=utf-8");
        include "$root_url/contents/index.user_home.unclaimed.inc";
        die();
    } # end if

    ############################################################
    if( $_REQUEST["mode"] == "fetch_wallet_transaction_counts" )
    ############################################################
    {
        header("Content-Type: application/json; charset=utf-8");
        list($tips_in, $tips_out, $deposits, $withdrawals, $all_activity) = fetch_wallet_transactions_table("counts");
        $return = array(
            "message" => "OK",
            "data"    => array(
                "tips_in"      => number_format($tips_in),
                "tips_out"     => number_format($tips_out),
                "deposits"     => number_format($deposits),
                "withdrawals"  => number_format($withdrawals),
                "all_activity" => ($all_activity < 1024 ? "" : "&gt;=") . number_format($all_activity),
            )
        );
        die( json_encode($return) );
    } # end if

    ############################################################
    if( $_REQUEST["mode"] == "fetch_wallet_transactions_table" )
    ############################################################
    {
        header("Content-Type: text/html; charset=utf-8");
        render_wallet_transactions_table($_REQUEST["category"]);
        die();
    } # end if

    header("Content-Type: text/plain; charset=utf-8");
    die("ERROR: Invalid method call");
