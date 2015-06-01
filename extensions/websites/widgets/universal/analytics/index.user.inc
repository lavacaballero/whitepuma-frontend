<?php
    /**
     * Platform Extension: Websites / analytics
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
     * @var string id_account         Optional. For admins
     * @var object $website
     * @var object $button
     * @var string ref                Optional. Referral code
     * @var string entry_id           Optional. For recording in the OpsLog
     * @var string target_data        Optional. Pipe separated params: account:string, name:string, email:string
     */

    if( empty($_GET["website_public_key"]) && empty($_GET["id_account"]) )
        $_GET["id_account"] = $account->id_account;
    elseif( ! empty($_GET["website_public_key"]) )
        $_GET["id_account"] = $website->id_account;

    include "heading.inc";
?>

<form name="analytics" id="analytics" method="get" action="<?=$_SERVER["PHP_SELF"]?>?wasuuup=<? md5(mt_rand(0,65535)) ?>">

    <input type="hidden" name="period"  value="<?=$_GET["period"]?>">
    <input type="hidden" name="results" value="<?=$_GET["results"]?>">
    <input type="hidden" name="ref"     value="<?=htmlspecialchars(stripslashes($_GET["ref"]))?>">

    <? $empty_websites = false; ?>

    <h1 style="text-align: left; background-image: url('graph.png');">
        <? if($account->id_account == $_GET["id_account"]) echo "My"; else echo "User"; ?>
        Analytics

        <? if( $is_admin ) : ?>
            <span class="selector ui-state-default ui-corner-all">
                <span class="fa fa-user"></span>
                <?
                    #==========================#
                    # User selector for admins #
                    #==========================#

                    $query = "
                        select distinct
                            ".$config->db_tables["account"].".id_account,
                            ".$config->db_tables["account"].".name
                        from
                            ".$config->db_tables["website_buttons"].",
                            ".$config->db_tables["websites"].",
                            ".$config->db_tables["account"]."
                        where
                            ".$config->db_tables["website_buttons"].".website_public_key = ".$config->db_tables["websites"].".public_key and
                            ".$config->db_tables["websites"].".id_account = ".$config->db_tables["account"].".id_account
                        order by name asc
                    ";
                    $res = mysql_query($query);
                ?>

                <select name="id_account" class="combobox" onchange="$('#analytics input[name=ref]').val(''); $('#analytics select[name=website_public_key] option').prop('selected', false); $('#analytics select[name=button_id] option').prop('selected', false); $('#analytics').submit();">

                    <? while($row = mysql_fetch_object($res)): ?>

                        <option <? if($row->id_account == $_GET["id_account"]) echo "selected"; ?> value="<?=$row->id_account?>"><?=$row->name?> (<?=$row->id_account?>)</option>

                    <? endwhile; ?>

                </select>
            </span><!-- /.selector -->
        <? endif; ?>

        <? if( $is_admin || $account->id_account == $_GET["id_account"] ): ?>
            <span class="selector ui-state-default ui-corner-all">
                <?
                    #==================#
                    # Website selector #
                    #==================#

                    $query = "
                        select distinct
                            ".$config->db_tables["websites"].".public_key,
                            ".$config->db_tables["websites"].".name
                        from
                            ".$config->db_tables["website_buttons"].",
                            ".$config->db_tables["websites"]."
                        where
                            ".$config->db_tables["website_buttons"].".website_public_key = ".$config->db_tables["websites"].".public_key and
                            ".$config->db_tables["websites"].".id_account = '$_GET[id_account]'
                        order by name asc
                    ";
                    $res = mysql_query($query);
                ?>
                <? if( mysql_num_rows($res) == 0 ): ?>
                    <? $empty_websites = true; ?>
                    <span class="fa fa-info-circle"></span>
                    No websites to show
                <? else: ?>
                    <span class="fa fa-globe"></span>
                    <select name="website_public_key" onchange="$('#analytics input[name=ref]').val(''); $('#analytics select[name=button_id] option').prop('selected', false); $('#analytics').submit();">

                        <option <? if( empty($_GET["website_public_key"])) echo "selected" ?> value="">&lt;All websites&gt;</option>

                        <? while($row = mysql_fetch_object($res)): ?>

                            <option <? if($row->public_key == $_GET["website_public_key"]) echo "selected"; ?> value="<?=$row->public_key?>"><?=$row->name?> (<?=$row->public_key?>)</option>

                        <? endwhile; ?>

                    </select>
                <? endif; ?>
            </span><!-- /.selector -->
        <? endif; ?>

        <? if( ! empty($_GET["website_public_key"]) ): ?>
            <span class="selector ui-state-default ui-corner-all">
                <span class="fa fa-hand-o-up"></span>
                <?
                    #=================#
                    # Button selector #
                    #=================#

                    $query = "
                        select
                            ".$config->db_tables["website_buttons"].".button_id,
                            ".$config->db_tables["website_buttons"].".button_name
                        from
                            ".$config->db_tables["website_buttons"]."
                        where
                            ".$config->db_tables["website_buttons"].".website_public_key = '".$_GET["website_public_key"]."'
                        order by button_name asc
                    ";
                    # echo "<pre>$query</pre>";
                    $res = mysql_query($query);
                ?>

                <select name="button_id" onchange="$('#analytics input[name=ref]').val(''); $('#analytics').submit();">

                    <option <? if( empty($_GET["button_id"])) echo "selected" ?> value="">&lt;All buttons&gt;</option>

                    <? while($row = mysql_fetch_object($res)): ?>

                        <option <? if($row->button_id == $_GET["button_id"]) echo "selected"; ?> value="<?=$row->button_id?>"><?=$row->button_name?> (<?=$row->button_id?>)</option>

                    <? endwhile; ?>

                </select>
            </span><!-- /.selector -->
        <? endif; ?>
    </h1>

    <? if($empty_websites) { echo "</form>"; return; } ?>

    <!-- Period selector -->
    <? include "period_selector.inc"; ?>

    <!-- Summary -->
    <? if( ! empty($button->button_id) ) include "button_summary.single_button.inc"; ?>
    <? if(   empty($button->button_id) && ! empty($website->public_key) ) include "button_summary.website_wide.inc"; ?>
    <? if(   empty($button->button_id) &&   empty($website->public_key) ) include "button_summary.user_wide.inc"; ?>

    <!-- Data for all user websites  -->
    <? if(   empty($button->button_id) && ! empty($website->public_key) ) include "summary_table.website_wide.inc"; ?>

    <!-- Data for all user webiste buttons -->
    <? if(   empty($button->button_id) &&   empty($website->public_key) ) include "summary_table.user_wide.inc"; ?>

    <!-- Results selector -->
    <? include "results_selector.inc"; ?>

    <!-- Counters -->
    <? if( $_GET["results"] == "counters" && ! empty($button->button_id) && ! empty($website->public_key) ) include "counters.inc"; ?>

    <!-- Table -->
    <? if( $_GET["results"] == "table" && ! empty($button->button_id) && ! empty($website->public_key) ) include "table.inc"; ?>

</form>
