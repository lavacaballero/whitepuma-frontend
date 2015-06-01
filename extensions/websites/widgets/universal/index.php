<?php
    /**
     * Platform Extension: Websites / iframed widget / main invocator
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
     * @param           string target_data        Optional. Pipe separated params: account:string, name:string, email:string
     */

    header( "Content-Type: text/html; charset: UTF-8" );
    $root_url = "../../../..";

    ####################################
    function throw_error($error_message)
    ####################################
    {
        $contents_segment = "index.contents.error.inc";
        include "index.contents.inc";
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

    # Basic validations
    include "bootstrap.inc";

    # Let's add the entry to the log
    if( ! is_robot() )
    {
        list($city, $region_name, $country_name, $isp) = explode("; ", forge_geoip_location($_SERVER["REMOTE_ADDR"]));
        mysql_query("
            insert into ".$config->db_tables["website_button_log"]." set
            button_id       = '$button->button_id',
            record_type     = 'click',
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

    $contents_segment = "index.contents.main.inc";
    include "index.contents.inc";
