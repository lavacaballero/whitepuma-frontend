/**
* Platform Extension: Websites / Websites functions
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

/////////////////////////////////
function edit_website(public_key)
/////////////////////////////////
{
    var url = 'index.php?mode=get_website_data&public_key=' + public_key + '&wasuuup=' + (Math.random() * 1000000000000000);
    $.getJSON(url, function(data)
    {
        if( data.message != 'OK' )
        {
            alert(data.message);
            return;
        } // end if

        $('#website_form_container .intro').hide();
        $('#website_form_container .creation_caption').hide();
        $('#website_form_container .editing_caption').show();
        $('#website_form_container .new_title').hide();
        $('#website_form_container .edit_title').show();

        $('#website_form')[0].reset();
        $('#website_form input[name="mode"]').val('save_website');
        $('#website_form input[name="name"]').val(data.data.name);
        $('#website_form select[name="category"] option').prop('selected', false);
        $('#website_form select[name="category"] option:contains("'+data.data.category+'")').prop('selected', true);
        $('#website_form input[name="public_key"]').val(data.data.public_key);
        $('#website_form input[name="public_key"]').prop('readonly', true);
        $('#website_form .secret_key').attr('secret_key', data.data.secret_key);
        $('#website_form input[name="main_url"]').val(data.data.main_url);
        $('#website_form input[name="icon_url"]').val(data.data.icon_url);
        $('#website_form textarea[name="description"]').text(data.data.description);
        $('#website_form textarea[name="valid_urls"]').text(data.data.valid_urls);
        $('#website_form input[name="allow_leeching"][value="'+data.data.allow_leeching+'"]').prop('checked', true);
        $('#website_form select[name="leech_button_type"] option').prop('selected', false);
        $('#website_form select[name="leech_button_type"] option[value="'+data.data.leech_button_type+'"]').prop('selected', true);
        $('#website_form select[name="leech_color_scheme"] option').prop('selected', false);
        $('#website_form select[name="leech_color_scheme"] option[value="'+data.data.leech_color_scheme+'"]').prop('selected', true);
        $('#website_form textarea[name="banned_websites"]').text(data.data.banned_websites);

        $('#register_new_website_button').button('disable');
        $('#create_leech_jar').button('disable');
        $('#refresh_websites_button').button('disable');
        $('#current_websites').hide();

        if( $('#website_form input[name="public_key"]').val().indexOf('lj.') >= 0 )
            $('#website_form_container .disable_for_jars').hide();
        else
            $('#website_form_container .disable_for_jars').show();

        $('#website_form_container').show();
    }); // end .getJSON

} // end function

/////////////////////////
function create_website()
/////////////////////////
{
    $('#website_form_container .intro').show();
    $('#website_form_container .creation_caption').show();
    $('#website_form_container .editing_caption').hide();
    $('#website_form_container .new_title').show();
    $('#website_form_container .edit_title').hide();
    $('#website_form input[name="mode"]').val('insert_website');
    $('#website_form input[name="public_key"]').prop('readonly', false);
    $('#website_form .secret_key').attr('secret_key', '');

    $('#register_new_website_button').button('disable');
    $('#create_leech_jar').button('disable');
    $('#refresh_websites_button').button('disable');
    $('#current_websites').hide();
    $('#website_form')[0].reset();
    $('#website_form input[name="mode"]').val('insert_website');
    $('#website_form_container .disable_for_jars').show();
    $('#website_form_container').show();
} // end function

///////////////////////////
function create_leech_jar()
///////////////////////////
{
    var url = 'index.php?mode=create_leech_jar&wasuuup=' + (Math.random() * 1000000000000000);
    $.get(url, function(response)
    {
        if( response != 'OK' )
        {
            alert(response);
            return;
        } // end if

        alert( "Your Piggy Bank has been created! Websites list will be refreshed. Please look for your jar and add some leeches!" );
        refresh_websites_list();
    }); // end function
} // end function

///////////////////////////////////
function hide_website_form_dialog()
///////////////////////////////////
{
    $('#website_form_container .intro').show();
    $('#website_form_container .creation_caption').show();
    $('#website_form_container .editing_caption').hide();
    $('#website_form_container .new_title').show();
    $('#website_form_container .edit_title').hide();
    $('#website_form input[name="mode"]').val('insert_website');
    $('#website_form input[name="public_key"]').prop('readonly', false);
    $('#website_form .secret_key').attr('secret_key', '');

    var scrollto_target = '.website[website="' + $('#website_form input[name="public_key"]').val() + '"]';

    $('#website_form_container').hide();
    $('#website_form')[0].reset();
    $('#current_websites').show();
    $('#register_new_website_button').button('enable');
    $('#create_leech_jar').button('enable');
    $('#refresh_websites_button').button('enable');

    $.scrollTo(scrollto_target, 'fast');
    $(scrollto_target).addClass('ui-state-highlight').delay(500).removeClass('ui-state-highlight', 1000);
} // end function

/////////////////////////////////////////////
function process_website_submission(response)
/////////////////////////////////////////////
{
    $('#website_form_container').unblock();
    if( response.indexOf('ERROR') >= 0 )
    {
        alert(response);
        return;
    } // end if

    if( $('#website_form input[name="mode"]').val() == 'insert_website' )
        alert( 'Your website has been registered! Now you will need to add a button and get the code to embed it.' );
    // else
    //    alert('Website saved.');
    hide_website_form_dialog()
    refresh_websites_list();
} // end function

////////////////////////////////////////////////////
function set_website_state(state, website_public_key)
////////////////////////////////////////////////////
{
    if( state == 'deleted' )
    {
        if( website_public_key.indexOf('lj.') >= 0 )
            message = 'Are you sure you no longer want your Piggy Bank?\n\n'
                    + 'Only one Piggy Bank can be created for one user, and deleting it\n'
                    + 'removes it from your list, but it wont be taken\n'
                    + 'out from the database to keep data consistency.\n\n'
                    + 'Do you want to continue?';
        else
            message = 'Are you sure you no longer want this website?\n\n'
                    + 'Note: it will be removed from your list, but it wont be taken\n'
                    + 'out from the database to keep data consistency.\n\n'
                    + 'Do you want to continue?';

        if( ! confirm(message) ) return;
    } // end if

    var url = 'index.php?mode=set_website_state'
            + '&state='      + state
            + '&public_key=' + website_public_key
            + '&wasuuup='    + (Math.random() * 1000000000000000)
            ;

    $target = $('.website[website="' + website_public_key + '"]');
    $target.block(blockUI_smaller_params);
    $.get(url, function(response)
    {
        $target.unblock();
        if( response != 'OK' ) return alert(response);

        switch( state )
        {
            case 'enabled':
                $target.toggleClass('ui-widget-content', true)
                       .toggleClass('ui-state-error', false)
                       .find('button.web_enable').hide()
                $target.find('button.web_disable').show()
                    ;
                break;
            case 'disabled':
                $target.toggleClass('ui-widget-content', false)
                       .toggleClass('ui-state-error', true)
                       .find('button.web_enable').show()
                $target.find('button.web_disable').hide()
                    ;
                break;
            case 'deleted':
                $target.remove();
                break;
        } // end switch
    }); // end .get
} // end function

////////////////////////////////
function refresh_websites_list()
////////////////////////////////
{
    var url = 'index.php?wasuuup='+(Math.random() * 1000000000000000)+' #current_websites_content';
    $('#current_websites').block(blockUI_medium_params);
    $('#current_websites').load(url, function()
    {
        $('#current_websites').unblock();
        $(this).find('button, a.buttonized').button();
        wpuni_mcwidget.render();
    }); // end function
} // end function

////////////////////////////
$(document).ready(function()
////////////////////////////
{
    $('#website_form').ajaxForm({
        target:       '#website_form_target',
        beforeSubmit: function() { $('#website_form_container').block(blockUI_medium_params); return true; },
        success:      process_website_submission
    }); // end ajaxForm
}); // end document.ready
