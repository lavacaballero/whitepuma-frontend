/**
 * Global functions
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

if( typeof root_url == 'undefined' ) root_url = '';

var blockUI_default_params  = { css: { border: '0', backgroundColor: 'rgba(0, 0, 0, .5)' },
                                message: '<img class="ajax_spinner" src="'+root_url+'img/spinner_128x128_v2.gif" width="128 height="128" border="0">' }
var blockUI_medium_params   = { css: { border: '0', backgroundColor: 'rgba(0, 0, 0, .5)' },
                                message: '<img class="ajax_spinner" src="'+root_url+'img/spinner_64x64.gif" width="64" height="64" border="0">' }
var blockUI_smaller_params  = { css: { border: '0', backgroundColor: 'rgba(0, 0, 0, .5)' },
                                message: '<img class="ajax_spinner" src="'+root_url+'img/spinner_32x32.gif" width="32" height="32" border="0">' }
var blockUI_smallest_params = { css: { border: '0', backgroundColor: 'rgba(0, 0, 0, .5)' },
                                message: '<img class="ajax_spinner" src="'+root_url+'img/spinner_16x16.gif" width="16" height="16" border="0">' }

/**
* Gets the current user wallet address
///////////////////////////// */
function get_wallet_address()
/////////////////////////////
{
    var url = 'toolbox.php?mode=get_wallet_address&wasuuup=' + Math.round(Math.random() * 999999999999);
    $('#span_walletaddress').block( blockUI_smallest_params );
    $.get(url, function(response)
    {
        if( response.indexOf(/error/i) >= 0 )
        {
            alert( response + '\n\n'
                 + 'Please try again. If you need assistance, please reach our\n'
                 + 'Help & Support link and we\'ll get on it ASAP.'
                 );
            $('#span_walletaddress').unblock();
            return;
        } // end if

        $('#span_walletaddress').text(response).unblock();
    }); // end .get
} // end function

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
