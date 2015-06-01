<?php
    /**
     * Platform Extension: Instagram / Connecting facility
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
    db_connect();

    header("Content-Type: text/html; charset=utf-8");

    #############################
    # [+] Disconnection utility #
    #############################
    {
        if( $_GET["disconnect"] == "true" )
        {
            $cookie_name         = $config->session_vars_prefix . $config->cookie_session_identifier;
            $session_from_cookie = false;
            if( empty($_COOKIE[$cookie_name]) ) die("You don't have an open session. Please login.");

            $user_id = decryptRJ256($config->tokens_encryption_key, $_COOKIE[$cookie_name]);
            if( empty($user_id) ) die("You don't have an open session. Please login.");

            $account = new account($user_id);
            if( ! $account->exists ) die("You have an open session but the account is unexistent. Please logout and login with a valid account.");

            $query = "select * from {$config->db_tables["instagram_users"]} where id_account = '{$account->id_account}'";
            $res   = mysql_query($query);
            if( mysql_num_rows($res) == 0 ) die("You don't have an Instagram account connected with your ".$config->app_display_shortname . " account.");

            $query = "delete from {$config->db_tables["instagram_users"]} where id_account = '{$account->id_account}'";

            # Logging out
            $cookie_name  = $config->session_vars_prefix . $config->cookie_session_identifier;
            setcookie($cookie_name, "", time() - 3600, "/", $config->cookie_domain);

            mysql_query($query);
            die("OK");
        } # end if
    }
    #############################
    # [-] Disconnection utility #
    #############################

    $trailing_error = "";
    ##############################
    # [+] Authentication section #
    ##############################
    {
        if( empty($_REQUEST["coming_from_oauth"]) )
        {
            # We're going to Instagram
            $_SESSION[$config->session_vars_prefix."instagram_login_caller"]
                = empty($_GET["return_to"])
                ? (empty($_SERVER["HTTP_REFERER"]) ? $config->website_pages["root_url"] : stripslashes($_SERVER["HTTP_REFERER"]))
                : stripslashes($_GET["return_to"])
                ;

            $_SESSION[$config->session_vars_prefix."ignore_session"] = ! empty($_GET["ignore_session"]);

            $redirect_uri = "https://{$config->app_root_domain}" . $_SERVER["PHP_SELF"]
                          . "?coming_from_oauth=true"
                          ;
            $_SESSION[$config->session_vars_prefix."instagram_redirect_uri"] = $redirect_uri;

            # Let's redirect the user
            $url = $config->instagram_api_urls["clientside_authorization"] . "?"
                 . http_build_query(
                    array(
                        "client_id"         => $config->instagram_client_info["client_id"],
                        "response_type"     => "code",
                        "redirect_uri"      => $redirect_uri
                    )
                 );
            header("Location: $url" );
            die("<a href='$url'>Click here to continue...</a>");
        } # end if

        #============================
        # We're coming from Instagram
        #============================

        if( ! empty($_GET['error']) )
        {
            $trailing_error = "We need your authorization to link your Instagram account with our systems.<br>
                               If you think you've received this message by error, please take a screenshot of this page
                               and send it to us on a support request at our
                               <a href='".$config->website_pages["support"]."'>Help &amp; support forum</a>.";
        }
        elseif( empty($_GET["code"]) )
        {
            $trailing_error = "We didn't receive an authorization code from Instagram. Please go back and try again.<br>
                               If the problem persists, please take a screenshot of this page
                               and send it to us on a support request at our
                               <a href='".$config->website_pages["support"]."'>Help &amp; support forum</a>.";
        }
        else
        {
            $url = $config->instagram_api_urls["access_token_grabbing"];
            $params = array(
                "client_id"     => $config->instagram_client_info["client_id"],
                "client_secret" => $config->instagram_client_info["client_secret"],
                "grant_type"    => "authorization_code",
                "redirect_uri"  => $_SESSION[$config->session_vars_prefix."instagram_redirect_uri"],
                "code"          => $_GET["code"]
            );
            list($res, $data) = post($url, $params);

            if( $res != "OK" )
            {
                $trailing_error = "We couldn't receive an access token for your account from Instagram.<br>
                                   Response: {$data}<br>
                                   Please try again. If the problem persists, please take a screenshot of this page
                                   and send it to us on a support request at our
                                   <a href='".$config->website_pages["support"]."'>Help &amp; support forum</a>.";
            }
            else
            {
                $user_info = json_decode($data);
                if( empty($user_info->user) )
                {
                    $trailing_error = "Can't get your account information from Instagram.<br>
                                       Response: ".print_r($user_info)."<br>
                                       Please try again. If the problem persists, please take a screenshot of this page
                                       and send it to us on a support request at our
                                       <a href='".$config->website_pages["support"]."'>Help &amp; support forum</a>.";
                } # end if

                if( empty($trailing_error) )
                {
                    # Let's find the user by its instagram user name
                    $query = "select id_account from ".$config->db_tables["instagram_users"] . " where user_name = '{$user_info->user->username}'";
                    $res   = mysql_query($query);
                    if( mysql_num_rows($res) > 0 )
                    {
                        # We found it, so let's grab it!
                        $row     = mysql_fetch_object($res);
                        $user_id = $row->id_account;

                        # Let's open the user session
                        $cookie_name  = $config->session_vars_prefix . $config->cookie_session_identifier;
                        $cookie_value = encryptRJ256($config->tokens_encryption_key, $user_id);
                        setcookie($cookie_name, $cookie_value, strtotime("now + 30 days"), "/", $config->cookie_domain);

                        # Let's update the record if it is empty
                        if( empty($row->user_id) )
                        {
                            # The account is valid, so let's tie it up
                            $query = "
                                update ".$config->db_tables["instagram_users"]." set
                                    user_id      = '{$user_info->user->id}',
                                    full_name    = '{$user_info->user->full_name}',
                                    access_token = '{$user_info->access_token}'
                                where
                                    user_name    = '{$user_info->user->username}'
                            ";
                            mysql_query($query);
                        } # end if

                        # Let's go back to the caller -if any-
                        $redirect  = $_SESSION[$config->session_vars_prefix."instagram_login_caller"];
                        $redirect .= (stristr($redirect, "?") === false ? "?" : "&") . "wasuuuup=" . md5(mt_rand(1,65535));
                        @unlink("/tmp/wpuni_igt_{$_GET['coming_from_oauth']}.dat");
                        header("Location: $redirect");
                        die("<a href='$redirect'>Click here to continue...</a>");
                    } # end if

                    # If we got here, there is no user tie. We need to check if there is an open session
                    $cookie_name         = $config->session_vars_prefix . $config->cookie_session_identifier;
                    $session_from_cookie = false;
                    $user_id             = "";

                    if( ! $_SESSION[$config->session_vars_prefix."ignore_session"] )
                    {
                        if( ! empty($_COOKIE[$cookie_name]) )
                            $user_id = decryptRJ256($config->tokens_encryption_key, $_COOKIE[$cookie_name]);
                    } # end if

                    if( empty($user_id) )
                    {
                        # There is no session open. We'll create an account.
                        $account                   = new account();
                        $account->name             = $user_info->user->full_name;
                        $account->tipping_provider = $config->current_tipping_provider_keyname;
                        $account->date_created     =
                        $account->last_update      =
                        $account->last_activity    = date("Y-m-d H:i:s");
                        $account->id_account       = $config->instagram_account_prefix . base_convert($user_info->user->id, 10, 26);
                        $account->save();
                        $user_id = $account->id_account;

                        # We'll open the session for the new account.
                        $cookie_name  = $config->session_vars_prefix . $config->cookie_session_identifier;
                        $cookie_value = encryptRJ256($config->tokens_encryption_key, $user_id);
                        setcookie($cookie_name, $cookie_value, strtotime("now + 30 days"), "/", $config->cookie_domain);
                    }
                    else
                    {
                        # There's an open session, so let's add the tie
                        $account = new account($user_id);
                        if( ! $account->exists )
                        {
                            # Oops... the account doesn't exist!
                            $trailing_error = "You're trying to tie an Instagram account with an unexisting {$config->app_single_word_name}
                                               account with an opened session. You need to have a valid session open.<br>
                                               Please try again. If the problem persists, please take a screenshot of this page
                                               and send it to us on a support request at our
                                               <a href='{$config->website_pages["support"]}'>Help &amp; support forum</a>.";
                        } # end if
                    } # end if
                } # end if

                if( empty($trailing_error) )
                {
                    # The account is valid, so let's tie it up
                    $query = "
                        insert into ".$config->db_tables["instagram_users"]." set
                            user_id      = '{$user_info->user->id}',
                            user_name    = '{$user_info->user->username}',
                            full_name    = '{$user_info->user->full_name}',
                            access_token = '{$user_info->access_token}',
                            id_account   = '{$account->id_account}'
                    ";
                    mysql_query($query);

                    # Let's go back to the caller -if any-
                    $redirect  = $_SESSION[$config->session_vars_prefix."instagram_login_caller"];
                    $redirect .= (stristr($redirect, "?") === false ? "?" : "&") . "wasuuuup=" . md5(mt_rand(1,65535));
                    @unlink("/tmp/wpuni_igt_{$_GET['coming_from_oauth']}.dat");
                    header("Location: $redirect");
                    die("<a href='$redirect'>Click here to continue...</a>");
                } # end if
            } # end if
        } # end if
    }
    ##############################
    # [-] Authentication section #
    ##############################

    $cookie_name         = $config->session_vars_prefix . $config->cookie_session_identifier;
    $session_from_cookie = false;
    if( ! empty($_COOKIE[$cookie_name]) )
    {
        $user_id = decryptRJ256($config->tokens_encryption_key, $_COOKIE[$cookie_name]);
    }
    else
    {
        $user_id = "";
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
        $title_append = $account->name . " - Twitter connection";
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
        $title_append = "Twitter connection";
    } # end if
    if( ! $config->engine_enabled ) $jquery_ui_theme = $config->engine_disabled_ui_theme;
?>
<html xmlns:fb="http://ogp.me/ns/fb#">
    <head>
        <title><?=$config->app_display_longname?> - <?=$title_append?></title>
        <meta name="viewport" content="width=device-width" />
        <meta http-equiv="X-UA-Compatible" content="IE=Edge" />
        <link rel="icon"          href="<?= $config->favicon ?>">
        <link rel="shortcut icon" href="<?= $config->favicon ?>">
        <script                                     src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
        <link rel="stylesheet" type="text/css"      href="//code.jquery.com/ui/1.10.4/themes/<?=$jquery_ui_theme?>/jquery-ui.css">
        <script                                     src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
        <link rel="stylesheet" type="text/css"      href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
    </head>
    <body>

        <? if( ! empty($trailing_error) ): ?>

            <div class="ui-state-error message_box ui-corner-all">
                <span class="fa fa-info-circle"></span>
                <?= $trailing_error ?>
            </div>

        <? endif; ?>

    </body>
</html>
