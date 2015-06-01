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

    $query = "select * from " . $config->db_tables["websites"] . "
              where public_key not like 'lj.%'
              and   state     = 'enabled'
              and   published = 1
              ".( empty($_GET["category"]) ? "" : "and category = '$_GET[category]'")."
              order by name asc";
    $res = mysql_query($query);
    $data = array();
    while( $row = mysql_fetch_object($res) )
    {
        if( empty($row->icon_url) ) $row->icon_url = $config->buttons_default_website_logo;
        $row->joined = time_elapsed_string($row->creation_date);
        $data[] = $row;
    } # end while

    die( $jsonp_start . json_encode(array("message" => "OK", "data" => $data)) . $jsonp_end );
?>
