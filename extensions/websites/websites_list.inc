<?php
    /**
     * Platform Extension: Websites / Websites list
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

<div id="current_websites">

    <div id="current_websites_content">

        <?
            $query = "select * from ".$config->db_tables["websites"]." where id_account = '$account->id_account' and state <> 'deleted' order by name asc";
            $res   = mysql_query($query);
            if( mysql_num_rows($res) == 0)
                include "websites_list_none_created.inc";
            else
                include "websites_list_all.inc";
        ?>

    </div><!-- /#current_websites_content -->

</div><!-- /#current_websites -->

<? include "code_generator.inc"; ?>
