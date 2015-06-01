<?php
    /**
     * Platform Extension: Coin infos / JSON deliverer
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
    db_connect();

    $jsonp_start = empty($_REQUEST["callback"]) ? "" : $_REQUEST["callback"]."( ";
    $jsonp_end   = empty($_REQUEST["callback"]) ? "" : ");";

    $usd_prices = array();
    $query = "select coin_name, price from ".$config->db_tables["coin_prices"]." group by coin_name order by date desc";
    $res   = mysql_query($query);
    while( $row = mysql_fetch_object($res) ) $usd_prices[$row->coin_name] = ($row->price);
    mysql_free_result( $res );

    $data = array();
    $coins = array_keys($config->tipping_providers_database[$config->current_tipping_provider_keyname]["per_coin_data"]);
    sort($coins);
    foreach($coins as $coin_name)
    {
        $coin_data = $config->tipping_providers_database[$config->current_tipping_provider_keyname]["per_coin_data"][$coin_name];
        $data[$coin_name] = array(
            "symbol"                 => strtoupper($coin_data["coin_sign"]),
            "icon"                   => $coin_data["coin_image"],
            "official_url"           => $coin_data["official_url"],
            "wallet_daemon_info"     => $coin_data["wallet_daemon_info"],
            "min_transaction_amount" => $coin_data["min_transaction_amount"],
            "transaction_fee"        => $coin_data["transaction_fee"],
            "withdrawal_fee"         => $coin_data["system_transaction_fee"],
            "usd_price"              => $usd_prices[$coin_name]
        );
    } # end foreach

    header("Content-Type: application/json; charset=utf-8");
    die( $jsonp_start . json_encode(array("message" => "OK", "data" => $data)) . $jsonp_end );
