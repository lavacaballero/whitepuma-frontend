<?php
    /**
     * Platform Extension: Websites / Websites list / All websites list
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

    while($row = mysql_fetch_object($res))
    {
        switch($row->state)
        {
            case "enabled":  $class = "ui-widget-content"; break;
            case "disabled": $class = "ui-state-error";    break;
            case "locked":   $class = "ui-state-disabled"; break;
        } # end switch

        $query2 = "
            select
                ".$config->db_tables["website_buttons"].".*,
                ( select count(record_date) from  ".$config->db_tables["website_button_log"]."
                  where ".$config->db_tables["website_button_log"].".button_id = ".$config->db_tables["website_buttons"].".button_id
                  and record_type = 'view'  ) as views,
                ( select count(record_date) from  ".$config->db_tables["website_button_log"]."
                  where ".$config->db_tables["website_button_log"].".button_id = ".$config->db_tables["website_buttons"].".button_id
                  and record_type = 'click' ) as clicks,
                ( select count(record_date) from  ".$config->db_tables["website_button_log"]."
                  where ".$config->db_tables["website_button_log"].".button_id = ".$config->db_tables["website_buttons"].".button_id
                  and record_type = 'conversion' ) as conversions
            from
                ".$config->db_tables["website_buttons"]."
            where
                website_public_key = '$row->public_key' and state <> 'deleted'
            order by button_name asc
        ";
        $res2   = mysql_query($query2);

        $contents_cookie_key = $config->session_vars_prefix .  str_replace(
                               array("-", "."), "_",
                               "websites_list_visibility_".$row->public_key);
        $contents_state = $_COOKIE[$contents_cookie_key] == "collapsed"
                        ? "collapsed"
                        : "expanded";
        ?>

        <div class="website <?=$class?> message_box ui-corner-all" website="<?=$row->public_key?>">

            <h3 class="ui-state-active message_box ui-corner-top" style="margin: 0;">

                <button <? if( mysql_num_rows($res2) == 0 ) echo "disabled"; ?>
                        onclick="$.cookie('<?=$contents_cookie_key?>', ($(this).closest('div').find('.website_buttons').is(':visible') ? 'collapsed' : 'expanded'), {expires: 365, path: '/', domain: '<?=$config->cookie_domain?>'} ); $(this).closest('div').find('.website_buttons').toggle(); $(this).find('.visible, .hidden').toggle();"
                        title="Toggle buttons" style="height: 24px;">
                    <span class="visible" style="<? if( $contents_state == "collapsed" ) echo "display: none;"; ?>"><span class="fa fa-caret-down"></span></span>
                    <span class="hidden"  style="<? if( $contents_state == "expanded"  ) echo "display: none;"; ?>"><span class="fa fa-caret-right"></span></span>
                </button>

                <span class="ui-widget-content ui-corner-all fixed_font"
                      style="font-weight: normal; font-size: 10pt; padding: 1px 3px; position: relative; top: -2px; cursor: pointer;"
                      onclick="prompt('This is your website public key:', '<?=$row->public_key?>')"
                      title="Click to copy your public key"
                      ><?=$row->public_key?></span>

                <?=$row->name?>

                <? if( mysql_num_rows($res2) > 0 ) echo "[".mysql_num_rows($res2)." buttons]"; ?>

                <span>
                    <button <? if($row->state == "locked") echo "disabled"; ?> onclick="edit_website('<?=$row->public_key?>')">
                        <span class="fa fa-pencil"></span>
                        Edit
                    </button>

                    <button class="web_enable" <? if($row->state == "locked") echo "disabled"; ?> <?if($row->state == "enabled") echo "style='display: none;'"; ?>
                            onclick="set_website_state('enabled', '<?=$row->public_key?>')">
                        <span class="fa fa-thumbs-o-up"></span>
                        Enable
                    </button>
                    <button class="web_disable" <? if($row->state == "locked") echo "disabled"; ?> <?if($row->state == "disabled") echo "style='display: none;'"; ?>
                            onclick="set_website_state('disabled', '<?=$row->public_key?>')">
                        <span class="fa fa-thumbs-o-down"></span>
                        Disable
                    </button>

                    <? if( substr($row->public_key, 0, 3) != "lj." ): ?>
                        <button class="delete" <? if($row->state == "locked") echo "disabled"; ?>
                                onclick="set_website_state('deleted', '<?=$row->public_key?>')">
                            <span class="fa fa-trash-o"></span>
                            Delete
                        </button>
                    <? endif; ?>

                    <? if($row->state == "locked"): ?>
                        <? if( ! $is_admin): ?>
                            <span class="fa fa-warning"></span>
                            Admin lock in place!
                        <? else: ?>
                            <button disabled>
                                <span class="fa fa-unlock"></span>
                                Admin unlock
                            </button>
                        <? endif; ?>
                    <? else: ?>
                        <? if( $is_admin): ?>
                            <button disabled>
                                <span class="fa fa-lock"></span>
                                Admin lock
                            </button>
                        <? endif; ?>
                    <? endif; ?>
                    <? if( $row->state != "locked" && ! $admin_impersonization_in_effect ): ?>
                        <button onclick="create_button('<?=$row->public_key?>', '<?=addslashes($row->name)?>')">
                            <span class="fa fa-star"></span>
                            Create button
                        </button>
                    <? endif; ?>

                    <a class="buttonized" href="<?="$config->buttons_analytics_root/$row->public_key"?>"
                       title="View website analytics" target="_blank" style="line-height: 22px; display: inline-block; color: #2779aa;">
                        <span class="fa fa-line-chart"></span>
                        Website Analytics
                    </a>
                </span>

            </h3>

            <div class="ui-state-default message_box ui-corner-bottom" style="margin-top: 0;">
                <? if( ! empty($row->icon_url) ): ?>
                    <? if($row->icon_url == "%leech_jar_url%") $row->icon_url = $config->buttons_leech_jar_image_url; ?>
                    <a class="lightbox" href="<?=$row->icon_url?>"><img class="icon" src="<?=$row->icon_url?>"></a>
                <? endif; ?>
                <div class="description"><?= $row->description ?></div>
                <div class="details">
                    <? if( substr($row->public_key, 0, 3) != "lj." && $row->published == 0 ): ?>
                        <div style="cursor: pointer;" title="Click to view details" onclick="alert('Your website is not listed on our <?=$config->buttons_buttonizer_rootname?> Enabled Websites page.\nPlease make sure to have our widget core added and make obvious\nyou\'re using it so we list it. Please contact us if you have any doubt.')"><span style="color: white; background-color: #585858;"><span class="fa fa-warning" style="margin: 0; padding: 0; border: 0; background: none; color: white;"></span> Unlisted</span></div>
                    <? endif; ?>
                    <div>Created: <span><?=$row->creation_date?></span></div>
                    <div>Public Key: <span><?=$row->public_key?></span></div>
                    <div>Secret Key: <span><button class="smallest secret_key"
                        onclick="prompt('Your website secret key:', $(this).attr('secret_key')); return false;"
                        secret_key="<?=$row->secret_key?>"><span class="fa fa-eye"> View</span></button></span></div>
                    <div>Category: <span><?=$row->category?></span></div>
                    <? if( ! empty($row->valid_urls) ): ?>
                        <div>Valid URLs: <span><?= str_replace("\n", " &bull; ", $row->valid_urls) ?></span></div>
                    <? endif; ?>
                    <? if($row->allow_leeching): ?>
                        <div><span>Allows leeching</span></div>
                    <? endif; ?>
                </div>
            </div>

            <div class="website_buttons" style="<? if( $contents_state == "collapsed" ) echo "display: none;"; ?>">

                <?
                    if( mysql_num_rows($res2) == 0 )
                    {
                        ?>

                        <div class="ui-state-highlight message_box ui-corner-all" style="font-size: 12pt; margin-bottom: 0;">
                            <span class="fa fa-info-circle"></span>
                            This website has no buttons registered. You can create one
                            <button class="smaller" onclick="create_button('<?=$row->public_key?>', '<?=addslashes($row->name)?>')">clicking here</button>!
                        </div>

                        <?
                    }
                    else
                    {
                        include "buttons_list.inc";
                    } # end if
                    mysql_free_result($res2);
                ?>

            </div><!-- /.website_buttons -->

        </div> <!-- /.website -->

        <?
    } # end while
