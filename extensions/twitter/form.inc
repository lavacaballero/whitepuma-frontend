<?php
    $query = "select * from {$config->db_tables["twitter"]} where id_account = '{$account->id_account}'";
    $res   = mysql_query($query);
    if( mysql_num_rows($res) == 0 )
    {
        ?>

        <!-- ================== -->
        <!-- Twitter connection -->
        <!-- ================== -->

        <h2 class="ui-widget-header ui-corner-all" style="padding: 10px; margin-top: 0;">
            <span class="fa-stack fa-lg">
                <i class="fa fa-square-o fa-stack-2x"></i>
                <i class="fa fa-twitter fa-stack-1x"></i>
            </span>
            Connect with Twitter
        </h2>

        <div align="center">

            <p>Connecting your <?=$config->app_single_word_name?> account with your Twitter account will give you
            access to our Twitter TipBot! <a href="<?= $config->twitter_about_page ?>" target="_blank">Click here to learn about it.</a></p>

            <p align="center"><a href="<?=$root_url?>/extensions/twitter/connect.php"><img
               src="<?=$root_url?>/extensions/twitter/connect-with-twitter-gray.png"
               border="0" width="158" height="28" alt="Connect with Twitter"></a></p>

        </div>

        <?
    }
    else
    {
        $row = mysql_fetch_object($res);
        ?>

        <!-- ================= -->
        <!-- Twitter functions -->
        <!-- ================= -->

        <script type="text/javascript">
            /////////////////////////////
            function disconnect_twitter()
            /////////////////////////////
            {
                var message = 'WARNING • WARNING • WARNING\n\n'
                            + 'If you have balances and you haven\'t set an email and\n'
                            + 'a password for your account, they will be lost forever.\n\n'
                            + 'You should edit your account and set an email and a password\n'
                            + 'unless you want to delete the account and loose\n'
                            + 'any remaining balances.\n\n'
                            + 'Do you want to proceed with unlinking?'
                            ;
                if( ! confirm(message) ) return;
                var url = '<?=$root_url?>/extensions/twitter/connect.php?disconnect=true';
                $.get(url, function(response)
                {
                    if( response == 'OK' )
                    {
                        alert('Account has been disconnected. This page will be reloaded.');
                        location.href = '<?=$root_url?>/edit_account.php?wasuuup=' + parseInt(Math.random() * 1000000000000000);
                    } // end if

                    alert( response );
                }); // end .get
            } // end function

            //////////////////////////////////////////////////////
            function process_rerouting_submission_result(response)
            //////////////////////////////////////////////////////
            {
                alert(response);
                $('#save_rerouting input[name="dummy[reroute_name]"]').val('Please reload this page to update this field.')
            } // end function

            ////////////////////////////
            $(document).ready(function()
            ////////////////////////////
            {
                $('#save_rerouting').ajaxForm({
                    target:        '#save_rerouting_target',
                    success:       process_rerouting_submission_result
                }); // end ajaxForm
            }); // end function

        </script>

        <h2 class="ui-widget-header ui-corner-all" style="padding: 10px; margin-top: 0;">
            <span class="fa-stack fa-lg">
                <i class="fa fa-square-o fa-stack-2x"></i>
                <i class="fa fa-twitter fa-stack-1x"></i>
            </span>
            Connected with <a href="https://twitter.com/<?=$row->screen_name?>" target="_blank">@<?=$row->screen_name?></a>
        </h2>

        <div align="center" style="margin-top: 10px;">

            <div class="field">

                <div class="ui-widget-content message_box ui-corner-all">
                    <div id="save_rerouting_target" style="display: none;"></div>
                    <form name="save_rerouting" id="save_rerouting" method="post"
                          action="<?=$root_url?>/toolbox.php?mode=save_account_extensions&wasuuup=<?=md5(mt_rand(1,65535))?>">
                        <h3 class="ui-widget-header message_box ui-corner-all" style="margin-top: 0;">
                            Incoming tips re-routing
                        </h3>

                        <? $account_extensions = new account_extensions($account->id_account); ?>

                        <div align="center">
                            Specify the <?= $config->app_single_word_name ?> account id you want to automatically send future Twitter tips.<br>
                            <b>It must be an existing <?=$config->app_display_shortname?> account id</b>:<br>
                            <input type="text" name="extension_data[reroute_to]" value="<?=$account_extensions->reroute_to?>"
                                   placeholder="Type or paste user id" style="width: 30%; text-align: center;">
                            <?
                                $reroute_to_name = "Target account owner unavailable at this time";
                                if( ! empty($account_extensions->reroute_to) )
                                {
                                    $tmp = new account($account_extensions->reroute_to);
                                    $reroute_to_name = "Target account owner: {$tmp->name}";
                                } # end if
                            ?>
                            <input type="text" disabled name="dummy[reroute_name]" value="<?=$reroute_to_name?>"
                                   style="width: 68%;">
                            <br>

                            <div class="ui-state-highlight message_box ui-corner-all">
                                <span class="fa fa-info-circle"></span>
                                Tips re-routing has no fees.
                            </div>

                            <button type="submit">
                                Save
                            </button>
                        </div>
                    </form>
                </div>

            </div>

            <div class="field">

                <div class="ui-widget-content message_box ui-corner-all">
                    <p align="center">
                        <button onclick="disconnect_twitter()">
                            <span class="fa fa-unlink"></span>
                            Disconnect account
                        </button>
                    </p>

                    Considerations to take before disconnecting:
                    <ul style="margin-top: 0;">
                        <li>After disconnecting it, <u>you may get a new account</u> being automatically created if someone tips you over Twitter.</li>
                        <li>You can reconnect it or even connect it with another <?=$config->app_single_word_name?> account without messing up or
                        mixing records.</li>
                    </ul>
                </div>

            </div>

        </div>

        <?
    } # end if
    mysql_free_result($res);
?>
