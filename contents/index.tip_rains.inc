
<!-- Expandible Textarea -->
<style type="text/css">
    .expandible_textarea { overflow-x: auto; overflow-y: hidden; -moz-box-sizing: border-box; resize: none; 
                           height: 19px; max-height: 190px; padding-bottom: 2px; width: 100%; 
                           font-family: Arial, Helvetica, sans-serif; font-size: 10pt; }
</style>
<script type="text/javascript" src="<?=$config->commons_url?>/lib/jquery.exptextarea.js"></script>
<script type="text/javascript">$(document).ready(function() { $('.expandible_textarea').expandingTextArea(); });</script>

<style type="text/css">
    #active_rains_list    .rain_entry ,
    #last_rain_drops_list .drop_entry { font-weight: normal; margin-bottom: 5px; padding: 5px; }
    #active_rains_list .rain_entry .ui-widget-header { font-size: 12pt; padding: 5px; }
    #active_rains_list, #last_rain_drops_list { padding: 5px; margin-top: 5px; height: 500px; overflow: auto; }
</style>

<div id="tip_rains">
    
    <!-- ############ -->
    <!-- Introduction -->
    <!-- ############ -->
    
    <div class="ui-state-highlight message_box ui-corner-all">
        <span class="ui-icon embedded ui-icon-info"></span>
        Tip rains work in the background. When one is created, a specified amount of coins is sent to a random amount of users
        <u>from the coin's user base</u>
        until all of them are tipped or the rainmaker gets out of funds. If you're picked up for a drop, you'll notice it in the
        lists below!<br>
        You can see the whole list of recipients for a rain by clicking on the <span class="ui-icon embedded ui-icon-search"></span>
        button on each rain's title.
    </div>
    
    <!-- ####### -->
    <!-- Scripts -->
    <!-- ####### -->
    
    <script type="text/javascript">
        var user_balance = parseFloat('<?=$balance?>');
    </script>
    
    <? if($is_admin): ?>
        <? $bot_account = new account($config->tippingbot_id_acount);
           $bot_balance = $bot_account->get_balance(); ?>
        <script type="text/javascript">
            var bot_balance = parseFloat('<?=$bot_balance?>');
            function rain_bbcheck( source_object )
            {
                if( $('#new_tip_batch input[name="using_bot_account"]').is(':checked') ) return bot_balance;
                else                                                                     return user_balance;
            } // end function
        </script>
    <? endif; ?>
    
    <script type="text/javascript">
        ////////////////////////////////////
        function refresh_active_rains_list()
        ////////////////////////////////////
        {
            var wasuuup = Math.random() * 1000000000000000;
            $('#active_rains_list').block(blockUI_medium_params);
            $('#active_rains_list').load('toolbox.php?mode=show_active_rains_list&wasuuup='+wasuuup, function()
            {
                if( $('#incoming_tip_rain_counts').length > 0 )
                    $('#tip_count_stats').text( '(' + $('#incoming_tip_rain_counts').text() + ')' );
                else
                    $('#tip_count_stats').text( '' );
                $('#active_rains_list button').button();
                $('#active_rains_list').unblock();
            }); // end load
        } // end function
        
        ///////////////////////////////////////
        function refresh_last_rain_drops_list()
        ///////////////////////////////////////
        {
            var wasuuup = Math.random() * 1000000000000000;
            $('#last_rain_drops_list').block(blockUI_medium_params);
            $('#last_rain_drops_list').load('toolbox.php?mode=show_last_rain_drops_list&wasuuup='+wasuuup, function()
            {
                $('#last_rain_drops_list').unblock();
            }); // end load
        } // end function
        
        /////////////////////////////////
        function calculate_coins_needed()
        /////////////////////////////////
        {
            var recipient_amount = parseFloat( $('#new_tip_batch input[name="recipient_amount"]').val() );
            var max              = parseFloat( $('#new_tip_batch input[name="coins_per_recipient_max"]').val() );
            var max_allowed      = recipient_amount * max;
            $('#coins_needed').text( parseFloat(max_allowed).toFixed(8) );
            
            if( typeof rain_bbcheck != 'undefined' ) base_balance = rain_bbcheck();
            else                                     base_balance = user_balance;
            $('#base_balance').text( base_balance.toFixed(8) );
            
            if( max_allowed > base_balance ) $('#max_tip_message').show();
            else                             $('#max_tip_message').hide();
            
            var min              = parseFloat( $('#new_tip_batch input[name="coins_per_recipient_min"]').val() );
            var min_allowed      = parseFloat( $('#new_tip_batch input[name="coins_per_recipient_min"]').attr('min_allowed') );
            if( min < min_allowed ) $('#min_tip_message').show();
            else                    $('#min_tip_message').hide();
            
        } // end function
        
        //////////////////////////////////////////
        function post_rain_notification_to_group()
        //////////////////////////////////////////
        {
            if( $('#rain_message_data').text() == '' )
            {
                alert('Couldn\'t get information for submitting the notification to the group! Please post a notification manually.');
                return;
            } // end if
            
            rain_group_id = $('#rain_group_id').text();
            eval( 'post_data = ' + $('#rain_message_data').text() );
            
            console.log( 'Rain Group Id: ', rain_group_id );
            console.log( 'Rain message data: ', $('#rain_message_data').text() );
            console.log( 'Post data: ', post_data );
            
            FB.api(
                "/"+rain_group_id+"/feed",
                "POST",
                { "object": post_data },
                function (response) {
                    if (response && !response.error) {
                        alert('Please check the JS console to view the response.');
                        console.log(response);
                    } // end if
                } // end function
            );
        } // end function
        
        ////////////////////////////////
        function open_tip_rain_creator()
        ////////////////////////////////
        {
            $('#tip_rain_submit_target').html('');
            $('#new_tip_batch')[0].reset();
            $('#tip_rain_creator .tip_rain_maker_text').show();
            $('#tip_rain_creator button[type="submit"]').show();
            $('#active_rains_contents').hide();
            $('#tip_rain_creator').show();
            // $.scrollTo('top', 'fast'); 
        } // end function
        
        /////////////////////////////////
        function close_tip_rain_creator()
        /////////////////////////////////
        {
            $('#tip_rain_submit_target').html('');
            $('#new_tip_batch')[0].reset();
            $('#tip_rain_creator .tip_rain_maker_text').hide();
            $('#tip_rain_creator').hide();
            $('#active_rains_contents').show();
            
            $.scrollTo('#tabs', 'fast');
            refresh_active_rains_list();
            refresh_last_rain_drops_list();
        } // end function
        
        ////////////////////////////
        $(document).ready(function()
        ////////////////////////////
        {
            $('#new_tip_batch').ajaxForm({
                target:        '#tip_rain_submit_target',
                beforeSubmit:  function()
                               {
                                   $.scrollTo('#tip_rain_creator', 'fast');
                                   $('#tip_rain_creator').block(blockUI_medium_params);
                               },
                success:       function()
                               {
                                   var message = $('#tip_rain_submit_target .message_box').text().replace(/\s+/g, ' ');
                                   $('#tip_rain_creator').unblock();
                                   if( message.indexOf("Error") >= 0 )
                                   {
                                       alert( message );
                                   }
                                   else
                                   {
                                       $('#tip_rain_creator .tip_rain_maker_text').hide();
                                       $('#tip_rain_creator button[type="submit"]').hide();
                                       // $('#new_tip_batch')[0].reset();
                                       // $('#tip_rain_intro').show();
                                       // $('#tip_rain_creator').dialog('close');
                                       // post_rain_notification_to_group();
                                   } // end if
                               } // end function
            }); // end ajaxform
            
            $('#tip_rains button').button();
            $('#tip_rains .buttonset').buttonset();
            refresh_active_rains_list();
            refresh_last_rain_drops_list();
        }); // end document.ready
    </script>
    
    <!-- ############ -->
    <!-- Rain details -->
    <!-- ############ -->
    
    <div id="tip_rain_details" class="tip_rain_details" style="display: none;">
        
        <div class="ui-widget-content message_box ui-corner-all" style="padding: 5px;">
            <h2 class="ui-widget-header message_box ui-corner-all" style="margin-top: 0;">
                    <span class="fa fa-times fa-border pull-right" title="Close rain details" style="cursor: pointer;" onclick="$(this).closest('.tip_rain_details').hide()"></span>
                <span class="title"></span>
            </h2>
            <div class="contents"></div>
        </div>
        
    </div><!-- /#tip_rain_details -->
    
    <!-- ########## -->
    <!-- RainMaking -->
    <!-- ########## -->
    
    <div id="tip_rain_creator" style="display: none;">
        
        <div class="ui-widget-content message_box ui-corner-all">
            
            <h2 class="ui-widget-header message_box ui-corner-all" style="margin-top: 0;">Tip rain maker</h2>
            
            <form name="new_tip_batch" id="new_tip_batch" method="post" action="toolbox.php">
                
                <input type="hidden" name="mode" value="create_tip_rain">
                
                <div id="tip_rain_submit_target"></div>
                
                <div class="tip_rain_maker_text">
                    <div id="tip_rain_intro" class="ui-state-highlight message_box ui-corner-all">
                        <b>Important considerations:</b>
                        <ul style="margin-top: 0; margin-bottom: 0; padding-left: 24px;">
                            <li><b>Tip rains now go over the coin's user base.</b> Group selection is only used to send notifications.</li>
                            <li>Please note: you need to give the app extended permissions (publish in groups on your behalf) so it can post notifications on the target group.
                                These permisions will only be used when you make tip rains.<br>
                                Please hit the button below to authorize the app or re-authorize it if you experience problems.<br>
                                <fb:login-button scope="<?=$config->facebook_auth_scope?>,publish_actions" show_faces="false" onlogin="alert('Done! Now please continue making your rain (~_~)')">
                                    (Re)authorize now
                                </fb:login-button>
                            </li>
                            <li>The bot will post a notification <u>on your behalf</u> on the target group, <u>but neither you or any of the recipients will be tagged</u>.
                                The notification will have a link back to the user's dashboard and it will automatically open details of the rain.</li>
                            <li>When the rain is submitted, the recipients are picked up <u>from the coin's user base</u> and added to a pool.
                                This pool has a speed of <u><?=$config->tip_rain_submissions_per_minute?> records per minute</u> from the oldest to the newest,
                                so, you may need to wait until your rain gets its turn.</li>
                            <li>You should <u>keep a close eye to your balance</u> in order to prevent a rain to be
                                cancelled <b>because of insufficient funds</b>! On the first fail before the last drop,
                                the rain will stop and wont be restarted.</li>
                            <li>You will not be punished because of a rain being cancelled, but <u>users will know it</u>
                                and it may affect your reputation. <b>Don't start a rain unless you are willing to afford it.</b></li>
                        </ul>
                        If you understand and are willing to abide to these points, please scroll down and fill all the fields of the form before submitting your rain.
                    </div><!-- /#tip_rain_intro -->
                    
                    <div>
                        <b>Which group do you want to notify?</b>
                        <i>Member counts are cached per hour and <u>they don't reflect the coin user base</u>.</i>
                    </div>
                    <div style="margin-left: 24px; margin-bottom: 10px;">
                        <?
                            $object_list = array();
                            foreach($config->facebook_monitor_objects as $key => $object)
                            {
                                $name = $object["name"];
                                $user_count = $object["type"] == "group" ? get_flag_value("group_counts:$key") : 0;
                                $object_list[$name] = array(
                                    "id"         => $object["id"],
                                    "type"       => $object["type"],
                                    "url"        => $object["url"],
                                    "name"       => $object["name"],
                                    "user_count" => $user_count
                                );
                            } # end foreach
                            ksort($object_list);
                        ?>
                        <select name="target_group" style="width: 100%; font-size: 14pt;">
                            <? $c = 0; foreach( $object_list as $key => $data ) { ?>
                                <? if($data["type"] == "group") { ?>
                                    <option <? if($c++ == 0) echo "selected"; ?> value="<?=$data["id"]?>">
                                        <?=$data["name"]?> 
                                        <?= empty($data["user_count"]) ? "" : "[".$data["user_count"]." members]" ?>
                                    </option>
                                <? } # end if ?>
                            <? } # end foreach ?>
                        </select>
                    </div>
                    
                    <div>
                        <b>Type a brief description for your rain</b> (your motivation, etc):
                        <i>You may include any link (URL, not &lt;a&gt; tag), better if you shorten it using <a href="http://goo.gl" target="_blank">Goo.gl</a>.
                        It wont be clickable on Facebook but it will be clickable on our Community Pulse.</i>
                    </div>
                    <div style="margin-left: 24px; margin-bottom: 10px;">
                        <input type="text" name="batch_title" style="width: 100%;">
                    </div>
                    
                    <div>
                        <b>Are you promoting something?</b> Explain everything you want and include other links here. Be concise and short.<br>
                        <i>This will not be included on the post to the group, but as a signature on the rain's link on our Community Pulse.</i>
                    </div>
                    <div style="margin-left: 24px; margin-bottom: 10px;">
                        <textarea name="signature" class="expandible_textarea"></textarea>
                    </div>
                    
                    <?
                        $query      = "select count(id_account) as user_count 
                                       from {$config->db_tables["account_wallets"]} 
                                       where coin_name = '{$config->current_coin_name}'";
                        $res        = mysql_query($query);
                        $row        = mysql_fetch_object($res);
                        $user_count = $row->user_count;
                        mysql_free_result($res);
                    ?>
                    <div>
                        <b>How many recipients and which kind?</b>
                        <i><?=$config->current_coin_name?> has <b><?=number_format($user_count)?> users</b> eligible for tipping.</i>
                    </div>
                    <div style="margin-left: 24px; margin-bottom: 10px;">
                        <input type="text" name="recipient_amount" size="5" style="text-align: center; font-size: 15px;" value="1" onkeyup="calculate_coins_needed()">
                        <div class="buttonset" style="display: inline-block;">
                            <input type="radio" checked name="recipient_type" id="rtype_any" value="any">
                            <label for="rtype_any">Any</label>
                            <input type="radio" name="recipient_type" id="rtype_tipper" value="tipper">
                            <label for="rtype_tipper">Tippers only</label>
                            <input type="radio" name="recipient_type" id="rtype_non_tipper" value="non_tipper">
                            <label for="rtype_non_tipper">Non-tippers only</label>
                        </div>
                        <div class="ui-state-highlight message_box ui-corner-all">
                            <span class="ui-icon embedded ui-icon-info"></span>
                            Tippers will be picked if they've tipped at least <b>two</b> different users.
                            Anyone else will be picked only if they're active.
                        </div>
                        <div class="ui-state-highlight message_box ui-corner-all">
                            <span class="ui-icon embedded ui-icon-alert"></span>
                            The more specific you are, the longer it takes to build the list.
                        </div>
                    </div>
                    
                    <div>How much do you want to tip?</div>
                    <div style="margin-left: 24px; margin-bottom: 10px;">
                        <? if($is_admin) { ?>
                            <div style="margin-bottom: 5px;">
                                <label><b><input type="checkbox" name="using_bot_account" value="true" onclick="calculate_coins_needed()">Tip using the bot's pool</b></label>
                            </div>
                        <? } # end if ?>
                        
                        Min: <input type="text" name="coins_per_recipient_min" size="8" style="text-align: center;" value="<?= round_crypto($config->current_coin_data["min_transaction_amount"]) ?>" min_allowed="<?= round_crypto($config->current_coin_data["min_transaction_amount"]) ?>" onkeyup="calculate_coins_needed()">
                        Max: <input type="text" name="coins_per_recipient_max" size="8" style="text-align: center;" value="<?= round_crypto($config->current_coin_data["min_transaction_amount"]) ?>" onkeyup="calculate_coins_needed()">
                        <span id="min_tip_message" class="ui-state-error ui-corner-all" style="display: none; padding: 1px 10px; margin-left: 5px;">
                            <b>Min is <span class="coin_signed"><?= round_crypto($config->current_coin_data["min_transaction_amount"]) ?></span>!</b>
                        </span>
                        <span id="max_tip_message" class="ui-state-error ui-corner-all" style="display: none; padding: 1px 10px; margin-left: 5px;">
                            <b>Max unbearable!</b>
                        </span>
                        <div class="ui-state-highlight message_box ui-corner-all">
                            <span class="ui-icon embedded ui-icon-info"></span>
                            Specify the same amount in both boxes for a fixed tip size.
                        </div>
                    </div>
                    
                    <div><b>Please review before submitting:</b></div>
                    <div style="margin-left: 24px; margin-bottom: 10px;">
                        <div class="ui-state-active message_box ui-corner-all">
                            <span class="ui-icon embedded ui-icon-alert"></span>
                            You must keep <span class="coin_signed"><span id="coins_needed"><?= round_crypto($config->current_coin_data["min_transaction_amount"]) ?></span></span>
                            out of your <span class="coin_signed"><span id="base_balance"><?= $balance ?></span></span>.
                            If you get out of funds before the rain ends, everyone will notice it and you'll gain bad reputation.
                        </div>
                        <div class="ui-state-active message_box ui-corner-all">
                            <span class="ui-icon embedded ui-icon-alert"></span>
                            Your rain should start <span id="rain_start_mins"><?
                            $query = "
                                select count(recipient_facebook_id) as outstanding_deliveries
                                from ".$config->db_tables["tip_batch_submissions"]."
                                where state = 'pending'
                            ";
                            $res = mysql_query($query);
                            $row = mysql_fetch_object($res);
                            mysql_free_result($res);
                            if($row->outstanding_deliveries == 0) echo 1;
                            else                                  echo ceil($row->outstanding_deliveries / $config->tip_rain_submissions_per_minute) + 1;
                            ?></span> minutes after creating it
                        </div>
                    </div>
                </div><!-- /.tip_rain_maker_text -->
                
                <div align="center">
                    
                    <button type="reset" onclick="close_tip_rain_creator(); return false;" style="width: 30%;">
                        <span class="fa fa-ban"></span>
                        Close
                    </button>
                    
                    <button type="submit" style="width: 30%;">
                        Make it rain!
                        <span class="fa fa-play"></span>
                    </button>
                    
                </div>
                
            </form>
            
        </div>
        
    </div><!-- /#tip_rain_creator -->
    
    <!-- ##################### -->
    <!-- Active rains contents -->
    <!-- ##################### -->
    
    <div id="active_rains_contents" class="columns" style="-moz-column-count: 2; -moz-column-gap: 10px; -webkit-column-count: 2; -webkit-column-gap: 10px;">
        
        <div class="active_tip_rains ui-widget-content ui-corner-all" style="padding: 5px;">
            <div class="ui-widget-header ui-corner-all" style="padding: 5px;">
                <button class="smaller" onclick="refresh_active_rains_list()" title="Refresh this list" style="float: right; height: 28px; margin-top: -2px;">
                    <span class="ui-icon embedded ui-icon-refresh"></span>
                </button>
                Latest tip rains <span id="tip_count_stats"></span>
                <? if( ! $admin_impersonization_in_effect ) { ?>
                    <? if( ! $config->current_coin_data["coin_disabled"] ) { ?>
                        <button class="smaller" onclick="open_tip_rain_creator(); return false;">Start one!</button>
                    <? } # end if ?>
                <? } # end if ?>
            </div>
            <div id="active_rains_list" class="ui-widget-content ui-corner-all">
                Please wait...
            </div>
        </div><!-- /.active_tip_rains -->
        
        <div class="last_tip_rain_drops ui-widget-content ui-corner-all" style="padding: 5px;">
            <div class="ui-widget-header ui-corner-all" style="padding: 5px;">
                <button class="smaller" onclick="refresh_last_rain_drops_list()" title="Refresh this list" style="float: right; height: 28px; margin-top: -2px;">
                    <span class="ui-icon embedded ui-icon-refresh"></span>
                </button>
                Last tip rain drops (ordered by time/recipient name)
            </div>
            <div id="last_rain_drops_list" class="ui-widget-content ui-corner-all">
                Please wait...
            </div>
        </div><!-- /.last_tip_rain_drops -->
    </div><!-- /.columns -->
    
</div><!-- /#tip_rains -->

