<?php
    /**
     * Platform Extension: Websites / Index
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
    include "$root_url/models/account_extensions.php";
    session_start();

    ####################
    # [+] Standard inits
    ####################
    {

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
            $title_append = $account->name . "'s websites &amp; buttons";
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
            $title_append = "Your websites &amp; buttons";
        } # end if
        if( ! $config->engine_enabled ) $jquery_ui_theme = $config->engine_disabled_ui_theme;

        if( ! is_resource($config->db_handler) ) db_connect();

    }
    ####################
    # [-] Standard inits
    ####################

    #############################################
    if( $_REQUEST["mode"] == "create_leech_jar" )
    #############################################
    {
        header("Content-Type: text/html; charset=utf-8");

        # Let's lookup for an existing public key
        $query = "select * from ".$config->db_tables["websites"]." where public_key = 'lj.$account->id_account'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) > 0 ) die("ERROR: You already have a Piggy Bank!");

        # Let's insert the record
        $secret_key = randomPassword(32);
        $query = "
            insert into ".$config->db_tables["websites"]." set
            id_account         = '".$account->id_account."',
            public_key         = 'lj.".$account->id_account."',
            secret_key         = '$secret_key',
            name               = '(My Piggy Bank)',
            category           = 'General',
            description        = 'Unattached container for buttons to be embedded on other websites.',
            icon_url           = '%leech_jar_url%',
            allow_leeching     = '1',
            creation_date      = '".date("Y-m-d H:i:s")."',
            state              = 'enabled'
        ";
        mysql_query($query);
        die("OK");
    } # end if

    ###########################################
    if( $_REQUEST["mode"] == "insert_website" )
    ###########################################
    {
        header("Content-Type: text/html; charset=utf-8");

        if( trim($_POST["name"])        == "" ) die("ERROR: Please specify your website's name");
        if( trim($_POST["public_key"])  == "" ) die("ERROR: Please specify a shorthand for your website");
        if( trim($_POST["category"])    == "" ) die("ERROR: Please select a category for your website");
        if( trim($_POST["description"]) == "" ) die("ERROR: Please specify a short description for your website");
        # if( trim($_POST["valid_urls"])  == "" ) die("ERROR: Please specify at least the URL of your website");

        if( trim($_POST["icon"]) != "" && trim($_POST["icon"]) != "%leech_jar_url%" )
            if( ! filter_var(trim($_POST["icon"]), FILTER_VALIDATE_URL) )
                die("ERROR: Please specify a valid URL for the icon of your website.");

        # Let's lookup for an existing public key
        $query = "select * from ".$config->db_tables["websites"]." where public_key = '".trim($_POST["public_key"])."'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) > 0 ) die("ERROR: that shorthand is already taken. Please specify another to be used as your public key.");

        if( strtolower(substr($_POST["main_url"], 0, 4)) != "http" ) $_POST["main_url"] = "http://" . $_POST["main_url"];

        # Let's insert the record
        $secret_key = randomPassword(32);
        $query = "
            insert into ".$config->db_tables["websites"]." set
            id_account         = '".$account->id_account."',
            public_key         = '".trim($_POST["public_key"])."',
            secret_key         = '$secret_key',
            name               = '".trim($_POST["name"])."',
            category           = '".trim($_POST["category"])."',
            description        = '".trim(addslashes(strip_tags(stripslashes($_POST["description"]), "<a><b><i><u>")))."',
            main_url           = '".trim($_POST["main_url"])."',
            icon_url           = '".trim($_POST["icon_url"])."',
            valid_urls         = '".trim($_POST["valid_urls"])."',
            allow_leeching     = '".trim($_POST["allow_leeching"])."',
            leech_button_type  = '".trim($_POST["leech_button_type"])."',
            leech_color_scheme = '".trim($_POST["leech_color_scheme"])."',
            banned_websites    = '".trim($_POST["banned_websites"])."',
            creation_date      = '".date("Y-m-d H:i:s")."',
            state              = 'enabled'
        ";
        mysql_query($query);

        @mail(
            $config->mail_recipient_for_alerts,
            $config->app_display_shortname . " - New website registered: " . stripslashes($_POST["name"]),
            "A new website has been registered at $config->app_display_shortname. Details ahead:\n" .
            "id_account         = '".$account->id_account."' ~ $account->name $account->email $account->alternate_email\n".
            "public_key         = '".stripslashes($_POST["public_key"])."'\n".
            "name               = '".stripslashes($_POST["name"])."'\n".
            "category           = '".stripslashes($_POST["category"])."'\n".
            "description        = '".stripslashes(addslashes(strip_tags(stripslashes($_POST["description"]), "<a><b><i><u>")))."'\n".
            "main_url           = '".stripslashes($_POST["main_url"])."'\n".
            "icon_url           = '".stripslashes($_POST["icon_url"])."'\n".
            "valid_urls         = '".stripslashes($_POST["valid_urls"])."'\n".
            "allow_leeching     = '".stripslashes($_POST["allow_leeching"])."'\n".
            "leech_button_type  = '".stripslashes($_POST["leech_button_type"])."'\n".
            "leech_color_scheme = '".stripslashes($_POST["leech_color_scheme"])."'\n",
            "From: " . $config->mail_sender_address
        );

        die("OK");
    } # end if

    #############################################
    if( $_REQUEST["mode"] == "get_website_data" )
    #############################################
    {
        header("Content-Type: application/json; charset=utf-8");

        if( trim($_REQUEST["public_key"]) == "" ) die( json_encode(array("message" => "ERROR: Public key not provided.")) );

        $query = "select * from ".$config->db_tables["websites"]." where public_key = '".trim($_REQUEST["public_key"])."'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 ) die( json_encode(array("message" => "ERROR: There is no website matching the provided public key.")) );
        $row  = mysql_fetch_object($res);
        if( $row->id_account != $account->id_account ) die( json_encode(array("message" => "ERROR: The provided website doesn't belong to you.")) );

        die( json_encode(array("message" => "OK", "data" => $row)) );
    } # end if

    #########################################
    if( $_REQUEST["mode"] == "save_website" )
    #########################################
    {
        header("Content-Type: text/html; charset=utf-8");

        if( trim($_REQUEST["public_key"]) == "" ) die("ERROR: Public key not provided.");
        $query = "select * from ".$config->db_tables["websites"]." where public_key = '".trim($_REQUEST["public_key"])."'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 ) die("ERROR: There is no website matching the provided public key.");
        $row  = mysql_fetch_object($res);
        if( $row->id_account != $account->id_account ) die("ERROR: The provided website doesn't belong to you.");
        if( $row->state == 'locked' ) die("ERROR: The website has an admin lock. It can't be edited.");

        if( trim($_POST["name"])        == "" ) die("ERROR: Please specify the website's name");
        if( trim($_POST["category"])    == "" ) die("ERROR: Please select a category");
        if( trim($_POST["description"]) == "" ) die("ERROR: Please specify a short description for your website");
        # if( trim($_POST["valid_urls"])  == "" ) die("ERROR: Please specify at least the URL of your website");

        if( trim($_POST["icon"]) != "" )
            if( ! filter_var(trim($_POST["icon"]), FILTER_VALIDATE_URL) )
                die("ERROR: Please specify a valid URL for the icon of your website.");

        if( strtolower(substr($_POST["main_url"], 0, 4)) != "http" ) $_POST["main_url"] = "http://" . $_POST["main_url"];

        # Let's save the record
        $secret_key = randomPassword(32);
        $query = "
            update ".$config->db_tables["websites"]." set
                name               = '".trim($_POST["name"])."',
                category           = '".trim($_POST["category"])."',
                description        = '".trim($_POST["description"])."',
                main_url           = '".trim($_POST["main_url"])."',
                icon_url           = '".trim($_POST["icon_url"])."',
                valid_urls         = '".trim($_POST["valid_urls"])."',
                allow_leeching     = '".trim($_POST["allow_leeching"])."',
                leech_button_type  = '".trim($_POST["leech_button_type"])."',
                leech_color_scheme = '".trim($_POST["leech_color_scheme"])."',
                banned_websites    = '".trim($_POST["banned_websites"])."'
            where
                public_key      = '".trim($_POST["public_key"])."'
        ";
        mysql_query($query);
        die("OK");
    } # end if

    ##############################################
    if( $_REQUEST["mode"] == "set_website_state" )
    ##############################################
    {
        header("Content-Type: text/html; charset=utf-8");

        if( trim($_REQUEST["public_key"]) == "" ) die("ERROR: Public key not provided.");
        $query = "select * from ".$config->db_tables["websites"]." where public_key = '".trim($_REQUEST["public_key"])."'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 ) die("ERROR: There is no website matching the provided public key.");
        $row  = mysql_fetch_object($res);
        if( $row->id_account != $account->id_account ) die("ERROR: The provided website doesn't belong to you.");
        if( $row->state == 'locked' ) die("ERROR: The website has an admin lock. It can't be edited.");

        if( ! in_array($_REQUEST["state"], array("enabled", "disabled", "deleted")) )
            die( "ERROR: You've provided an invalid state for the website." );

        if( $row->state == $_REQUEST["state"] )
            die( "ERROR: The website already has the '$row->state' flag. Nothing has been changed." );

        # Let's save the record
        $query = "
            update ".$config->db_tables["websites"]." set
                state           = '".trim($_REQUEST["state"])."'
            where
                public_key      = '".trim($_REQUEST["public_key"])."'
        ";
        mysql_query($query);
        die("OK");
    } # end if

    ##########################################
    if( $_REQUEST["mode"] == "insert_button" )
    ##########################################
    {
        header("Content-Type: text/html; charset=utf-8");

        # Cleanup
        unset( $_POST["dummy"] );

        # Mappings
        if( $_POST["properties"]["default_coin"] == "_none_" && $_POST["properties"]["coin_scheme"] == "multi_direct" )
        {
            $_POST["properties"]["per_coin_requests"] = $_POST["properties"]["per_coin_requests"]["direct"];
            unset( $_POST["properties"]["per_coin_requests"]["direct"] );
        }
        elseif( $_POST["properties"]["default_coin"] == "_none_" && $_POST["properties"]["coin_scheme"] == "multi_converted" )
        {
            $_POST["properties"]["per_coin_requests"] = $_POST["properties"]["per_coin_requests"]["from_usd"];
            unset( $_POST["properties"]["per_coin_requests"]["from_usd"] );
        } # end if
        if( ! empty($_POST["properties"]["per_coin_requests"]) )
            foreach($_POST["properties"]["per_coin_requests"] as $coin_name => $data)
                if( empty($data["show"]) ) unset($_POST["properties"]["per_coin_requests"][$coin_name]);

        # Validations
        if( trim($_REQUEST["website_public_key"]) == "" ) die("ERROR: Public key not provided.");
        $query = "select * from ".$config->db_tables["websites"]." where public_key = '".trim($_REQUEST["website_public_key"])."'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 ) die("ERROR: There is no website matching the provided public key.");
        $row  = mysql_fetch_object($res);
        if( $row->id_account != $account->id_account ) die("ERROR: The provided website doesn't belong to you.");
        if( $row->state == 'locked' ) die("ERROR: The website has an admin lock. It can't be edited.");

        if( trim($_POST["properties"]["caption"]) == "" ) die("ERROR: Please provide a caption for the button.");

        if( $_POST["properties"]["coin_scheme"] == "multi_converted" && trim($_POST["properties"]["amount_in_usd"]) == "" )
            die("ERROR: Please provide an amount in USD to convert to coins for the button.");

        if( $_POST["properties"]["coin_scheme"] != "single_from_default_coin"
            && empty($_POST["properties"]["per_coin_requests"]) )
            die("ERROR: Please select at least one coin to receive.");

        # Premium only
        $account_extensions = new account_extensions($account->id_account);
        if( ! in_array($account_extensions->account_class, array("vip", "premium")) )
        {
            unset( $_POST["properties"]["allow_target_overrides"] );
            unset( $_POST["properties"]["custom_logo"] );
            unset( $_POST["properties"]["callback"] );
            unset( $_POST["properties"]["description"] );
        } # end if

        if( ! empty($_POST["properties"]["description"]) )
            $_POST["properties"]["description"] = str_replace("\n", "<br>", htmlspecialchars(substr(trim(strip_tags(stripslashes($_POST["properties"]["description"]), "<a><b><i><u>")), 0, 500)));

        $query = "
            insert into ".$config->db_tables["website_buttons"]." set
            button_name        = '".trim($_POST["button_name"])."',
            button_id          = '".uniqid(true)."',
            website_public_key = '".trim($_POST["website_public_key"])."',
            type               = '".trim($_POST["type"])."',
            color_scheme       = '".trim($_POST["color_scheme"])."',
            properties         = '".addslashes(json_encode($_POST["properties"]))."',
            creation_date      = '".date("Y-m-d H:i:s")."',
            state              = 'enabled'
        ";
        mysql_query($query);
        die("OK");
    } # end if

    ############################################
    if( $_REQUEST["mode"] == "get_button_data" )
    ############################################
    {
        header("Content-Type: application/json; charset=utf-8");

        if( trim($_REQUEST["website_public_key"]) == "" ) die( json_encode(array("message" => "ERROR: Website public key not provided.")) );
        if( trim($_REQUEST["button_id"])          == "" ) die( json_encode(array("message" => "ERROR: Button id not provided.")) );

        # Website validation
        $query = "select * from ".$config->db_tables["websites"]." where public_key = '".trim($_REQUEST["website_public_key"])."'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 ) die("ERROR: There is no website matching the provided public key.");
        $row  = mysql_fetch_object($res);
        if( $row->id_account != $account->id_account ) die("ERROR: The provided website doesn't belong to you.");
        if( $row->state == 'locked' ) die("ERROR: The website has an admin lock. It can't be edited.");

        # Button validation
        $query = "select * from ".$config->db_tables["website_buttons"]."
                  where website_public_key = '".trim($_REQUEST["website_public_key"])."'
                  and   button_id          = '".trim($_REQUEST["button_id"])."'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 ) die( json_encode(array("message" => "ERROR: There is no button matching the provided id for ".trim($_REQUEST["website_public_key"]).".")) );
        $row  = mysql_fetch_object($res);
        if( $row->state == 'deleted' ) die( json_encode(array("message" => "ERROR: The button has been deleted from the database.")) );

        $row->properties = json_decode($row->properties);
        $row->properties->caption     = html_entity_decode(stripslashes($row->properties->caption));
        $row->properties->description = html_entity_decode(str_replace("<br>", "\n", $row->properties->description));
        die( json_encode(array("message" => "OK", "data" => $row)) );
    } # end if

    ########################################
    if( $_REQUEST["mode"] == "save_button" )
    ########################################
    {
        header("Content-Type: text/html; charset=utf-8");

        # Cleanup
        unset( $_POST["dummy"] );

        # Mappings
        if( $_POST["properties"]["default_coin"] == "_none_" && $_POST["properties"]["coin_scheme"] == "multi_direct" )
        {
            $_POST["properties"]["per_coin_requests"] = $_POST["properties"]["per_coin_requests"]["direct"];
            unset( $_POST["properties"]["per_coin_requests"]["direct"] );
        }
        elseif( $_POST["properties"]["default_coin"] == "_none_" && $_POST["properties"]["coin_scheme"] == "multi_converted" )
        {
            $_POST["properties"]["per_coin_requests"] = $_POST["properties"]["per_coin_requests"]["from_usd"];
            unset( $_POST["properties"]["per_coin_requests"]["from_usd"] );
        } # end if
        if( ! empty($_POST["properties"]["per_coin_requests"]) )
            foreach($_POST["properties"]["per_coin_requests"] as $coin_name => $data)
                if( empty($data["show"]) ) unset($_POST["properties"]["per_coin_requests"][$coin_name]);

        # Validations
        if( trim($_REQUEST["website_public_key"]) == "" ) die("ERROR: Public key not provided.");
        $query = "select * from ".$config->db_tables["websites"]." where public_key = '".trim($_REQUEST["website_public_key"])."'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 ) die("ERROR: There is no website matching the provided public key.");
        $row  = mysql_fetch_object($res);
        if( $row->id_account != $account->id_account ) die("ERROR: The provided website doesn't belong to you.");
        if( $row->state == 'locked' ) die("ERROR: The website has an admin lock. You can't update any button.");

        $query = "select * from ".$config->db_tables["website_buttons"]."
                  where website_public_key = '".trim($_REQUEST["website_public_key"])."'
                  and   button_id          = '".trim($_REQUEST["button_id"])."'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 ) die( "ERROR: There is no button matching the provided id for ".trim($_REQUEST["website_public_key"])."." );
        $row  = mysql_fetch_object($res);
        if( $row->state == 'deleted' ) die( "ERROR: The button has been deleted from the database." );

        if( trim($_POST["properties"]["caption"]) == "" ) die("ERROR: Please provide a caption for the button.");

        if( $_POST["properties"]["coin_scheme"] == "multi_converted" && trim($_POST["properties"]["amount_in_usd"]) == "" )
            die("ERROR: Please provide an amount in USD to convert to coins for the button.");

        if( $_POST["properties"]["coin_scheme"] != "single_from_default_coin"
            && empty($_POST["properties"]["per_coin_requests"]) )
            die("ERROR: Please select at least one coin to receive.");

        # Premium only
        $account_extensions = new account_extensions($account->id_account);
        if( ! in_array($account_extensions->account_class, array("vip", "premium")) )
        {
            unset( $_POST["properties"]["allow_target_overrides"] );
            unset( $_POST["properties"]["custom_logo"] );
            unset( $_POST["properties"]["callback"] );
            unset( $_POST["properties"]["description"] );
        } # end if

        if( ! empty($_POST["properties"]["description"]) )
            $_POST["properties"]["description"] = str_replace("\n", "<br>", htmlspecialchars(substr(trim(strip_tags(stripslashes($_POST["properties"]["description"]), "<a><b><i><u>")), 0, 500)));

        $query = "
            update ".$config->db_tables["website_buttons"]." set
                button_name        = '".trim($_POST["button_name"])."',
                type               = '".trim($_POST["type"])."',
                color_scheme       = '".trim($_POST["color_scheme"])."',
                properties         = '".addslashes(json_encode($_POST["properties"]))."'
            where
                website_public_key = '".trim($_POST["website_public_key"])."' and
                button_id          = '".trim($_POST["button_id"])."'
        ";
        mysql_query($query);
        die("OK");
    } # end if

    #############################################
    if( $_REQUEST["mode"] == "set_button_state" )
    #############################################
    {
        header("Content-Type: text/html; charset=utf-8");

        # Validations
        if( trim($_REQUEST["website_public_key"]) == "" ) die("ERROR: Public key not provided.");
        $query = "select * from ".$config->db_tables["websites"]." where public_key = '".trim($_REQUEST["website_public_key"])."'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 ) die("ERROR: There is no website matching the provided public key.");
        $row  = mysql_fetch_object($res);
        if( $row->id_account != $account->id_account ) die("ERROR: The provided website doesn't belong to you.");
        if( $row->state == 'locked' ) die("ERROR: The website has an admin lock. You can't update any button.");

        $query = "select * from ".$config->db_tables["website_buttons"]."
                  where website_public_key = '".trim($_REQUEST["website_public_key"])."'
                  and   button_id          = '".trim($_REQUEST["button_id"])."'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 ) die( "ERROR: There is no button matching the provided id for ".trim($_REQUEST["website_public_key"])."." );
        $row  = mysql_fetch_object($res);
        if( $row->state == 'deleted' ) die( "ERROR: The button has been deleted from the database." );

        if( ! in_array($_REQUEST["state"], array("enabled", "disabled", "deleted")) )
            die( "ERROR: You've provided an invalid state for the button." );

        if( $row->state == $_REQUEST["state"] )
            die( "ERROR: The button already has the '$row->state' flag. Nothing has been changed." );

        $query = "
            update ".$config->db_tables["website_buttons"]." set
                state              = '".$_REQUEST["state"]."'
            where
                website_public_key = '".trim($_REQUEST["website_public_key"])."' and
                button_id          = '".trim($_REQUEST["button_id"])."'
        ";
        mysql_query($query);
        die("OK");
    } # end if

    #############################################
    if( $_REQUEST["mode"] == "test_callback_url")
    #############################################
    {
        if( empty($_GET["url"]) ) die("Please provide a valid URL of an existing script on your server to send test data to.");
        if( filter_var($_GET["url"], FILTER_VALIDATE_URL) === false ) die("The URL you provided doesn't seem valid. Please check it and try again.");

        $post_data = array(
            "order_id"                  => "12345678901234",
            "timestamp"                 => time(),
            "date"                      => date("Y-m-d H:i:s") . " GMT-0500",
            "full_order_handler"        => "website_public_key/button_id:order_id",
            "invoker_website_key"       => "invoker_website_public_key",
            "button_id"                 => "button_id",
            "button_owner_website_key"  => "button_owner_website_key",
            "entry_id"                  => "entry_id",
            "entry_title"               => "entry_title",
            "ref_code"                  => "ref_code",
            "sender_data"               => array(
                                               "account_id" => "sender_id",
                                               "name"       => "sender_name",
                                               "email"      => "sender_email"
                                            ),
            "recipient_data"            => array(
                                               "account_id" => "recipient_id",
                                               "name"       => "recipient_name",
                                               "email"      => "recipient_email"
                                           ),
            "request_type"              => "request_type",
            "coin_scheme"               => "coin_scheme",
            "per_coin_transaction_data" => array(
                                               array(
                                                   "opslog_id"         => "opslog_id",
                                                   "coin_name"         => "coin_name",
                                                   "usd_rate"          => "usd_rate",
                                                   "gross_coin_amount" => "gross_coin_amount",
                                                   "deducted_fees"     => array(
                                                                              array(
                                                                                  "opslog_id"         => "opslog_id",
                                                                                  "target_account_id" => "target_account_id",
                                                                                  "amount_paid"       => "amount_paid",
                                                                                  "usd_value"         => "usd_value",
                                                                              )
                                                                          ),
                                                   "net_coin_amount"   => "net_coin_amount",
                                                   "gross_usd_value"   => "gross_usd_value",
                                                   "net_usd_value"     => "net_usd_value",
                                               ),
                                           ),
            "total_usd_value"           => "total_usd_value"
        );

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL,            $_GET["url"]                  );
        curl_setopt( $ch, CURLOPT_POST,           1                             );
        curl_setopt( $ch, CURLOPT_POSTFIELDS,     http_build_query($post_data)  );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true                          );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5                             );
        curl_setopt( $ch, CURLOPT_TIMEOUT,        10                            );
        $res = curl_exec($ch);
        if( $res !== false ) $redirect_to = $res;

        if( curl_error($ch) ) die( "There has been a problem connecting to the specified url:\n\n"
                              .    curl_error($ch) . "\n\n"
                              .    "Please try again.\n\n"
                              .    "If the problem persists, please post a support request on our Help & support forum." );
        die( "Data posted successfully." . ($redirect_to ? "\nResponse received: $redirect_to" : "") );
    } # end if

    header("Content-Type: text/html; charset=utf-8");
?>
<html xmlns:fb="http://ogp.me/ns/fb#">
    <head>
        <title><?=$config->app_display_longname?> - <?=$title_append?></title>
        <meta name="viewport"                       content="width=device-width" />
        <meta http-equiv="X-UA-Compatible"          content="IE=Edge" />
        <link rel="icon"                            href="<?= $config->favicon ?>">
        <link rel="shortcut icon"                   href="<?= $config->favicon ?>">
        <meta property="og:title"                   content="<?=$config->app_display_longname?>" />
        <meta property="og:image"                   content="<?=$config->facebook_canvas_image?>" />

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
        <script                                     src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
        <link rel="stylesheet" type="text/css"      href="//code.jquery.com/ui/1.10.4/themes/<?=$jquery_ui_theme?>/jquery-ui.css">
        <script                                     src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
        <link rel="stylesheet" type="text/css"      href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
        <script type="text/javascript"              src="<?=$config->commons_url?>/lib/jquery.blockUI.js"></script>
        <link rel="stylesheet" type="text/css"      href="<?=$root_url?>/misc/styles.css?v=<?=$config->scripts_and_styles_version?>">
        <style type="text/css">
            <?= $config->user_home_body_font_definition ?>
            <?= $config->user_home_ui_font_definition ?>
            .coin_signed:after { content: ' <?=$config->current_coin_data["coin_name_plural"]?>'; }
        </style>
        <script type="text/javascript"              src="<?=$root_url?>/misc/functions.js?v=<?=$config->scripts_and_styles_version?>"></script>
        <script type="text/javascript"              src="<?=$config->commons_url?>/lib/jquery.timeago.js"></script>
        <script type="text/javascript"              src="<?=$config->commons_url?>/lib/jquery.form.min.js"></script>
        <script type="text/javascript"              src="<?=$config->commons_url?>/lib/jquery.scrollTo-min.js"></script>
        <link rel="stylesheet" type="text/css"      href="<?=$config->commons_url?>/lib/jquery-lightbox/jquery.lightbox.css">
        <script type="text/javascript"              src="<?=$config->commons_url?>/lib/jquery-lightbox/jquery.lightbox.js"></script>
        <script type="text/javascript">
            $(function()
            {
                // UI stuff
                $('a.buttonized, button').button();
                $('.lightbox').lightbox({
                    fileLoadingImage: root_url + 'lib/jquery-lightbox/loading.gif',
                    fileBottomNavCloseImage: root_url + 'lib/jquery-lightbox/closelabel.gif',
                });
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
        <script type="text/javascript"              src="<?=$config->commons_url?>/lib/jquery.exptextarea.js"></script>
        <script type="text/javascript">$(document).ready(function() { $('.expandible_textarea').expandingTextArea(); });</script>

        <!-- TableSorter -->
        <script type="text/javascript"              src="<?=$config->commons_url?>/lib/jquery-tablesorter/jquery.tablesorter.min.js"></script>
        <script type="text/javascript"              src="<?=$config->commons_url?>/lib/jquery-tablesorter/jquery.metadata.js"></script>
        <link rel="stylesheet" type="text/css"      href="<?=$config->commons_url?>/lib/jquery-tablesorter/themes/blue/style.css">

        <style type="text/css">
            .dialog_section { -webkit-box-shadow: 4px 4px 5px 0px rgba(50, 50, 50, 0.75);
                              -moz-box-shadow:    4px 4px 5px 0px rgba(50, 50, 50, 0.75);
                              box-shadow:         4px 4px 5px 0px rgba(50, 50, 50, 0.75); }
        </style>

        <!-- Other -->
        <script type="text/javascript" src="<?=$config->commons_url?>/lib/jquery.cookie.js"></script>

        <!-- Websites related -->
        <link rel="stylesheet" type="text/css"      href="websites.css?v=<?=$config->scripts_and_styles_version?>">
        <script type="text/javascript"              src="websites.js?v=<?=$config->scripts_and_styles_version?>"></script>

        <!-- Buttons related -->
        <link rel="stylesheet" type="text/css"      href="buttons.css?v=<?=$config->scripts_and_styles_version?>">
        <script type="text/javascript"              src="buttons.js?v=<?=$config->scripts_and_styles_version?>"></script>

        <!-- Button Widget per-se -->
        <script type="text/javascript"              src="widgets/universal/button.php?website_public_key=<?=$config->default_buttons_website?>"></script>
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
        <h3 style="margin-top: 0; font-style: italic;">Your websites &amp; buttons</h3>

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

        <h1 class="ui-widget-header message_box ui-corner-all">
            Your websites and buttons
            <span id="websites_main_buttons">
                <? if( ! $admin_impersonization_in_effect ): ?>
                    <button id="register_new_website_button" onclick="create_website()">
                        <span class="fa fa-plus"></span>
                        Register new website
                    </button>
                <? endif; ?>
                <?
                    $query = "select * from ".$config->db_tables["websites"]." where public_key = 'lj.$account->id_account'";
                    $res   = mysql_query($query);
                    if( mysql_num_rows($res) == 0 )
                    {
                        ?>
                        <button id="create_leech_jar" onclick="create_leech_jar()">
                            <img src="leech_jar.png" width="16" height="16" style="position: relative; top: 2px;">
                            Create a Piggy Bank
                        </button>
                        <?
                    } # end if
                ?>
                <button id="refresh_websites_button"  onclick="refresh_websites_list()">
                    <span class="fa fa-refresh"></span>
                    Refresh websites list
                </button>
                <a class="buttonized" href="<?=$root_url?>/manual/" target="_blank">
                    <span class="fa fa-book"></span>
                    Manual &amp; reference
                </a>
            </span>
        </h1>

        <!-- ##################### -->
        <!-- Current websites list -->
        <!-- ##################### -->

        <? include "websites_list.inc"; ?>

        <!-- ############################### -->
        <!-- Website addition/edition dialog -->
        <!-- ############################### -->

        <? include "websites_dialog.inc"; ?>

        <!-- ############################## -->
        <!-- Button addition/edition dialog -->
        <!-- ############################## -->

        <? include "buttons_dialog.inc"; ?>

    </body>
</html>
