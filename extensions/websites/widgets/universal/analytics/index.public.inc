<?php
    /**
     * Platform Extension: Websites / public analytics (single button)
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

    include "heading.inc";
?>
<form name="analytics" id="analytics" method="get" action="<?=$_SERVER["PHP_SELF"]?>?wasuuup=<? md5(mt_rand(0,65535)) ?>">

    <input type="hidden" name="period"             value="<?=$_GET["period"]?>">
    <input type="hidden" name="website_public_key" value="<?=$_GET["website_public_key"]?>">
    <input type="hidden" name="button_id"          value="<?=$_GET["button_id"]?>">
    <input type="hidden" name="ref"                value="">

    <h1 style="text-align: left; background-image: url('graph.png');">
        Public Analytics for button <?=$button->button_id?> from <?=$website->name?>
        by <?=$website_account->name?>
        <? if( ! empty($account->id_account) ): ?>
            <button onclick="location.href = 'index.php?wasuuup=' + parseInt(Math.random() * 1000000000000000)">
                <span class="fa fa-line-chart"></span>
                My analytics
            </button>
        <? endif; ?>
    </h1>

    <?
        $website_account_extended = new account_extensions($website_account->id_account);
        if( $website_account_extended->account_class != "standard" && ! empty($button->properties->custom_logo) )
            $added_style = "background-image: url('".$button->properties->custom_logo."');";
        elseif( $website_account_extended->account_class != "standard" && ! empty($website->icon_url) )
            $added_style = "background-image: url('".$website->icon_url."');";
        else
            $added_style = $button->properties->button_logo == "_default_" ? ""
                         : "background-image: url('".$coins_data[$button->properties->button_logo]->logo."');";
        $added_class = empty($added_style) ? "emptied" : "";
    ?>
    <div class="ui-state-highlight message_box ui-corner-all" style="text-align: center;">
        <h1 class="<?=$added_class?>" style="display: inline-block; margin-bottom: 0; border-bottom: none; <?= $added_style?>">
            <?= $button->properties->caption ?>
        </h1>
        <? if( ! empty($button->properties->description) ): ?>
            <? if( $website_account_extended->account_class != "standard" ): ?>
                <br><i><?= html_entity_decode($button->properties->description) ?></i>
            <? endif; ?>
        <? endif; ?>
    </div>

    <!-- Period selector -->
    <? include "period_selector.inc"; ?>

    <!-- Summary -->
    <? include "button_summary.single_button.inc"; ?>

    <!-- Results selector -->
    <? include "results_selector.inc"; ?>

    <!-- Counters -->
    <? if( $_GET["results"] == "counters" && ! empty($button->button_id) && ! empty($website->public_key) ) include "counters.inc"; ?>

    <!-- Table -->
    <? if( $_GET["results"] == "table" && ! empty($button->button_id) && ! empty($website->public_key) ) include "table.inc"; ?>

</form>
