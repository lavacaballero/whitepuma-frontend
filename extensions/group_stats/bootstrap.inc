<?php
    /**
     * Platform Extension: Facebook Group Stats
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

    ##############
    # Requisites #
    #########################
    if( ! $is_admin ) return;
    if( empty($config->facebook_monitor_objects) ) return;
    ######################################################

    ##################################
    # Heading main buttons rendering #
    ##############################################
    if( $location_case == "heading_main_buttons" )
    ##############################################
    {
        if( basename($_SERVER["PHP_SELF"]) == basename(__FILE__) ) return;
        ?>
        <a href="<?="$basedir/extensions/group_stats/?wasuuup=".md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>"
           class="buttonized">
            <span class="fa fa-bar-chart-o"></span>
            Group stats
        </a>
        <?
        return;
    } # end if

    return;
?>