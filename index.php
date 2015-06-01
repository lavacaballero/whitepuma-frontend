<?
    /**
     * Canvas page for the app
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

    ##########################################
    if( trim($_REQUEST["switch_coin"]) != "" )
    ##########################################
    {
        $tipping_provider_keyname = get_tipping_provider_keyname_by_coin(trim($_REQUEST["switch_coin"]));
        if( ! empty($tipping_provider_keyname) )
        {
            $_SESSION[$config->session_vars_prefix."current_tipping_provider"] = $tipping_provider_keyname;
            $_SESSION[$config->session_vars_prefix."current_coin_name"]        = trim($_REQUEST["switch_coin"]);

            $config->current_tipping_provider_keyname = $_SESSION[$config->session_vars_prefix."current_tipping_provider"];
            $config->current_coin_name                = $_SESSION[$config->session_vars_prefix."current_coin_name"];
            $config->current_tipping_provider_data    = $config->tipping_providers_database[$config->current_tipping_provider_keyname];
            $config->current_coin_data                = $config->current_tipping_provider_data["per_coin_data"][$config->current_coin_name];
        } # end if
    } # end if

    include "facebook-php-sdk/facebook.php";
    $fb_params = array(
        'appId'              => $config->facebook_app_id,
        'secret'             => $config->facebook_app_secret,
        'fileUpload'         => false,
        'allowSignedRequest' => true
    );
    $facebook = new Facebook($fb_params);

    ###################################
    if( $_REQUEST["mode"] == "logout" )
    ###################################
    {
        $cookie_name = $config->session_vars_prefix . $config->cookie_session_identifier;
        setcookie($cookie_name, '', time() - 3600, "/", $config->cookie_domain);
        unset( $_COOKIE[$cookie_name] );
        $facebook->destroySession();
        include "$root_url/{$config->contents["welcome"]}";
        die();
    } # end if

    $user_id = get_online_user_id();
    if( empty($user_id) )
    {
        include $config->contents["welcome"];
        die();
    } # end if

    $account  = new account($user_id);
    $is_admin = isset($config->sysadmins[$account->id_account]);
    # header( "X-Admin: $user_id // $account->id_account ~ " . $config->sysadmins[$account->id_account] );
    if( $is_admin ) $admin_level = $config->sysadmins[$account->id_account];

    # [+] Admin impersonation helper
    ################################
    {
        if( $is_admin && ! empty($_REQUEST["view_profile_id"]) )
        {
            $impersonator = clone $account;
            $account = new account($_REQUEST["view_profile_id"]);
            if( ! $account->exists )
            {
                header("Content-Type: text/html; charset=utf-8");
                die("<html>
                        <head>
                            <meta name='viewport' content='width=device-width' />
                            <meta http-equiv='X-UA-Compatible' content='IE=Edge' />
                            <title>Account doesn't exist!</title>
                        </head>
                        <body>
                            <h1>Account doesn't exist!</h1>
                            <p>Please invoke this page with a valid account id.</p>
                        </body>
                     </html>");
            } # end if
            $admin_impersonization_in_effect = true;
            $is_admin = isset($config->sysadmins[$account->id_account]);
            if( $is_admin ) $admin_level = $config->sysadmins[$account->id_account];
            include "contents/index.user_home.inc";
            die();
        } # end if
    }
    ################################
    # [-] Admin impersonation helper

    try
    {
        if( ! $account->exists )
        {
            # Let's create an account for this user
            # $facebook = new Facebook($fb_params);
            $access_token = $facebook->getAccessToken();
            $facebook->setExtendedAccessToken();
            # $user_id = $facebook->getUser();
            $user_profile              = $facebook->api("/me");
            $account->facebook_id      = $user_id;
            $account->name             = $user_profile["name"];
            $account->timezone         = $user_profile["timezone"];
            $account->tipping_provider = $config->current_tipping_provider_keyname;
            $account->date_created     =
            $account->last_update      =
            $account->last_activity    = date("Y-m-d H:i:s");
            $account->make_id_account();

            if( $config->facebook_login_enforced )
            {
                $account->email = $user_profile["email"];
            }
            else
            {
                # Let's see if the email is already used somewhere else
                $query = "select * from ".$config->db_tables["account"]."
                          where email = '".addslashes($user_profile["email"])."'
                          or    alternate_email = '".addslashes($user_profile["email"])."'";
                $res   = mysql_query($query);
                if( mysql_num_rows($res) == 0 ) $account->email = $user_profile["email"];
                mysql_free_result($res);
            } # end if

            $account->wallet_address = $account->register();
            $account->save();
        }
        else
        {
            # Let's try to fetch the wallet in case it isn't shown
            if( empty($account->wallet_address) )
            {
                $account->wallet_address = $account->register();
                if( ! empty($account->wallet_address) )
                    $account->save();
            } # end if

            # Let's "ping" the account
            $account->ping();
            # Let's update the user profile if needed
            if( ! $session_from_cookie )
            {
                if( empty($account->facebook_user_access_token) || $account->facebook_user_access_token != $access_token ||
                    empty($account->email) || empty($account->timezone) || $account->last_update < date("Y-m-d H:i:s", strtotime("today - 7 days")) )
                {
                    try
                    {
                        # $facebook = new Facebook($fb_params);
                        $access_token = $facebook->getAccessToken();
                        $facebook->setExtendedAccessToken();
                        $user_id = $facebook->getUser();
                        $user_profile = $facebook->api("/me");
                    }
                    catch(Exception $e)
                    {
                        if( $config->facebook_login_enforced ) include "contents/index.bootstrap_error.inc";
                        else                                   include $config->contents["welcome"];
                        die();
                    } # end try...catch

                    $account->facebook_user_access_token = $access_token;
                    $account->name                       = $user_profile["name"];
                    $account->email                      = $user_profile["email"];
                    $account->timezone                   = $user_profile["timezone"];
                    $account->last_update                = date("Y-m-d H:i:s");

                    if( $config->facebook_login_enforced )
                    {
                        $account->email = $user_profile["email"];
                    }
                    else
                    {
                        # Let's see if the email is already used somewhere else
                        $query = "select * from ".$config->db_tables["account"]."
                                  where id_account <> '$account->id_account'
                                  and   ( email           = '".addslashes($user_profile["email"])."' or
                                          alternate_email = '".addslashes($user_profile["email"])."'
                                        )";
                        $res   = mysql_query($query);
                        if( mysql_num_rows($res) == 0 ) $account->email = $user_profile["email"];
                        mysql_free_result($res);
                    } # end if

                    $account->save();
                } # end if
            } # end if
        } # end if

        if( $is_admin && ! empty($_REQUEST["admin_mode"]) )
            include "contents/index.admin_tools.inc";
        else
            include "contents/index.user_home.inc";
    }
    catch(FacebookApiException $e)
    {
        if( $config->facebook_login_enforced ) include "contents/index.reauthorize.inc";
        else                                   include $config->contents["welcome"];
    } # end try... catch
