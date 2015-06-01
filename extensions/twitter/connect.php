<?php
    /**
     * Platform Extension: Twitter / Connecting facility
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
    include "lib/twitteroauth.php";
    include "lib/TwitterAPIExchange.php";
    session_start();
    db_connect();

    header("Content-Type: text/html; charset=utf-8");

    #####################################
    # [+] Twitter disconnection utility #
    #####################################
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

            $query = "select * from {$config->db_tables["twitter"]} where id_account = '{$account->id_account}'";
            $res   = mysql_query($query);
            if( mysql_num_rows($res) == 0 ) die("You don't have a twitter account connected with your ".$config->app_display_shortname . " account.");
            # if( empty($account->email) && empty($account->alternate_email) ) die( "Your account is tied to your Twitter account and you haven't set an alternate way to get into {$config->app_display_longname}. Please edit your account and set an email and a password or contact us and let us know if you want your account being deleted." );

            $query = "delete from {$config->db_tables["twitter"]} where id_account = '{$account->id_account}'";

            # Logging out
            $cookie_name  = $config->session_vars_prefix . $config->cookie_session_identifier;
            setcookie($cookie_name, "", time() - 3600, "/", $config->cookie_domain);

            mysql_query($query);
            die("OK");
        } # end if
    }
    #####################################
    # [-] Twitter disconnection utility #
    #####################################

    $twitter_error = "";
    #########################################################################################################
    # Adapted from http://code.tutsplus.com/tutorials/how-to-authenticate-users-with-twitter-oauth--net-13595
    #========================================================================================================
    # Er... the page rendering functionality below has been superseeded by this section.
    #########################################################################################################
    # [+] Twitter authentication section #
    ######################################
    {
        if( empty($_REQUEST["coming_from_twitter_oauth"]) )
        {
            # We're going to Twitter.
            $_SESSION[$config->session_vars_prefix."twitter_login_caller"]
                = empty($_GET["return_to"])
                ? (empty($_SERVER["HTTP_REFERER"]) ? $config->website_pages["root_url"] : stripslashes($_SERVER["HTTP_REFERER"]))
                : stripslashes($_GET["return_to"])
                ;

            $_SESSION[$config->session_vars_prefix."ignore_session"] = ! empty($_GET["ignore_session"]);

            // The TwitterOAuth instance
            # echo "<pre>\$config->twitter_consumer_key := $config->twitter_consumer_key</pre>";
            $twitteroauth = new TwitterOAuth($config->twitter_consumer_key, $config->twitter_consumer_secret);
            # echo "<pre>\$twitteroauth := " . print_r($twitteroauth, true) . "</pre>";
            // Requesting authentication tokens, the parameter is the URL we will be redirected to
            $redirect_url = (empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?coming_from_twitter_oauth=true";
            $request_token = $twitteroauth->getRequestToken($redirect_url, "read");

            // If everything goes well..
            if($twitteroauth->http_code==200)
            {
                // Saving them into the session
                $_SESSION[$config->session_vars_prefix.'twitter_oauth_token']        = $request_token['oauth_token'];
                $_SESSION[$config->session_vars_prefix.'twitter_oauth_token_secret'] = $request_token['oauth_token_secret'];

                // Let's generate the URL and redirect
                $url = $twitteroauth->getAuthorizeURL($request_token['oauth_token']);
                header('Location: '. $url);
                die("<a href='$url'>Click here to continue...</a>");
            }
            else
            {
                // It's a bad idea to kill the script, but we've got to know when there's an error.
                $twitter_error = "Couldn't authenticate with twitter! Response: ".$twitteroauth->http_code.".<br>
                                  Please try again. If the problem persists, please take a screenshot of this page
                                  and send it to us on a support request at our
                                  <a href='".$config->website_pages["support"]."'>Help &amp; support forum</a>.";
                $twitter_action = "throw_error_and_show_login_form";
            } # end if
        }
        else
        {
            # We're coming from Twitter

            if( empty($_GET['oauth_verifier']) ||
                empty($_SESSION[$config->session_vars_prefix.'twitter_oauth_token']) ||
                empty($_SESSION[$config->session_vars_prefix.'twitter_oauth_token_secret']))
            {
                // Something's missing, go back to square 1
                $twitter_error = "We didn't receive anything from Twitter. It may have been a network error in the middle
                                  of our communication with their servers.<br>
                                  Please try again. If the problem persists, please take a screenshot of this page
                                  and send it to us on a support request at our
                                  <a href='".$config->website_pages["support"]."'>Help &amp; support forum</a>.";
            }
            else
            {
                // TwitterOAuth instance, with two new parameters we got in twitter_login.php
                $twitteroauth = new TwitterOAuth($config->twitter_consumer_key, $config->twitter_consumer_secret,
                                                 $_SESSION[$config->session_vars_prefix.'twitter_oauth_token'],
                                                 $_SESSION[$config->session_vars_prefix.'twitter_oauth_token_secret']);
                // Let's request the access token
                $access_token = $twitteroauth->getAccessToken($_GET['oauth_verifier']);
                if( empty($access_token['oauth_token']) )
                {
                    $twitter_error = "We couldn't receive an access token for your account from Twitter.<br>
                                      Object received: ".print_r($access_token)."<br>
                                      Please try again. If the problem persists, please take a screenshot of this page
                                      and send it to us on a support request at our
                                      <a href='".$config->website_pages["support"]."'>Help &amp; support forum</a>.";
                }
                else
                {
                    // Save it in a session var
                    $_SESSION[$config->session_vars_prefix.'twitter_access_token'] = $access_token;
                    // Let's get the user's info
                    $user_info = $twitteroauth->get('account/verify_credentials');
                    // Let's see what we do with the user info
                    if(isset($user_info->error))
                    {
                        // Something's wrong, go back to square 1
                        $twitter_error = "We couldn't receive your user data from Twitter.<br>
                                          Please try again. If the problem persists, please take a screenshot of this page
                                          and send it to us on a support request at our
                                          <a href='".$config->website_pages["support"]."'>Help &amp; support forum</a>.";
                    }
                    else
                    {
                        // Let's find the user by its twitter id
                        $_SESSION[$config->session_vars_prefix.'twitter_user_real_name'] = $user_info->name;
                        $query = "select id_account from ".$config->db_tables["twitter"] . " where twitter_id = '$user_info->id'";
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

                            # Let's see if we have an access token from the user
                            if( empty($row->access_token) )
                            {
                                # We don't. Let's update the existing one
                                # The account is valid, so let's tie it up
                                $query = "
                                    update ".$config->db_tables["twitter"]." set
                                        screen_name  = '{$user_info->screen_name}',
                                        access_token = '{$access_token['oauth_token']}',
                                        token_secret = '{$access_token['oauth_token_secret']}'
                                    where
                                        twitter_id   = '{$user_info->id}'
                                ";
                                mysql_query($query);

                                # Now let's follow the user
                                $twitter_options = array(
                                    'oauth_access_token'        => $config->twitter_access_token,
                                    'oauth_access_token_secret' => $config->twitter_token_secret,
                                    'consumer_key'              => $config->twitter_consumer_key,
                                    'consumer_secret'           => $config->twitter_consumer_secret
                                );
                                $twitter = new TwitterAPIExchange($twitter_options);
                                $response = $twitter
                                            ->buildOauth("https://api.twitter.com/1.1/friendships/create.json", "POST")
                                            ->setPostfields( array( "user_id" => $user_info->id
                                                                  , "follow"  => "true"
                                                                  ) )
                                            ->performRequest();
                            } # end if

                            # Let's go back to the caller -if any-
                            $redirect  = $_SESSION[$config->session_vars_prefix."twitter_login_caller"];
                            $redirect .= (stristr($redirect, "?") === false ? "?" : "&") . "wasuuuup=" . md5(mt_rand(1,65535));
                            header("Location: $redirect");
                            die("<a href='$redirect'>Click here to continue...</a>");
                        } # end if

                        # If we got here, there is no twitter tie. We need to check if there is an open session
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
                            $account->name             = $user_info->name;
                            $account->tipping_provider = $config->current_tipping_provider_keyname;
                            $account->date_created     =
                            $account->last_update      =
                            $account->last_activity    = date("Y-m-d H:i:s");
                            $account->id_account       = $config->twitter_account_prefix . base_convert($user_info->id, 10, 26);
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
                                $twitter_error = "You're trying to tie a Twitter account with an unexisting {$config->app_single_word_name}
                                                  account with an opened session. You need to have a valid session open.<br>
                                                  Please try again. If the problem persists, please take a screenshot of this page
                                                  and send it to us on a support request at our
                                                  <a href='{$config->website_pages["support"]}'>Help &amp; support forum</a>.";
                            } # end if
                        } # end if
                        if( empty($twitter_error) )
                        {
                            # The account is valid, so let's tie it up
                            $query = "
                                insert into ".$config->db_tables["twitter"]." set
                                    twitter_id   = '{$user_info->id}',
                                    screen_name  = '{$user_info->screen_name}',
                                    access_token = '{$access_token['oauth_token']}',
                                    token_secret = '{$access_token['oauth_token_secret']}',
                                    id_account   = '{$account->id_account}'
                            ";
                            mysql_query($query);

                            # Now let's follow the user
                            $twitter_options = array(
                                'oauth_access_token'        => $config->twitter_access_token,
                                'oauth_access_token_secret' => $config->twitter_token_secret,
                                'consumer_key'              => $config->twitter_consumer_key,
                                'consumer_secret'           => $config->twitter_consumer_secret
                            );
                            $twitter = new TwitterAPIExchange($twitter_options);
                            $response = $twitter
                                        ->buildOauth("https://api.twitter.com/1.1/friendships/create.json", "POST")
                                        ->setPostfields( array( "user_id" => $user_info->id
                                                              , "follow"  => "true"
                                                              ) )
                                        ->performRequest();

                            # Now let's send a welcome message to the user
                            $twitter_options = array(
                                'oauth_access_token'        => $config->twitter_access_token,
                                'oauth_access_token_secret' => $config->twitter_token_secret,
                                'consumer_key'              => $config->twitter_consumer_key,
                                'consumer_secret'           => $config->twitter_consumer_secret
                            );
                            $twitter = new TwitterAPIExchange($twitter_options);
                            $response = $twitter
                                        ->buildOauth("https://api.twitter.com/1.1/direct_messages/new.json", "POST")
                                        ->setPostfields( array( "user_id" => $user_info->id
                                                              , "text" => $config->twitter_welcome_message
                                                              ) )
                                        ->performRequest();

                            # Let's go back to the caller -if any-
                            $redirect  = $_SESSION[$config->session_vars_prefix."twitter_login_caller"];
                            $redirect .= (stristr($redirect, "?") === false ? "?" : "&") . "wasuuuup=" . md5(mt_rand(1,65535));
                            header("Location: $redirect");
                            die("<a href='$redirect'>Click here to continue...</a>");
                        } # end if
                    } # end if
                } # end if
            } # end if
        } # end if
    }
    ######################################
    # [-] Twitter authentication section #
    ######################################

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

        <? if( ! empty($twitter_error) ): ?>

            <div class="ui-state-error message_box ui-corner-all">
                <span class="fa fa-info-circle"></span>
                <?= $twitter_error ?>
            </div>

        <? endif; ?>

    </body>
</html>
