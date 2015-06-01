<script type="text/javascript">
    ///////////////////////////////////////
    function show_withdrawal_request_form()
    ///////////////////////////////////////
    {
        $('#alternate_info').hide();
        $('#withdraw_request_form').show();
    } // end function
    
    ////////////////////////////////////
    function submit_withdrawal_request()
    ////////////////////////////////////
    {
        coin_name = $('input[name="coin_name"]').val();
        amount    = $('input[name="withdraw_coins"]').val();
        address   = $('input[name="target_address"]').val();
        var url   = 'request_withdrawal.php?amount=' + amount + '&coin_name=' + coin_name + '&address=' + address + '&wasuuup=' + (Math.random() * 1000000000000000) ;
        $('#withdraw_request_form').block(blockUI_medium_params);
        $.get(url, function(response)
        {
            $('#withdraw_request_form').unblock();
            if( response == 'OK' )
            {
                alert('Request successfully sent. Please check your email and follow instructions.');
                $('input[name="withdraw_coins"]').val('all');
                $('input[name="target_address"]').val('');
                close_withdrawal_request_form();
            }
            else
            {
                alert( 'Couldn\'t send the request email. Reason:\n\n' 
                     + response 
                     + '\n\n'
                     + 'If you need asistance, please look for the Help & Support link and contact us.'
                     );
            } // end if
        }); // end function
        
    } // end function
    
    ////////////////////////////////////////
    function close_withdrawal_request_form()
    ////////////////////////////////////////
    {
        $('#withdraw_request_form').hide();
    } // end function
</script><!-- /widthraw-request-dialog -->

<div id="withdraw_request_form" class="ui-widget-content message_box ui-corner-all" style="display: none;">
    
    <h2 class="ui-widget-header message_box ui-corner-all" style="margin-top: 0;">
        Withdrawal request submission
    </h2>
    
<?
    $standard_show = true;
    if( empty($account->email) && empty($account->alternate_email) && ! $admin_impersonization_in_effect && ! $session_from_cookie )
    {
        if( ! $config->facebook_login_enforced )
        {
            ?>
            <div class="message_box ui-state-error ui-corner-all">
                <span class="ui-icon embedded ui-icon-alert"></span>
                There is no email address tied to your account!
                You need to <a href="edit_account.php">edit your account</a> and set an email address in order to withdraw funds.
                If you need help <a href="<?=$config->website_pages["support"]?>" target="_blank">please contact us ASAP</a>.
            </div>
            <?
        }
        else
        {
            try
            {
                $user_profile         = $facebook->api("/me");
                $account->name        = $user_profile["name"];
                $account->email       = $user_profile["email"];
                $account->timezone    = $user_profile["timezone"];
                $account->last_update = date("Y-m-d H:i:s");
                $account->save();
            }
            catch( Exception $e )
            {
                $standard_show = false;
            } # end try...catch
        } # end if
    } # end if
    
    if( ! $standard_show )
    {
        ?>
        <div class="message_box ui-state-error ui-corner-all">
            <span class="ui-icon embedded ui-icon-alert"></span>
            We couldn't get your email address! Please de-authorize this app from
            <a href="https://www.facebook.com/settings?tab=applications" target="_blank">your Facebook apps configuration</a>
            and then re-authorize it <a href="<?=$_SERVER["PHP_SELF"]?>?wasuuup=<?=md5(mt_rand(1,65535))?>">Reloading this page</a>.
            Then try the withdraw again. If this can't be achieved,
            <a href="<?=$config->website_pages["support"]?>" target="_blank">please contact us ASAP</a>.
        </div>
        <?
    }
    else
    {
        ?>
        <div>
            <span class="ui-state-active ui-corner-all" style="display: inline-blocK; padding: 0 5px;">
                Min withdraw: <b><span class="coin_signed"><?= $min_withdraw?></span></b>
            </span>
            <? if( ! empty($network_tx_fee)) { ?>
                <span class="ui-state-active ui-corner-all" style="display: inline-blocK; padding: 0 5px;">
                    Network withdraw fee: <b><span class="coin_signed"><?= $network_tx_fee?></span></b>
                    (for miners)
                </span>
            <? } # end if ?>
            <? if( ! empty($withdraw_fee)) { ?>
                <span class="ui-state-active ui-corner-all" style="display: inline-blocK; padding: 0 5px;">
                    Platform Provider (<?=$config->current_tipping_provider_data["shortname"]?>) Fee:
                    <b><span class="coin_signed"><?= $withdraw_fee?></span></b>
                    (to help pay servers)
                </span>
            <? } # end if ?>
        </div><br>
        
        <input type="hidden" name="coin_name" value="<?= $config->current_coin_name ?>">
        
        Please enter the amount of <?=$config->current_coin_data["coin_name_plural"]?> you want to withdraw:
        <div style="margin-left: 25px;">
            <input type="text" name="withdraw_coins" size="20" style="text-align: center;" value="all"> of <span class="coin_signed"><?= $account->get_balance() ?></span>
        </div><br>
        
        Enter a wallet address to receive them <b>and double check it is correct</b>:<br>
        <div style="margin-left: 25px;">
            <input type="text" name="target_address" style="width: 100%;">
        </div><br>
        
        <div class="message_box ui-state-highlight ui-corner-all">
            <span class="ui-icon embedded ui-icon-info"></span>
            Once you submit this info, an email will be sent to <b><?= empty($account->email) ? $account->alternate_email : $account->email; ?></b>
            with the latest calculated balance and a confirmation link to follow.<br>
            <b>Please ensure you can get email on that address!</b> and check your spam folder
            looking for our email.<br>
            If you can't get it after a few mins, is possible that your email provider is
            blocking email from our server. In such case, please <a href="<?=$config->website_pages["support"]?>" target="_blank">contact us</a> so we can
            help you get your coins with a manual transfer.
        </div>
        
        <p align="center">
            <button onclick="submit_withdrawal_request()">
                Submit
                <span class="fa fa-play"></span>
            </button>
            &nbsp;
            <button onclick="close_withdrawal_request_form()">
                <span class="fa fa-ban"></span>
                Close
            </button>
        </p>
        
        <?
    } # end if
?>
</div><!-- /#withdraw_request_form -->

