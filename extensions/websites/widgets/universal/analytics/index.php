<?php
    /**
     * Platform Extension: Websites / analytics page
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
     * @param string period             Optional: today, yesterday, this_week, last_week, this_month, last_month, all_time
     * @param string id_account         Optional. For admins
     * @param string website_public_key Optional.
     * @param string button_id          Optional.
     * @param string ref                Optional. Referral code
     * @param string entry_id           Optional. For recording in the OpsLog
     * @param string target_data        Optional. Pipe separated params: account:string, name:string, email:string
     */

    header( "Content-Type: text/html; charset: UTF-8" );
    $root_url = "../../../../..";
    $widget_files_rel_path = "../";

    ####################################
    function throw_error($error_message)
    ####################################
    {
        global $widget_files_rel_path;
        $contents_segment = $widget_files_rel_path."index.contents.error.inc";
        include $widget_files_rel_path."index.contents.inc";
        die();
    } # end function

    if( ! is_file("$root_url/config.php") ) throw_error("ERROR: config file not found.");
    include "$root_url/config.php";
    include "$root_url/functions.php";
    include "$root_url/lib/geoip_functions.inc";
    include("$root_url/models/tipping_provider.php");
    include("$root_url/models/account.php");
    include("$root_url/models/account_extensions.php");
    db_connect();

    # Period settings
    if( empty($_GET["period"])    ) $_GET["period"] = "this_month";
    if( empty($_GET["coin_name"]) ) $_GET["coin_name"] = "%";
    $periods = array(
        "today"      => "Today",
        "yesterday"  => "Yesterday",
        "this_week"  => "This week",
        "last_week"  => "Last week",
        "this_month" => "This month",
        "last_month" => "Last month",
        "all_time"   => "All time",
    );
    switch($_GET["period"])
    {
        case "today":      $start_date = date("Y-m-d 00:00:00");                                  $end_date = date("Y-m-d 00:00:00", strtotime("tomorrow"));            break;
        case "yesterday":  $start_date = date("Y-m-d 00:00:00", strtotime("yesterday"));          $end_date = date("Y-m-d 00:00:00");                                   break;
        case "this_week":  $start_date = date("Y-m-d 00:00:00", strtotime("this week - 1 day"));  $end_date = date("Y-m-d 00:00:00", strtotime("tomorrow"));            break;
        case "last_week":  $start_date = date("Y-m-d 00:00:00", strtotime("last week - 1 day"));  $end_date = date("Y-m-d 00:00:00",  strtotime("last week + 6 days")); break;
        case "this_month": $start_date = date("Y-m-01 00:00:00");                                 $end_date = date("Y-m-01 00:00:00", strtotime("next month"));         break;
        case "last_month": $start_date = date("Y-m-01 00:00:00", strtotime("last month"));        $end_date = date("Y-m-01 00:00:00");                                  break;
        case "all_time":   $start_date = date("2014-01-01 00:00:00");                             $end_date = date("Y-m-d 00:00:00", strtotime("tomorrow"));            break;
    } # end switch


    # Special session checking
    $throw_empty_account_on_anonymous = true;
    include "../session_handler.inc";

    # Checking for anonymous user with no params
    if( empty($account->id_account) && (empty($_GET["website_public_key"]) || empty($_GET["button_id"])) )
    {
        $contents_segment = "../index.welcome.inc";
        include "../index.contents.inc";
        die();
    } # end if

    # $website preload
    $website = (object) array();
    if( ! empty($_GET["website_public_key"]) )
    {
        $query = "select * from ".$config->db_tables["websites"] . " where public_key = '$_GET[website_public_key]'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 ) throw_error("No website has been found with that public key on our database");
        $website = mysql_fetch_object($res);
        mysql_free_result($res);
        if( $website->state != "enabled" && $website->id_account != $account->id_account )
            throw_error("Sorry, but $row->name is not available at the moment.");
        $website_account = new account($website->id_account);
    } # end if

    # $button preload
    $button = (object) array();
    if( ! empty($_GET["website_public_key"]) && ! empty($_GET["button_id"]) )
    {
        $query = "select * from ".$config->db_tables["website_buttons"] . "
                  where website_public_key = '$_GET[website_public_key]'
                  and button_id            = '$_GET[button_id]'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 ) throw_error("No button has been found with that identifier in our database.");
        $button = mysql_fetch_object($res);
        mysql_free_result($res);
        if( $button->state != "enabled" && $website->id_account != $account->id_account && ! $is_admin )
            throw_error("Sorry, but this button is not available at the moment.");
        $button->properties = json_decode($button->properties);
        if( $button->properties->private_basic_analytics == "true" && $website->id_account != $account->id_account && ! $is_admin )
            throw_error("Sorry, but there are no public analytics data available for this button.");
        # if( stristr($button->type, "table") === false && $button->properties->hide_tips_counter != "true" )
        #     throw_error("Sorry, but this button doesn't show counters or table data.");
    } # end if

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
                    "disabled"                    => $coin_data["coin_disabled"],
                    "exclude_from_global_balance" => $coin_data["exclude_from_global_balance"],
                    "logo"                        => $coin_data["coin_image"],
                    "symbol"                      => strtoupper($coin_data["coin_sign"]),
                    "min_tip"                     => $coin_data["min_transaction_amount"]
                    );
            } # end foreach
        } # end foreach
    }
    # [-] Coins data

    # What do we show?
    if( empty($account->id_account) )
    {
        # Anonymous user
        $contents_segment = "analytics/index.public.inc";
        include "../index.contents.inc";
        die();
    }
    else if( ! $is_admin && $account->id_account != $website->id_account
             && empty($_GET["website_public_key"])
             && ! empty($_GET["button_id"])
           )
    {
        throw_error("You need to specify a website public key and button id to show analytics for.");
    }
    else if( ! $is_admin && $account->id_account != $website->id_account
             && ! empty($_GET["website_public_key"])
             && empty($_GET["button_id"])
           )
    {
        throw_error("You need to specify a button id to show analytics for.");
    }
    else if( ! $is_admin && $account->id_account != $website->id_account
             && ! empty($_GET["website_public_key"])
             && ! empty($_GET["button_id"])
           )
    {
        # User other than the owner
        $contents_segment = "analytics/index.public.inc";
        include "../index.contents.inc";
        die();
    } # end if

    # Statistics for the user
    $contents_segment = "analytics/index.user.inc";
    include "../index.contents.inc";
