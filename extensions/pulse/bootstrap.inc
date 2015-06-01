<?php
    /**
     * Platform Extension: Pulse
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
     * Trailing vars:
     * --------------
     * @var string  $location_case                   Insertion placement
     * @var object  $config                          Configuration per-se.
     * @var boolean $is_admin
     * @var boolean $admin_impersonization_in_effect
     */

    include_once dirname(__FILE__) . "/functions.inc";

    ###################
    # Requisite check #
    ######################################################
    if( empty($config->db_tables["pulse_posts"]) ) return;
    ######################################################

    ##################################
    # Heading main buttons rendering #
    ##############################################
    if( $location_case == "heading_main_buttons" )
    ##############################################
    {
        ?>
        <a href="<?="$basedir/extensions/pulse/?wasuuup=".md5(mt_rand(1,65535))?>"
           class="buttonized">
            <span class="fa fa-heart"></span>
            Pulse
        </a>
        <?
        return;
    } # end if

    ######################################################
    if( $location_case == "index_user_home_pre_contents" )
    ######################################################
    {
        if( empty($_GET["mode"]) ) return;
        if( $_GET["mode"] == "show_pulse_post" && empty($_GET["post"]) ) return;
        $url = "{$config->website_pages["root_url"]}extensions/pulse/?post={$_GET["post"]}&comment={$_GET["comment"]}&wasuuup=".md5(mt_rand(1,65535));
        header("Location: $url");
        die("<a href='$url'>Click here to continue...</a>");
    } # end if

    #########################################################
    if( $location_case == "coin_dashboard_below_user_stats" )
    #########################################################
    {
        global $account;
        # $default_comments_state  = "expanded";
        $base_coin_filter        = $config->current_coin_name == "_none_" ? "" : $config->current_coin_name;
        include dirname(__FILE__) . "/layout.inc";
        return;
    } # end if

    #########################################################
    if( $location_case == "multicoin_home_above_indicators" )
    #########################################################
    {
        include dirname(__FILE__) . "/notifications.inc";
        return;
    } # end if

    #####################################################
    if( $location_case == "rain_maker_pre_notification" )
    #####################################################
    {
        global $account, $message_params, $group_key, $root_url;
        include "$basedir/extensions/pulse/model_post.php";

        $post = new pulse_post();

        $post->type        = "rain";
        $post->target_coin = $config->current_coin_name;
        $post->target_feed = $group_key;
        $post->id_author   = $account->id_account;
        $post->caption     = $message_params["name"];
        $post->content     = "{$config->current_coin_name} rain! " . $message_params["description"];
        $post->signature   = stripslashes($_POST["signature"]);
        $post->picture     = $message_params["picture"];
        $post->link        = str_replace($config->facebook_canvas_page, $config->website_pages["root_url"], $message_params["link"]);
        $post->save();
        return;
    } # end if
