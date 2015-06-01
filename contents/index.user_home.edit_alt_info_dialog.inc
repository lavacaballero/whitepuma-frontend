<script type="text/javascript">
    
    var alternate_info_submitted = false;
    
    /////////////////////////////////////
    function show_alternate_info_editor()
    /////////////////////////////////////
    {
        $('#withdraw_request_form').hide();
        $('#alternate_info').show();
    } // end function
    
    //////////////////////////////////////
    function prepare_alt_info_submission()
    //////////////////////////////////////
    {
        $('#alternate_info').block(blockUI_medium_params);
        $('#alternate_info').scrollTo(0, 'fast');
    } // end function
    
    //////////////////////////////////////
    function process_alt_info_submission()
    //////////////////////////////////////
    {
        alternate_info_submitted = true;
        $('#alternate_info').unblock();
        alert( $('#alternate_info_target').text().replace(/\s+/g, ' ') );
    } // end function
    
    /////////////////////////////////////
    function hide_alternate_info_editor()
    /////////////////////////////////////
    {
        if( alternate_info_submitted )
        {
            var active = $('#tabs').tabs('option', 'active');
            location.href = '<?=$_SERVER["PHP_SELF"]."?wasuuup=".md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>&tab=' + active;
            return;
        } // end if
        
        $('#alternate_info_form')[0].reset();
        $('#alternate_info').hide();
    } // end function
    
    ////////////////////////////
    $(document).ready(function()
    ////////////////////////////
    {
        $('#alternate_info_form').ajaxForm({ 
            target:        '#alternate_info_target',
            beforeSubmit:  prepare_alt_info_submission,
            success:       process_alt_info_submission
        }); 
    }); // end function
</script><!-- /aternate info dialog -->

<div id="alternate_info" class="ui-widget-content message_box ui-corner-all" style="display: none;">
    
    <h2 class="ui-widget-header message_box ui-corner-all" style="margin-top: 0;">
        Emergency recovery login info
    </h2>
    
    <div id="alternate_info_target">
        <div class="ui-state-highlight message_box ui-corner-all">
            <span class="ui-icon embedded ui-icon-info"></span>
            The information you provide here is for your own security. It is being stored on our database to be used
            for an alternate login method. Please fill the form below and bookmark this URL so you can get into
            your dashboard in case you can't access to Facebook:
            <a href="<?=$config->website_pages["root_url"]?>" target="_blank"><u><?=$config->website_pages["root_url"]?></u></a>
        </div>
    </div>
    
    <form name="alternate_info_form" id="alternate_info_form" method="post" action="toolbox.php?mode=save_alternate_info&wasuuup=<?=md5(mt_rand(1,65535))?>">
        
        The email on your FB account (as it was provided to us):
        <div style="margin-left: 25px;">
            <input type="text" disabled name="email" style="width: 450px;" value="<?= empty($account->email) ? "<none available>" : htmlspecialchars($account->email)?>">
            <div class="ui-state-highlight message_box ui-corner-all">
                <span class="ui-icon embedded ui-icon-info"></span>
                This address can't be edited, for it is provided by Facebook.
            </div>
        </div><br>
        
        Alternate email address:
        <div style="margin-left: 25px;">
            <input type="text" name="alternate_email" style="width: 450px;" value="<?=htmlspecialchars($account->alternate_email)?>">
            <div class="ui-state-highlight message_box ui-corner-all">
                <span class="ui-icon embedded ui-icon-info"></span>
                Please be sure that you can access this account. Withdrawal request emails are also sent here.
            </div>
        </div><br>
        
        Alternate authentication password:
        <div style="margin-left: 25px;">
            <input type="password" name="alternate_password" style="width: 450px;" value="">
            <div class="ui-state-highlight message_box ui-corner-all">
                <span class="ui-icon embedded ui-icon-info"></span>
                Please remember it and keep it safe! It is encrypted before being stored, so if you loose it and you can't access your
                email accounts, you will not be able to access to your dashboard.
            </div>
        </div><br>
        
        Please repeat the password:
        <div style="margin-left: 25px;">
            <input type="password" name="alternate_password2" style="width: 450px;" value="">
        </div>
        
        <p align="center">
            <button type="submit">
                Submit
                <span class="fa fa-play"></span>
            </button>
            &nbsp;
            <button type="reset" onclick="hide_alternate_info_editor(); return false;">
                <span class="fa fa-ban"></span>
                Close
            </button>
        </p>
        
    </form>
</div><!-- /#alternate_info -->
