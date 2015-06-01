<?php
    /**
     * Platform Extension: Websites / iframed widget / Application itself
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
?>
<? include "index.contents.user_info.inc"; ?>

<div id="submission_target" style=""></div>

<form name="transaction_submission" id="transaction_submission" method="post" action="send_transaction.php">

    <input type="hidden" name="token"              value="<?= stripslashes($_REQUEST["token"]) ?>">
    <input type="hidden" name="website_public_key" value="<?= stripslashes($_REQUEST["website_public_key"]) ?>">
    <input type="hidden" name="button_id"          value="<?= stripslashes($_REQUEST["button_id"]) ?>">
    <input type="hidden" name="ref"                value="<?= htmlspecialchars(stripslashes(trim($_REQUEST["ref"]))) ?>">
    <input type="hidden" name="entry_id"           value="<?= htmlspecialchars(stripslashes(trim($_REQUEST["entry_id"]))) ?>">
    <input type="hidden" name="entry_title"        value="<?= htmlspecialchars(stripslashes(trim($_REQUEST["entry_title"]))) ?>">
    <input type="hidden" name="target_data"        value="<?= htmlspecialchars(stripslashes(trim($_REQUEST["target_data"]))) ?>">
    <input type="hidden" name="http_referer"       value="<?= htmlspecialchars(stripslashes(trim($_SERVER["HTTP_REFERER"]))) ?>">
    <input type="hidden" name="amount_in_usd"      value="<?= stripslashes($_REQUEST["amount_in_usd"]) ?>">

    <div id="preinits">
        <!--
        <?
            if( ! empty ($website->icon_url) ) $added_style = "background-image: url('".$website->icon_url."');";
            else                               $added_style = $button->properties->button_logo == "_default_" ? "" : "background-image: url('".$coins_data[$button->properties->button_logo]->logo."');";
            $added_class = empty($added_style) ? "emptied" : "";
        ?>
        <h1 class="<?=$added_class?>" style="<?= $added_style?>">
            <?= $button->properties->caption ?>
        </h1>
        -->

        <?
            $website_owner = new account($website->id_account);
            $website_owner_extended_data = new account_extensions($website->id_account);

            # Control array for components
            $coins = array();
            if( $button->properties->coin_scheme == "single_from_default_coin" )
            {
                $coins[$button->properties->default_coin] = $coins_data[$button->properties->default_coin];
                $coins[$button->properties->default_coin]->amount_wanted = $button->properties->coin_amount;
            }
            elseif( $button->properties->coin_scheme == "multi_direct" )
            {
                foreach($button->properties->per_coin_requests as $coin_name => $coin_data)
                {
                    if( $coin_data->show != "true" ) continue;
                    if( ! isset($coins_data[$coin_name]) ) continue;
                    $coins[$coin_name] = $coins_data[$coin_name];
                    $coins[$coin_name]->amount_wanted = $coin_data->amount;
                } # end foreach
            }
            elseif( $button->properties->coin_scheme == "multi_converted" )
            {
                foreach($button->properties->per_coin_requests as $coin_name => $coin_data)
                {
                    if( $coin_data->show != "true" ) continue;
                    if( ! isset($coins_data[$coin_name]) ) continue;
                    $coins[$coin_name] = $coins_data[$coin_name];
                } # end foreach
            } # end if

            # Descriptions of what to do
            $disallow_balance_translation = true;
            $request_details = $fulfill_legend = "";
            if( $button->properties->amount_in_usd < 1 && substr($button->properties->amount_in_usd, 0, 1) != "0" )
                $button->properties->amount_in_usd = "0".$button->properties->amount_in_usd;
            if( $button->properties->request_type == "fixed" )
            {
                if( $button->properties->coin_scheme == "single_from_default_coin" )
                {
                    $request_details = "A <u>fixed</u> amount of <u>".($button->properties->coin_amount)." ".$button->properties->default_coin."</u> is being requested.";
                    $fulfill_legend  = "Review the request and submit the transaction.";
                }
                elseif( $button->properties->coin_scheme == "multi_direct" )
                {
                    $request_details = "A <u>fixed</u> amount <u>from any of ".count($coins)." coins</u> is being requested.
                                        Only one of the listed coins should be sent.";
                    $fulfill_legend  = "Select the coin you want to use to fulfill the request. You can't change the amounts.";
                }
                elseif( $button->properties->coin_scheme == "multi_converted" )
                {
                    $disallow_balance_translation = false;
                    $request_details = "A <u>fixed</u> amount <u>of USD \$".$button->properties->amount_in_usd."</u>
                                        worth in any of ".count($coins)." coins is being requested.
                                        At least one of the listed coins should be selected for sending.";
                    $fulfill_legend  = "Select the coins you want to use and adjust the amounts until their USD value is
                                        <u>equal or greater</u> than the requested amount.<br>
                                        <b>Note:</b> USD prices are estimated.";
                } # end if
            }
            elseif( $button->properties->request_type == "suggestion" )
            {
                if( $button->properties->coin_scheme == "single_from_default_coin" )
                {
                    $disallow_balance_translation = false;
                    $request_details = "A <u>suggested</u> amount of
                                        <u>".$button->properties->coin_amount." ".$button->properties->default_coin."</u>
                                        is being requested as minimum.
                                        It has been preset, but you can increase it if you want.";
                    $fulfill_legend  = "Review the amount and increase it if you want before submitting the transaction.";
                }
                elseif( $button->properties->coin_scheme == "multi_direct" )
                {
                    $disallow_balance_translation = false;
                    $request_details = "A <u>suggested</u> amount <u>from any of ".count($coins)." coins</u> is being requested.
                                        All coins have been preset to the requested minimum for you to decide which
                                        of them and how much is to be sent (at least one should be selected).";
                    $fulfill_legend  = "Select the coins and set the amounts you want to send before submitting the transaction.";
                }
                elseif( $button->properties->coin_scheme == "multi_converted" )
                {
                    $disallow_balance_translation = false;
                    $request_details = "A <u>suggested</u> amount <u>of USD \$".$button->properties->amount_in_usd." </u>
                                        worth in any of ".count($coins)." coins is being requested.
                                        You may select the coins and amounts for each one you want to send with a
                                        minimum USD sum.";
                    $fulfill_legend  = "Select the coins you want to use and adjust the amounts until their USD value is
                                        <u>equal or greater</u> than the requested amount.
                                        <b>Note:</b> USD prices are estimated.";
                } # end if
            } # end if

            if( ! empty($target_data->email) )
            {
                if( ! $target_data->exists )
                    $request_details .= "<br><br>The recipient for this transaction will be $target_data->email.";
                else
                    $request_details .= "<br><br>The recipient for this transaction will be [$target_data->id_account] $target_data->name.";
            } # end if

            if( $website_owner_extended_data->account_class != "standard" &&
                ! empty($button->properties->description) )
                $request_details = html_entity_decode($button->properties->description)
                                 . "<div class='automated_details ui-widget-content ui-corner-all'>"
                                 . $request_details
                                 . "</div>";
        ?>
    </div><!-- /#preinits -->

    <div class="block transaction_details" style="padding-top: 0;">

        <table>
            <tr>
                <td class="left">
                    <div style="max-height: 200px; overflow: auto;">
                        <b>Request details:</b>
                        <blockquote>
                            <?= $request_details ?>
                        </blockquote>
                    </div>
                </td>
                <td class="right">
                    <div style="max-height: 200px; overflow: auto;">
                        <b>Record data:</b>
                        <blockquote>
                            Identifier:
                            <blockquote><?= $button->properties->entry_id ?></blockquote>

                            Title:
                            <blockquote><?= $button->properties->entry_title ?></blockquote>

                            <? if( ! empty($target_data->email) ): ?>
                                <? if( ! $target_data->exists ): ?>
                                    Target:
                                    <blockquote><?= $target_data->email ?></blockquote>
                                <? else: ?>
                                    Target:
                                    <blockquote>[<?= $target_data->id_account ?>] <?= $target_data->name ?></blockquote>
                                <? endif; ?>
                            <? endif; ?>

                            <? if( ! empty($_REQUEST["ref"]) ): ?>
                                Referrral:
                                <blockquote><?= stripslashes($_REQUEST["ref"]) ?></blockquote>
                            <? endif; ?>
                        </blockquote>
                    </div>
                </td>
            </tr>
        </table>

    </div><!-- /.block.transaction_details -->

    <div class="block delivery_info">

        <table>
            <tr>

                <? if( $website->icon_url == "%leech_jar_url%" ) $website->icon_url = $config->buttons_leech_jar_public_url; ?>
                <? if( empty($invoker_website->name) && empty($target_data->email) ): ?>
                    <? # Sending directly to the website owner ?>

                    <td width="50%">
                        <span class="fa fa-border fa-user fa-2x"></span><br>
                        You
                    </td>
                    <td class="arrow"><span class="fa fa-arrow-right fa-2x"></span></td>
                    <td width="50%">
                        <? if( ! empty($website->icon_url) ): ?>
                            <img src="<?=$website->icon_url?>">
                        <? else: ?>
                            <span class="fa fa-globe fa-2x"></span>
                        <? endif; ?>
                        <br><i><?=$website->name ?></i>
                        <br><?=$website_owner->name ?>
                    </td>

                <? elseif( empty($invoker_website->name) && ! empty($target_data->email)): ?>
                    <? # Sending to another user within the website ?>

                    <td width="50%">
                        <span class="fa fa-border fa-user fa-2x"></span><br>
                        You
                    </td>
                    <td class="arrow"><span class="fa fa-arrow-right fa-2x"></span></td>
                    <td width="50%">
                        <? if( ! empty($website->icon_url) ): ?>
                            <img src="<?=$website->icon_url?>">
                        <? else: ?>
                            <span class="fa fa-globe fa-2x"></span>
                        <? endif; ?>
                        <br><i><?=$website->name ?></i>
                        <br><?= $target_data->exists ? "[$target_data->id_account] $target_data->name" : $target_data->email ?>
                    </td>

                <? elseif( ! empty($invoker_website->name) && empty($target_data->email) ): ?>
                    <? # Direct leeching: another owner of another website ?>
                    <? $leeching_website = $website; ?>
                    <? $leeching_user    = new account($leeching_website->id_account); ?>

                    <td width="33%">
                        <span class="fa fa-border fa-user fa-2x"></span><br>
                        You
                    </td>
                    <td class="arrow"><span class="fa fa-arrow-right fa-2x"></span></td>
                    <td width="33%">
                        <? if( ! empty($invoker_website->icon_url) ): ?>
                            <img src="<?=$invoker_website->icon_url?>">
                        <? else: ?>
                            <span class="fa fa-globe fa-2x"></span>
                        <? endif; ?>
                        <br><i><?=$invoker_website->name ?></i>
                    </td>
                    <td class="arrow"><span class="fa fa-arrow-right fa-2x"></span></td>
                    <td width="33%">
                        <? if( ! empty($leeching_website->icon_url) ): ?>
                            <img src="<?=$leeching_website->icon_url?>">
                        <? else: ?>
                            <span class="fa fa-globe fa-2x"></span>
                        <? endif; ?>
                        <br><i><?=$leeching_website->name ?></i>
                        <br><?= $leeching_user->name ?>
                    </td>

                <? elseif( ! empty($invoker_website->name) && ! empty($target_data->email) ): ?>
                    <? # Blatant leeching: another target of another website ?>
                    <? $leeching_website = $website; ?>
                    <? $leeching_account  = new account($leeching_website->id_account); ?>

                    <td width="33%">
                        <span class="fa fa-border fa-user fa-2x"></span><br>
                        You
                    </td>
                    <td class="arrow"><span class="fa fa-arrow-right fa-2x"></span></td>
                    <td width="33%">
                        <? if( ! empty($invoker_website->icon_url) ): ?>
                            <img src="<?=$invoker_website->icon_url?>">
                        <? else: ?>
                            <span class="fa fa-globe fa-2x"></span>
                        <? endif; ?>
                        <br><i><?=$invoker_website->name ?></i>
                    </td>
                    <td class="arrow"><span class="fa fa-arrow-right fa-2x"></span></td>
                    <td width="33%">
                        <? if( ! empty($leeching_website->icon_url) ): ?>
                            <img src="<?=$leeching_website->icon_url?>">
                        <? else: ?>
                            <span class="fa fa-globe fa-2x"></span>
                        <? endif; ?>
                        <br><i><?=$leeching_website->name ?></i>
                        <br><?= $target_data->exists ? "[$target_data->id_account] $target_data->name" : $target_data->email ?>
                        <br>Through <?= $leeching_account->name ?>
                    </td>

                <? endif; ?>

            </tr>
        </table>

    </div><!-- /.block.delivery_info -->

    <? if( $button->properties->coin_scheme != "single_from_default_coin" ): ?>

        <div id="review_and_continue" class="ui-state-highlight message_box ui-corner-all" style="text-align: center;">
            <span class="fa fa-info-circle"></span>
            This request supports <u><?= count($coins) ?></u> coins.
            It may take from some seconds to a couple of minutes to get all your wallet balances.
            <p align="center">
                <button big onclick="$('#review_and_continue, .block.delivery_info').hide(); $('.coins_component').show(); get_wallet_balances(); update_all_coins_usd_value(); return false;">
                    Proceed
                    <span class="fa fa-play"></span>
                </button>
            </p>
        </div>

    <? endif; ?>

    <div class="coins_component" request_type="<?=$button->properties->request_type?>"
         style="<? if( $button->properties->coin_scheme != "single_from_default_coin" ) echo "display: none"; ?>">

        <div class="ui-state-highlight message_box ui-corner-all" style="text-align: center;">
            <?= $fulfill_legend ?>
        </div>

        <table>

            <thead>
                <tr>
                    <? if( $button->properties->coin_scheme == "single_from_default_coin" ): ?>
                        <th colspan="7" style="text-align: left;">
                            Your balance:
                            <span class="balance_target <? if($disallow_balance_translation) echo "disabled" ?>"
                                  coin_name="<?= $button->properties->default_coin ?>" minified_balance="" real_balance="0"
                                  onclick="set_all_balance_from(this)">
                                N/A
                            </span>
                            <?= $coins[$button->properties->default_coin]->symbol ?>
                            <button onclick="get_wallet_balances(); return false;">
                                <span class="fa fa-briefcase"></span>
                                Update
                            </button>
                        </th>
                        <th colspan="4" style="text-align: right;">
                            Est. Dollar value:
                            <span class="usd_sum showme_on_short">N/A</span>
                        </th>
                        <th class="hideme_on_short">&nbsp;</th>
                        <th class="usd_sum hideme_on_short">N/A</th>
                    <? elseif( $button->properties->coin_scheme == "multi_direct" ): ?>
                        <th colspan="6" style="text-align: left;">
                            <!--
                            <button onclick="toggle_empty_coins(); return false;">
                                <span class="fa fa-eye"></span>
                                Toggle blanks
                            </button>
                            -->
                            <button onclick="get_wallet_balances(); return false;">
                                <span class="fa fa-briefcase"></span>
                                Update balances
                            </button>
                        </th>
                        <th colspan="5" style="text-align: right;">
                            Est. Dollar value of selected coins:
                            <span class="usd_sum showme_on_short">N/A</span>
                        </th>
                        <th class="hideme_on_short">&nbsp;</th>
                        <th class="usd_sum hideme_on_short">N/A</th>
                    <? elseif( $button->properties->coin_scheme == "multi_converted" ): ?>
                        <th colspan="6" style="text-align: left;">
                            <!--
                            <button onclick="toggle_empty_coins(); return false;">
                                <span class="fa fa-eye"></span>
                                Toggle blanks
                            </button>
                            -->
                            <button onclick="get_wallet_balances(); return false;">
                                <span class="fa fa-briefcase"></span>
                                Update balances
                            </button>
                        </th>
                        <th colspan="5" style="text-align: right;">
                            Requested:
                            $<span id="usd_wanted"><?=$button->properties->amount_in_usd?></span>
                            &nbsp;
                            Selected:
                            <span class="usd_sum">N/A</span>
                            &nbsp;
                            Remaining:
                            <span id="surpassed_hit" style="display: none; color: blue; cursor: pointer;"
                                  class="fa fa-info-circle"
                                  onclick="alert('You\'ve surpassed the requested amount of USD. You may want to readjust your selections down in order to bring it closer.')"></span>
                            <span class="usd_remaining showme_on_short">N/A</span>
                        </th>
                        <th class="hideme_on_short">&nbsp;</th>
                        <th class="usd_remaining hideme_on_short">N/A</th>
                    <? endif; ?>
                </tr>
            </thead>

            <tbody>

                <? foreach($coins as $coin_name => $coin_data): ?>

                    <tr class="coin <? if( $button->properties->coin_scheme == "single_from_default_coin" ) echo "selected"?>" coin_name="<?=$coin_name?>">
                        <td>
                            <? if( $button->properties->coin_scheme == "single_from_default_coin" ): ?>
                                <input type="radio" class="selected_coin" coin_name="<?=$coin_name?>" name="selected" id="sel_<?=$coin_name?>" value="<?=$coin_name?>" checked>
                            <? elseif( $button->properties->coin_scheme == "multi_direct" ): ?>
                                <? if($button->properties->request_type == "suggestion"): ?>
                                    <input type="checkbox" class="selected_coin" coin_name="<?=$coin_name?>" name="selected[<?=$coin_name?>]" id="sel_<?=$coin_name?>" value="true"
                                           onchange="$(this).closest('tr').toggleClass('selected', $(this).is(':checked')); update_coin_usd_value('<?=$coin_name?>');">
                                <? elseif($button->properties->request_type == "fixed"): ?>
                                    <input type="radio" class="selected_coin" coin_name="<?=$coin_name?>" name="selected" id="sel_<?=$coin_name?>" value="<?=$coin_name?>"
                                           onchange="$(this).closest('tbody').find('tr').toggleClass('selected', false); $(this).closest('tr').toggleClass('selected', true); update_coin_usd_value('<?=$coin_name?>');">
                                <? endif; ?>
                            <? elseif( $button->properties->coin_scheme == "multi_converted" ): ?>
                                    <input type="checkbox" class="selected_coin" coin_name="<?=$coin_name?>" name="selected[<?=$coin_name?>]" id="sel_<?=$coin_name?>" value="true"
                                           onchange="$(this).closest('tr').toggleClass('selected', $(this).is(':checked')); update_coin_usd_value('<?=$coin_name?>');">
                            <? endif; ?>
                        </td>
                        <td>&nbsp;</td>
                        <td class="image"><label for="sel_<?=$coin_name?>"><img src="<?=$coin_data->logo?>"></label></td>
                        <td>&nbsp;</td>
                        <td class="coin_name hideme_on_short">
                            <span class="name"><?=$coin_name?></span><br>
                            <span class="coin_symbol">
                                <? if( $button->properties->coin_scheme != "single_from_default_coin" ): ?>
                                    <span class="balance_target <? if($disallow_balance_translation) echo "disabled" ?>"
                                          coin_name="<?= $coin_name ?>" minified_balance="" real_balance="0"
                                          onclick="set_all_balance_from(this)">
                                        N/A
                                    </span>
                                <? endif; ?>
                                <?= $coin_data->symbol ?>
                            </span>
                        </td>
                        <td class="hideme_on_short">&nbsp;</td>
                        <td class="control">
                            <button <? if($button->properties->request_type == "fixed" && $button->properties->coin_scheme != "multi_converted") echo "disabled"; ?>
                                    onclick="change_amount('<?=$coin_name?>', 'down'); return false;"
                                    title="Decrease by <?=$coin_data->min_tip?> <?= $coin_data->symbol ?>">
                                <span class="fa fa-minus fa-fw"></span>
                            </button>
                        </td>
                        <td class="amount">
                            <?
                                if( $button->properties->coin_scheme == "single_from_default_coin" )
                                    $value = $button->properties->coin_amount;
                                elseif( $button->properties->coin_scheme == "multi_direct" )
                                    $value = $button->properties->per_coin_requests->{$coin_name}->amount;
                                elseif( $button->properties->coin_scheme == "multi_converted" )
                                    $value = $coin_data->min_tip;
                            ?>

                            <input type="text" <? if($button->properties->request_type == "fixed" && $button->properties->coin_scheme != "multi_converted") echo "readonly"; ?>
                                   class="coin_amount_target" name="amount[<?=$coin_name?>]" coin_name="<?=$coin_name?>"
                                   value="<?=$value?>" minimum="<?=$coin_data->min_tip?>" lower_limit="<?=$value?>"
                                   dollar_price="<?= $usd_prices[$coin_name] ?>"
                                   autocomplete="off"
                                   onchange="check_direct_value(this); update_coin_usd_value('<?=$coin_name?>')"
                                   onkeyup="check_direct_value(this); update_coin_usd_value('<?=$coin_name?>')"
                                   onblur="check_direct_value(this); update_coin_usd_value('<?=$coin_name?>')">

                            <div class="showme_on_short">
                                <span class="name"><?=$coin_name?></span><br>
                                <span class="coin_symbol">
                                    <? if( $button->properties->coin_scheme != "single_from_default_coin" ): ?>
                                        <span class="balance_target <? if($disallow_balance_translation) echo "disabled" ?>"
                                              coin_name="<?= $coin_name ?>" minified_balance="" real_balance="0"
                                              onclick="set_all_balance_from(this)">
                                            N/A
                                        </span>
                                    <? endif; ?>
                                    <?= $coin_data->symbol ?>
                                </span>
                            </div>
                            <span class="showme_on_short"><br>USD:
                                <span class="usd_value" for_coin="<?=$coin_name?>">
                                    N/A
                                </span>
                            </span>
                        </td>
                        <td class="control">
                            <button <? if($button->properties->request_type == "fixed" && $button->properties->coin_scheme != "multi_converted") echo "disabled"; ?>
                                    onclick="change_amount('<?=$coin_name?>', 'up'); return false;"
                                    title="Increase by <?=$coin_data->min_tip?> <?= $coin_data->symbol ?>">
                                <span class="fa fa-plus fa-fw"></span>
                            </button>
                        </td>
                        <td>&nbsp;</td>
                        <td class="symbol"></td>
                        <td class="hideme_on_short">&nbsp;</td>
                        <td class="usd_value hideme_on_short" for_coin="<?=$coin_name?>">
                            N/A
                        </td>
                        <td>&nbsp;</td>
                    </tr><!-- /.coin -->

                <? endforeach; ?>

            </tbody>

        </table>

        <? if( $button->properties->coin_scheme == "single_from_default_coin" ): ?>
            <script type="text/javascript"> $(document).ready(function() { get_wallet_balances(); update_coin_usd_value('<?=$coin_name?>') }); </script>
        <? endif; ?>

        <p align="center">
            <button big type="reset" onclick="reset_form(); return false;">
                <span class="fa fa-undo"></span>
                Start over
            </button>

            <button big type="submit">
                Proceed
                <span class="fa fa-play"></span>
            </button>
        </p>

    </div><!-- /.coins_component -->

</form>
