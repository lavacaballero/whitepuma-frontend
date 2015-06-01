
<? if( stristr($config->admin_tab_functions_disabled, "groups") !== false ) return; ?>

<div id="group_functions_container" class="group_functions ui-widget-content ui-corner-all" style="padding: 5px; margin-top: 20px;">

    <div class="ui-widget-header ui-corner-all" style="padding: 5px; margin-bottom: 5px;">
        Supported groups
        <button onclick="show_group_addition_form()">
            <span class="ui-icon embedded ui-icon-document"></span>
            Register new
        </button>
        <button onclick="refresh_groups_list()">
            <span class="ui-icon embedded ui-icon-refresh"></span>
            Refresh list
        </button>
    </div>

    <? if( ! is_writable("../groups.dat")): ?>
        <div class="ui-state-error message_box ui-corner-all" style="font-size: 14pt; text-align: center;">
            <span class="ui-icon embedded ui-icon-info"></span>
            The "groups.dat" file is not writable! Please make it globally accessible (chmod 0777) before attempting
            to add any groups!
        </div>
    <? endif; ?>

    <div id="new_group_form_target"></div>

    <div id="new_group_form_container" style="display: none;">
        <form name="new_group_form" id="new_group_form" method="post" action="toolbox.php">
            <input type="hidden" name="mode" value="add_group">

            <div class="ui-state-highlight message_box ui-corner-all">
                <span class="ui-icon embedded ui-icon-info"></span>
                Before adding a new group, join it and ask the admins/owners. Never add groups without permission.
                The list of submitted requests is
                <a href="https://docs.google.com/spreadsheets/d/1sqcj7uH_l8l0EHdloWrvdfPEKvrh5Jx0tuWy7m67yCA/pubhtml" target="_blank">here</a>.<br>
                <b>Important: make sure the group is <u>open</u> before adding it!</b>
            </div>

            <table class="tablesorter" width="100%" cellpadding="2" cellspacing="1" border="0">
                <tbody>
                    <tr>
                        <td width="30%">
                            <b>Handler:</b><br>
                            <i>Internal handler. Alphanumeric, lower case, no spaces, only underscores.
                            Make sure to put the <u>_group</u> suffix.</i>
                        </td>
                        <td width="70%"><input type="text" name="handler" style="width: 100%;"></td>
                    </tr>
                    <tr>
                        <td width="30%">
                            <b>Group name (description):</b><br>
                            <i>Do not add quotes or double quotes. Be sure to add the "Group" suffix.
                            If the group is from an official coin, make sure to specify it.</i>
                        </td>
                        <td width="70%"><input type="text" name="name" style="width: 100%;"></td>
                    </tr>
                    <tr>
                        <td width="30%">
                            <b>URL:</b><br>
                            <i>Make sure the URL points to the main page <u>of a group</u><br>
                            (like http://www.facebook.com/groups/number_or_name/).</i>
                        </td>
                        <td width="70%"><input type="text" name="url" style="width: 100%;"></td>
                    </tr>
                    <tr>
                        <td width="30%">
                            <b>Facebook Group id:</b><br>
                            Lookup by URL <a href="http://lookup-id.com/" target="_blank">using this app</a>
                            (you may need to authorize it)
                        </td>
                        <td width="70%"><input type="text" name="id" style="width: 100%;"></td>
                    </tr>
                    <tr>
                        <td width="30%">
                            <b>Attached tipbot account:</b><br>
                            <i>Use the user lookup form above to find it. Default is "Javier A. Caballero" account.</i>
                        </td>
                        <td width="70%"><input type="text" name="tippingbot_id_acount" value="ianbel8i6d" style="width: 100%;"></td>
                    </tr>
                    <tr>
                        <td width="30%">
                            <b>Attached tipbot Facebook id:</b><br>
                            <i>Use the user lookup form above to find it.</i>
                        </td>
                        <td width="70%"><input type="text" name="tippingbot_fb_id_account" value="100007642663617" style="width: 100%;"></td>
                    </tr>
                </tbody>
            </table>

            <div align="center">
                <button type="submit">
                    <span class="ui-icon embedded ui-icon-check"></span>
                    Submit
                </button>
                <button type="reset" onclick="close_group_addition_form()">
                    <span class="ui-icon embedded ui-icon-closethick"></span>
                    Cancel
                </button>

            </div>
        </form>
    </div><!-- /#new_group_form_container -->

    <div id="current_groups" class="table_wrapper">
        <div class="ui-state-highlight message_box ui-corner-all" style="text-align: center; padding: 20px;">
            <img src="img/progress_16x16_gray.gif" height="16" width="16"> Loading list. Please wait.
        </div>
    </div><!-- /#current_groups -->

    <script type="text/javascript">
        //////////////////////////////
        function refresh_groups_list()
        //////////////////////////////
        {
            $('#current_groups')
                .block(blockUI_default_params)
                .load('toolbox.php?mode=throw_groups_admin_table&wasuuup='+(Math.random() * 1111111111111111), function()
                {
                    $('#current_groups').unblock()
                });
        } // end function

        //////////////////////////////
        function delete_group(handler)
        //////////////////////////////
        {
            if( confirm('Are you sure do you want to delete group "'+handler+'"?\n\nThis cannot be undone.') )
            {
                var url = 'toolbox.php?mode=delete_group&handler=' + escape(handler);
                $('#new_group_form_target').load(url, function(response)
                {
                    if( response.indexOf('error') < 0 )
                        $('#current_groups tr[group_handler="'+handler+'"]').fadeOut('fast');
                    $.scrollTo('#group_functions_container', 'fast');
                }); // end .load
            } // end if
        } // end function

        ///////////////////////////////////
        function show_group_addition_form()
        ///////////////////////////////////
        {
            $('#current_groups').hide();
            $('#new_group_form_container').show();
            $('#new_group_form_target').html('');
            $('#new_group_form_container form[name="new_group_form"] input[name="handler"]').focus();
        } // end function

        ////////////////////////////////////
        function close_group_addition_form()
        ////////////////////////////////////
        {
            $('#current_groups').show();
            $('#new_group_form_container').hide();
            $('#new_group_form')[0].reset();
        } // end function

        ///////////////////////////////////////////////////////////////
        function check_submitted_group_result(text, status, xhr, $form)
        ///////////////////////////////////////////////////////////////
        {
            if( text.indexOf('error') < 0 )
                close_group_addition_form();
        } // end function

        ////////////////////////////
        $(document).ready(function()
        ////////////////////////////
        {
            $('#current_groups').load('toolbox.php?mode=throw_groups_admin_table&wasuuup='+(Math.random() * 1111111111111111));

            $('#new_group_form').ajaxForm({
                target:  '#new_group_form_target',
                success: check_submitted_group_result
            });
        }); // end document.ready
    </script>
</div><!-- /.group_functions -->
