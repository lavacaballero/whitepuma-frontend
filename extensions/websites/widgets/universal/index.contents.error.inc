<?php
    /**
     * Platform Extension: Websites / iframed widget / Error message
     *
     * @package    WhitePuma OpenSource Platform
     * @subpackage Frontend
     * @copyright  2014 Alejandro Caballero
     * @author     Alejandro Caballero - acaballero@lavasoftworks.com
     * @license    GNU-GPL v3 (http://www.gnu.org/licenses/gpl.html)
     *
     * Copyright (C) 2014 Alejandro Caballero
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

    include "index.contents.user_info.inc";
?>

<table width="100%" height="100%" cellpadding="0" cellspacing="0" border="0">
    <tr align="center" valign="middle">
        <td width="100%" height="100%">
            <div class="ui-state-error message_box ui-corner-all" style="margin: 0 20px; padding: 20px;">
                <span class="fa fa-warning"></span>
                <?= $error_message ?>
            </div>
        </td>
    </tr>
</table>
