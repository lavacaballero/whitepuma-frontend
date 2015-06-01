<?php
    /**
     * CLI price getter
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

    chdir( dirname(__FILE__) );

    $root_url = "../..";
    if( isset($_SERVER["HTTP_HOST"])) die("<h3>This script is not ment to be called through a web browser. You must invoke it through a command shell or a cron job.</h3>");
    if( ! is_file("$root_url/config.php") ) die("ERROR: config file not found.");
    include "$root_url/config.php";
    include "$root_url/functions.php";
    include "$root_url/lib/cli_helper_class.php";
    cli::$use_utf8 = false;
    db_connect();

    cli::write(date("Y-m-d H:i:s")." - Starting\n");

    ########################################
    # Let's build a lowercased coins array #
    ########################################

    $coin_prices_by_name   = array();
    $coin_names_by_sign    = array();
    $coin_signs_by_name    = array();
    $price_sources_by_coin = array();
    foreach($config->current_tipping_provider_data["per_coin_data"] as $coin_name => $coin_data)
    {
        if($coin_name == "BitcoinBITS") continue;
        if($coin_name == "BitcoinBTC") $coin_name = "Bitcoin";
        $coin_sign = $coin_data["coin_sign"];

        $coin_sign = strtoupper($coin_sign);
        $coin_prices_by_name[strtolower($coin_name)] = 0;
        $coin_names_by_sign[$coin_sign]              = strtolower($coin_name);
        $coin_signs_by_name[strtolower($coin_name)]  = $coin_sign;
    } # end foreach
    ksort($coin_prices_by_name);

    ####################################
    $current_source = "Cryptonator/USD";
    ####################################

    cli::write("\n");
    cli::write("                     Starting Cryptonator check...\n", cli::$forecolor_yellow);

    foreach($coin_prices_by_name as $coin_name => $price)
    {
        if( $price > 0 ) continue;

        $coin_sign = strtolower($coin_signs_by_name[$coin_name]);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://www.cryptonator.com/api/ticker/$coin_sign-usd");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $rawData = curl_exec($curl);
        curl_close($curl);
        # print_r($rawData);
        $data = json_decode($rawData);
        # print_r($data);

        if( $data->success )
        {
            $price = $data->ticker->price;
            cli::write("                     Got $coin_name ($coin_sign) price of $price.\n", cli::$forecolor_brown);
            $coin_prices_by_name[$coin_name]   = ($price);
            $price_sources_by_coin[$coin_name] = $current_source;
        }
        else
        {
            cli::write("                     Price not found for $coin_name ($coin_sign).\n", cli::$forecolor_yellow);
        } # end if
    } # end foreach

    $btc_usd = $coin_prices_by_name["bitcoin"];

    #########################
    # Presets for Bleutrade #
    #########################

    # Conversion of Bitcoin to bits
    if( ! empty($btc_usd) )
    {
        $coin_prices_by_name["bitcoin"]     = number_format($btc_usd,           16, ".", "");
        $coin_prices_by_name["bitcoinbtc"]  = number_format($btc_usd,           16, ".", "");
        $coin_prices_by_name["bitcoinbits"] = number_format($btc_usd / 1000000, 16, ".", "");
        $price_sources_by_coin["bitcoin"]     = $current_source;
        $price_sources_by_coin["bitcoinbtc"]  = $current_source;
        $price_sources_by_coin["bitcoinbits"] = $current_source;
    } # end if

    ##################################
    $current_source = "Bleutrade/BTC";
    ##################################

    cli::write("\n");
    cli::write("                     Starting Bleutrade check...\n", cli::$forecolor_yellow);

    if( ! empty($btc_usd) )
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://bleutrade.com/api/v1/last_trade?basemarket=BTC");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $rawData = curl_exec($curl);
        cli::write("\n");
        cli::write("                     Obtained ".strlen($rawData)." bytes from $current_source\n", cli::$forecolor_light_green);
        curl_close($curl);
        # print_r($rawData);
        $data = explode("\n", trim($rawData));

        foreach($data as $this_coin)
        {
            list($sign, $blah, $blah, $blah, $blah, $price) = explode(";", $this_coin);
            $sign = strtoupper($sign);
            if( isset($coin_names_by_sign[$sign]) )
            {
                $coin_name = $coin_names_by_sign[$sign];
                if( ! empty($coin_prices_by_name[$coin_name]) ) continue;
                $coin_prices_by_name[$coin_name]   = number_format($price * $btc_usd, 16);
                $price_sources_by_coin[$coin_name] = $current_source;
                if( ! empty($coin_prices_by_name[$coin_name]) ) cli::write("                     • Got $coin_name price of ".($coin_prices_by_name[$coin_name])."\n", cli::$forecolor_green);
            } # end if
        } # end foreach
        # print_r($coin_prices_by_name);
    } # end if

    ############################################################################################
    cli::write("\n                     Starting DB updates...\n", cli::$forecolor_light_purple);
    ############################################################################################

    $today         = date("Y-m-d H:i:s");
    $compound_data = array();
    foreach($config->current_tipping_provider_data["per_coin_data"] as $coin_name => $coin_data)
    {
        $price  = $coin_prices_by_name[strtolower($coin_name)];
        $source = $price_sources_by_coin[strtolower($coin_name)];
        cli::write("                     • $coin_name --> $price from $source.\n");
        if( empty($price) ) continue;
        $price  = str_replace(",", "", $price);

        $query = "
            insert into ".$config->db_tables["coin_prices"]." set
                `coin_name` = '$coin_name',
                `date`      = '$today',
                `price`     = '$price',
                `source`    = '$source'
            on duplicate key update
                `coin_name` = '$coin_name',
                `price`     = '$price',
                `source`    = '$source'
        ";
        mysql_query($query);
        if( mysql_affected_rows() > 0 ) cli::write("                     • $coin_name updated on database OK.\n", cli::$forecolor_purple);
        else                            cli::write("                     • $coin_name not updated! ".mysql_error()."\n", cli::$forecolor_light_red);
    } # end foreach

    if( mysql_affected_rows() > 0 ) cli::write("                     Operations finished.\n");
    echo "\n";
