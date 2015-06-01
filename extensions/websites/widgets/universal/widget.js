/**
 * Platform Extension: Websites / widget functions
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

function check_direct_value(inputbox)
{
    var coin_name   = $(inputbox).attr('coin_name');
    var minimum     = parseFloat( $(inputbox).attr('minimum') );
    var lower_limit = parseFloat( $(inputbox).attr('lower_limit') );
    var maximum     = parseFloat( $('.balance_target[coin_name='+coin_name+']').attr('real_balance') );
    var value       = parseFloat( $(inputbox).val() );

    if( value >= lower_limit && value <= maximum ) $(inputbox).removeClass('errored');
    else                                           $(inputbox).addClass('errored');
} // end function

function change_amount(coin_name, direction)
{
    var inputbox    = 'input[name="amount['+coin_name+']"]';
    var minimum     = parseFloat( $(inputbox).attr('minimum') );
    var maximum     = parseFloat( $('.balance_target[coin_name='+coin_name+']').attr('real_balance') );
    var lower_limit = parseFloat( $(inputbox).attr('lower_limit') );
    var value       = parseFloat( $(inputbox).val() );

    if( direction == 'up' ) value = value + minimum;
    else                    value = value - minimum;

    if( value < lower_limit ) value = lower_limit;
    if( value > maximum     ) value = maximum;

    $(inputbox).val( parseFloat(value.toFixed(8)) );

    check_direct_value(inputbox);
    update_coin_usd_value(coin_name);
} // end function

function update_coin_usd_value(coin_name)
{
    var inputbox     = 'input[name="amount['+coin_name+']"]';
    var target_span  = '.usd_value[for_coin="'+coin_name+'"]';
    var coins        = parseFloat($(inputbox).val());

    if( isNaN(coins) )
    {
        $(target_span).text('N/A');
        return;
    } // end if

    var dollar_price = 0, usd_value = 0;
    if( $(inputbox).closest('tr').find('.selected_coin').is(':checked') )
    {
        dollar_price = parseFloat($(inputbox).attr('dollar_price'));
        usd_value    = (coins * dollar_price).toFixed(8);
    }
    else
    {
        usd_value = 0;
    } // end if

    if( usd_value == 0 ) $(target_span).text( 'N/A' );
    else                 $(target_span).text( '$' + usd_value );

    // Sum calculation
    var usd_sum = 0;
    $('.usd_value:visible').each(function()
    {
        var value = parseFloat( $(this).text().replace('$', '') );
        if( ! isNaN(value) ) usd_sum = usd_sum + value;
    });
    if( usd_sum == 0 ) $('.usd_sum').text( 'N/A' );
    else               $('.usd_sum').text( '$' + usd_sum.toFixed(8) );

    // Sum diff if it applies
    if( $('#usd_wanted, .usd_remaining').length > 0 )
    {
        var wanted    = parseFloat( $('#usd_wanted').text() );
        var remaining = wanted - usd_sum;
        $('.usd_remaining').text('$' + remaining.toFixed(8));
        if( remaining == 0 )     $('.usd_remaining').removeClass('empty surpassed').addClass('empty');
        else if( remaining < 0 ) $('.usd_remaining').removeClass('empty surpassed').addClass('surpassed');
        else                     $('.usd_remaining').removeClass('empty surpassed');
        if( remaining < 0 ) $('#surpassed_hit').show(); else $('#surpassed_hit').hide();
    } // end if

} // end function

function reset_form()
{
    $('#submission_target').html('').hide();
    $('#transaction_submission').show();
    $('form[name="transaction_submission"]')[0].reset();
    $('form[name="transaction_submission"]').find('input[type="text"]').removeClass('errored');
    $('.coins_component tr.coin').each(function()
    {
        if( $(this).find('.selected_coin:checked').length == 0 ) $(this).removeClass('selected');
        if( parseFloat($(this).find('.balance_target').attr('real_balance')) == '0' ) $(this).find('input[type="text"]').val(0);
    }); // end .each
    update_all_coins_usd_value();
} // end function

function update_all_coins_usd_value()
{
    $('input[type="text"][coin_name]').each(function()
    {
        var coin_name = $(this).attr('coin_name');
        update_coin_usd_value(coin_name);
    }); // end .each
} // end function

function toggle_empty_coins()
{
    $('.coins_component tr.coin').each(function()
    {
        var amount = parseFloat( $(this).find('input[name*="amount"]').val() );
        if( amount == 0 ) $(this).toggle();
    }); // end function
} // end function

function get_wallet_balances()
{
    $('.balance_target:visible').each(function()
    {
        var $this      = $(this);
        var coin_name  = $this.attr('coin_name');
        var spinner    = '<img src="' + root_url + '/img/progress_16x16_gray.gif" align="absbottom">';
        var helper_url = root_url + '/toolbox.php?mode=get_address_and_balance&coin_name=' + coin_name + '&wasuuup=' + (Math.random() * 1000000000000000);

        $this.html(spinner);
        $.get(helper_url, function(response)
        {
            if( response.indexOf('OK') < 0 )
                return $this.html('<span class="fa fa-warning" style="color: red;" title="Couldn\'t get your ' + coin_name + ' balance!"></span>')

            var parts    = response.split(':');
            var minified = parts[2];
            var real     = parseFloat(parts[5]);

            $this.attr( 'minified_balance', minified )
                 .attr( 'real_balance',     real     )
                 .html( real )
                 ;

            if( real > 0 )
            {
                var title = $this.hasClass('disabled') ? ''
                          : 'Click here to set the whole amount to the input box.';
                $this.toggleClass('empty', false).attr('title', title);
            }
            else
            {
                $this.toggleClass('empty', true).attr('title', '');
                $('.coins_component tr.coin[coin_name="'+coin_name+'"]').find('input, button').prop('disabled', true);
                $('.coins_component tr.coin[coin_name="'+coin_name+'"]').find('input[type="text"]').val(0);
                $('.coins_component tr.coin[coin_name="'+coin_name+'"]').toggleClass('disabled', true);
                update_coin_usd_value(coin_name);
            } // end if
        }); // end .get
    }); // end function
} // end function

function set_all_balance_from(balance_target)
{
    $target   = $(balance_target);
    if( $target.hasClass('disabled') ) return;

    coin_name = $target.attr('coin_name');

    $input    = $('input.coin_amount_target[coin_name="'+coin_name+'"]');
    amount    = parseFloat( $target.attr('real_balance') );
    if( amount > 0 )
    {
        $input.val( amount );
        if( ! $('.selected_coin[coin_name="'+coin_name+'"]').is(':checked') )
            $('.selected_coin[coin_name="'+coin_name+'"]').click();
        else
            update_coin_usd_value( coin_name );
    } // end if
} // end function

function precheck_form_submission(formData, jqForm, options)
{
    $('#submission_target').html('').show();

    var count = $('.coins_component .selected_coin:checked').length;
    if( count == 0 ) { alert('Please select at least one coin and specify the amount to send.'); return false; }
    if( count == 1 )
        message = 'You are going to submit an operation for one coin.\n'
                + 'Processing takes about 1~3 seconds. Please do not close\n'
                + 'this window until you receive a response.\n\n'
                + 'Do you want to continue?'
                ;
    else if( count > 1 && count < 10 )
        message = 'You are going to submit an operation for '+count+' coins.\n'
                + 'Processing of each coin usually takes about 1~3 seconds.\n'
                + 'Please do not close this window until you receive a response.\n\n'
                + 'Do you want to continue?'
                ;
    else
        message = 'IMPORTANT   IMPORTANT   IMPORTANT\n\n'
                + 'You are going to submit an operation for '+count+' coins.\n'
                + 'Processing of each coin usually takes about 1~3 seconds,\n'
                + 'And if you interrupt the process, it couldn\'t be rolled back.\n'
                + 'Please do not close this window until you receive a response.\n\n'
                + 'If possible, try to reduce the amount of coins for operation.'
                + 'Do you want to continue?'
                ;
    if( ! confirm(message) ) return false;

    $.blockUI(blockUI_default_params);
    return true;
} // end function

function process_submission_response(responseText, statusText, xhr, $form)
{
    $.unblockUI();
    $('#transaction_submission').hide();
    // if( responseText.indexOf('OK') < 0 ) return alert(responseText);
} // end function

$(document).ready(function()
{
    $('#transaction_submission').ajaxForm({
        target:        '#submission_target',
        beforeSubmit:  precheck_form_submission,
        success:       process_submission_response
    });
}); // end document.ready
