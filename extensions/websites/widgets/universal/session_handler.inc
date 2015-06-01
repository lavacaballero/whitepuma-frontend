<?php
    /**
     * Platform Extension: Websites / Session handler
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

    if( empty($widget_files_rel_path) ) $widget_files_rel_path = "./";

    ###################################
    if( $_REQUEST["mode"] == "logout" )
    ###################################
    {
        $cookie_name = $config->session_vars_prefix . $config->cookie_session_identifier;
        setcookie($cookie_name, '', time() - 3600, "/", $config->cookie_domain);
        unset( $_COOKIE[$cookie_name] );
        # $facebook->destroySession();
        parse_str($_SERVER["QUERY_STRING"], $params);
        unset($params["mode"]);
        $params["wasuuup"] = md5(mt_rand(1,65535));
        header("Location: " . $_SERVER["PHP_SELF"]."?" . http_build_query($params));
        die("<html><body><a href='".$_SERVER["REQUEST_URI"]."'>Click here to continue</a></html>");
    } # end if

    ##############################################
    if( $_REQUEST["mode"] == "do_facebook_login" )
    ##############################################
    {
        if( empty($_GET["fbat"]) )
            throw_error( "You're trying to connect with Facebook but we couldn't receive an access token.
                          Please close this widget and open it again. If the problem persists,
                          please try opening your session from the <a href='".$config->website_pages["root_url"]."' target='_blank'>UniDash</a>.
                          If you need assistance, please post a support request at <a href='".$config->website_pages["support"]."' target='_blank'>Help &amp; Support Forum</a>" );
        include "$root_url/facebook-php-sdk/facebook.php";
        $fb_params = array(
            'appId'              => $config->facebook_app_id,
            'secret'             => $config->facebook_app_secret,
            'fileUpload'         => false,
            'allowSignedRequest' => true
        );
        $facebook = new Facebook($fb_params);
        $facebook->setAccessToken($_GET["fbat"]);

        try
        {
            $user_id              = $facebook->getUser();
            $cookie_name         = $config->session_vars_prefix . $config->cookie_session_identifier;
            $cookie_value        = encryptRJ256($config->tokens_encryption_key, $user_id);
            $session_from_cookie = true;
            setcookie($cookie_name, $cookie_value, strtotime("now + 30 days"), "/", $config->cookie_domain);
            $_COOKIE[$cookie_name] = $cookie_value;
        }
        catch(Exception $e)
        {
            throw_error( "Can't get profile information from Facebook!<br><br>
                          Reason:<br>" .$e->getMessage()."<br><br>
                          Please try reloading this widget.
                          If the problem persists, please post a support request at <a href='".$config->website_pages["support"]."' target='_blank'>Help &amp; Support Forum</a>" );
        } // end try...catch
    } # end if

    $cookie_name         = $config->session_vars_prefix . $config->cookie_session_identifier;
    $session_from_cookie = false;
    if( ! empty($_COOKIE[$cookie_name]) )
    {
        $user_id = decryptRJ256($config->tokens_encryption_key, $_COOKIE[$cookie_name]);
        if( ! empty($user_id) )
        {
            $session_from_cookie = true;
        } # end if
    } # end if
    if( empty($user_id) )
    {
        if( $throw_empty_account_on_anonymous )
        {
            $account  = new account();
            $is_admin = false;
            return;
        } # end if

        $contents_segment = $widget_files_rel_path."index.welcome.inc";
        include $widget_files_rel_path."index.contents.inc";
        die();
    } # end if

    $account  = new account($user_id);
    $is_admin = isset($config->sysadmins[$account->id_account]);
    if( ! $account->exists ) throw_error("ERROR: Your account was not found in our database.");
?>
