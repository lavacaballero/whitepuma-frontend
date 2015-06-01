<?php
    /**
     * Platform Extension: Websites / summary data
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

    $query = "
        select
            (
                select count(".$config->db_tables["website_button_log"].".record_date)
                from  ".$config->db_tables["websites"].",
                      ".$config->db_tables["website_buttons"].",
                      ".$config->db_tables["website_button_log"]."
                where ".$config->db_tables["websites"].".id_account                =  '$_GET[id_account]'
                and   ".$config->db_tables["website_buttons"].".website_public_key =  ".$config->db_tables["websites"].".public_key
                and   ".$config->db_tables["website_button_log"].".button_id       =  ".$config->db_tables["website_buttons"].".button_id
                and ".$config->db_tables["website_button_log"].".record_date       >= '$start_date'
                and ".$config->db_tables["website_button_log"].".record_date       <  '$end_date'
                and ".$config->db_tables["website_button_log"].".record_type       =  'view'
            ) as views,
            (
                select count(".$config->db_tables["website_button_log"].".record_date)
                from  ".$config->db_tables["websites"].",
                      ".$config->db_tables["website_buttons"].",
                      ".$config->db_tables["website_button_log"]."
                where ".$config->db_tables["websites"].".id_account                =  '$_GET[id_account]'
                and   ".$config->db_tables["website_buttons"].".website_public_key =  ".$config->db_tables["websites"].".public_key
                and   ".$config->db_tables["website_button_log"].".button_id       =  ".$config->db_tables["website_buttons"].".button_id
                and ".$config->db_tables["website_button_log"].".record_date       >= '$start_date'
                and ".$config->db_tables["website_button_log"].".record_date       <  '$end_date'
                and ".$config->db_tables["website_button_log"].".record_type       =  'click'
            ) as clicks,
            (
                select count(".$config->db_tables["website_button_log"].".record_date)
                from  ".$config->db_tables["websites"].",
                      ".$config->db_tables["website_buttons"].",
                      ".$config->db_tables["website_button_log"]."
                where ".$config->db_tables["websites"].".id_account                =  '$_GET[id_account]'
                and   ".$config->db_tables["website_buttons"].".website_public_key =  ".$config->db_tables["websites"].".public_key
                and   ".$config->db_tables["website_button_log"].".button_id       =  ".$config->db_tables["website_buttons"].".button_id
                and ".$config->db_tables["website_button_log"].".record_date       >= '$start_date'
                and ".$config->db_tables["website_button_log"].".record_date       <  '$end_date'
                and ".$config->db_tables["website_button_log"].".record_type       =  'conversion'
            ) as conversions
    ";
    $res = mysql_query($query);
    $row = mysql_fetch_object($res);
?>

<div class="indicators ui-widget-content ui-corner-all">

    <div class="indicator ui-widget-content ui-corner-all">
        <div class="title ui-state-active ui-corner-all">Views</div>
        <div class="content">
            <?= number_format($row->views) ?>
        </div>
    </div>

    <div class="indicator ui-widget-content ui-corner-all">
        <div class="title ui-state-active ui-corner-all">Impressions</div>
        <div class="content">
            <?= number_format($row->clicks) ?>
        </div>
    </div>

    <div class="indicator ui-widget-content ui-corner-all">
        <div class="title ui-state-active ui-corner-all">Conversions</div>
        <div class="content">
            <?= number_format($row->conversions) ?>
        </div>
    </div>

    <??>

    <!-- ############### -->
    <!-- Total worth USD -->
    <!-- ############### -->

    <?
        $likes = array();
        $query = "select public_key from ".$config->db_tables["websites"]." where id_account = '$_GET[id_account]'";
        $res   = mysql_query($query);
        while( $row = mysql_fetch_object($res) ) $likes[] = " from_handler like '$row->public_key/%'";
        mysql_free_result( $res );

        $usd_value = 0;
        if( ! empty($likes) )
        {
            $query = "
                select coin_name, sum(coins) as total_obtained from ".$config->db_tables["log"]."
                where (
                    ".implode(" or\n                    ", $likes)."
                )
                and entry_type      = 'website_button'
                and action_type     = 'send'
                and state           = 'OK'
                and date_processed >= '$start_date' and date_processed < '$end_date'
                group by coin_name
            ";
            # echo "<pre style='text-align: left'>$query</pre>";
            $res = mysql_query($query);
            while($row = mysql_fetch_object($res)) $usd_value += $row->total_obtained * $usd_prices[$row->coin_name];
            mysql_free_result($res);
        } # end if
    ?>

    <div class="indicator ui-widget-content ui-corner-all">
        <div class="title ui-state-active ui-corner-all">Total worth USD</div>
        <div class="content">
            $<?= number_format($usd_value, 8) ?>
            <span class="pseudo_link fa fa-info-circle" style="text-decoration: none;"
                  title="Prices are an estimation based on data taken from different exchanges."
                  onclick="alert($(this).attr('title'))"></span>
        </div>
    </div>

</div>
