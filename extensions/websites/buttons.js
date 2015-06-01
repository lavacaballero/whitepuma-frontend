/**
* Platform Extension: Websites / Buttons functions
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

var default_button_markup = '';

////////////////////////////////////////////////////////
function create_button(website_public_key, website_name)
////////////////////////////////////////////////////////
{
    $('#register_new_website_button').button('disable');
    $('#refresh_websites_button').button('disable');
    $('#current_websites').hide();

    $('#button_form_container .website_name').text(website_name);
    $('#button_form_container .edit_title').hide();
    $('#button_form_container .new_title').show();
    $('#button_form input[name="website_public_key"]').val(website_public_key);

    reset_button_form();
    wpuni_mcwidget.render();

    if( website_public_key.indexOf('lj.') >= 0 )
        $('#button_advanced').hide();
    else
        $('#button_advanced').show();

    $('#save_to_generate').show();
    $('#button_form_container').show();
    $.scrollTo(0);
} // end function

////////////////////////////
function reset_button_form()
////////////////////////////
{
    $('#button_form')[0].reset();
    $('#button_form input[name="mode"]').val('insert_button');
    $('#button_form input[name="button_id"]').val('');

    change_appearance_helper_background( 'appearance_helper_grid' );
    toggle_coin_controls('multi_direct');
    $('#appearance_helper').html( default_button_markup );
} // end function

////////////////////////////////////////////////////////////////
function edit_button(website_public_key, button_id, save_as_new)
////////////////////////////////////////////////////////////////
{
    var url = 'index.php?mode=get_button_data&website_public_key=' + website_public_key + '&button_id=' + button_id + '&wasuuup=' + (Math.random() * 1000000000000000);
    $.getJSON(url, function(data)
    {
        if( data.message != 'OK' )
        {
            alert(data.message);
            return;
        } // end if

        $('#register_new_website_button').button('disable');
        $('#refresh_websites_button').button('disable');
        $('#current_websites').hide();

        $('#button_form_container .website_name').text(data.data.website_public_key);
        $('#button_form_container .button_id').text(data.data.button_id);
        $('#button_form_container .edit_title').show();
        $('#button_form_container .new_title').hide();

        reset_button_form();

        $('#button_form input[name="mode"]').val('save_button');
        $('#button_form input[name="website_public_key"]').val(data.data.website_public_key);
        $('#button_form input[name="button_id"]').val(data.data.button_id);
        $('#button_form input[name="button_name"]').val(data.data.button_name);
        $('#button_form select[name="type"] option:not([value="'+data.data.type+'"])').prop('selected', false);
        $('#button_form select[name="type"] option[value="'+data.data.type+'"]').prop('selected', true);
        $('#button_form select[name="color_scheme"] option:not([value="'+data.data.color_scheme+'"])').prop('selected', false);
        $('#button_form select[name="color_scheme"] option[value="'+data.data.color_scheme+'"]').prop('selected', true);
        $('#button_form input[name="properties[caption]"]').val(data.data.properties.caption);
        $('#button_form select[name="properties[default_coin]"] option:not([value="'+data.data.properties.default_coin+'"])').prop('selected', false);
        $('#button_form select[name="properties[default_coin]"] option[value="'+data.data.properties.default_coin+'"]').prop('selected', true);
        $('#button_form select[name="properties[button_logo]"] option:not([value="'+data.data.properties.button_logo+'"])').prop('selected', false);
        $('#button_form select[name="properties[button_logo]"] option[value="'+data.data.properties.button_logo+'"]').prop('selected', true);
        $('#button_form input[name="properties[hide_tips_counter]"]').prop('checked', (data.data.properties.hide_tips_counter == 'true'));
        $('#button_form input[name="properties[drop_shadow]"]').prop('checked', (data.data.properties.drop_shadow == 'true'));
        $('#button_form input[name="properties[inverted_drop_shadow]"]').prop('checked', (data.data.properties.inverted_drop_shadow == 'true'));
        $('#button_form select[name="properties[request_type]"] option:not([value="'+data.data.properties.request_type+'"])').prop('selected', false);
        $('#button_form select[name="properties[request_type]"] option[value="'+data.data.properties.request_type+'"]').prop('selected', true);
        $('#button_form input[name="properties[coin_scheme]"]:not([value="'+data.data.properties.coin_scheme+'"])').prop('checked', false);
        $('#button_form input[name="properties[coin_scheme]"][value="'+data.data.properties.coin_scheme+'"]').prop('checked', true);
        toggle_coin_controls(data.data.properties.coin_scheme);
        $('#button_form input[name="properties[coin_amount]"]').val(data.data.properties.coin_amount);
        $('#button_form input[name="properties[amount_in_usd]"]').val(data.data.properties.amount_in_usd);
        $('#button_form input[name="properties[entry_id]"]').val(data.data.properties.entry_id);
        $('#button_form input[name="properties[entry_title]"]').val(data.data.properties.entry_title);
        $('#button_form input[name="properties[callback]"]').val(data.data.properties.callback);
        $('#button_form input[name="properties[custom_logo]"]').val(data.data.properties.custom_logo);
        $('#button_form input[name="properties[allow_target_overrides]"]').prop('checked', (data.data.properties.allow_target_overrides == 'true'));
        $('#button_form textarea[name="properties[referral_codes]"]').text(data.data.properties.referral_codes);
        $('#button_form textarea[name="properties[description]"]').text(data.data.properties.description);
        $('#button_form input[name="properties[private_basic_analytics]"]').prop('checked', (data.data.properties.private_basic_analytics == 'true'));

        if( save_as_new )
        {
            // Duplicate
            $('#button_form input[name="mode"]').val('insert_button');
            $('#button_form input[name="button_id"]').val('');
            $('#button_form input[name="button_name"]').val('Copy of ' + data.data.button_name);
        } // end if

        if( data.data.properties.coin_scheme != 'single_from_default_coin' )
        {
            $('#button_form input[type="checkbox"][name*="properties[per_coin_requests][direct]"]').each(function()
            {
                var field_name = $(this).attr('name');
                $(this).prop('checked', false);
                $(this).closest('tr').toggleClass('selected', false);

                var value_field = field_name.replace('[show]', '[amount]');
                var min_tip     = $(this).closest('tr').find(value_field).attr('minimum');
                $(this).closest('tr').find(value_field).val(min_tip);
            }); // end .each
            $('#button_form input[type="checkbox"][name*="properties[per_coin_requests][from_usd]"]').prop('checked', false);
            $('#button_form input[type="checkbox"][name*="properties[per_coin_requests][from_usd]"]').closest('tr').toggleClass('selected', false);
        } // end if

        if( data.data.properties.coin_scheme == 'multi_direct' )
        {
            for(var i in data.data.properties.per_coin_requests)
            {
                var coin_name = i;
                var coin_data = data.data.properties.per_coin_requests[i];
                $('#button_form input[name="properties[per_coin_requests][direct]['+coin_name+'][show]"]').prop('checked', (coin_data.show == 'true'));
                $('#button_form input[name="properties[per_coin_requests][direct]['+coin_name+'][show]"]').closest('tr').toggleClass('selected', (coin_data.show == 'true'));
                $('#button_form input[name="properties[per_coin_requests][direct]['+coin_name+'][amount]"]').val(coin_data.amount);
            } // end for
        }
        else if( data.data.properties.coin_scheme == 'multi_converted' )
        {
            for(var i in data.data.properties.per_coin_requests)
            {
                var coin_name = i;
                var coin_data = data.data.properties.per_coin_requests[i];
                $('#button_form input[name="properties[per_coin_requests][from_usd]['+coin_name+'][show]"]').prop('checked', (coin_data.show == 'true'));
                $('#button_form input[name="properties[per_coin_requests][from_usd]['+coin_name+'][show]"]').closest('tr').toggleClass('selected', (coin_data.show == 'true'));
            } // end for
        } // end if
        update_prices();

        $('#dummy_button')
            .attr( 'button_type',           data.data.type                            )
            .attr( 'color_scheme',          data.data.color_scheme                    )
            .attr( 'caption',               data.data.properties.caption              )
            .attr( 'default_coin',          data.data.properties.default_coin         )
            .attr( 'button_logo',           data.data.properties.button_logo          )
            .attr( 'drop_shadow',           data.data.properties.drop_shadow          )
            .attr( 'inverted_drop_shadow',  data.data.properties.inverted_drop_shadow )
            .attr( 'hide_tips_counter',     data.data.properties.hide_tips_counter    )
        wpuni_mcwidget.render('#dummy_button');

        if( website_public_key.indexOf('lj.') >= 0 )
            $('#button_advanced').hide();
        else
            $('#button_advanced').show();

        $('#save_to_generate').show();
        $('#button_form_container').show();
        $.scrollTo(0, 'fast');
    }); // end .getJSON

} // end function

////////////////////////////////////////////
function process_button_submission(response)
////////////////////////////////////////////
{
    $('#button_form_container').unblock();
    if( response.indexOf('ERROR') >= 0 )
    {
        alert(response);
        return;
    } // end if

    // alert('Button saved.');
    hide_button_form_dialog()
    refresh_websites_list();
} // end function

//////////////////////////////////////////////////////////////
function set_buton_state(state, website_public_key, button_id)
//////////////////////////////////////////////////////////////
{
    if( state == 'deleted' )
    {
        var message = 'Are you sure you no longer want this button?\n\n'
                    + 'Note: it will be removed from your list, but it wont be taken\n'
                    + 'out from the database to keep data consistency.\n\n'
                    + 'Do you want to continue?';
        if( ! confirm(message) ) return;
    } // end if

    var url = 'index.php?mode=set_button_state'
            + '&state='              + state
            + '&website_public_key=' + website_public_key
            + '&button_id='          + button_id
            + '&wasuuup='            + (Math.random() * 1000000000000000)
            ;

    $target = $('.button_container[website="' + website_public_key + '"][button_id="' + button_id + '"]');
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
                       .find('button.enable').hide()
                $target.find('button.disable').show()
                    ;
                break;
            case 'disabled':
                $target.toggleClass('ui-widget-content', false)
                       .toggleClass('ui-state-error', true)
                       .find('button.enable').show()
                $target.find('button.disable').hide()
                    ;
                break;
            case 'deleted':
                $target.remove();
                break;
        } // end switch
    }); // end .get
} // end function

//////////////////////////////////
function hide_button_form_dialog()
//////////////////////////////////
{
    $('#register_new_website_button').button('enable');
    $('#refresh_websites_button').button('enable');
    $('#button_form_container').hide();
    var scrollto_target = '.button_container'
                        + '[website="'   + $('#button_form input[name="website_public_key"]').val() + '"]'
                        + '[button_id="' + $('#button_form input[name="button_id"]').val()          + '"]';
    $('#button_form')[0].reset();
    $('#current_websites').show();
    $.scrollTo(scrollto_target, 'fast');
    $(scrollto_target).addClass('ui-state-highlight').delay(500).removeClass('ui-state-highlight', 1000);
} // end function

////////////////////////////////////////////////////////////////////
function change_appearance_helper_background( new_background_class )
////////////////////////////////////////////////////////////////////
{
    var old_class = $('#appearance_helper').attr('current_color');
    $('#appearance_helper').removeClass(old_class);
    $('#appearance_helper').addClass(new_background_class);
    $('#appearance_helper').attr('current_color', new_background_class)
} // end function

/////////////////////////////////////////////
function update_dummy_button( source, param )
/////////////////////////////////////////////
{
    wpuni_mcwidget.set('#dummy_button', param);
    wpuni_mcwidget.render('#dummy_button');
} // end function

/////////////////////////////////////
function set_coin_data_from( option )
/////////////////////////////////////
{
    var min_tip_size = $(option).attr('min_tip_size');
    var coin_sign    = $(option).attr('coin_sign');

    if( $(option).val() != '_none_' )
    {
        $('#button_form input[type="radio"][name="properties[coin_scheme]"][value="single_from_default_coin"]').prop('checked', true);
        toggle_coin_controls('single_from_default_coin');
    }
    else
    {
        $('#button_form input[type="radio"][name="properties[coin_scheme]"][value="single_from_default_coin"]').prop('checked', false);
        $('#button_form input[type="radio"][name="properties[coin_scheme]"][value="multi_direct"]').prop('checked', true);
        toggle_coin_controls('multi_direct');
    } // end if

    $('#button_form .current_coin_sign').text( coin_sign );
    $('#button_form .minimum_tip_size_for_current_coin').text( min_tip_size );
    $('#button_form input[name="properties[amount]"]').val( min_tip_size );
} // end function

///////////////////////////////////////
function toggle_coin_controls( active )
///////////////////////////////////////
{
    if( active == 'single_from_default_coin' )
    {
        $('#button_coin_data .single_coin input').prop('disabled', false);
        $('#button_coin_data .single_coin').removeClass('ui-state-disabled').addClass('ui-state-active');

        $('#button_coin_data .multi_direct input').prop('disabled', true);
        $('#button_coin_data .multi_direct button').button('disable');
        $('#button_coin_data .multi_direct').removeClass('ui-state-active').addClass('ui-state-disabled');

        $('#button_coin_data .multi_converted input').prop('disabled', true);
        $('#button_coin_data .multi_converted button').button('disable');
        $('#button_coin_data .multi_converted').removeClass('ui-state-active').addClass('ui-state-disabled');
    }
    else if( active == 'multi_direct' )
    {
        $('#button_coin_data .single_coin input').prop('disabled', true);
        $('#button_coin_data .single_coin').removeClass('ui-state-active').addClass('ui-state-disabled');

        $('#button_coin_data .multi_direct input').prop('disabled', false);
        $('#button_coin_data .multi_direct button').button('enable');
        $('#button_coin_data .multi_direct').removeClass('ui-state-disabled').addClass('ui-state-active');

        $('#button_coin_data .multi_converted input').prop('disabled', true);
        $('#button_coin_data .multi_converted input[name="properties[coin_scheme]"]').prop('disabled', false);
        $('#button_coin_data .multi_converted button').button('disable');
        $('#button_coin_data .multi_converted').removeClass('ui-state-active').removeClass('ui-state-disabled');
    }
    else
    {
        $('#button_coin_data .single_coin input').prop('disabled', true);
        $('#button_coin_data .single_coin').removeClass('ui-state-active').addClass('ui-state-disabled');

        $('#button_coin_data .multi_direct input').prop('disabled', true);
        $('#button_coin_data .multi_direct input[name="properties[coin_scheme]"]').prop('disabled', false);
        $('#button_coin_data .multi_direct button').button('disable');
        $('#button_coin_data .multi_direct').removeClass('ui-state-active').removeClass('ui-state-disabled');

        $('#button_coin_data .multi_converted input').prop('disabled', false);
        $('#button_coin_data .multi_converted button').button('enable');
        $('#button_coin_data .multi_converted').removeClass('ui-state-disabled').addClass('ui-state-active');
    } // end if
} // end function

/////////////////////////////////////
function check_direct_value(inputbox)
/////////////////////////////////////
{
    minimum = parseFloat($(inputbox).attr('minimum'));
    value   = parseFloat($(inputbox).val());
    if(value >= minimum) $(inputbox).removeClass('errored');
    else                 $(inputbox).addClass('errored');
} // end function

////////////////////////
function update_prices()
////////////////////////
{
    var requested_usd = parseFloat($('.multi_converted input[name="properties[amount_in_usd]"]').val());
    if( isNaN(requested_usd) ) { $('.multi_converted .converted_from_usd').text('Check USD value!'); return; }
    var trailing_errors = 0;
    $('.multi_converted .converted_from_usd').each(function()
    {
        var this_minimum = parseFloat($(this).attr('minimum'));
        var dollar_price = parseFloat($(this).attr('dollar_price'));
        if( isNaN(this_minimum) || isNaN(dollar_price) )
        {
            $(this).text('N/A');
        }
        else
        {
            var obtainable = (requested_usd / dollar_price).toFixed(8);
            if( obtainable >= this_minimum) { $(this).text(obtainable).removeClass('errored'); }
            else                            { $(this).text('Below minimum!').addClass('errored'); trailing_errors++; }
        } // end if
    }); // end .each
    if( trailing_errors == 0 ) $('#errored_conversion').fadeOut(250);
    else                       $('#errored_conversion').fadeIn(100).fadeOut(100).fadeIn(100).fadeOut(100).fadeIn(100);
} // end function

//////////////////////////////////////////////////
function toggle_table_checkboxes( source_trigger )
//////////////////////////////////////////////////
{
    var set_state = $(source_trigger).attr('all_checked') == 'true';
    $(source_trigger).closest('table').find('input[type="checkbox"]').prop('checked', ! set_state);
    $(source_trigger).closest('table').find('input[type="checkbox"]').closest('tr').toggleClass('selected', ! set_state);
    $(source_trigger).attr('all_checked', (set_state ? 'false' : 'true'));
} // end function

///////////////////
function test_ipn()
///////////////////
{
    var url = escape($('#button_form input[name="properties[callback]"]').val());
    $.get('index.php?mode=test_callback_url&url=' + url, function(response)
    {
        alert(response);
    });
} // end function

////////////////////////////
$(document).ready(function()
////////////////////////////
{
    default_button_markup = $('#appearance_helper').html();

    toggle_coin_controls('multi_direct');

    $('#button_form').ajaxForm({
        target:       '#button_form_target',
        beforeSubmit: function() { $('#button_form_container').block(blockUI_medium_params); return true; },
        success:      process_button_submission
    }); // end ajaxForm
}); // end document.ready
