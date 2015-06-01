<?php
    /**
     * Platform Extension: Websites / analytics / heading
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
     * @var string id_account         Optional. For admins
     * @var object $website
     * @var object $button
     * @var string ref                Optional. Referral code
     * @var string entry_id           Optional. For recording in the OpsLog
     * @var string target_data        Optional. Pipe separated params: account:string, name:string, email:string
     */
?>
<style type="text/css">
    h1 .selector            { display: inline-block; padding: 0 5px; margin: 0; }
    h1 .selector .ufd.plain { position: relative; top: 4px; }

    .indicators                             { padding: 15px 0 0 10px; text-align: center; margin-bottom: 10px; }
    .indicators .indicator                  { display: inline-block; padding: 5px 10px; margin-right: 10px; margin-bottom: 10px; }
    .indicators .indicator .title           { font-size: 8pt; font-weight: bold; margin-left: -15px; margin-top: -10px; padding: 2px; }
    .indicators .indicator .content         { display: block; font-size: 14pt; font-weight: normal; padding: 5px 0; }
    .indicators .indicator .content.numeric { text-align: right; }
    .indicators .indicator.smaller .content { font-size: 12pt; font-weight: normal; }
    .indicators .indicator.full_sized       { display: block;    }

    .table_wrapper           { overflow-x: auto; overflow-y: hidden; width: 100%; }
    .table_wrapper.scrolling { border-left: 2px dotted black; border-right: 2px dotted black; }

    table.tablesorter th ,
    table.tablesorter td { font-family: Arial, Helvetica; font-size: 10pt; }
</style>
<script type="text/javascript">
    ///////////////////////////////
    function check_wrapped_tables()
    ///////////////////////////////
    {
        $('.table_wrapper').each(function()
        {
            if( $(this).find('table').width() > $(this).width() )
                $(this).addClass('scrolling');
            else
                $(this).removeClass('scrolling');
        }); // end function
    } // end function

    ////////////////////////////
    $(document).ready(function()
    ////////////////////////////
    {
        $(window).resize(function()
        {
            check_wrapped_tables();
        }); // end resize
        check_wrapped_tables();
    }); // end document.ready
</script>

<link rel="stylesheet" type="text/css" href="<?= $config->commons_url ?>/lib/jquery.ui.ufd-0.6/css/ufd-base.css">
<link rel="stylesheet" type="text/css" href="<?= $config->commons_url ?>/lib/jquery.ui.ufd-0.6/css/plain/plain.css">
<script type="text/javascript"          src="<?= $config->commons_url ?>/lib/jquery.ui.ufd-0.6/jquery.ui.ufd.min.js"></script>
<script type="text/javascript"> $(document).ready(function() { $('select.combobox').ufd(); }); </script>

<link rel="stylesheet" type="text/css"  href="<?=$config->commons_url?>/lib/jquery-tablesorter/themes/blue/style.css">
<script type="text/javascript"           src="<?=$config->commons_url?>/lib/jquery-tablesorter/jquery.tablesorter.min.js"></script>
<script type="text/javascript"           src="<?=$config->commons_url?>/lib/jquery-tablesorter/jquery.metadata.js"></script>
<script type="text/javascript"> $(function() { $('.tablesorter').tablesorter(); }); </script>

<? include "../index.contents.user_info.inc"; ?>
