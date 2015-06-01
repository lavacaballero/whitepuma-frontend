<?
    /**
     * Platform Extension: Websites / API / button data
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
     * @param           string entry_id           Optional entry id for counters and tables data
     * @param           string ref                Optional referral code for log generation
     * @param           string target_data        Optional target data for log generation
     * @param           string callback           Optional JS callback for JSONP
     *
     * @returns json object { message:string, data:mixed }
     */

    $root_url = "../../..";

    header( "Content-Type: application/json; charset: UTF-8" );
    $jsonp_start = empty($_REQUEST["callback"]) ? "" : $_REQUEST["callback"]."( ";
    $jsonp_end   = empty($_REQUEST["callback"]) ? "" : ");";

    if( ! is_file("$root_url/config.php") ) die( $jsonp_start . json_encode(array("message" => "ERROR: config file not found.")) . $jsonp_end );
    include "$root_url/config.php";
    include "$root_url/functions.php";
    include "$root_url/models/tipping_provider.php";
    include "$root_url/models/account.php";
    include "$root_url/models/account_extensions.php";
    include "$root_url/lib/geoip_functions.inc";
    db_connect();

    # [+] Params validation
    {
        if( empty($_REQUEST["website_public_key"]) ) die( $jsonp_start . json_encode(array("message" => "ERROR: Website public key not specified.")) . $jsonp_end );
        if( empty($_REQUEST["button_id"])          ) die( $jsonp_start . json_encode(array("message" => "ERROR: Button id not specified."))          . $jsonp_end );
        if( empty($_REQUEST["token"])              ) die( $jsonp_start . json_encode(array("message" => "ERROR: Access token not provided."))        . $jsonp_end );
        # header("X-WP-GBD-Token-Stage2: " . $_REQUEST["token"]);
    }
    # [-] Params validation

    # [+] Token validation
    {
        if( ! is_file("/tmp/_tk_".md5(stripslashes($_REQUEST["token"]))) ) die( $jsonp_start . json_encode(array("message" => "ERROR: Token not found.")) . $jsonp_end );
        $res = decryptRJ256($config->tokens_encryption_key, $_REQUEST["token"]);
        if( stristr($res, "\t") === false ) die( $jsonp_start . json_encode(array("message" => "ERROR: Invalid token.")) . $jsonp_end );
        list($invoker_referer_url, $invoker_website_public_key) = explode("\t", $res);
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
            if( mysql_num_rows($res) == 0 ) die( $jsonp_start . json_encode(array("message" => "ERROR: Button invoked from an unregistered website.")) . $jsonp_end );
            $invoker_website = mysql_fetch_object($res);
            if( $invoker_website->state == 'disabled' ) die( $jsonp_start . json_encode(array("message" => "ERROR: Button invoked from a disabled website.")) . $jsonp_end );
            if( $invoker_website->state == 'locked'   ) die( $jsonp_start . json_encode(array("message" => "ERROR: Button invoked from a locked website.")) . $jsonp_end );
            mysql_free_result( $res );
            if( empty($invoker_website->allow_leeching) ) die( $jsonp_start . json_encode(array("message" => "ERROR: this website doesn't allow external buttons.")) . $jsonp_end );
            if( stristr($invoker_website->banned_websites, $_REQUEST["website_public_key"]) !== false)
                die( $jsonp_start . json_encode(array("message" => "ERROR: this website has banned this external button source.")) . $jsonp_end );
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
        if( mysql_num_rows($res) == 0 ) die( $jsonp_start . json_encode(array("message" => "ERROR: Website $_REQUEST[website_public_key] not found in our database.")) . $jsonp_end );
        $website = mysql_fetch_object($res);
        if( $website->state == 'disabled' ) die( $jsonp_start . json_encode(array("message" => "ERROR: Website $_REQUEST[website_public_key] is disabled.")) . $jsonp_end );
        if( $website->state == 'locked'   ) die( $jsonp_start . json_encode(array("message" => "ERROR: Website $_REQUEST[website_public_key] is locked.")) . $jsonp_end );
        mysql_free_result( $res );
    }
    # [-] Website validation

    # [+] Button Validation
    {
        $query = "select * from ".$config->db_tables["website_buttons"]."
                  where website_public_key = '$website->public_key'
                  and   button_id = '".addslashes($_REQUEST["button_id"])."'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 ) die( $jsonp_start . json_encode(array("message" => "ERROR: Button not found on this website.")) . $jsonp_end );
        $button = mysql_fetch_object($res);
        if( $button->state == 'disabled' ) die( $jsonp_start . json_encode(array("message" => "ERROR: Button is disabled.")) . $jsonp_end );
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
            if( ! $matched ) die( $jsonp_start . json_encode(array("message" => "ERROR: Button embedded on a non-owned website.")) . $jsonp_end );
        } # end if
        # Note: at this point, the button either doesn't have any valid_url listed,
        # thus, it can be embedded anywhere by anyone.
    }
    # [-] Valid URL checking

    ##################################
    # All checks OK Up to this point #
    ##################################

    $website_owner_extended_data = new account_extensions($website->id_account);

    $button->owner_class = $website_owner_extended_data->account_class;
    $button->properties = json_decode($button->properties);
    $button->properties->caption = stripslashes($button->properties->caption);
    if( ! empty($button_overrides->type)         ) $button->type = $button_overrides->type;
    if( ! empty($button_overrides->color_scheme) ) $button->color_scheme = $button_overrides->color_scheme;

    # [+] Entry id selection
    {
        if( empty($button->properties->entry_id)   )  $button->properties->entry_id    = $_REQUEST["entry_id"];
        if( empty($button->properties->entry_title) ) $button->properties->entry_title = $_REQUEST["entry_title"];
    }
    # [-] Entry id selection

    # Website logo and custom logo
    $website_owner = new account_extensions($website->id_account);
    if( in_array($button->properties->button_logo, array("_website_", "_custom_"))
        && ! in_array($website_owner->account_class, array("vip", "premium"))      )
    {
        $button->properties->button_logo = "_default_";
    }
    else
    {
        if( $button->properties->button_logo == "_website_" )
        {
            if( empty($website->icon_url) ) $button->properties->button_logo = "_website_";
            else                            $button->properties->button_logo = "inset:".$website->icon_url;
        } # end if
        if( $button->properties->button_logo == "_custom_" )
        {
            if( empty($button->properties->custom_logo) ) $button->properties->button_logo = "_custom_";
            else                                          $button->properties->button_logo = "inset:".$button->properties->custom_logo;
        } # end if
    } # end if

    # Target data calculation
    $target_data = new account();
    if( ! empty($_REQUEST["target_data"]) )
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

    # Inits for counters and tables
    $handler = $_REQUEST["website_public_key"] . "/" . $_REQUEST["button_id"] ;

    # Now let's look for tip counters
    $button->counters = null;
    if( $button->properties->hide_tips_counter != "true" )
    {
        $counters_query = $query = "
            select coin_name, sum(coins) as coins
            from ".$config->db_tables["log"]."
            where entry_type    =    'website_button'
            and   action_type   =    'send'
            and   from_handler  like '$handler%'
            and   entry_id      =    '".$button->properties->entry_id."'
            and   to_id_account =    '".(empty($target_data->id_account) ? $website->id_account : $target_data->id_account)."'
            and   state         =    'OK'
            group by coin_name
        ";
        $res = mysql_query($query);
        if( mysql_num_rows($res) )
        {
            $button->counters = array();
            while($row = mysql_fetch_object($res))
            {
                $button->counters[$row->coin_name] = array(
                    "amount" => number_format_crypto_condensed($row->coins, 3),
                #   "symbol" => strtoupper($config->current_tipping_provider_data["per_coin_data"][$row->coin_name]["coin_sign"])
                );
            } # end while
        } # end if
    } # end if

    # Now let's look for table data
    $button->table_data = null;
    $button->table_data_tlength = 0;
    if( in_array($button->type, array("round_table", "square_table")) )
    {
        # Output should be [{from:string, coin_name:string, amount:number, symbol:string, since:string}, {...}]
        $data_table_query = $query = "
            select
                ".$config->db_tables["account"].".name as account_name,
                ".$config->db_tables["log"].".coin_name,
                ".$config->db_tables["log"].".coins,
                ".$config->db_tables["log"].".date_processed
            from
                ".$config->db_tables["log"].",
                ".$config->db_tables["account"]."
            where
                ".$config->db_tables["account"].".id_account     =    ".$config->db_tables["log"].".from_id_account
                and ".$config->db_tables["log"].".entry_type     =    'website_button'
                and ".$config->db_tables["log"].".action_type    =    'send'
                and ".$config->db_tables["log"].".from_handler   like '$handler%'
                and ".$config->db_tables["log"].".entry_id       =    '".$button->properties->entry_id."'
                and ".$config->db_tables["log"].".to_id_account  =    '".(empty($target_data->id_account) ? $website->id_account : $target_data->id_account)."'
                and ".$config->db_tables["log"].".state          =    'OK'
            order by date_processed desc
            limit 20
        ";
        $res = mysql_query($query);
        $button->table_data_tlength = mysql_num_rows($res);
        if( $button->table_data_tlength )
        {
            $c = 0;
            $button->table_data = array();
            while($row = mysql_fetch_object($res))
            {
                $button->table_data[] = array(
                    "from"      => $row->account_name,
                    "coin_name" => $row->coin_name,
                    "amount"    => number_format_crypto_condensed($row->coins, 3),
                #   "symbol"    => strtoupper($config->current_tipping_provider_data["per_coin_data"][$row->coin_name]["coin_sign"]),
                    "since"     => time_elapsed_string($row->date_processed)
                );
                $c++;
                if( $c > 19 ) break;
            } # end while
        } # end if

    } # end if

    # Let's add the entry to the log
    if( ! is_robot() )
    {
        list($city, $region_name, $country_name, $isp) = explode("; ", forge_geoip_location($_SERVER["REMOTE_ADDR"]));
        mysql_query("
            insert into ".$config->db_tables["website_button_log"]." set
            button_id       = '$button->button_id',
            record_type     = 'view',
            record_date     = '".date("Y-m-d H:i:s")."',
            entry_id        = '".$button->properties->entry_id."',
            host_website    = '".$_REQUEST["website_public_key"]."',
            referral_code   = '".$_REQUEST["ref"]."',
            target_account  = '".addslashes($target_data->id_account)."',
            target_name     = '".addslashes($target_data->name)."',
            target_email    = '".addslashes($target_data->email)."',
            client_ip       = '".$_SERVER["REMOTE_ADDR"]."',
            user_agent      = '".addslashes($_SERVER["HTTP_USER_AGENT"])."',
            country         = '".addslashes($country_name)."',
            region          = '".addslashes($region_name)."',
            city            = '".addslashes($city)."',
            isp             = '".addslashes($isp)."'
        ");
    } # end if

    # We only send appearance-related data
    unset( $button->properties->allow_target_overrides,
           $button->properties->entry_id,     $button->properties->entry_title,
           $button->properties->callback,     $button->properties->referral_codes,
           $button->properties->request_type, $button->properties->per_coin_requests );

    die( $jsonp_start . json_encode(array("message" => "OK", "data" => $button)) . $jsonp_end );
?>
