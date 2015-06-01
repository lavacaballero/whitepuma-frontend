
<? if( stristr($config->admin_tab_functions_disabled, "logs") !== false ) return; ?>

<div class="view_logs ui-widget-content ui-corner-all" style="padding: 5px; margin-top: 20px;">

    <div class="ui-widget-header ui-corner-all" style="padding: 5px;">
        Monitor bot logs
        <button onclick="refresh_logs_list()">
            <span class="ui-icon embedded ui-icon-refresh"></span>
            Refresh list
        </button>
    </div>

    <? if( ! is_writable("../logs")): ?>
        <div class="ui-state-error message_box ui-corner-all" style="font-size: 14pt; text-align: center;">
            <span class="ui-icon embedded ui-icon-info"></span>
            The logs directory is not writable! Please make it globally accessible (chmod 0777) to keep track of
            monitor operations!
        </div>
    <? endif; ?>

    <div id="logs_container">
        <div class="ui-state-highlight message_box ui-corner-all" style="text-align: center; padding: 20px;">
            <img src="img/progress_16x16_gray.gif" height="16" width="16"> Loading list. Please wait.
        </div>
    </div><!-- /#logs_container -->

    <script type="text/javascript">
        ////////////////////////////
        function refresh_logs_list()
        ////////////////////////////
        {
            $('#logs_container')
                .block(blockUI_default_params)
                .load('toolbox.php?mode=throw_monitor_bot_logs&wasuuup='+(Math.random() * 1111111111111111), function()
                {
                    $('#logs_container').unblock();
                    $('#logs_container a.buttonized').button();
                });
        } // end function

        //////////////////////////////
        function open_log_viewer(file)
        //////////////////////////////
        {
            // $.blockUI(blockUI_default_params);
            var url = 'toolbox.php?mode=show_log&file=' + escape(file) + '&wasuuup=' + Math.round(Math.random() * 100000000000);
            window.open(url);
            return;
            $( "#log_helper" ).load(url, function()
            {
                $.unblockUI();
                $( "#log_helper" ).dialog({
                    title:    'Log file viewer ~ ' + file,
                    modal:    true,
                    width:    $(window).width() - 100,
                    height:   $(window).height() - 150,
                    buttons:  {
                        "Reload": function()
                        {
                            var wasuuup = Math.random() * 100000000000;
                            $( "#log_helper" ).html('Reloading...');
                            $( "#log_helper" ).load('toolbox.php?mode=show_log&file=' + escape(file) + '&wasuuup=' + Math.round(Math.random() * 100000000000));
                        },
                        "Close": function()
                        {
                            $(this).dialog("destroy");
                        }
                    }
                });
            });
        } // end function

        ////////////////////////////
        $(document).ready(function()
        ////////////////////////////
        {
            refresh_logs_list();
        }); // end document.ready
    </script>

    <div id="log_helper" style="display: none;"></div>

</div><!-- /.view_logs -->

