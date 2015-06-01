<?php
    /**
     * Platform Extension: Websites / counters for button
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

    if( stristr($button->button_type, "table") !== false && ! ($website->id_account == $account->id_account || $is_admin) )
    {
        ?>
        <div class="ui-state-highlight message_box ui-corner-all">
            <span class="fa fa-info-circle"></span>
            Sorry, but details table is not available for this button.
        </div>
        <?
        return;
    } # end if

    if( ! ($website->id_account == $account->id_account || $is_admin) ) $_GET["ref"] = "";
    $from_handler_filter = empty($_GET["ref"])
                         ? "$website->public_key/$button->button_id%"
                         : "$website->public_key/$button->button_id/$_GET[ref]%";
    $query = "
        select
            ".$config->db_tables["log"].".op_id,
            ".$config->db_tables["log"].".from_id_account,
            ".$config->db_tables["account"].".name as from_name,
            ".$config->db_tables["log"].".coin_name,
            ".$config->db_tables["log"].".coins,
            ".$config->db_tables["log"].".date_processed
        from
            ".$config->db_tables["log"].",
            ".$config->db_tables["account"]."
        where ".$config->db_tables["log"].".from_id_account = ".$config->db_tables["account"].".id_account
        and   ".$config->db_tables["log"].".from_handler like '$from_handler_filter'
        and   ".$config->db_tables["log"].".entry_type      = 'website_button'
        and   ".$config->db_tables["log"].".action_type     = 'send'
        and   ".$config->db_tables["log"].".state           = 'OK'
        and   ".$config->db_tables["log"].".date_processed >= '$start_date'
        and   ".$config->db_tables["log"].".date_processed < '$end_date'
        order by ".$config->db_tables["log"].".date_processed desc,
                 ".$config->db_tables["log"].".coin_name asc
    ";
    $res = mysql_query($query);
    if( mysql_num_rows($res) == 0 )
    {
        ?>
        <div class="ui-state-highlight message_box ui-corner-all">
            <span class="fa fa-info-circle"></span>
            There is no table data available for the selected website.
        </div>
        <?
        return;
    } # end if
?>
    <div class="table_wrapper">
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>Date</th>
                    <? if($is_admin || $website->id_account == $account->id_account): ?>
                        <th>OpId</th>
                    <? endif; ?>
                    <th>Coin</th>
                    <th>From</th>
                    <th>Amount</th>
                    <th class="{sorter: false}">Symbol</th>
                    <th>USD</th>
                </tr>
            </thead>
            <tbody>

                <? $total_usd = $usd_value = 0; ?>
                <? while($row = mysql_fetch_object($res) ): ?>
                    <? $usd_value = $row->coins * $usd_prices[$row->coin_name];
                       $total_usd += $usd_value; ?>
                    <tr>
                        <td><?= $row->date_processed ?></td>
                        <? if($is_admin || $website->id_account == $account->id_account): ?>
                            <td align="center"><?= $row->op_id ?></td>
                        <? endif; ?>
                        <td><?= $row->coin_name ?></td>
                        <td><?= "[$row->from_id_account] $row->from_name" ?></td>
                        <td align="right"><?= number_format($row->coins, 8) ?></td>
                        <td><?= $coins_data[$row->coin_name]->symbol ?></td>
                        <td align="right">$<?= number_format($usd_value, 8) ?></td>
                    </tr>
                <? endwhile; ?>

        </tbody>
        <? if( ($is_admin || $website->id_account == $account->id_account) || $button->properties->hide_tips_counter != "true"): ?>
            <tfoot>
                <tr>
                    <td colspan="<?= ($is_admin || $website->id_account == $account->id_account) ? 6 : 5  ?>">&nbsp;</td>
                    <td align="right">$<?= number_format($total_usd, 8) ?></td>
                </tr>
            </tfoot>
        <? endif; ?>
    </table>
    </div><!-- /.table_wrapper -->

<? mysql_free_result($res); ?>
