
<div class="user_lookup ui-widget-content ui-corner-all" style="padding: 5px; margin-top: 20px;">
    
    <form onsubmit="return false;" style="margin: 0;">
        <div class="ui-widget-header ui-corner-all" style="padding: 5px; margin-bottom: 5px;">
            User lookup:
            Type in some chars to lookup users by name, id or email:
            <input type="text" name="user_lookup_input" size="30">
            <button type="submit" onclick="lookup_users(); return false;">
                <span class="ui-icon embedded ui-icon-play"></span>
                Submit
            </button>
            <button type="reset">
                <span class="ui-icon embedded ui-icon-refresh"></span>
                Reset
            </button>
            
        </div>
    </form>
    <div id="user_lookup_results" class="ui-widget-content ui-corner-all" style="padding: 5px;">
        <div class="ui-state-hightlight ui-corner-all" style="padding: 5px;">
            <span class="ui-icon embedded ui-icon-info"></span>
            Type in some chars in the box
        </div>
    </div>
    
    <script type="text/javascript">
        ///////////////////////
        function lookup_users()
        ///////////////////////
        {
            var search_string = $('input[name="user_lookup_input"]').val().trim();
            if( search_string == '' ) return;
            
            var url = 'toolbox.php?mode=lookup_users&q=' + escape(search_string) + '&wasuuup=' + Math.round(Math.random()*999999999999);
            $('#user_lookup_results').block(blockUI_smaller_params);
            $('#user_lookup_results').load(url, function() {
                $('#user_lookup_results').unblock();
                $('#user_lookup_results .tablesorter').tablesorter();
            }); // end .load
        } // end function
    </script>
    
</div><!-- /.user_lookup -->

<div class="view_profile ui-widget-content ui-corner-all" style="padding: 5px; margin-top: 20px;">
    
    <form onsubmit="return false;" style="margin: 0;">
        <div class="ui-widget-header ui-corner-all" style="padding: 5px;">
            View user profile by account id:
            <input type="text" name="user_account_preview" size="20">
            <button type="submit" onclick="window.open('<?=$config->website_pages["root_url"]?>?view_profile_id=' + $('input[name=user_account_preview]').val()); $('input[name=user_account_preview]').val(''); return false;">
                <span class="ui-icon embedded ui-icon-play"></span>
                Go
            </button>
        </div>
    </form>
</div><!-- /.view_profile -->
