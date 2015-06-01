
<div id="alternate_login" class="ui-widget-content message-box ui-corner-all" style="text-align: center; padding: 5px;">
    
    <h2 class="ui-widget-header message_box ui-corner-all" style="margin-top: 0; margin-bottom: 10px; padding: 5px;">
        Alternate login
    </h2>
    
    Your alternate email address:
    <input type="text" name="alternate_login_email" style="width: 200px;" value="">
    
    Your alternate password:
    <input type="password" name="alternate_login_password" style="width: 100px;" value="">
    
    <button onclick="do_alternate_login()">
        Login
        <span class="fa fa-play"></span>
    </button>
    
    <br>
    
    Forgotten password? Enter your email above and <span class="pseudo_link" 
    onclick="do_alternate_login('request_alt_password_reset')">request a password reset</span>.
    
</div>

<script type="text/javascript">
    /////////////////////////////////
    function do_alternate_login(mode)
    /////////////////////////////////
    {
        if( typeof mode == 'undefined' ) mode = 'do_alternate_login';
        var params = {
            'email':    $('input[name="alternate_login_email"]').val(),
            'password': $('input[name="alternate_login_password"]').val(),
            'mode':     mode
        }
        var url = 'toolbox.php?wasuuup='+(Math.random() * 1000000000000000);
        $('#alternate_login').block(blockUI_medium_params);
        $.post(url, params, function(response)
        {
            if( response != 'OK' )
            {
                alert( 'There has been a problem:\n\n' 
                     + response + '\n\n'
                     + 'Please try again. If you need assistance, please click the "Help & support" link on the page and post a support request.'
                     );
                $('#alternate_login').unblock();
                return;
            } // end function
            
            if( mode == 'request_alt_password_reset' )
            {
                alert( 'An email has been sent to the address you\'ve specified. Please check your inbox -and your spam box- and follow instructions.\n\n'
                     + 'If you can\'t get the email after a few minutes, please click the "Help & support" link on the page and post a support request.' 
                     );
                $('#alternate_login').unblock();
                return;
            } // end if
            
            location.href = 'index.php?wasuuup='+(Math.random() * 1000000000000000);
        }); // end .post
        
    } // end function
</script>
