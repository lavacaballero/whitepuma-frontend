<?php
    /**
     * Platform Extension: Websites / Transaction processor
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
     * POST params being trailed from the widget:
     * @param encrypted string token              public key of the website, encrypted,
     * @param           string website_public_key Per-se
     * @param           string button_id          Per-se
     * @param           string ref                Optional. Referral code
     * @param           string entry_id           Optional. For recording in the OpsLog
     * @param           string entry_title        Optional. For recording in the OpsLog
     * @param           string target_data        Optional. email or data:stringed_email
     * @param           string http_referer       Trailed from the widget invocator
     *
     * POST params added by the widget:
     * @param            mixed selected           It may be a string or an array
     * @param            array amount             Keys are coin names, values are coin values
     */

    header( "Content-Type: text/html; charset: UTF-8" );
    $root_url = "../../../..";

    ####################################
    function throw_error($error_message)
    ####################################
    {
        die( $error_message);
    } # end function

    if( ! is_file("$root_url/config.php") ) throw_error("ERROR: config file not found.");
    include "$root_url/config.php";
    include "$root_url/functions.php";
    include "$root_url/lib/geoip_functions.inc";
    include("$root_url/models/tipping_provider.php");
    include("$root_url/models/account.php");
    include("$root_url/models/account_extensions.php");
    db_connect();

    # Basic validations
    $messages          = array();
    $include_all_coins = true;
    include "bootstrap.inc";

    ###################
    # Account related #
    ###################

    /**
    * Here we have:
    * $account         Sender
    * $invoker_website The one embedding the button
    * $website         The same or the owner of the button if different
    * $button          Per-se
    * $target_data     Target account
    */

    if( empty($invoker_website->id_account) ) $invoker_website = $website;

    ###############################
    # Target account pre-creation #
    ###############################

    $save_new_recipient = false;
    if( ! empty($target_data->email) && empty($target_data->id_account) )
    {
        # Let's add the account
        $target_data->name               = "Unconfirmed user";
        $target_data->alternate_password = md5(randomPassword());
        $target_data->tipping_provider   = $config->current_tipping_provider_keyname;
        $target_data->date_created       =
        $target_data->last_update        =
        $target_data->last_activity      = date("Y-m-d H:i:s");
        $target_data->id_account         = $config->custom_account_creation_prefix . uniqid(true);
        $save_new_recipient = true;
    } # end if

    # Recipient calculation
    if( ! empty($target_data->email) )
        $recipient =& $target_data; # Target specified. Coins go to his account.
    else
        $recipient = new account($website->id_account);     # No target specified. Coins go to the button's website owner.

    $messages[] = "Recipient: account: $recipient->id_account ($recipient->name)";

    ##################
    # Self-tip check #
    ##################

    if( $account->id_account == $recipient->id_account )
    {
        $html = "<div class='ui-state-error message_box ui-corner-all'>"
              . "   <span class='fa fa-warning'></span>"
              . "   Thou cannot send coins to thyself."
              . "   <div align='center'>"
              . "       <button big onclick=\"$('#submission_target').html('').hide(); $('#transaction_submission').show()\">"
              . "           <span class='fa fa-undo'></span>"
              . "           Dismiss &amp; retry"
              . "       </button>"
              . "   </div>"
              . "</div>";
        die($html);
    } # end if

    ###########################
    # Transaction validations #
    ###########################

    if( empty($_POST["selected"]) ) die("ERROR: No coin to send has been selected.");
    if( ! is_array($_POST["selected"]) ) $_POST["selected"] = array($_POST["selected"] => true);

    foreach($_POST["selected"] as $coin_name => $selected)
    {
        if( ! isset($coins_data[$coin_name])  )   die("ERROR: $coin_name does not exist or it has been removed before the reception of your submission. Please reload the widget to show only valid coins.");
        if( $coins_data[$coin_name]->disabled )   die("ERROR: $coin_name has been disabled before the reception of your submission. Please reload the widget to show only valid coins.");
        if( empty($_POST["amount"][$coin_name]) ) die("ERROR: You selected $coin_name but didn't specify a value. Please try again.");
    } # end foreach

    #############################
    # Requested minimums checks #
    #############################

    reset($_POST["selected"]); reset ($_POST["amount"]);
    $error = "";
    if( $button->properties->request_type == "fixed" )
    {
        if( $button->properties->coin_scheme == "single_from_default_coin" )
        {
            if( count($_POST["selected"]) > 1 ) $error = "You selected more than one coin. You must send only one coin.";
            $coin_name = "";
            if( empty($error) )
            {
                $coin_name = key($_POST["selected"]);
                if( $coin_name != $button->properties->default_coin )
                    $error = "Requested coin is " . $button->properties->default_coin . " and you specified $coin_name.
                              Please stick to the request";
            } # end if
            if( empty($error) )
            {
                $amount = $_POST["amount"][$coin_name];
                if( $amount != $button->properties->coin_amount )
                {
                    $error = "A specific amount of ".($button->properties->coin_amount)." $coin_name is being requested,
                              and you're sending ".($amount)." which is different. Please adjust the amount to match the request.";
                } # end if
            } # end if
        }
        elseif( $button->properties->coin_scheme == "multi_direct" )
        {
            if( count($_POST["selected"]) > 1 ) $error = "You selected more than one coin. You must send only one coin.";
            $coin_name = "";
            if( empty($error) )
            {
                $coin_name      = key($_POST["selected"]);
                $accepted_coins = implode( ", ", array_keys((array) $button->properties->per_coin_requests) );
                if( empty($button->properties->per_coin_requests->{$coin_name}) )
                    $error = "The requested coins are $accepted_coins and you specified $coin_name which is not on the list.
                              Please stick to the request";
            } # end if
            if( empty($error) )
            {
                $amount = $_POST["amount"][$coin_name];
                if( $amount != $button->properties->per_coin_requests->{$coin_name}->amount )
                {
                    $error = "A specific amount of ".($button->properties->per_coin_requests->{$coin_name}->amount)." for $coin_name is being requested,
                              and you're sending ".($amount)." which is different. Please adjust the amount to match the request or select a different coin.";
                } # end if
            } # end if
        }
        elseif( $button->properties->coin_scheme == "multi_converted" )
        {
            $usd_sum = 0;
            $accepted_coins = implode( ", ", array_keys((array) $button->properties->per_coin_requests) );
            foreach($_POST["selected"] as $coin_name => $selected)
            {
                if( ! isset($button->properties->per_coin_requests->{$coin_name}) )
                {
                    $error = "You're sending $coin_name, which is not in the request's accepted coins ($accepted_coins).";
                    break;
                } # end if
                $usd_sum += $_POST["amount"][$coin_name] * $usd_prices[$coin_name];
            } # end if
            if( $usd_sum < $button->properties->amount_in_usd )
                $error = "Your order is below the requested amount by \$".ltrim(number_format($usd_sum - $button->properties->amount_in_usd, 8), "-")."
                          (You're sending \$".number_format($usd_sum, 8) . "
                          but the request is for \$".number_format($button->properties->amount_in_usd, 8) . ").";
        } # end if
    }
    elseif( $button->properties->request_type == "suggestion" )
    {
        if( $button->properties->coin_scheme == "single_from_default_coin" )
        {
            if( count($_POST["selected"]) > 1 ) $error = "You selected more than one coin. You must send only one coin.";
            $coin_name = "";
            if( empty($error) )
            {
                $coin_name = key($_POST["selected"]);
                if( $coin_name != $button->properties->default_coin )
                    $error = "Requested coin is " . $button->properties->default_coin . " and you specified $coin_name.
                              Please stick to the request";
            } # end if
            if( empty($error) )
            {
                $amount = $_POST["amount"][$coin_name];
                if( $amount < $button->properties->coin_amount )
                {
                    $error = "A minimum amount of ".($button->properties->coin_amount)." $coin_name is being requested,
                              and you're sending ".($amount)." which is below. Please raise the amount.";
                } # end if
            } # end if
        }
        elseif( $button->properties->coin_scheme == "multi_direct" )
        {
            $accepted_coins = implode( ", ", array_keys((array) $button->properties->per_coin_requests) );
            foreach($_POST["selected"] as $coin_name => $selected)
            {
                if( ! isset($button->properties->per_coin_requests->{$coin_name}) )
                {
                    $error = "You're sending $coin_name, which is not in the request's accepted coins ($accepted_coins).";
                    break;
                }
                else
                {
                    if( $_POST["amount"][$coin_name] < $button->properties->per_coin_requests->{$coin_name}->amount )
                    {
                        $error = "You're sending ".$_POST["amount"][$coin_name]." $coin_name, but the minimum suggested amount is " . $button->properties->per_coin_requests->{$coin_name}->amount . ". "
                               . "Please raise it or select another coin.";
                        break;
                    } # end if
                } # end if
            } # end if
        }
        elseif( $button->properties->coin_scheme == "multi_converted" )
        {
            $usd_sum = 0;
            $accepted_coins = implode( ", ", array_keys((array) $button->properties->per_coin_requests) );
            foreach($_POST["selected"] as $coin_name => $selected)
            {
                if( ! isset($button->properties->per_coin_requests->{$coin_name}) )
                {
                    $error = "You're sending $coin_name, which is not in the request's accepted coins ($accepted_coins).";
                    break;
                } # end if
                $usd_sum += $_POST["amount"][$coin_name] * $usd_prices[$coin_name];
            } # end if
            if( $usd_sum < $button->properties->amount_in_usd )
                $error = "Your order is below the requested amount by \$".ltrim(number_format($usd_sum - $button->properties->amount_in_usd, 8), "-")."
                          (You're sending \$".number_format($usd_sum, 8) . "
                          but the request is for \$".number_format($button->properties->amount_in_usd, 8) . ").";
        } # end if
    } # end if
    if( ! empty($error) )
    {
        $html = "<div class='ui-state-highlight message_box ui-corner-all'>"
              . "   <span class='fa fa-info-circle'></span>"
              . "   $error<br><br>"
              . "   If you need assistance, copy this information and"
              . "   paste it on a new topic at our <a href='".$config->website_pages["support"]."' target='_blank'>Help &amp; support forum.</a>"
              . "   <div align='center'>"
              . "       <button big onclick=\"$('#submission_target').html('').hide(); $('#transaction_submission').show()\">"
              . "           <span class='fa fa-undo'></span>"
              . "           Dismiss &amp; retry"
              . "       </button>"
              . "   </div>"
              . "</div>";
        die($html);
    } # end if

    ####################
    # Balance checking #
    ####################

    $error = "";
    $messages[] = "Starting sender balance checks.";
    foreach($_POST["selected"] as $coin_name => $selected)
    {
        # Let's check if the coin is enabled
        if( $coins_data[$coin_name]->disabled )
        {
            $error = "Sorry, but $coin_name has been disabled before you placed the order.
                      Please check <a href='$config->facebook_app_page' target='_blank'>our news page</a> to get information about it.";
            break;
        } # end if

        # Let's check the balance...
        $amount = $_POST["amount"][$coin_name];

        # Coin switching
        $tipping_provider_keyname                 = get_tipping_provider_keyname_by_coin($coin_name);
        $config->current_tipping_provider_keyname = $tipping_provider_keyname;
        $config->current_coin_name                = $coin_name;
        $config->current_tipping_provider_data    = $config->tipping_providers_database[$config->current_tipping_provider_keyname];
        $config->current_coin_data                = $config->current_tipping_provider_data["per_coin_data"][$config->current_coin_name];
        $coin_symbol                              = strtoupper($config->current_coin_data["coin_sign"]);

        $tipping_provider = new tipping_provider( $config->current_coin_data["api_url"]    ,
                                                  $config->current_tipping_provider_data["public_key"] ,
                                                  $config->current_tipping_provider_data["secret_key"] );
        $tipping_provider->cache_api_responses_time = 0;

        $res = $tipping_provider->get_balance($account->id_account);
        if( $res->message != "OK" )
        {
            $error = "Couldn't get your $coin_name balance! Response from the server: " . json_encode($res);
            break;
        } # end if
        if( $res->data < $amount )
        {
            $error = "Your $coin_name balance is ".($res->data).". You can't place the order for ".($amount). ".
                      Please deposit on your wallet or select another coin (if possible).";
            break;
        } # end if
        $messages[] = "$coin_name balance holds the amount to send.";
    } # end foreach
    $messages[] = "Balance checks ended.";
    if( ! empty($error) )
    {
        $html = "<div class='ui-state-error message_box ui-corner-all'>"
              . "   <span class='fa fa-info-warning'></span>"
              . "   $error<br><br>"
              . "   If you need assistance, copy this information and"
              . "   paste it on a new topic at our <a href='".$config->website_pages["support"]."' target='_blank'>Help &amp; support forum.</a>"
              . "   <div align='center'>"
              . "       <button big onclick=\"$('#submission_target').html('').hide(); $('#transaction_submission').show(); get_wallet_balances();\">"
              . "           <span class='fa fa-undo'></span>"
              . "           Dismiss, update balances &amp; retry"
              . "       </button>"
              . "   </div>"
              . "</div>";
        die($html);
    } # end if

    ###########################
    # Target account creation #
    ###########################

    if( $save_new_recipient )
    {
        # Let's add the account
        $target_data->save();

        $account_extensions                               = new account_extensions($target_data->id_account);
        $account_extensions->id_account                   = $target_data->id_account;
        $account_extensions->referer_website_key          = $invoker_website->public_key;
        $account_extensions->referer_button_website_key   = $button->website_public_key;
        $account_extensions->referer_button_id            = $button->button_id;
        $account_extensions->referer_button_referral_code = $_POST["ref"];
        $account_extensions->save();

        $token        = encryptRJ256($config->tokens_encryption_key, $target_data->id_account);
        $mail_from    = "$config->mail_sender_name<$config->mail_sender_address>";
        $mail_to      = $target_data->email;

        $mail_subject = $config->app_display_longname . " - Welcome letter";
        $mail_body = "We're excited to let you know that someone you may know as decided to send you\r\n"
                   . "cryptocurrencies through $config->app_display_shortname.\r\n"
                   . "This usually happens when you're referenced on one of our subscriber websites\r\n"
                   . "that allow their users and guests to receive tips for posting contents or\r\n"
                   . "replying to messages (such as blogs and forums).\r\n"
                   . "\r\n"
                   . "An account has been setup for you using this email address as identifier.\r\n"
                   . "Deposit wallet addresses will be added further.\r\n"
                   . "\r\n"
                   . "To login and claim your coins, send them to others or have access to\r\n"
                   . "cryptocurrency to dollar conversions, please use the next info:\r\n"
                   . "\r\n"
                   . "Website: ".$config->website_pages["root_url"]."\r\n"
                   . "Your email: $target_data->email\r\n"
                   . "Your password: $target_data->alternate_password\r\n"
                   . "\r\n"
                   . "Note: once you login, please edit your account and change your password.\r\n"
                   . "\r\n"
                   . "If you're not interested or don't want to receive further messages from us,\r\n"
                   . "please follow the next link:\r\n"
                   . "".$config->website_pages["toolbox"] . "?mode=disable_notifications&token=" . urlencode($token)."\r\n"
                   . "\r\n"
                   . "Feel free to contact us if you have any doubt by following the next link:\r\n"
                   . $config->website_pages["support"] . "\r\n"
                   . "\r\n"
                   . "Regards,\r\n"
                   . $config->app_display_longname . "'s Team\r\n"
                   ;
        @mail(
            $mail_to, $mail_subject, $mail_body,
            "From: ".$mail_from . "\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-Type: text/plain; charset=utf-8\r\n"
        );

    } # end if

    #######################
    # Transactions per-se #
    #######################

    /** @var  [ id_account:string => amount_to_charge:mixed ] */
    $fee_targets    = array();
    $successful     =
    $failed         = array();
    $operation_id   = uniqid(true);
    $messages[]     = "Order id is $operation_id";
    $from_handler   = $website->public_key
                    . "/" . $button->button_id
                    . ( empty($_POST["ref"]) ? "" : "/".$_POST["ref"] )
                    . ":" . $operation_id
                    ;
    $operation_timestamp       = time();
    $successful_opslog_ids     = array();
    $successful_opslog_fee_ids = array();

    foreach($_POST["selected"] as $coin_name => $selected)
    {
        # Coin switching
        $tipping_provider_keyname                 = get_tipping_provider_keyname_by_coin($coin_name);
        $config->current_tipping_provider_keyname = $tipping_provider_keyname;
        $config->current_coin_name                = $coin_name;
        $config->current_tipping_provider_data    = $config->tipping_providers_database[$config->current_tipping_provider_keyname];
        $config->current_coin_data                = $config->current_tipping_provider_data["per_coin_data"][$config->current_coin_name];
        $coin_symbol                              = strtoupper($config->current_coin_data["coin_sign"]);

        $tipping_provider = new tipping_provider( $config->current_coin_data["api_url"]    ,
                                                  $config->current_tipping_provider_data["public_key"] ,
                                                  $config->current_tipping_provider_data["secret_key"] );
        $tipping_provider->cache_api_responses_time = 0;

        $amount      = $_POST["amount"][$coin_name];
        $messages[]  = "Entering $coin_name, working with $amount $coin_symbol.";

        # How many fees outputs are done?
        if( count($fee_targets) == 0 )
        {
            $fee_targets = array( $config->current_coin_data["fees_account"] => $config->current_coin_data["transaction_fee"] );
            if( ! empty($target_data->email) )
            {
                # Target specified. Coins go to his account.
                # If it is different than the invoker website owner (leech!), we add a fee going to the invoker website owner.
                if( $recipient->id_account != $invoker_website->id_account && $invoker_website->id_account != $account->id_account )
                {
                    $fee_targets[$invoker_website->id_account] = $config->buttons_leeching_fee;
                    $messages[] = "Added '$invoker_website->id_account' as fee target from a defined target.";
                } # end if
            }
            else
            {
                # No target specified. Coins go to the button's website owner.
                # If the invoker and the button's website are different. Fee goes to the invoker.
                if( $recipient->id_account != $invoker_website->id_account && $invoker_website->id_account != $account->id_account )
                {
                    $fee_targets[$invoker_website->id_account] = $config->buttons_leeching_fee;
                    $messages[] = "Added '$invoker_website->id_account' as fee target from standard selection.";
                } # end if
            } # end if
            $messages[] = "Fee charges for the recipient: " . json_encode($fee_targets);
        } # end if

        # $messages[] = "Recipient: $recipient->id_account ($recipient->name) ";
        # echo "<pre>$config->current_coin_name \$fees_targets := ".print_r($fees_targets, true)."</pre>";
        # echo "<pre>$config->current_coin_name \$recipient := ".print_r($recipient, true)."</pre>";

        # If the target has no wallet address, we make it for him
        $address = $recipient->get_wallet_address_for_coin($coin_name);
        if( ! empty($address) )
        {
            $messages[] = "Wallet address for recipient found.";
        }
        else
        {
            $messages[] = "Recipient doesn't have a $coin_name wallet address. Attempting to register it.";
            $res = $tipping_provider->get_address($recipient->id_account);
            if( $res->message == "ERROR:UNREGISTERED_ACCOUNT" )
            {
                $messages[] = "Account $recipient->id_account not registered in the wallet daemon. Attempting to register it.";
                $res = $tipping_provider->register($recipient->id_account);
                if( $res->message != "OK" )
                {
                    $messages[] = "Can't register the account on the wallet daemon.";
                    $failed[$coin_name] = "Can't register account on the wallet daemon.";
                    continue;
                } # end if
            } # end if
            if( $res->message == "OK" )
            {
                if( ! is_wallet_address($res->data) )
                {
                    $messages[] = "Account registered. The deposit address looks invalid.";
                    $failed[$coin_name] = "New address received from the daemon looks invalid.";
                    continue;
                } # end if
                if( empty($recipient->wallet_address) )
                {
                    $recipient->wallet_address = $res->data;
                    $recipient->last_update = date("Y-m-d H:i:s");
                    $recipient->save();
                    $messages[] = "Wallet address saved.";
                } # end if
            } # end if
        } # end if

        # Let's put the transaction
        $res = $tipping_provider->send($account->id_account, $recipient->id_account, $amount);
        if( stristr($res->message, "ERROR") !== false )
        {
            $messages[] = "Sending of ".($amount)." $coin_symbol failed: $res->message: " . $res->extended_info->returned_message;
            $failed[$coin_name] = $res->message;
            $query = "
                insert into ".$config->db_tables["log"]." set
                entry_type        = 'website_button',
                from_handler      = '$from_handler',
                entry_id          = '".addslashes($button->properties->entry_id)."',
                action_type       = 'send',
                from_facebook_id  = '',
                from_id_account   = '".$account->id_account."',
                coin_name         = '".$coin_name."',
                coins             = '".($amount)."',
                to_facebook_id    = '',
                to_id_account     = '".$recipient->id_account."',
                message           = '".addslashes($button->properties->entry_id)."',
                state             = 'ERROR',
                info              = 'UNABLE_TO_SEND_COINS',
                date_analyzed     = '".date("Y-m-d H:i:s", $operation_timestamp)."',
                api_call_message  = '$res->message',
                api_extended_info = '".json_encode($res->extended_info)."',
                date_processed    = '".date("Y-m-d H:i:s", $operation_timestamp)."'
            ";
            mysql_query($query);
            break;
        }
        else
        {
            $messages[] = "Sending of ".($amount)." $coin_symbol OK.";
            $successful[$coin_name] = $amount;
            $query = "
                insert into ".$config->db_tables["log"]." set
                entry_type        = 'website_button',
                from_handler      = '$from_handler',
                entry_id          = '".addslashes($button->properties->entry_id)."',
                action_type       = 'send',
                from_facebook_id  = '',
                from_id_account   = '".$account->id_account."',
                coin_name         = '".$coin_name."',
                coins             = '".($amount)."',
                to_facebook_id    = '',
                to_id_account     = '".$recipient->id_account."',
                message           = '".addslashes($button->properties->entry_id)."',
                state             = 'OK',
                info              = '',
                date_analyzed     = '".date("Y-m-d H:i:s", $operation_timestamp)."',
                api_call_message  = '',
                api_extended_info = '',
                date_processed    = '".date("Y-m-d H:i:s", $operation_timestamp)."'
            ";
            mysql_query($query);
            $successful_opslog_ids[$coin_name] = mysql_insert_id();
        } # end if
    } # end foreach
    sleep(1);

    ###############
    # Final steps #
    ###############

    $per_coin_paid_fees = array();
    #==================================================#
    if( count($failed) == 0 && count($successful) == 0 )
    #==================================================#
    {
        $html = "<div class='ui-state-highlight message_box ui-corner-all'>"
              . "   <span class='fa fa-info-circle'></span>"
              . "   No order has been recorded. Please try again.<br>"
              . "   Full operations log:"
              . "   <ul><li>" . implode("</li><li>", $messages) . "</li></ul>"
              . "   If you need assistance, copy this information and paste it on a new topic at our "
              . "   <a href='".$config->website_pages["support"]."' target='_blank'>Help &amp; support forum.</a>"
              . "   <div align='center'>"
              . "       <button big onclick=\"$('#submission_target').html('').hide(); $('#transaction_submission').show()\">"
              . "           <span class='fa fa-undo'></span>"
              . "           Dismiss &amp; retry"
              . "       </button>"
              . "   </div>"
              . "</div>";
        die($html);
    }
    #=====================================================#
    elseif( count($failed) > 0 && count($successful) == 0 )
    #=====================================================#
    {
        $html = "<div class='ui-state-highlight message_box ui-corner-all'>"
              . "   <span class='fa fa-info-circle'></span>"
              . "   Some transactions failed but they don't need to be rolled back.<br>"
              . "   Full operations log:"
              . "   <ul><li>" . implode("</li><li>", $messages) . "</li></ul>"
              . "   Please try again. If you need assistance, copy this information and"
              . "   paste it on a new topic at our <a href='".$config->website_pages["support"]."' target='_blank'>Help &amp; support forum.</a>"
              . "   <div align='center'>"
              . "       <button big onclick=\"$('#submission_target').html('').hide(); $('#transaction_submission').show()\">"
              . "           <span class='fa fa-undo'></span>"
              . "           Dismiss &amp; retry"
              . "       </button>"
              . "   </div>"
              . "</div>";
        die($html);
    }
    #====================================================#
    elseif( count($failed) > 0 && count($successful) > 0 )
    #====================================================#
    {
        $messages[] = "Errors detected. " . count($successful). " transactions will be checked for rollback.";
        foreach($successful as $coin_name => $amount)
        {
            # Coin switching
            $tipping_provider_keyname                 = get_tipping_provider_keyname_by_coin($coin_name);
            $config->current_tipping_provider_keyname = $tipping_provider_keyname;
            $config->current_coin_name                = $coin_name;
            $config->current_tipping_provider_data    = $config->tipping_providers_database[$config->current_tipping_provider_keyname];
            $config->current_coin_data                = $config->current_tipping_provider_data["per_coin_data"][$config->current_coin_name];
            $coin_symbol                              = strtoupper($config->current_coin_data["coin_sign"]);

            $tipping_provider = new tipping_provider( $config->current_coin_data["api_url"]    ,
                                                      $config->current_tipping_provider_data["public_key"] ,
                                                      $config->current_tipping_provider_data["secret_key"] );
            $tipping_provider->cache_api_responses_time = 0;

            $res = $tipping_provider->send($recipient->id_account, $account->id_account, $amount);
            if( stristr($res->message, "ERROR") !== false )
            {
                $messages[] = "CRITICAL: returning of ".($amount)." $coin_symbol failed: $res->message";
                continue;
            }
            else
            {
                $messages[] = "Returning of ".($amount)." $coin_symbol OK.";
                $query = "
                    update ".$config->db_tables["log"]." set
                        state             = 'RETURNED'
                    where
                        entry_type        = 'website_button' and
                        from_handler      = '$from_handler' and
                        entry_id          = '".addslashes($button->properties->entry_id)."' and
                        coin_name         = '".$coin_name."' and
                        coins             = '".($amount)."' and
                        state             = 'OK'
                ";
                mysql_query($query);
                $query = "
                    insert into ".$config->db_tables["log"]." set
                    entry_type        = 'website_button',
                    from_handler      = '$from_handler',
                    entry_id          = '".addslashes($button->properties->entry_id)."',
                    action_type       = 'return',
                    from_facebook_id  = '',
                    from_id_account   = '".$account->id_account."',
                    coin_name         = '".$coin_name."',
                    coins             = '".($amount)."',
                    to_facebook_id    = '',
                    to_id_account     = '".$recipient->id_account."',
                    message           = '".addslashes($button->properties->entry_id)."',
                    state             = 'OK',
                    info              = 'Transaction rolled back due to failures in multiple coins order.',
                    date_analyzed     = '".date("Y-m-d H:i:s")."',
                    api_call_message  = '',
                    api_extended_info = '',
                    date_processed    = '".date("Y-m-d H:i:s")."'
                ";
                mysql_query($query);
            } # end if
        } # end foreach
        $messages[] = "Rollbacks loop finished.";

        $mail_recipients = array();
        if( ! empty($recipient->email          ) ) $mail_recipients[] = "$recipient->name<$recipient->email>";
        if( ! empty($recipient->alternate_email) ) $mail_recipients[] = "$recipient->name alternate email<$recipient->alternate_email>";

        $ip           = $_SERVER['REMOTE_ADDR'];
        $hostname     = gethostbyaddr($_SERVER['REMOTE_ADDR']);
        $fecha_envio  = date("Y-m-d H:i:s");
        $mail_from    = "$config->mail_sender_name<$config->mail_sender_address>";
        $url          = $config->website_pages["root_url"];

        reset($successful); reset($failed);
        $short_coins_info = count($successful) > 1
                          ? count($successful) . " different coins"
                          : current($successful) . " " . $config->current_tipping_provider_data["per_coin_data"][key($successful)]["coin_sign"];

        $successful_info = $failed_info = array();
        foreach($successful as $coin_name => $amount)
        {
            $coin_symbol = $config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_sign"];
            $successful_info[] = "$coin_name: ".($amount) . " $coin_symbol";
        } # end foreach
        foreach($failed as $coin_name => $amount)
        {
            $coin_symbol = $config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_sign"];
            $failed_info[] = "$coin_name: ".($amount) . " $coin_symbol";
        } # end foreach

        $token        = encryptRJ256($config->tokens_encryption_key, $recipient->id_account);
        $mail_subject = $config->app_display_shortname . " - Failed incoming Cryptocurrency order notification";
        $mail_body = ( empty($mail_recipients) ? "(No email notification sent to the recipient)\r\n\r\n" : "" )
                   . "There has been an attempt to complete an order of $short_coins_info.\r\n"
                   . "to your account from another user. This is not an error for you, it is\r\n"
                   . "just a courtesy to keep you informed in case of a claim from the sender.\r\n"
                   . "\r\n"
                   . "Order id: $operation_id\r\n"
                   . "Full order handler: $from_handler\r\n"
                   . ( empty($_POST["http_referer"]) ? ""
                     : "Invoked from:\r\n" .$_POST["http_referer"] . "\r\r"
                     )
                   . "Invoker website: [$invoker_website->public_key] $invoker_website->name\r\n"
                   . "Related button id: $button->button_id\r\n"
                   . "Button caption: ".$button->properties->caption."\r\n"
                   . "Owned by: [$website->public_key] $website->name\r\n"
                   . "Button entry id: ".$button->properties->entry_id."\r\n"
                   . "Button entry title: ".$button->properties->entry_title."\r\n"
                   . "\r\n"
                   . "Sender info:\r\n"
                   . "Account Id: $account->id_account\r\n"
                   . "Name: $account->name\r\n"
                   . "Email: ".(empty($account->email) ? "N/A" : $account->email)."\r\n"
                   . "Alternate email: ".(empty($account->alternate_email) ? "N/A" : $account->alternate_email)."\r\n"
                   . "\r\n"
                   . ( empty($failed_info) ? ""
                     : "Failed transactions:\r\n"
                     . "• " . implode("\r\n• ", $failed_info) . "\r\n"
                     )
                   . ( empty($successful_info) ? ""
                     : "Successful transactions that have been rolled back:\r\n"
                     . "• " . implode("\r\n• ", $successful_info) . "\r\n"
                     )
                   . "\r\n"
                   . "Full operations log:\r\n"
                   . "• " . implode("\r\n• ", $messages) . "\r\n"
                   . "\r\n"
                   . "You will find deep insight information when you visit your ".$config->app_display_shortname." at:\r\n"
                   . "$url\r\n"
                   . "\r\n"
                   . "Please do not reply to this email. If you need to contact us,\r\n"
                   . "go to our Help & Support forum and post a new topic:\r\n"
                   . $config->website_pages["support"] . "\r\n"
                   . "\r\n"
                   . "If you don't want to receive further notifications, please follow the next link:\r\n"
                   . "".$config->website_pages["toolbox"] . "?mode=disable_notifications&token=" . urlencode($token)."\r\n"
                   . "\r\n"
                   . "Regards,\r\n"
                   . $config->app_display_longname . "'s Team\r\n"
                   . "\r\n"
                   . "-----------------------------\r\n"
                   . "Date/Time:   $fecha_envio\r\n"
                   . "IP:          $ip\r\n"
                   . "Host:        $hostname\r\n"
                   . "Browser:     ".$_SERVER["HTTP_USER_AGENT"]."\r\n"
                   ;
        if( ! empty($mail_recipients) )
            @mail(
                implode(", ", $mail_recipients), $mail_subject, $mail_body,
                "From: ".$mail_from . "\r\n" .
                "MIME-Version: 1.0\r\n" .
                "Content-Type: text/plain; charset=utf-8"
            );
        @mail(
            $config->mail_recipient_for_alerts, "Fw: " . $mail_subject, $mail_body,
            "From: ".$mail_from . "\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-Type: text/plain; charset=utf-8\r\n"
        );

        $html = "<div class='ui-state-error message_box ui-corner-all'>"
              . "   <span class='fa fa-warning'></span>"
              . "   There were failures in the operations loop.<br>"
              . "   Full operations log:"
              . "   <ul><li>" . implode("</li><li>", $messages) . "</li></ul>"
              . "   Please review this log and report all critical failures as soon as possible "
              . "   pasting this information on a new topic at our <a href='".$config->website_pages["support"]."' target='_blank'>Help &amp; support forum.</a>"
              . "   <div align='center'>"
              . "       <button big onclick=\"$('#submission_target').html('').hide(); $('#transaction_submission').show() get_wallet_balances();\">"
              . "           <span class='fa fa-undo'></span>"
              . "           Dismiss &amp; retry"
              . "       </button>"
              . "   </div>"
              . "</div>";
        die($html);
    }
    #=====================================================#
    elseif( count($failed) == 0 && count($successful) > 0 )
    #=====================================================#
    {
        $messages[] = "All operations OK. Begining fee deductions from recipient funds.";
        foreach($successful as $coin_name => $amount)
        {
            # Coin switching
            $tipping_provider_keyname                 = get_tipping_provider_keyname_by_coin($coin_name);
            $config->current_tipping_provider_keyname = $tipping_provider_keyname;
            $config->current_coin_name                = $coin_name;
            $config->current_tipping_provider_data    = $config->tipping_providers_database[$config->current_tipping_provider_keyname];
            $config->current_coin_data                = $config->current_tipping_provider_data["per_coin_data"][$config->current_coin_name];
            $coin_symbol                              = strtoupper($config->current_coin_data["coin_sign"]);

            $tipping_provider = new tipping_provider( $config->current_coin_data["api_url"]    ,
                                                      $config->current_tipping_provider_data["public_key"] ,
                                                      $config->current_tipping_provider_data["secret_key"] );
            $tipping_provider->cache_api_responses_time = 0;

            $messages[] = "Entering $coin_name, working with $amount $coin_symbol.";

            foreach($fee_targets as $fee_target_account => $fee)
            {
                $messages[] = "Checking for $fee_target_account.";

                # Fee is always a percent
                if( empty($fee) )
                {
                    $messages[] = "No fees for target $fee_target_account.";
                    continue;
                } # end if

                $fee     = str_replace("%", "", $fee);
                $fee     = $fee / 100;
                $charged = number_format($amount * $fee, 8, ".", "");
                if( $charged < 0.00000001 )
                {
                    $messages[] = "Deduction is too low. Discarding (fee free transaction!)";
                    continue;
                } # end if

                # Let's put the transaction
                $res = $tipping_provider->send($recipient->id_account, $fee_target_account, $charged, "true");
                if( stristr($res->message, "ERROR") !== false )
                {
                    $messages[] = "Deduction of $charged $coin_symbol failed: $res->message. We sacrifice it.";
                    continue;
                }
                else
                {
                    $messages[] = "Deduction of $charged $coin_symbol OK.";
                    $query = "
                        insert into ".$config->db_tables["log"]." set
                        entry_type        = 'website_button',
                        from_handler      = '$from_handler',
                        entry_id          = '".addslashes($button->properties->entry_id)."',
                        action_type       = 'fee',
                        from_facebook_id  = '',
                        from_id_account   = '".$recipient->id_account."',
                        coin_name         = '".$coin_name."',
                        coins             = '".($charged)."',
                        to_facebook_id    = '',
                        to_id_account     = '".$fee_target_account."',
                        message           = '".addslashes($button->properties->entry_id)."',
                        state             = 'OK',
                        info              = '',
                        date_analyzed     = '".date("Y-m-d H:i:s", $operation_timestamp)."',
                        api_call_message  = '',
                        api_extended_info = '',
                        date_processed    = '".date("Y-m-d H:i:s", $operation_timestamp)."'
                    ";
                    mysql_query($query);
                    $successful_opslog_fee_ids[$coin_name][$fee_target_account] = mysql_insert_id();
                    $per_coin_paid_fees[$coin_name][$fee_target_account] = $charged;
                } # end if
                $messages[] = "Checking for $fee_target_account ended.";
            } # end foreach
        } # end foreach
        $messages[] = "Fee deductions finished.";

        list($city, $region_name, $country_name, $isp) = explode("; ", forge_geoip_location($_SERVER["REMOTE_ADDR"]));
        mysql_query("
            insert into ".$config->db_tables["website_button_log"]." set
            button_id       = '$button->button_id',
            record_type     = 'conversion',
            record_date     = '".date("Y-m-d H:i:s", $operation_timestamp)."',
            entry_id        = '".$button->properties->entry_id."',
            host_website    = '".$invoker_website->public_key."',
            referral_code   = '".$_REQUEST["ref"]."',
            target_account  = '".addslashes($recipient->id_account)."',
            target_name     = '".addslashes($recipient->name)."',
            target_email    = '".addslashes($recipient->email)."',
            client_ip       = '".$_SERVER["REMOTE_ADDR"]."',
            user_agent      = '".addslashes($_SERVER["HTTP_USER_AGENT"])."',
            country         = '".addslashes($country_name)."',
            region          = '".addslashes($region_name)."',
            city            = '".addslashes($city)."',
            isp             = '".addslashes($isp)."'
        ");

        $mail_recipients = array();
        if( ! empty($recipient->email          ) ) $mail_recipients[] = "$recipient->name<$recipient->email>";
        if( ! empty($recipient->alternate_email) ) $mail_recipients[] = "$recipient->name alternate email<$recipient->alternate_email>";

        $cc_recipients = array();
        if( ! empty($account->email          ) ) $cc_recipients[] = "$account->name<$account->email>";
        if( ! empty($account->alternate_email) ) $cc_recipients[] = "$account->name alternate email<$account->alternate_email>";

        $ip           = $_SERVER['REMOTE_ADDR'];
        $hostname     = gethostbyaddr($_SERVER['REMOTE_ADDR']);
        $fecha_envio  = date("Y-m-d H:i:s");
        $mail_from    = "$config->mail_sender_name<$config->mail_sender_address>";
        $mail_to      = implode(", ", $mail_recipients);
        $url          = $config->website_pages["root_url"];

        reset($successful); reset($failed);
        $short_coins_info = count($successful) > 1
                          ? count($successful) . " different coins"
                          : current($successful) . " " . $config->current_tipping_provider_data["per_coin_data"][key($successful)]["coin_sign"]
                          ;

        $usd_sum = 0;
        foreach($successful as $coin_name => $amount)
        {
            $coin_symbol = $config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_sign"];
            $successful_info[] = "$coin_name: ".($amount) . " $coin_symbol (worth $".number_format($amount * $usd_prices[$coin_name], 8).")";
            $usd_sum += $amount * $usd_prices[$coin_name];
        } # end foreach

        $mail_subject = $config->app_display_shortname . " - Receipt for Cryptocurrency order #$operation_id";
        $mail_body = "Order receipt for $short_coins_info at $config->app_display_longname.\r\n"
                   . "\r\n"
                   . "Order id: $operation_id\r\n"
                   . "Full order handler: $from_handler\r\n"
                   . ( empty($_POST["http_referer"]) ? ""
                     : "Invoked from:\r\n" .$_POST["http_referer"] . "\r\r"
                     )
                   . "Invoker website: [$invoker_website->public_key] $invoker_website->name\r\n"
                   . "Related button id: $button->button_id\r\n"
                   . "Button caption: ".$button->properties->caption."\r\n"
                   . "Owned by: [$website->public_key] $website->name\r\n"
                   . "Button entry id: ".$button->properties->entry_id."\r\n"
                   . "Button entry title: ".$button->properties->entry_title."\r\n"
                   . "\r\n"
                   . "Sender:\r\n"
                   . "Account Id: $account->id_account\r\n"
                   . "Name: $account->name\r\n"
                   . "Email: ".(empty($account->email) ? "N/A" : $account->email)."\r\n"
                   . "Alternate email: ".(empty($account->alternate_email) ? "N/A" : $account->alternate_email)."\r\n"
                   . "\r\n"
                   . "Recipient:\r\n"
                   . "Account Id: $recipient->id_account\r\n"
                   . "Name: ".(empty($recipient->name) ? "N/A" : $recipient->name)."\r\n"
                   . "Email: ".(empty($recipient->email) ? "N/A" : $recipient->email)."\r\n"
                   . "Alternate email: ".(empty($recipient->alternate_email) ? "N/A" : $recipient->alternate_email)."\r\n"
                   . "\r\n"
                   . "Transaction details:\r\n"
                   . "• " . implode("\r\n• ", $successful_info) . "\r\n"
                   . "-----------------------------------\r\n"
                   . "Total USD value: \$" . number_format($usd_sum, 8) . "\r\n"
                   . ( $button->properties->coin_scheme != "multi_converted" ? ""
                     : "Requested USD: \$" . number_format($button->properties->amount_in_usd, 8) . "\r\n"
                     . "Difference: \$" . number_format($usd_sum - $button->properties->amount_in_usd, 8) . "\r\n"
                     )
                   . "\r\n"
                   . "Full operations log:\r\n"
                   . "• " . implode("\r\n• ", $messages) . "\r\n"
                   . "\r\n"
                   . "You will find deep insight information when you visit your ".$config->app_display_shortname." at:\r\n"
                   . "$url\r\n"
                   . "\r\n"
                   . "%unsubscribe_info%"
                   . "\r\n"
                   . "Please do not reply to this email. If you need to contact us,\r\n"
                   . "go to our Help & Support forum and post a new topic:\r\n"
                   . $config->website_pages["support"] . "\r\n"
                   . "\r\n"
                   . "Regards,\r\n"
                   . $config->app_display_longname . "'s Team\r\n"
                   . "\r\n"
                   . "-----------------------------\r\n"
                   . "Date/Time:   $fecha_envio\r\n"
                   . "IP:          $ip\r\n"
                   . "Host:        $hostname\r\n"
                   . "Browser:     ".$_SERVER["HTTP_USER_AGENT"]."\r\n"
                   ;
        if( ! empty($mail_recipients) )
        {
            $token = encryptRJ256($config->tokens_encryption_key, $recipient->id_account);
            @mail(
                implode(", ", $mail_recipients), $mail_subject,
                str_replace("%unsubscribe_info%",
                            "If you don't want to receive further notifications, please follow the next link:\r\n"
                            . "".$config->website_pages["toolbox"] . "?mode=disable_notifications&token=" . urlencode($token)."\r\n"
                            . "\r\n" , $mail_body),
                "From: ".$mail_from . "\r\n" .
                "MIME-Version: 1.0\r\n" .
                "Content-Type: text/plain; charset=utf-8\r\n"
            );
        } # end if
        if( ! empty($cc_recipients) )
        {
            $token = encryptRJ256($config->tokens_encryption_key, $account->id_account);
            @mail(
                implode(", ", $cc_recipients), $mail_subject,
                str_replace("%unsubscribe_info%",
                            "If you don't want to receive further notifications, please follow the next link:\r\n"
                            . "".$config->website_pages["toolbox"] . "?mode=disable_notifications&token=" . urlencode($token)."\r\n"
                            . "\r\n" , $mail_body),
                "From: ".$mail_from . "\r\n" .
                "MIME-Version: 1.0\r\n" .
                "Content-Type: text/plain; charset=utf-8\r\n"
            );
        } # end if

        # Notification sending
        $redirect_to = "";
        if( ! empty($button->properties->callback) )
        {
            $button_owner_extended_info = new account_extensions($website->id_account);
            if( in_array($button_owner_extended_info->account_class, array("vip", "premium")) )
            {
                $per_coin_transaction_data = array();
                $total_usd_value           = 0;

                foreach($successful as $coin_name => $amount)
                {
                    $total_fees_paid = 0;
                    $fees_paid = array();
                    if( ! empty($per_coin_paid_fees[$coin_name]) )
                    {
                        foreach($per_coin_paid_fees[$coin_name] as $fee_target_account => $fee)
                        {
                            $fees_paid[] = array(
                                "opslog_id"         => $successful_opslog_fee_ids[$coin_name][$fee_target_account],
                                "target_account_id" => $fee_target_account,
                                "amount_paid"       => number_format($fee, 8, ".", ""),
                                "usd_value"         => number_format($fee * $usd_prices[$coin_name], 8, ".", ""),
                            );
                            $total_fees_paid += $fee;
                        } # end foreach
                    } # end if
                    $per_coin_transaction_data[] = array(
                        "opslog_id"         => $successful_opslog_ids[$coin_name],
                        "coin_name"         => $coin_name,
                        "usd_rate"          => number_format($usd_prices[$coin_name], 8, ".", ""),
                        "gross_coin_amount" => number_format($amount, 8, ".", ""),
                        "deducted_fees"     => $fees_paid,
                        "net_coin_amount"   => number_format($amount - $fee, 8, ".", ""),
                        "gross_usd_value"   => number_format($amount * $usd_prices[$coin_name], 8, ".", ""),
                        "net_usd_value"     => number_format(($amount - $total_fees_paid) * $usd_prices[$coin_name], 8, ".", ""),
                    );
                    $total_usd_value += number_format($amount * $usd_prices[$coin_name], 8, ".", "");
                } # end foreach

                $post_data = array(
                    "order_id"                  => $operation_id,
                    "timestamp"                 => $operation_timestamp,
                    "date"                      => date("Y-m-d H:i:s", $operation_timestamp) . " GMT-0500",
                    "full_order_handler"        => $from_handler,
                    "invoker_website_key"       => $invoker_website->public_key,
                    "button_id"                 => $button->button_id,
                    "button_owner_website_key"  => $website->public_key,
                    "entry_id"                  => $button->properties->entry_id,
                    "entry_title"               => $button->properties->title,
                    "ref_code"                  => stripslashes($_POST["ref"]),
                    "sender_data"               => array(
                                                       "account_id" => $account->id_account,
                                                       "name"       => $account->name,
                                                       "email"      => $account->email
                                                    ),
                    "recipient_data"            => array(
                                                       "account_id" => $recipient->id_account,
                                                       "name"       => $recipient->name,
                                                       "email"      => $recipient->email
                                                   ),
                    "request_type"              => $button->properties->request_type,
                    "coin_scheme"               => $button->properties->coin_scheme,
                    "per_coin_transaction_data" => $per_coin_transaction_data,
                    "total_usd_value"           => $total_usd_value
                );

                $ch = curl_init();
                curl_setopt( $ch, CURLOPT_URL,            $button->properties->callback );
                curl_setopt( $ch, CURLOPT_POST,           1                             );
                curl_setopt( $ch, CURLOPT_POSTFIELDS,     http_build_query($post_data)  );
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true                          );
                curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5                             );
                curl_setopt( $ch, CURLOPT_TIMEOUT,        10                            );
                $res = curl_exec($ch);
                if( $res !== false ) $redirect_to = $res;
                curl_close($ch);
            } # end if
        } # end if

        if( ! empty($redirect_to) )
        {
            $redirect_to = "
                <p><i>Message from $website->name:</i></p>
                <div class='ui-state-highlight message_box ui-corner-all' style='text-align: left;'>
                    ".substr(strip_tags(stripslashes($redirect_to), "<a>,<b>,<i>,<br>"),0,1000)."
                </div>
            ";
        } # end if
        $html = "<div class='ui-state-highlight message_box ui-corner-all'>"
              . "   <span class='fa fa-info-circle'></span>"
              . "   Order fuflilled completely. "
              . ( ! empty($mail_recipients)
                ? "Email notification sent to the recipient. "
                : "<b>No email notification sent to the recipient!</b> "
                )
              . ( ! empty($cc_recipients)
                ? "Email notification sent to you. "
                : "<b>No email notification sent to you! Please edit your account settings and set an email!</b> "
                )
              . "   <span class='pseudo_link ui-state-default ui-corner-all-all' style='padding: 1px 2px; font-size: 10pt;' onclick='\$(\"#post_submit_operations_log\").show()'>View full operations log</span>"
              . "   <div id='post_submit_operations_log' class='ui-widget-content message_box ui-corner-all' style='display: none; font-size: 10pt;'>"
              . "       <ul><li>" . implode("</li><li>", $messages) . "</li></ul>"
              . "   </div>"
              . "   <div align='center'>"
              .         $redirect_to
              . "       <button big onclick=\"$('#submission_target').html('').hide(); $('#transaction_submission').show(); reset_form(); get_wallet_balances();\">"
              . "           <span class='fa fa-undo'></span>"
              . "           Send more coins"
              . "       </button>"
              . "   </div>"
              . "</div>";
        die($html);
    } # end if

    # echo "<br><br>Messages:<br>".implode("<br>", $messages);
    # echo "<br><br>Successful:<br>".implode("<br>", $successful);
    # echo "<br><br>Failed:<br>".implode("<br>", $failed);
