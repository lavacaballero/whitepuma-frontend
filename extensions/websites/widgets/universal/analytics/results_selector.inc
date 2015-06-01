<?php
    /**
     * Platform Extension: Websites / results selector
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

    $allow_counters = ( ! ($button->properties->hide_tips_counter == "true" && ! ($website->id_account == $account->id_account || $is_admin)) );
    $allow_table    = ( ! (stristr($button->button_type, "table") !== false && ! ($website->id_account == $account->id_account || $is_admin)) );

    if(     $allow_counters   && ! $allow_table ) { $_GET["results"] = "counters"; return; }
    elseif( ! $allow_counters &&   $allow_table ) { $_GET["results"] = "table";    return; }
    elseif( ! $allow_counters && ! $allow_table ) { $_GET["results"] = "";         return; }
    elseif( empty($_GET["results"]) )             { $_GET["results"] = "counters";         }

?>
<div class="results_selector ui-widget-header message_box ui-corner-all" style="text-align: center">

    <input type="hidden" name="results" value="<?=$_GET["results"]?>">

    <? if( $allow_counters ): ?>
        <span value="counters" class="pseudo_link <?= $_GET["results"] == "counters" ? "ui-state-active" : "ui-state-default" ?> ui-corner-all"
              onmouseover="$(this).toggleClass('ui-state-hover')" onmouseout="$(this).toggleClass('ui-state-hover')"
              onclick="$('#analytics input[name=results]').val( $(this).attr('value') ); $('#analytics').submit();"
              style="display: inline-block; padding: 2px 5px; margin: 5px 2px;">Counters</span>
    <? endif; ?>

    <? if( $allow_table ): ?>

        <span value="table" class="pseudo_link <?= $_GET["results"] == "table" ? "ui-state-active" : "ui-state-default" ?> ui-corner-all"
              onmouseover="$(this).toggleClass('ui-state-hover')" onmouseout="$(this).toggleClass('ui-state-hover')"
              onclick="$('#analytics input[name=results]').val( $(this).attr('value') ); $('#analytics').submit();"
              style="display: inline-block; padding: 2px 5px; margin: 5px 2px;">Table</span>

    <? endif; ?>

</div><!-- /.results_selector -->

<? if( $website->id_account == $account->id_account || $is_admin): ?>

    <div class="referrals_selector">

        <?
            $query = "
                select from_handler
                from ".$config->db_tables["log"]."
                where from_handler like '$website->public_key/$button->button_id/%'
                and   entry_type      = 'website_button'
                and   action_type     = 'send'
                and   state           = 'OK'
                and   date_processed >= '$start_date'
                and   date_processed < '$end_date'
                group by from_handler
            ";
            # echo "<pre>$query</pre>";
            $res = mysql_query($query);
            $referral_list = array();
            while( $row = mysql_fetch_object($res) )
            {
                $referral = str_replace("$website->public_key/$button->button_id/", "", $row->from_handler);
                list($referral, $id_order) = explode(":", $referral);
                if( in_array($referral, $referral_list) ) continue;
                $referral_list[] = $referral;
            } # end while
            mysql_free_result($res);
        ?>

        <? if( count($referral_list) > 0 ): ?>
                <div class="ui-widget-header message_box ui-corner-all" style="text-align: center">

                    <span value=""
                          class="pseudo_link <?= empty($_GET["ref"]) ? "ui-state-active" : "ui-state-default" ?> ui-corner-all"
                          onmouseover="$(this).toggleClass('ui-state-hover')" onmouseout="$(this).toggleClass('ui-state-hover')"
                          onclick="$('#analytics input[name=ref]').val( $(this).attr('value') ); $('#analytics').submit();"
                          style="display: inline-block; padding: 2px 5px; margin: 5px 2px;"
                          title="Reset referral filter">
                          No referral filter
                    </span>
                    <? foreach($referral_list as $referral): ?>
                        <span value="<?=htmlspecialchars($referral)?>"
                              class="pseudo_link <?= stripslashes($_GET["ref"]) == $referral ? "ui-state-active" : "ui-state-default" ?> ui-corner-all"
                              onmouseover="$(this).toggleClass('ui-state-hover')" onmouseout="$(this).toggleClass('ui-state-hover')"
                              onclick="$('#analytics input[name=ref]').val( $(this).attr('value') ); $('#analytics').submit();"
                              style="display: inline-block; padding: 2px 5px; margin: 5px 2px;"
                              title="Filter table for this referral">
                              <?=$referral?>
                        </span>
                    <? endforeach; ?>

                </div>
        <? endif; ?>

    </div><!-- /.referrals_selector -->

<? endif; ?>
