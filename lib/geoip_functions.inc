<?php
    /**
     * GeoIP functions
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

    /**
     * Forging of country, state, city info
     *
     * @param string $ip
     *
     * @returns string
     */
    function forge_geoip_location($ip)
    {
        if( ! function_exists("geoip_record_by_name")   &&
            ! function_exists("geoip_org_by_name")      &&
            ! function_exists("geoip_region_name_by_code") ) return "n/a; n/a; n/a; n/a";

        $record       = @geoip_record_by_name($ip);
        $country_code = $record["country_code"];
        $country_name = $record["country_name"];
        $region_name  = @geoip_region_name_by_code($record["country_code"], $record["region"]);
        $city         = $record["city"];
        $isp          = @geoip_org_by_name($ip);

        if( empty($city) )         $city         = "n/a";
        if( empty($region_name) )  $region_name  = "n/a";
        if( empty($country_name) ) $country_name = "n/a";
        if( empty($isp) )          $isp          = "n/a";

        return "$city; $region_name; $country_name; $isp";
    } # end function

    /**
     * Returns explicit GeoIP info
     *
     * @param string $ip
     * @param string $what country_code, country_name, region_name, city, ISP
     *
     * @returns string
     */
    function get_geoip_location_data($ip, $what = "country_code")
    {
        if( ! function_exists("geoip_record_by_name")   &&
            ! function_exists("geoip_org_by_name")      &&
            ! function_exists("geoip_region_name_by_code") ) return "";

        $record       = @geoip_record_by_name($ip);
        $country_code = $record["country_code"];
        $country_name = $record["country_name"];
        $region_name  = @geoip_region_name_by_code($record["country_code"], $record["region"]);
        $city         = $record["city"];
        $isp          = @geoip_org_by_name($ip);

        if( empty($city) )         $city         = "n/a";
        if( empty($region_name) )  $region_name  = "n/a";
        if( empty($country_name) ) $country_name = "n/a";
        if( empty($isp) )          $isp          = "n/a";

        if($what == "country_code") return $country_code;
        if($what == "country_name") return $country_name;
        if($what == "region_name")  return $region_name;
        if($what == "city")         return $city;
        if($what == "isp")          return $isp;
    } # end if
