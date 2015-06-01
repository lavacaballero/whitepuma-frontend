<?php
    /**
     * Platform Extension: Websites / Buttons addition/edition dialog
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

    $account_extensions = new account_extensions($account->id_account);
    # $account_extensions = new account_extensions();
?>

<style type="text/css">
    .premium_only.ui-state-error .ui-widget-content  ,
    .premium_only.ui-state-error .ui-state-highlight { color: #ACACAC; }
</style>

<div id="button_form_container" style="display: none">

    <div id="button_form_target" style="display: none"></div>

    <h2 class="new_title ui-widget-header message_box ui-corner-all"  style="margin-top: 0;">
        New button for <span class="website_name"></span>
    </h2>
    <h2 class="edit_title ui-widget-header message_box ui-corner-all" style="margin-top: 0; display: none;">
        Editing button <span class="button_id"></span> for <span class="website_name"></span>
    </h2>

    <form name="button_form" id="button_form" method="post" action="<?=$_SERVER["PHP_SELF"]?>?wasuuup=<?=md5(mt_rand(1,65535))?>">

        <input type="hidden" name="mode" value="insert_button">
        <input type="hidden" name="website_public_key" value="">
        <input type="hidden" name="button_id" value="">

        <div class="notabs">

            <!-- #### -->
            <!-- Name -->
            <!-- #### -->

            <div class="ui-state-active message_box ui-corner-all" style="font-size: 14pt;">
                <span class="fa fa-info-circle"></span>
                Specify a name for this button so you can identify it when you make others.
                <input type="text" name="button_name" value="Default button" maxlength="255" style="width: 100%; font-size: 14pt;">
            </div>

            <!-- ########## -->
            <!-- Appearance -->
            <!-- ########## -->

            <div id="button_appearance" class="dialog_section ui-widget-content message_box ui-corner-all">

                <h2 class="ui-widget-header ui-corner-all" style="padding: 5px; margin: 0 0 10px 0;">Appearance settings</h2>

                <div id="appearance_helper" class="ui-widget-content ui-corner-all appearance_helper_grid" current_color="appearance_helper_grid">
                    <div id="dummy_button" class="<?= $config->buttons_default_selector_raw_classname ?>"
                         is_static="true"
                         button_type="round_button"
                         color_scheme="light_blue"
                         caption="Cryptos accepted here!"
                         default_coin="_none_"
                         button_logo="_default_"
                         drop_shadow="true"></div>
                </div><!-- /#appearance_helper -->

                <div class="controls">

                    <div class="control ui-widget-content message_box ui-corner-all">
                        Preview background color:<br>
                        <select name="dummy[appearance_background]"
                                onchange="change_appearance_helper_background(this.options[this.selectedIndex].value)">
                            <option selected value="appearance_helper_grid"  >Transparent (grid)</option>
                            <option          value="appearance_helper_white" >White</option>
                            <option          value="appearance_helper_black" >Black</option>
                            <option          value="appearance_helper_silver">Silver</option>
                            <option          value="appearance_helper_gray"  >Gray</option>

                        </select>
                    </div><!-- /preview_background -->

                    <div class="control ui-widget-content message_box ui-corner-all">
                        Button type:<br>
                        <select name="type" onchange="update_dummy_button(this, {button_type: this.options[this.selectedIndex].value})">
                            <option selected value="round_button" >Round button (adaptive width)</option>
                            <option          value="round_bar"    >Round bar (full width)</option>
                            <option          value="round_table"  >Round table (full width with details)</option>
                            <option          value="square_button">Square button (adaptive width)</option>
                            <option          value="square_bar"   >Square bar (full width)</option>
                            <option          value="square_table" >Square table (full width with details)</option>
                            <option          value="thin_button"  >Thin button (adaptive width)</option>
                            <option          value="thin_bar"     >Thin bar (full width)</option>
                        </select>
                    </div><!-- /button_type -->

                    <div class="control ui-widget-content message_box ui-corner-all">
                        Color scheme:<br>
                        <select name="color_scheme" onchange="update_dummy_button(this, {color_scheme: this.options[this.selectedIndex].value})">
                            <option          value="red"       >Red</option>
                            <option          value="orange"    >Orange</option>
                            <option          value="yellow"    >Yellow</option>
                            <option          value="green"     >Green</option>
                            <option selected value="light_blue">Light Blue (default)</option>
                            <option          value="blue"     >Blue</option>
                            <option          value="indigo"   >Indigo</option>
                            <option          value="violet"   >Violet</option>
                            <option          value="light"    >Light grey</option>
                            <option          value="dark"     >Dark grey</option>
                        </select>
                    </div><!-- /color_scheme -->

                    <div class="control ui-widget-content message_box ui-corner-all">
                        Button caption:<br>
                        <input type="text" name="properties[caption]" value="Cryptos accepted here!" style="width: 100%; font-size: 12pt;"
                               onkeyup="update_dummy_button(  this, {caption: $.trim(this.value)} )"
                               onchange="update_dummy_button( this, {caption: $.trim(this.value)} )"
                               onblur="update_dummy_button(   this, {caption: $.trim(this.value)} )">
                    </div><!-- /caption -->

                    <div class="control ui-widget-content message_box ui-corner-all">
                        <?
                            $coin_logos = array();
                            foreach($config->current_tipping_provider_data["per_coin_data"] as $coin_name => $coin_data)
                                if( ! $coin_data["coin_disabled"] )
                                    $coin_logos[$coin_name] = $coin_data["coin_image"];
                            ksort($coin_logos);
                            $coin_logos = array_merge(array("_none_" => $config->facebook_canvas_image), $coin_logos);
                            $checked = "";
                        ?>
                        Default coin:<br>
                        <select name="properties[default_coin]" onchange="set_coin_data_from( this.options[this.selectedIndex] );">
                            <? foreach($coin_logos as $coin_name => $logo_url): ?>
                                <option <? if($coin_name == "_none_") echo "selected"; ?> value="<?=$coin_name?>"
                                        min_tip_size="<?= number_format_crypto($config->current_tipping_provider_data["per_coin_data"][$coin_name]["min_transaction_amount"], 8) ?>"
                                        coin_sign="<?= $config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_name_plural"] ?>"><?= $coin_name == "_none_" ? "None (all supported)" : $coin_name ?></option>
                            <? endforeach; ?>
                        </select>
                    </div><!-- /default_coin -->

                    <div class="control ui-widget-content message_box ui-corner-all">
                        <?
                            $coin_logos = array();
                            foreach($config->current_tipping_provider_data["per_coin_data"] as $coin_name => $coin_data)
                                if( ! $coin_data["coin_disabled"] )
                                    $coin_logos[$coin_name] = $coin_data["coin_image"];
                            ksort($coin_logos);
                            $coin_logos = array_merge(
                                array("_default_" => $config->facebook_canvas_image),
                                array("_website_" => $config->buttons_default_website_logo),
                                array("_custom_"  => $config->buttons_default_custom_logo),
                                $coin_logos
                            );
                            $special_names = array(
                                "_default_" => "Default",
                                "_website_" => "(Premium) My website icon (Set it on your website settings)",
                                "_custom_"  => "(Premium) Custom logo (Define it on Advanced Settings)",
                            );
                            $checked = "";
                        ?>
                        Button logo:<br>
                        <select name="properties[button_logo]" onchange="update_dummy_button(this, {button_logo: this.options[this.selectedIndex].value});">
                            <? foreach($coin_logos as $coin_name => $logo_url): ?>
                                <? $disabled = ( $account_extensions->account_class == "standard" &&
                                                 in_array($coin_name, array("_custom_", "_website_")) )
                                             ? "disabled"
                                             : ""; ?>
                                <option <? if($coin_name == "_default_") echo "selected"; ?> value="<?=$coin_name?>" <?=$disabled?>>
                                    <?= isset($special_names[$coin_name]) ? $special_names[$coin_name] : $coin_name ?>
                                </option>
                            <? endforeach; ?>
                        </select>
                    </div><!-- /button_logo -->

                    <div class="control ui-widget-content message_box ui-corner-all">
                        Other options:<br>
                        <label>
                            <input type="checkbox" name="properties[hide_tips_counter]" value="true"
                                   onchange="update_dummy_button(this, {hide_tips_counter: $(this).prop('checked')})">
                            Hide counters
                        </label>
                        <br>

                        <label>
                            <input type="checkbox" checked name="properties[drop_shadow]" value="true"
                                   onchange="update_dummy_button(this, {drop_shadow: $(this).prop('checked')})">
                            Show drop shadow
                        </label>
                        <br>

                        <label>
                            <input type="checkbox" name="properties[inverted_drop_shadow]" value="true"
                                   onchange="update_dummy_button(this, {inverted_drop_shadow: $(this).prop('checked')})">
                            White glow instead of shadow (for dark backgrounds)
                        </label>
                        <br>
                    </div><!-- /options -->

                </div><!-- /.controls -->

            </div><!-- /#button_appearance -->

            <!-- ############# -->
            <!-- Coin settings -->
            <!-- ############# -->

            <div id="button_coin_data" class="dialog_section ui-widget-content message_box ui-corner-all">

                <h2 class="ui-widget-header ui-corner-all" style="padding: 5px; margin: 0 0 10px 0;">Coin settings</h2>

                <div class="intro ui-state-highlight message_box ui-corner-all" style="text-align: left;">
                    <span class="fa fa-info-circle"></span>
                    Use these fields to specify how much do you want to get and how the user is queried about the amount(s).
                    Please note that every supported coin has a minimum transaction size, so if you specify an amount below
                    that it will be overriden when presented to the user.
                </div>

                <div class="ui-widget-content message_box ui-corner-all">
                    Coins request type:
                    <select name="properties[request_type]" onchange="">
                        <option selected value="suggestion" >Suggest the amount(s), accept anything above that</option>
                        <option          value="fixed"      >Fixed request</option>
                    </select>
                </div>

                <div class="controls">

                    <div class="control single_coin ui-widget-content message_box ui-corner-all">

                        <h3 class="ui-widget-header ui-corner-all" style="padding: 5px; margin: 0 0 10px 0;">
                            <label>
                                <input type="radio" name="properties[coin_scheme]" value="single_from_default_coin"
                                       onchange="toggle_coin_controls( this.value )">
                                Single coin scheme
                            </label>
                        </h3>

                        Amount requested (Min is <span class="minimum_tip_size_for_current_coin">N/A</span> <span class="current_coin_sign">N/A</span>):<br>
                        <input type="text" autocomplete="off" name="properties[coin_amount]" value="0" style="width: 100%; text-align: center;">
                    </div><!-- /.control.single_coin -->

                    <div class="control multi_direct ui-widget-content message_box ui-corner-all">

                        <h3 class="ui-widget-header ui-corner-all" style="padding: 5px; margin: 0 0 10px 0;">
                            <label>
                                <input type="radio" checked name="properties[coin_scheme]" value="multi_direct" onchange="toggle_coin_controls( this.value )">
                                Multiple coins, per-coin direct values
                            </label>
                        </h3>

                        <table class="tablesorter" width="100%" cellpadding="1" cellspacing="1">
                            <thead>
                                <tr align="center">
                                    <th class="{sorter: false}">
                                        <button all_checked="false" onclick="toggle_table_checkboxes(this); return false;">
                                            <span class="fa fa-flag"></span>
                                        </button>
                                    </th>
                                    <th class="{sorter: false}">Coin</th>
                                    <th class="{sorter: false}">Amount</th>
                                    <th class="{sorter: false}">&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody>
                                <? foreach($coin_logos as $coin_name => $logo_url): ?>
                                    <? if( in_array($coin_name, array("_none_", "_default_", "_website_", "_custom_")) ) continue; ?>
                                    <tr align="center">
                                        <td>
                                            <input type="checkbox" name="properties[per_coin_requests][direct][<?=$coin_name?>][show]" value="true"
                                                   onchange="$(this).closest('tr').toggleClass('selected', $(this).prop('checked'))">
                                        </td>
                                        <td><?=$coin_name?></td>
                                        <td><input type="text" autocomplete="off" name="properties[per_coin_requests][direct][<?=$coin_name?>][amount]"
                                                   value="<?= number_format_crypto($config->current_tipping_provider_data["per_coin_data"][$coin_name]["min_transaction_amount"], 8) ?>"
                                                   minimum="<?= number_format_crypto($config->current_tipping_provider_data["per_coin_data"][$coin_name]["min_transaction_amount"], 8) ?>"
                                                   class="fixed_font" style="text-align: right; width: 60px;"
                                                   onkeyup="check_direct_value(this)"
                                                   onchange="check_direct_value(this)"
                                                   onblur="check_direct_value(this)"></td>
                                        <td><?= $config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_name_plural"] ?></td>
                                    </tr>
                                <? endforeach; ?>
                            </tbody>
                        </table>

                    </div><!-- /.control.multi_direct -->

                    <div class="control multi_converted ui-widget-content message_box ui-corner-all">

                        <h3 class="ui-widget-header ui-corner-all" style="padding: 5px; margin: 0 0 10px 0;">
                            <label>
                                <input type="radio" name="properties[coin_scheme]" value="multi_converted"
                                       onchange="toggle_coin_controls( this.value )">
                                Multiple coins, converted from dollar value
                            </label>
                        </h3>

                        Amount requested in USD:<br>
                        <input type="text" autocomplete="off" disabled name="properties[amount_in_usd]" value="0"
                               style="width: 100%; text-align: center;"
                               onkeyup="update_prices()"
                               onchange="update_prices()"
                               onblur="update_prices()">
                        <br><br>

                        Per coin conversions as of latest ticker update:
                        <span id="errored_conversion" style="color: red; display: none;" class="fa fa-warning" title="At least one coin is below the minimum. Please adjust the USD amount."></span><br>
                        <table class="tablesorter" width="100%" cellpadding="1" cellspacing="1">
                            <thead>
                                <tr align="center">
                                    <th class="{sorter: false}">
                                        <button all_checked="false" onclick="toggle_table_checkboxes(this); return false;">
                                            <span class="fa fa-flag"></span>
                                        </button>
                                    </th>
                                    <th class="">Coin</th>
                                    <th class="">You get</th>
                                    <th class="{sorter: false}">&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody>
                                <? foreach($coin_logos as $coin_name => $logo_url): ?>
                                    <?
                                        if( in_array($coin_name, array("_none_", "_default_", "_website_", "_custom_")) ) continue;
                                        $query = "select * from ".$config->db_tables["coin_prices"]." where coin_name = '$coin_name' order by date desc limit 1";
                                        $res   = mysql_query($query);
                                        if( mysql_num_rows($res) == 0 ) $row = (object) array("price" => 0);
                                        else                            $row = mysql_fetch_object($res);
                                    ?>
                                    <tr align="center">
                                        <td>
                                            <input type="checkbox" disabled name="properties[per_coin_requests][from_usd][<?=$coin_name?>][show]" value="true"
                                                   onchange="$(this).closest('tr').toggleClass('selected', $(this).prop('checked'))">
                                        </td>
                                        <td><?=$coin_name?></td>
                                        <td align="right">
                                            <span class="fixed_font converted_from_usd" coin_name="<?=$coin_name?>"
                                                  minimum="<?= number_format_crypto($config->current_tipping_provider_data["per_coin_data"][$coin_name]["min_transaction_amount"], 8) ?>"
                                                  dollar_price="<?= ($row->price) ?>"><?= $row->price == 0 ? 0 : round(1 / $row->price, 8) ?></span>
                                        </td>
                                        <td><?= $config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_name_plural"] ?></td>
                                    </tr>
                                <? endforeach; ?>
                            </tbody>
                        </table>

                    </div><!-- /.control.multi_converted -->

                </div><!-- /.controls -->

            </div><!-- /#button_coin_data -->

            <!-- ######## -->
            <!-- Advanced -->
            <!-- ######## -->

            <div id="button_advanced">

                <div class="optionals dialog_section ui-widget-content message_box ui-corner-all">

                    <h2 class="ui-widget-header ui-corner-all" style="padding: 5px; margin: 0 0 10px 0;">Advanced settings</h2>

                    <div class="ui-widget-content message_box ui-corner-all">
                        [Optional] Entry identifier:<br>
                        <input type="text" name="properties[entry_id]" style="width: 100%;"><br>
                        <div class="intro ui-state-highlight message_box ui-corner-all" style="margin-bottom: 0;">
                            <span class="fa fa-info-circle"></span>
                            Use this field if you want to identify the transaction with a specific id (i.e. the URL of a document, the SKU of an item, a permalink to a comment, etc.).
                            If you do not provide it, the URL of the document where the button is embedded will be used as the id.
                        </div>
                    </div>

                    <div class="ui-widget-content message_box ui-corner-all">
                        [Optional] Entry title:<br>
                        <input type="text" name="properties[entry_title]" style="width: 100%;"><br>
                        <div class="intro ui-state-highlight message_box ui-corner-all" style="margin-bottom: 0;">
                            <span class="fa fa-info-circle"></span>
                            If you're providing an entry identifier above, you can also put a title for the entry (i.e. the title of a document, the name of an item, a comment, etc.).
                            If you don't provide it, the title will be set from the &lt;head&gt; of the document where the button is embedded,
                            firstly looking into the &lt;meta name="description"&gt; tag and, if empty, from the &lt;title&gt; tag.
                        </div>
                    </div>

                    <div class="ui-widget-content message_box ui-corner-all">
                        [Optional] Referral codes:<br>
                        <textarea class="expandible_textarea" name="properties[referral_codes]"></textarea>
                        <div class="intro ui-state-highlight message_box ui-corner-all" style="margin-bottom: 0;">
                            <span class="fa fa-info-circle"></span>
                            If you want to share this button with websites other than yours, you can specify a comma-separated
                            list of keywords (i.e. <span class="ui-state-active ui-corner-all">moms_blog, cousins_store, my_other_website</span>)
                            to properly identify where the coins are coming from.<br>
                            The list you provide here is only used as a helper so you can have a reference of referral codes being created by you,
                            specially because referral codes are completely arbitrary in the invocation code and it is highly likely for you
                            to have unknown referrals in your button's analytics.
                            <span class="fa fa-warning"></span>
                            <b>Important:</b> leeching restrictions set on your website or other websites may prevent registering the referral codes.
                            If you use this feature, be sure to check your website's leeching settings and valid URLs and, when inserting the button,
                            make sure the host website allows leeching.
                        </div>
                    </div>

                    <div class="<?=$inner_class?> message_box ui-corner-all">
                        <label><b>
                            <input type="checkbox" <?= $disabled ?> name="properties[allow_target_overrides]" value="true">
                            Allow target overriding.
                        </b></label>
                        Normally, the coins will go to your account. By checking this option, you'll allow the button to receive
                        a different target for the operations.
                        <div class="ui-state-highlight message_box ui-corner-all" style="margin-bottom: 0;">
                            <span class="fa fa-info-circle"></span>
                            Use this option only if the button is going to be used on different sections with the same context
                            and you can programmatically set the target recipient, i.e.
                            below a post on a forum so the poster gets tipped and below every reply so the replier gets tipped.
                        </div>
                    </div>

                </div><!-- /.optionals -->

                <?
                    $disabled       = $account_extensions->account_class == "standard" ? "disabled" : "";
                    $class          = $account_extensions->account_class == "standard" ? "ui-state-error"    : "ui-state-default";
                    $inner_class    = $account_extensions->account_class == "standard" ? "ui-widget-content" : "ui-state-default";
                ?>

                <div class="premium_only dialog_section <?=$class?> message_box ui-corner-all" style="<?=$style_addition?>">

                    <h2 class="ui-widget-header ui-corner-all" style="padding: 5px; margin: 0 0 10px 0;">Premium settings</h2>

                    <? if($account_extensions->account_class == "standard"): ?>
                        <a href="<?=$config->website_pages["premium_account_info_page"]?>" target="_blank" class="ui-state-active message_box ui-corner-all"
                           style="display: block; font-size: 14pt;">
                            <span class="fa fa-star"></span>
                            Get access to these and other cool settings by upgrading your account to premium!
                            Click here for more information!
                        </a>
                    <? endif; ?>

                    <div class="<?=$inner_class?> message_box ui-corner-all">
                        Description for the widget:<br>
                        <textarea name="properties[description]" class="expandible_textarea" maxlength="500"></textarea>
                        <div class="intro ui-state-highlight message_box ui-corner-all" style="margin-bottom: 0;">
                            <span class="fa fa-info-circle"></span>
                            When a user clicks on the button or opens the widget on a browser window,
                            the request details are shown. You can override them with a concise description
                            for your button, I.E. why are you requesting coins, what are you going to give
                            in return, etc.<br>
                            Up to 500 characters. HTML tags allowed: &lt;b&gt; &lt;i&gt; &lt;u&gt; &lt;a&gt;
                        </div>
                    </div>

                    <div class="<?=$inner_class?> message_box ui-corner-all">
                        Custom logo URL:<br>
                        <input type="text" <?= $disabled ?> name="properties[custom_logo]" style="width: 100%;"><br>
                        <div class="intro ui-state-highlight message_box ui-corner-all" style="margin-bottom: 0;">
                            <span class="fa fa-info-circle"></span>
                            Please specify the URL (HTTPS preferred) of a 256x256 PNG logo with transparent background
                            for your button.
                        </div>
                    </div>

                    <div class="<?=$inner_class?> message_box ui-corner-all">
                        Callback URL:<br>
                        <table cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr valign="middle">
                                <td width="100%">
                                    <input type="text" <?= $disabled ?> name="properties[callback]" style="width: 100%;"><br>
                                </td>
                                <td nowrap style="padding-left: 10px">
                                    <button <?= $disabled ?> class="smaller" onclick="test_ipn(); return false;">
                                        Test
                                    </button>
                                </td>
                            </tr>
                        </table>
                        <div class="intro ui-state-highlight message_box ui-corner-all" style="margin-bottom: 0;">
                            <span class="fa fa-info-circle"></span>
                            If you want our system to post all data to your website when a transaction arrives, specify the URL of the receiving script here.
                        </div>
                    </div>

                    <div class="<?=$inner_class?> message_box ui-corner-all">
                        <label><b>
                            <input type="checkbox" <?= $disabled ?> name="properties[private_basic_analytics]" value="true">
                            Restrict public access to basic analytics.
                        </b></label>
                        If this option is enabled, nobody but you will be able to access basic button analytics.
                    </div>

                </div><!-- /.premium_only -->

            </div><!-- /#button_advanced -->

        </div><!-- /.tabs -->

        <div id="save_to_generate" align="center" class="ui-state-highlight message_box ui-corner-all">
            <span class="fa fa-info-circle"></span>
            Once the button is saved, you will be able to generate the invocation code.
        </div>

        <div align="center">
            <button type="submit">
                <span class="fa fa-check"></span>
                Save
            </button>
            <button type="reset" onclick="hide_button_form_dialog()">
                <span class="fa fa-ban"></span>
                Cancel
            </button>
        </div>

    </form>

</div><!-- /#button_form_container -->
