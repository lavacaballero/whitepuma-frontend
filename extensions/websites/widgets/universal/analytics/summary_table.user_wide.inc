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

    # Let's first get the websites
    $query = "select public_key, name from ".$config->db_tables["websites"]." where id_account = '$_GET[id_account]'";
    $res   = mysql_query($query);
    $finals = array();
    while( $row = mysql_fetch_object($res) )
    {
        $finals[] = "
            select count(op_id) as entries,
                   coin_name,
                   '$row->name' as website_name,
                   sum(coins) as total_obtained,
                   date(date_processed) as date_processed
            from ".$config->db_tables["log"]."
            where from_handler like '$row->public_key/%'
            and   entry_type      = 'website_button'
            and   action_type     = 'send'
            and   state           = 'OK'
            and   date_processed >= '$start_date' and date_processed < '$end_date'
            group by website_name, coin_name
        ";
    } # end while
    mysql_free_result($res);

    $query = implode("\nunion\n", $finals);
    $query .= "\norder by website_name desc, coin_name asc";
    # echo "<pre>$query</pre>";
    echo mysql_error();
    $res = mysql_query($query);
    if( mysql_num_rows($res) == 0 )
    {
        ?>
        <div class="ui-state-highlight message_box ui-corner-all">
            <span class="fa fa-info-circle"></span>
            There is no data available.
        </div>
        <?
        return;
    } # end if
?>

    <div class="table_wrapper">
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>Website</th>
                    <th>Coin</th>
                    <th class="{sorter: false}">Entries</th>
                    <th>Obtained</th>
                    <th class="{sorter: false}">Symbol</th>
                    <th>USD</th>
                </tr>
            </thead>
            <tbody>

                <? $total_usd = $usd_value = 0; ?>
                <? while($row = mysql_fetch_object($res) ): ?>
                    <? $usd_value = $row->total_obtained * $usd_prices[$row->coin_name];
                       $total_usd += $usd_value; ?>
                    <tr>
                        <td><?= $row->website_name ?></td>
                        <td><?= $row->coin_name ?></td>
                        <td align="center"><?= $row->entries ?></td>
                        <td align="right"><?= number_format($row->total_obtained, 8) ?></td>
                        <td><?= $coins_data[$row->coin_name]->symbol ?></td>
                        <td align="right">$<?= number_format($usd_value, 8) ?></td>
                    </tr>
                <? endwhile; ?>

        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">&nbsp;</td>
                <td align="right">$<?= number_format($total_usd, 8) ?></td>
            </tr>
        </tfoot>
    </table>
    </div><!-- /.table_wrapper -->

<? mysql_free_result($res); ?>
