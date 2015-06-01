<?php
    /**
     * Platform Extension: Websites / iframed widget / Header with user information
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

    if( ! $account->exists ) return;
    parse_str($_SERVER["QUERY_STRING"], $params);
    $params["wasuuup"] = md5(mt_rand(1,65535));
    $params["mode"]    = "logout";
    $logout_link = $_SERVER["PHP_SELF"] . "?" . http_build_query($params);
?>
<div class="ui-state-active message_box ui-corner-all" style="margin-top: 0;">
    <span style="float: right;">
        <button onclick="location.href = '<?=$logout_link?>'">
            <span class="fa fa-sign-out"></span>
            Logout
        </button>
    </span>
    <span class="fa fa-border fa-user"></span>
    [<?=$account->id_account?>] <?=$account->name?>
    <button onclick="window.open('<?=$config->website_pages["root_url"]?>')" title="Go to <?=$config->app_display_shortname?>">
        <span class="fa fa-home"></span>
        <?=$config->app_single_word_name?>
    </button>
</div>
