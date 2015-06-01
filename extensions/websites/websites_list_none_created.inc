<?php
    /**
     * Platform Extension: Websites / Websites list / None created
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

<!-- ################### -->
<!-- No websites created -->
<!-- ################### -->

<div class="ui-state-highlight message_box ui-corner-all" style="font-size: 14pt; text-align: center; padding: 20px; margin: 20px;">
    <span class="fa fa-info-circle"></span>
    You don't have any website registered! Get started now!
</div>

<div align="center" style="margin: 0 20px;">

    <h2>Do you own a website?</h2>
    <p>You can register your website and create buttons to <u>get coins for your content</u>, then embed the code in very easy steps.</p>

    <h2>Do you sell goods or services?</h2>
    <p>If you have a website, you can also create buttons for <u>express payments</u> using our engine.<br>
    You can setup <u>a callback script</u> so your website gets an automated notification for your systems to release
    purchases upon payment.</p>

    <h2>Do you want to get coins from third party websites?</h2>
    <p>You can also create buttons <u>and embed them on our listed websites</u> that allows it.<br>
    If you don't own a website, you can make your buttons on a <b>Piggy Bank</b>
    and <u>spread them everywhere</u>.</p>

    <button class="smaller" onclick="create_website()" style="margin: 5px;">
        I have a website,<br>
        <span style="font-size: 16pt;">
            I want to register it!
        </span>
    </button>
    <button class="smaller" onclick="create_leech_jar()" style="margin: 5px;">
        I <u>don't</u> have a website,<br>
        <span style="font-size: 16pt;">
            I want a Piggy Bank
        </span>
    </button>
</div>
