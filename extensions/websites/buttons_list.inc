<?php
    /**
     * Platform Extension: Websites / Websites list / Buttons for a website
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

    while($row2 = mysql_fetch_object($res2))
    {
        $class      = $row2->state == "enabled" ? "ui-widget-content" : "ui-state-error";
        $properties = json_decode($row2->properties);
        ?>

        <div class="button_container message_box <?=$class?> ui-corner-all" website="<?=$row->public_key?>" button_id="<?=$row2->button_id?>">

            <h4 class="ui-widget-header message_box ui-corner-all" style="padding: 10px; margin-top: 0; margin-bottom: 10px;">

                <span class="ui-widget-content ui-corner-all fixed_font"
                      style="font-weight: normal; font-size: 8pt; padding: 1px 3px; position: relative; top: -2px; cursor: pointer"
                      onclick="prompt('This is your button id:', '<?=$row2->button_id?>')"
                      title="Click to copy your button id"
                      ><?=$row2->button_id?></span>

                <?=$row2->button_name?>

                <span class="button_actions">

                    <button href="<?=$config->buttons_buttonizer_invocator_url?>?website_public_key=<?=$row->public_key?>&button_id=<?=$row2->button_id?>"
                            title="Live preview on a new window" style="margin: 2px 4px; padding-bottom: 2px;"
                            onclick="window.open($(this).attr('href'),'button_preview','directories=no,width=720,height=540,left='+((screen.width-720)/2)+',top='+(((screen.height-540)/2)-50)+',menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no');">
                        <span class="fa fa-eye"></span>
                        Preview
                    </button>

                    <button onclick="edit_button('<?=$row->public_key?>', '<?=$row2->button_id?>')"
                            title="Edit button" style="margin: 2px 4px; padding-bottom: 2px;">
                        <span class="fa fa-pencil"></span>
                        Edit
                    </button>

                    <button onclick="edit_button('<?=$row->public_key?>', '<?=$row2->button_id?>', true)"
                            title="Duplicate button" style="margin: 2px 4px; padding-bottom: 2px;">
                        <span class="fa fa-copy"></span>
                        Duplicate
                    </button>

                    <button class="enable"  <?if($row2->state == "enabled") echo "style='display: none;'"; ?> onclick="set_buton_state('enabled', '<?=$row->public_key?>', '<?=$row2->button_id?>')"
                            title="Enable button" style="margin: 2px 4px; padding-bottom: 2px;">
                        <span class="fa fa-thumbs-o-up"></span>
                        Enable
                    </button>
                    <button class="disable" <?if($row2->state == "disabled") echo "style='display: none;'"; ?> onclick="set_buton_state('disabled', '<?=$row->public_key?>', '<?=$row2->button_id?>')"
                            title="Disable button" style="margin: 2px 4px; padding-bottom: 2px;">
                        <span class="fa fa-thumbs-o-down"></span>
                        Disable
                    </button>

                    <button class="delete"  onclick="set_buton_state('deleted', '<?=$row->public_key?>', '<?=$row2->button_id?>')"
                            title="Delete button" style="margin: 2px 4px; padding-bottom: 2px;">
                        <span class="fa fa-trash-o"></span>
                        Delete
                    </button>

                    <button onclick="generate_code('<?=$row->public_key?>', '<?=$row2->button_id?>', '<?= addslashes(htmlspecialchars($properties->caption))?>')"
                            title="Generate invocation code" style="margin: 2px 4px; padding-bottom: 2px;">
                        <span class="fa fa-code"></span>
                        Get code
                    </button>

                    <a class="buttonized" href="<?="$config->buttons_analytics_root/$row->public_key/$row2->button_id"?>" target="_blank"
                       title="View button analytics" style="margin: 2px 4px; padding-bottom: 2px; height: 20px; display: inline-block;">
                        <span class="fa fa-line-chart"></span>
                        Analytics
                    </a>
                </span>
            </h4>
            <div>
                <div align="center">

                    <div class="<?= $config->buttons_default_selector_raw_classname ?>"
                         is_static="true"
                         website_public_key="<?=$row->public_key?>"
                         button_id="<?=$row2->button_id?>"
                         button_type="<?=$row2->type?>"
                         color_scheme="<?=$row2->color_scheme?>"
                         caption="<?=htmlspecialchars(stripslashes($properties->caption))?>"
                         default_coin="<?=$properties->default_coin?>"
                         button_logo="<?=$properties->button_logo?>"
                         drop_shadow="<?=$properties->drop_shadow?>"
                         hide_tips_counter="<?=$properties->hide_tips_counter?>"
                         inverted_drop_shadow="<?=$properties->inverted_drop_shadow?>"
                         ></div>

                </div>
                <div class="details" style="margin-top: 10px;">
                    <div>
                        Request type:
                        <span><?=$properties->request_type?></span>
                    </div>
                    <div>
                        Coin scheme:
                        <span><?=$properties->coin_scheme?></span>
                    </div>
                    <? if($properties->coin_scheme == "multi_converted"): ?>
                        <div>
                            USD amount:
                            <span>
                                $<?= $properties->amount_in_usd ?>
                            </span>
                        </div>
                    <? endif; ?>
                    <? if($properties->coin_scheme == "single_from_default_coin"): ?>
                        <div>
                            Amount asked:
                            <span>
                                <?= $properties->coin_amount ?>
                            </span>
                        </div>
                    <? else: ?>
                        <div>
                            Coins selected:
                            <span>
                                <?= count( (array) $properties->per_coin_requests ) ?>
                                /
                                <?= count($config->current_tipping_provider_data["per_coin_data"]) ?>
                            </span>
                        </div>
                    <? endif; ?>
                    <div>
                        Default coin:
                        <span><?=$properties->default_coin?></span>
                    </div>

                    <?
                        if( ! empty($properties->referral_codes) )
                        {
                            echo "<div>Referral codes: ";
                            $parts = preg_split('/\s*,\s*/', $properties->referral_codes);
                            foreach($parts as $part) echo "<span>$part</span> ";
                            echo "</div>";
                        } # end if
                    ?>

                    <br>
                    <div class="analytics_counter">
                        Views:
                        <span><?= number_format($row2->views) ?></span>
                    </div>
                    <div class="analytics_counter">
                        Impressions:
                        <span><?= number_format($row2->clicks) ?></span>
                    </div>
                    <div class="analytics_counter">
                        Conversions:
                        <span><?= number_format($row2->conversions) ?></span>
                    </div>
                </div>
            </div>

        </div><!-- /.button_container -->

        <?
    } # end while
