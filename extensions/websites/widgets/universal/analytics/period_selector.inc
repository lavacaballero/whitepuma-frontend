<?php
    /**
     * Platform Extension: Websites / period selector
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
<div class="periods_selector ui-widget-header message_box ui-corner-all" style="text-align: center">

    <? foreach($periods as $key => $val): ?>

        <span value="<?=$key?>" class="pseudo_link <?= $_GET["period"] == $key ? "ui-state-active" : "ui-state-default" ?> ui-corner-all"
              onmouseover="$(this).toggleClass('ui-state-hover')" onmouseout="$(this).toggleClass('ui-state-hover')"
              onclick="$('#analytics input[name=period]').val( $(this).attr('value') ); $('#analytics').submit();"
              style="display: inline-block; padding: 2px 5px; margin: 5px 2px;"><?=$val?></span>

    <? endforeach; ?>

</div><!-- /.periods_selector -->

