<?php
    /**
     * Platform Extension: Websites / iframed widget / inits
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
     * @param encrypted string token              public key of the website, encrypted,
     * @param           string website_public_key Per-se
     * @param           string button_id          Per-se
     * @param           string ref                Optional. Referral code
     * @param           string entry_id           Optional. For recording in the OpsLog
     * @param           string entry_title        Optional. For recording in the OpsLog
     * @param           string target_data        Optional. email or data:email
     */

    # [+] Params validation
    {
        # if( empty($_REQUEST["website_public_key"]) ) throw_error("ERROR: Website public key not specified.");
        # if( empty($_REQUEST["button_id"])          ) throw_error("ERROR: Button id not specified.");
        if( empty($_REQUEST["website_public_key"]) || empty($_REQUEST["button_id"]) )
        {
            $contents_segment = "index.welcome.inc";
            include "index.contents.inc";
            die();
        } # end if
    }
    # [-] Params validation

    # [+] Token forging
    {
        # If we're not receiving a token, we'll generate one
        if( empty($_REQUEST["token"]) )
        {
            $website_for_token = $config->default_buttons_website;
            $referer_for_token = $config->default_buttons_referer_url;

            $query = "select * from ".$config->db_tables["websites"]." where public_key = '".$_REQUEST["website_public_key"]."'";
            $res   = mysql_query($query);
            if( mysql_num_rows($res) > 0 )
            {
                $res = mysql_query($query);
                $row = mysql_fetch_object($res);
                if( ! empty($row->main_url) )
                {
                    $website_for_token       = $row->public_key;
                    $referer_for_token       = $row->main_url;
                    $_SERVER["HTTP_REFERER"] = $row->main_url;
                } # end if
            } # end if
            mysql_free_result($res);

            $token = encryptRJ256($config->tokens_encryption_key, $referer_for_token."\t".$website_for_token."\t".uniqid(true));
            @touch("/tmp/_tk_".md5($token));
            $_REQUEST["token"]       = base64_encode($token);
            if( empty($_REQUEST["entry_id"])    ) $_REQUEST["entry_id"]    = $config->default_buttons_referer_url;
            if( empty($_REQUEST["entry_title"]) ) $_REQUEST["entry_title"] = $config->buttons_buttonizer_direct_caption;
        } # end if
        $token = base64_decode($_REQUEST["token"]);
        # header("X-WP-GBD-Token-Stage2: " . $_REQUEST["token"]);
    }
    # [-] Token forging

    # [+] Token validation
    {
        if( ! is_file("/tmp/_tk_".md5($token)) ) throw_error("ERROR: Token not found. It may have expired. Please reload the parent page to generate a new token.");
        $res = decryptRJ256($config->tokens_encryption_key, $token);
        if( stristr($res, "\t") === false ) throw_error("ERROR: Invalid token.");
        list($invoker_referer_url, $invoker_website_public_key, $random_seed) = explode("\t", $res);
    }
    # [-] Token validation

    # [+] Invoker website leeching check
    {
        $button_overrides = (object) array();
        $invoker_website = (object) array();
        # header("X-WP-GBD-IWP: [" . $invoker_website_public_key . "]");
        # header("X-WP-GBD-RWP: [" . $_REQUEST["website_public_key"] . "]");
        if( $invoker_website_public_key != $_REQUEST["website_public_key"] )
        {
            $query = "select * from ".$config->db_tables["websites"]." where public_key = '$invoker_website_public_key'";
            $res   = mysql_query($query);
            if( mysql_num_rows($res) == 0 ) throw_error("ERROR: Button invoked from an unregistered website.");
            $invoker_website = mysql_fetch_object($res);
            if( $invoker_website->state == 'disabled' ) throw_error("ERROR: Button invoked from a disabled website.");
            if( $invoker_website->state == 'locked'   ) throw_error("ERROR: Button invoked from a locked website.");
            mysql_free_result( $res );
            if( empty($invoker_website->allow_leeching) ) throw_error("ERROR: this website doesn't allow external buttons.");
            if( stristr($invoker_website->banned_websites, $_REQUEST["website_public_key"]) !== false)
                throw_error("ERROR: this website has banned this external button source.");
            # If we made it here, we'll set some overrides for later usage.
            if( ! empty($invoker_website->allow_leeching) )
            {
                if( ! empty($invoker_website->leech_button_type)  ) $button_overrides->type         = $invoker_website->leech_button_type;
                if( ! empty($invoker_website->leech_color_scheme) ) $button_overrides->color_scheme = $invoker_website->leech_color_scheme;
            } # end if
        } # end if
    }
    # [-] Invoker website leeching check

    # [+] Website validation
    {
        $query = "select * from ".$config->db_tables["websites"]." where public_key = '".addslashes($_REQUEST["website_public_key"])."'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 ) throw_error("ERROR: Website not found in our database.");
        $website = mysql_fetch_object($res);
        if( $website->state == 'disabled' ) throw_error("ERROR: Website is disabled.");
        if( $website->state == 'locked'   ) throw_error("ERROR: Website is locked.");
        mysql_free_result( $res );
    }
    # [-] Website validation

    # [+] Button Validation
    {
        $query = "select * from ".$config->db_tables["website_buttons"]."
                  where website_public_key = '$website->public_key'
                  and   button_id = '".addslashes($_REQUEST["button_id"])."'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 ) throw_error("ERROR: Button not found on this website.");
        $button = mysql_fetch_object($res);
        if( $button->state == 'disabled' ) throw_error("ERROR: Button is disabled.");
        mysql_free_result( $res );
    }
    # [-] Button Validation

    # [+] Valid URL checking
    {
        $referer = $_SERVER["HTTP_REFERER"];
        # header("X-WP-GBD-SR-R: $referer");
        # header("X-WP-GBD-SR-VU: " . str_replace("\n", " ", $website->valid_urls));
        if( empty($invoker_website->allow_leeching) && ! empty($website->valid_urls) )
        {
            # Website has URL limitations...
            $valid_urls = explode("\n", $website->valid_urls);
            $matched    = false;
            foreach($valid_urls as $this_url)
            {
                $this_url = trim($this_url);
                $this_url = str_replace("www.", "", $this_url); # Just in case...
                if( stristr($referer, $this_url) !== false )
                {
                    $matched = true;
                    break;
                } # end if
            } # end foreach
            if( ! $matched ) throw_error("ERROR: Button embedded on a non-owned website.");
        } # end if
        # Note: at this point, the button either doesn't have any valid_url listed,
        # thus, it can be embedded anywhere by anyone.
    }
    # [-] Valid URL checking

    # Session checking
    include "session_handler.inc";

    ##################################
    # All checks OK Up to this point #
    ##################################

    $button->properties = json_decode($button->properties);
    if( ! empty($button_overrides->type)         ) $button->type = $button_overrides->type;
    if( ! empty($button_overrides->color_scheme) ) $button->color_scheme = $button_overrides->color_scheme;

    # [+] Entry id selection
    {
        if( empty($button->properties->entry_id)   )  $button->properties->entry_id    = $_REQUEST["entry_id"];
        if( empty($button->properties->entry_title) ) $button->properties->entry_title = $_REQUEST["entry_title"];
    }
    # [-] Entry id selection

    # [+] Target data calculation: email or (object) account
    {
        $target_data = new account();
        if( ! empty($button->properties->allow_target_overrides) && ! empty($_REQUEST["target_data"]) )
        {
            if( substr(strtolower($_REQUEST["target_data"]), 0, 5) == "data:" )
            {
                $target_email = trim(base64_decode( str_ireplace("data:", "", $_REQUEST["target_data"]) ));
            }
            else
            {
                $target_email = trim($_REQUEST["target_data"]);
            } # end if
            if( stristr($target_email, " ") !== false ) $target_email = str_replace(" ", "+", $target_email);

            # Let's lookup the email in the database
            $query = "select * from " . $config->db_tables["account"] . "
                      where email = '$target_email' or alternate_email = '$target_email'";
            $res   = mysql_query($query);
            if( mysql_num_rows($res) == 0 )
            {
                $target_data->email = $target_email;
            }
            else
            {
                $row = mysql_fetch_object($res);
                $target_data = new account($row);
            } # end if
            mysql_free_result( $res );
        } # end if
    }
    # [+] Target data calculation: account, name, email

    # [+] Amount in USD override
    {
        if( ! empty($_REQUEST["amount_in_usd"]) && is_numeric($_REQUEST["amount_in_usd"]) )
        {
            $website_account_extended = new account_extensions($website->id_account);
            if( $website_account_extended->account_class != "standard" &&
                $button->properties->coin_scheme == "multi_converted"  &&
                $_REQUEST["amount_in_usd"] > $button->properties->amount_in_usd )
                $button->properties->amount_in_usd = $_REQUEST["amount_in_usd"];
        } # end if
    }
    # [-] Amount in USD override

    # [+] Coins data
    {
        $usd_prices = array();
        # $query = "select coin_name, price from ".$config->db_tables["coin_prices"]." group by coin_name order by date desc";
        # $res   = mysql_query($query);
        # while( $row = mysql_fetch_object($res) ) $usd_prices[$row->coin_name] = ($row->price);
        # mysql_free_result( $res );

        $coins_data = array();
        foreach($config->current_tipping_provider_data["per_coin_data"] as $coin_name => $coin_data)
        {
            if( $include_all_coins || ( ! $include_all_coins && ! $coin_data["coin_disabled"] ) )
            {
                $query = "select price from ".$config->db_tables["coin_prices"]." where coin_name = '$coin_name' order by date desc limit 1";
                $res   = mysql_query($query);
                if( mysql_num_rows($res) == 0 ) $usd_prices[$coin_name] = 0;
                $row = mysql_fetch_object($res);
                $usd_prices[$coin_name] = ($row->price);
                mysql_free_result($res);

                $coins_data[$coin_name] = (object) array(
                    "disabled" => $coin_data["coin_disabled"],
                    "logo"     => $coin_data["coin_image"],
                    "symbol"   => strtoupper($coin_data["coin_sign"]),
                    "min_tip"  => $coin_data["min_transaction_amount"]
                    );
            } # end if
        } # end foreach
    }
    # [-] Coins data
