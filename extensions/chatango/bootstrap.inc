<?php
    /**
     * Platform Extension: chatango
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
    ##############################################
    if( empty($config->chatango_handler) ) return;
    ##############################################

    ##################################
    # Heading main buttons rendering #
    ##############################################
    if( $location_case == "heading_main_buttons" )
    ##############################################
    {
        ?>
        <style type="text/css"> #chatango_button_container iframe { border-radius: 5px; } </style>
        <span id="chatango_button_container" style="display: inline-block; vertical-align: bottom;">
            <script id="sid0020000077457073699">(function() {function async_load(){s.id="cid0020000077457073699";
            s.src=(window.location.href.indexOf('file:///') > -1 ? 'http:' : '') + '//st.chatango.com/js/gz/emb.js';
            s.style.cssText="<?=$config->chatango_dimensions?>";s.async=true;
            s.text='{"handle":"<?=$config->chatango_handler?>","arch":"js","styles":{"a":"<?=$config->chatango_background_color?>","b":100,"c":"<?=$config->chatango_foreground_color?>","d":"<?=$config->chatango_foreground_color?>","k":"<?=$config->chatango_background_color?>","l":"<?=$config->chatango_background_color?>","m":"<?=$config->chatango_background_color?>","n":"<?=$config->chatango_foreground_color?>","q":"<?=$config->chatango_background_color?>","r":100,"bpos":"tr","cv":1,"cvfntsz":"10px","cvfntw":"bold","cvbg":"<?=$config->chatango_background_color?>","cvw":70,"cvh":23}}';
            var ss = document.getElementsByTagName('script');
            for (var i=0, l=ss.length; i < l; i++){if (ss[i].id=='sid0020000077457073699'){ss[i].id +='_';ss[i].parentNode.insertBefore(s, ss[i]);break;}}}
            var s=document.createElement('script');
            if (s.async==undefined){if (window.addEventListener) {addEventListener('load',async_load,false);}
            else if (window.attachEvent) {attachEvent('onload',async_load);}}else {async_load();}})();</script>
        </span>
        <?
        return;
    } # end if

    return;
