<?
    /**
     * Feed monitor for background processing
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

    $root_url = "../..";
    set_time_limit( 3600 );
    ini_set("error_reporting", E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED & ~E_WARNING );

    if( isset($_SERVER["HTTP_HOST"])) die("<h3>This script is not ment to be called through a web browser. You must invoke it through a command shell or a cron job.</h3>");
    if( ! is_file("$root_url/config.php") ) die("ERROR: config file not found.");
    include "$root_url/config.php";
    include "$root_url/functions.php";
    include "$root_url/lib/cli_helper_class.php";
    include "$root_url/models/tipping_provider.php";
    include "$root_url/models/account.php";
    include("$root_url/lib/self_running_checker.inc");
    define( "LOCK_FILE", sprintf("/tmp/%s%s.pid", $config->session_vars_prefix, "pulse_feed_monitor") );
    db_connect();

    include "$root_url/bot_functions/generic.php";
    include "model_post.php";
    include "process_feed_entry.php";
    include "process_comments_entry.php";

    #########
    # Inits #
    #########

    $arg = extract_parameters($argv);

    $object_id   = "none";
    $object_key  = "pulse";
    $object_edge = "feed";

    #############
    # Prechecks #
    #############
    {
        if( ! $config->engine_enabled )
        {
            cli::write("\n");
            cli::write( date("Y-m-d H:i:s") . " Engine is disabled. Exiting.", cli::$forecolor_black, cli::$backcolor_red );
            cli::write("\n");
            die();
        } # end if

        $config->tippingbot_id_acount     = $config->facebook_monitor_objects[$object_key]["tippingbot_id_acount"];
        $config->tippingbot_fb_id_account = $config->facebook_monitor_objects[$object_key]["tippingbot_fb_id_account"];
    } # end prechecks block

    #################
    # Overlap check #
    #################
    {
        if( self_running_checker() )
        {
            cli::write( "                    |  Skipping new run! another instance is running!", cli::$forecolor_white, cli::$backcolor_red );
            cli::write( "\n" );
            die();
        } # end if
    } # end overlap check

    #######################
    # Facebok SDK loading #
    #######################
    {
        include "$root_url/facebook-php-sdk/facebook.php";
        $fb_params = array(
            'appId'              => $config->facebook_app_id,
            'secret'             => $config->facebook_app_secret,
            'fileUpload'         => false, // optional
            'allowSignedRequest' => true,  // optional, but should be set to false for non-canvas apps
        );

        try
        {
            $facebook = new Facebook($fb_params);
            $access_token = $facebook->getAccessToken();
        }
        catch( Exception $e )
        {
            cli::write("\n");
            cli::write("Fatal error! Can't load Facebook SDK!\n", cli::$forecolor_light_red);
            cli::write("\n");
            cli::write("Exception: " . $e->getMessage() . "\n", cli::$forecolor_light_red);
            cli::write("\n");
            cli::write("Program terminated abnormally.\n");
            cli::write("\n");
            unlink( LOCK_FILE );
            die();
        } # end try...catch

        $last_read_date = get_flag_value("$object_key/$object_edge:last_read_date");
        if( empty($last_read_date) ) $last_read_date = date("2014-01-01 00:00:00");

        $start   = time();
        cli::write("\n");
        cli::write( date("Y-m-d H:i:s") . " .- Starting feed analysis for ".cli::color("$object_key/$object_edge", cli::$forecolor_light_cyan). "[" );
        cli::write( "id:$object_id", cli::$forecolor_cyan );
        cli::write("]\n");
    } # end continuity block

    #################
    # Analysis loop #
    #################
    {
        $actions = array();

        $new_last_read_date = date("Y-m-d H:i:s");
        if( $arg["--no-date-limit"] ) cli::write( "                    |  Invoking without date limit...\n");
        else                          cli::write( "                    |  Invoking since $last_read_date...\n");

        $query = "
            select * from {$config->db_tables["pulse_posts"]}
            where hidden = 0
            and created >= '$last_read_date' and created < '$new_last_read_date'
            order by created asc
        ";
        $res = mysql_query($query);

        cli::write( "                    |  Done. " . mysql_num_rows($res) . " entries downloaded.\n");

        if( mysql_num_rows($res) == 0 )
        {
            cli::write( "                    |  Nothing to do here.\n", cli::$forecolor_yellow);
        }
        else
        {
            cli::write( "                    |  Data loop start\n" );
            $feed_comments = array();
            while($row = mysql_fetch_object($res))
            {
                $post = new pulse_post($row);
                $res2 = process_feed_entry($post);
                if( ! is_array($res2) )
                {
                    $res2->date_analyzed = date("Y-m-d H:i:s");
                    $actions[] = $res2;
                }
                else
                {
                    foreach($res2 as $index => $xaction)
                    {
                        $xaction->date_analyzed = date("Y-m-d H:i:s");
                        $actions[] = $xaction;
                    } # end if
                } # end if
            } # end foreach
            cli::write( "                    |  Data loop end\n" );
        } # end if
        cli::write( "                    '- Analysis finished in " . number_format(time() - $start) . " seconds.\n");
    } # end analysis loop

    #################
    # Comments Loop #
    #################
    {
        $query = "
            select * from {$config->db_tables["pulse_comments"]}
            where hidden = 0
            and created >= '$last_read_date' and created < '$new_last_read_date'
            order by created asc
        ";
        $res = mysql_query($query);

        $comment_actions = array();
        if( mysql_num_rows($res) == 0)
        {
            cli::write( "                    There are no comments to analyze. Comments loop will not proceed.\n", cli::$forecolor_light_blue );
        }
        else
        {
            cli::write( "                    .-- Starting comments analysis -----------------------------\n", cli::$forecolor_blue );
            while($entry = mysql_fetch_object($res) )
            {
                $res2 = process_comments_entry($entry);
                if( ! is_array($res2) )
                {
                    $res2->date_analyzed = date("Y-m-d H:i:s");
                    $comment_actions[] = $res2;
                }
                else
                {
                    foreach($res2 as $index => $xaction)
                    {
                        $xaction->date_analyzed = date("Y-m-d H:i:s");
                        $comment_actions[] = $xaction;
                    } # end if
                } # end if
            } # end foreach
            cli::write( "                    '---Comments analysis finished -----------------------------\n", cli::$forecolor_blue );
            cli::write( "                    .- Starting comment actions execution -------------------------\n", cli::$forecolor_light_blue );
            if( count($comment_actions) == 0 )
            {
                cli::write( "                    |  There are no comment actions to process.\n", cli::$forecolor_blue );
            }
            else
            {
                cli::write( "                    |  There are " . count($comment_actions) . " actions to process. Copying to actions array... ", cli::$forecolor_light_blue );
                foreach($comment_actions as $comment_action) $actions[] = $comment_action;
                cli::write( "Done!\n", cli::$forecolor_light_blue );
            } # end if
            cli::write( "                    '- Comment actions execution finished -------------------------\n", cli::$forecolor_light_blue );
        } # end if
    } # end comments loop

    ###############
    # Action loop #
    ###############
    {
        $notifications_pool = array();
        if( $arg["--no-actions-loop"] )
        {
            cli::write( "                    Actions loop skipped.\n", cli::$forecolor_yellow);
            cli::write( "                    All operations ended in " . number_format(time() - $start) . " seconds.\n");
            cli::write("\n");
            unlink( LOCK_FILE );
            die();
        } # end if

        $start2 = time();
        cli::write( "                    .- Starting action loop...\n", cli::$forecolor_light_cyan );
        if( count($actions) == 0 )
        {
            cli::write( "                    |  There are no actions to do.\n", cli::$forecolor_yellow );
        }
        else
        {
            #====================#
            # Actions loop start #
            #====================#

            $action_index = 0;
            foreach( $actions as $this_action )
            {
                $action_index++;

                /** @var action */
                $action = $this_action;

                # Presets
                $action->message  = str_replace("\n", " ", $action->message);

                # Patch to prevent the bot account being tipped
                /*
                {
                    if( $action->to_id_account == $config->tippingbot_id_acount )
                    {
                        cli::write( "                    |  Action #$action_index ~ Tip to the bot account detected. Ignoring entry.\n", cli::$forecolor_light_red );
                        continue;
                    } # end if
                }
                */

                # Some prechecks
                if( $action->state == "IGNORE" )
                {
                    cli::write( "                    |  Action #$action_index [$action->entry_type:$action->entry_id] ignored per trailing request.\n", cli::$forecolor_light_purple );
                    continue;
                } # end if
                if( $action->action_type == "not_found" )
                {
                    cli::write( "                    |  Action #$action_index [$action->entry_type:$action->entry_id] not found. Ignoring message.\n", cli::$forecolor_light_purple );
                    continue;
                } # end if
                if( empty($action->coins) )
                {
                    cli::write( "                    |  Action #$action_index [$action->entry_type:$action->entry_id] with no coins detected. Ignoring message.\n", cli::$forecolor_light_purple );
                    continue;
                } # end if

                if( empty($action->from_id_account) )
                {
                    cli::write( "                    |  * Problem with sender info: Name:='", cli::$forecolor_light_purple );
                    cli::write( $action->from_name, cli::$forecolor_purple );
                    cli::write( "', id_account:'", cli::$forecolor_light_purple );
                    cli::write( $action->from_id_account, cli::$forecolor_purple );
                    cli::write( "'. Entry ignored.\n", cli::$forecolor_purple );
                    continue;
                } # end if

                # Check if there's no double spending during the last time...
                {
                    $query = "
                        select * from ".$config->db_tables["log"] . " where
                            entry_id          = '".$action->entry_id."'
                        and from_id_account   = '".$action->from_id_account."'
                        and to_id_account     = '".$action->to_id_account."'
                        and coin_name         = '".$action->coin_name."'
                        and coins             = '".$coins."'
                    ";
                    # echo $query;
                    $resx = mysql_query($query);
                    if( mysql_num_rows($resx) > 0 )
                    {
                        cli::write( "                    |  * Entry already processed. Ignored.\n", cli::$forecolor_light_red );
                        mysql_free_result($resx);
                        continue;
                    } # end if
                    mysql_free_result($resx);
                } # end block

                # Now let's check for errors.
                # Note: this is here to prevent already processed posts being re-logged!
                if($action->state == "ERROR")
                {
                    cli::write( "                    | Entry $action->entry_type id $action->entry_id from ".$action->from_name." invalid. State: $action->state. Logging and discarding.\n", cli::$forecolor_light_red );
                    $query = "
                        insert into ".$config->db_tables["log"]." set
                        entry_type        = '".$action->entry_type."',
                        from_handler      = '".$object_key."',
                        entry_id          = '".$action->entry_id."',
                        action_type       = '".$action->action_type."',
                        from_facebook_id  = '".$action->from_facebook_id."',
                        from_id_account   = '".$action->from_id_account."',
                        message           = '".addslashes($action->message)."',
                        coin_name         = '".$action->coin_name."',
                        coins             = '".$action->coins."',
                        to_facebook_id    = '".$action->to_facebook_id."',
                        to_id_account     = '".$action->to_id_account."',
                        state             = '".$action->state."',
                        info              = '".$action->info."',
                        date_analyzed     = '$action->date_analyzed',
                        api_call_message  = '',
                        api_extended_info = '',
                        date_processed    = '".date("Y-m-d H:i:s")."'
                    ";
                    mysql_query($query);
                    continue;
                } # emd of

                # Data preparation
                $id_account = $action->from_id_account;
                $xsender = new account($id_account);
                # For some reason, the $xsender object isn't being formed. Next fixes the problem.
                {
                    $query = "select * from ".$config->db_tables["account"]." where id_account = '$id_account'";
                    # echo "$query\n";
                    $res   = mysql_query($query);
                    if( mysql_num_rows($res) == 0 )
                    {
                        cli::write( "                    |  * Sender ".$action->from_id_account."(".$action->from_name.") Can't be found on DB! Ignoring message.", cli::$forecolor_black, cli::$backcolor_magenta );
                        cli::write("\n");
                        continue;
                    }
                    else
                    {
                        $raw_sender_row = mysql_fetch_object($res);
                        # echo "row: [$id_account] := " . print_r($raw_sender_row, true);
                        if( empty($raw_sender_row->id_account) )
                        {
                            cli::write( "                    |  * Sender's record ".$action->from_id_account."(".$action->from_name.") is EMPTY! Ignoring message.", cli::$forecolor_black, cli::$backcolor_magenta );
                            cli::write("\n");
                            cli::write( "                    |  $query", cli::$forecolor_black, cli::$backcolor_magenta );
                            cli::write("\n");
                            cli::write("                    |  ".str_replace("\n", "\n                    |  ", print_r($raw_sender_row, true)), cli::$forecolor_black, cli::$backcolor_magenta );
                            cli::write("\n");
                            continue;
                        } # end if
                    } # end if
                    mysql_free_result($res);
                }

                $tipping_provider_keyname = get_tipping_provider_keyname_by_coin($action->coin_name);
                $config->current_tipping_provider_keyname = $tipping_provider_keyname;
                $config->current_coin_name                = $action->coin_name;
                $config->current_tipping_provider_data    = $config->tipping_providers_database[$tipping_provider_keyname];
                $config->current_coin_data                = $config->current_tipping_provider_data["per_coin_data"][$config->current_coin_name];

                $recipient        = new account($action->to_id_account);
                $recipient_name   = $recipient->exists ? $recipient->name : "(NEW) " . $action->to_name;
                $is_new_recipient = ! $recipient->exists;
                cli::write( "                    | Entry $action->entry_type #$action->entry_id valid.\n", cli::$forecolor_light_green );
                cli::write( "                    | '--> Going to send $action->coins $action->coin_name from [".$action->from_id_account."](".$raw_sender_row->name.") to [".$recipient->id_account."] $recipient_name...\n", cli::$forecolor_light_green );

                # New recipient. Let's insert it into the DB.
                if( ! $recipient->exists )
                {
                    cli::write( "                    |  * Recipient {$action->to_id_account}($recipient_name) Can't be found on DB! Ignoring message.", cli::$forecolor_black, cli::$backcolor_magenta );
                    cli::write("\n");
                    continue;
                } # end if

                # Get wallet address if empty
                if( empty($recipient->wallet_address) )
                {
                    cli::write( "                    |      * Target account doesn't have a wallet address. Attempting to register it...\n", cli::$forecolor_green );

                    $tipping_provider = new tipping_provider( $config->current_coin_data["api_url"]    ,
                                                              $config->current_tipping_provider_data["public_key"] ,
                                                              $config->current_tipping_provider_data["secret_key"] );
                    $tipping_provider->cache_api_responses_time = 0;
                    $res = $tipping_provider->register($recipient->id_account);
                    # print_r($res);
                    if( $res->message == "OK" )
                    {
                        for( $try = 2; $try <= 4; $try++ )
                        {
                            if( empty($res->data) )
                            {
                                # Another try...
                                cli::write( "                    |      * Attempt #$try...\n", cli::$forecolor_green );
                                $res = $tipping_provider->register($recipient->id_account);
                                if( $res->message == "OK" && ! empty($res->data) ) break;
                                sleep(1);
                            } # end if
                        } # end for

                        if( empty($res->data) )
                        {
                            cli::write( "                    |        ! Can't register account on the daemon! Message got: $res->message\n", cli::$forecolor_light_red );
                            $query = "
                                insert into ".$config->db_tables["log"]." set
                                entry_type        = '".$action->entry_type."',
                                from_handler      = '".$object_key."',
                                entry_id          = '".$action->entry_id."',
                                action_type       = '".$action->action_type."',
                                from_facebook_id  = '".$raw_sender_row->facebook_id."',
                                from_id_account   = '".$raw_sender_row->id_account."',
                                message           = '".addslashes($action->message)."',
                                coin_name         = '".$action->coin_name."',
                                coins             = '".$action->coins."',
                                to_facebook_id    = '".$recipient->facebook_id."',
                                to_id_account     = '".$recipient->id_account."',
                                state             = 'ERROR',
                                info              = 'UNABLE_TO_REGISTER_TARGET_ACCOUNT',
                                date_analyzed     = '$action->date_analyzed',
                                api_call_message  = '$res->message',
                                api_extended_info = '$res->extended_info',
                                date_processed    = '".date("Y-m-d H:i:s")."'
                            ";
                            mysql_query($query);
                            cli::write( "                    |      * Skipping record.\n", cli::$forecolor_yellow );
                            continue;
                        } # end if

                        cli::write( "                    |        * OK! Account registered on the daemon! Yet, wallet address $res->data will NOT be inserted at this time.\n", cli::$forecolor_light_green );
                        # $recipient->wallet_address = $res->data;
                        # $recipient->save();
                    }
                    else
                    {
                        cli::write( "                    |        ! Can't register account on the daemon! Message got: $res->message\n", cli::$forecolor_light_red );
                        $query = "
                            insert into ".$config->db_tables["log"]." set
                            entry_type        = '".$action->entry_type."',
                            from_handler      = '".$object_key."',
                            entry_id          = '".$action->entry_id."',
                            action_type       = '".$action->action_type."',
                            from_facebook_id  = '".$raw_sender_row->facebook_id."',
                            from_id_account   = '".$raw_sender_row->id_account."',
                            message           = '".addslashes($action->message)."',
                            coin_name         = '".$action->coin_name."',
                            coins             = '".$action->coins."',
                            to_facebook_id    = '".$recipient->facebook_id."',
                            to_id_account     = '".$recipient->id_account."',
                            state             = 'ERROR',
                            info              = 'UNABLE_TO_REGISTER_TARGET_ACCOUNT',
                            date_analyzed     = '$action->date_analyzed',
                            api_call_message  = '$res->message',
                            api_extended_info = '$res->extended_info',
                            date_processed    = '".date("Y-m-d H:i:s")."'
                        ";
                        mysql_query($query);
                        cli::write( "                    |      * Skipping record.\n", cli::$forecolor_yellow );
                        continue;
                    } # end if
                } # end if

                # Proceed to transfer funds
                $tipping_provider = new tipping_provider( $config->current_coin_data["api_url"],
                                                          $config->current_tipping_provider_data["public_key"] ,
                                                          $config->current_tipping_provider_data["secret_key"] );
                $tipping_provider->cache_api_responses_time = 0;
                cli::write( "                    |      ~ Issuing send(sender:$raw_sender_row->id_account, recipient:$recipient->id_account, coins:$action->coins) \n", cli::$forecolor_light_green );
                $res = $tipping_provider->send($raw_sender_row->id_account, $recipient->id_account, $action->coins);
                if( stristr($res->message, "ERROR") !== false )
                {
                    # print_r($res);
                    cli::write( "                    |      * Can't transfer the coins! Message: $res->message. Logging and skipping.\n", cli::$forecolor_light_red );
                    $query = "
                        insert into ".$config->db_tables["log"]." set
                        entry_type        = '".$action->entry_type."',
                        from_handler      = '".$object_key."',
                        entry_id          = '".$action->entry_id."',
                        action_type       = '".$action->action_type."',
                        from_facebook_id  = '".$raw_sender_row->facebook_id."',
                        from_id_account   = '".$raw_sender_row->id_account."',
                        coin_name         = '".$action->coin_name."',
                        coins             = '".$action->coins."',
                        to_facebook_id    = '".$recipient->facebook_id."',
                        to_id_account     = '".$recipient->id_account."',
                        message           = '".addslashes($action->message)."',
                        state             = 'ERROR',
                        info              = 'UNABLE_TO_SEND_COINS',
                        date_analyzed     = '$action->date_analyzed',
                        api_call_message  = '$res->message',
                        api_extended_info = '$res->extended_info',
                        date_processed    = '".date("Y-m-d H:i:s")."'
                    ";
                    mysql_query($query);
                    cli::write( "                    |      * Skipping record.\n", cli::$forecolor_yellow );
                    continue;
                }
                else
                {
                    cli::write( "                    |      * Coins sent flawlessly!\n", cli::$forecolor_light_green );
                    $query = "
                        insert into ".$config->db_tables["log"]." set
                        entry_type        = '".$action->entry_type."',
                        from_handler      = '".$object_key."',
                        entry_id          = '".$action->entry_id."',
                        action_type       = '".$action->action_type."',
                        from_facebook_id  = '".$raw_sender_row->facebook_id."',
                        from_id_account   = '".$raw_sender_row->id_account."',
                        coin_name         = '".$action->coin_name."',
                        coins             = '".$action->coins."',
                        to_facebook_id    = '".$recipient->facebook_id."',
                        to_id_account     = '".$recipient->id_account."',
                        message           = '".addslashes($action->message)."',
                        state             = 'OK',
                        info              = '',
                        date_analyzed     = '$action->date_analyzed',
                        api_call_message  = '$res->message',
                        api_extended_info = '',
                        date_processed    = '".date("Y-m-d H:i:s")."'
                    ";
                    mysql_query($query);
                    $op_id = mysql_insert_id();

                    # Let's ping the sender account
                    $query = "update ".$config->db_tables["account"]." set last_activity    = '".date("Y-m-d H:i:s")."' where id_account = '$raw_sender_row->id_account'";
                    mysql_query($query);

                    # Let's notify or invite the recipient
                    $recipient_notification_message = "";
                    try
                    {
                        $balance = $recipient->get_balance();
                        $message = "You've received $action->coins ".$config->current_coin_data["coin_name_plural"]." from @[$raw_sender_row->facebook_id]! Your new balance is $balance ".$config->current_coin_data["coin_name_plural"]."";
                        $params  = array("template" => $message, "href" => "?coming_from=recipient_notification");
                        $url = "/$recipient->facebook_id/notifications";
                        $res = $facebook->api($url, "POST", $params);
                        if( $res["success"] )
                        {
                            cli::write( "                    |      * Notification successfully sent to $recipient->name. That's all.\n", cli::$forecolor_light_green );
                            $recipient_notification_message = "FB Notification sent.";
                        }
                        else
                        {
                            cli::write( "                    |      * Couldn't send success notification to $recipient->name. ".$res["error"]["message"].".\n", cli::$forecolor_brown );
                            $recipient_notification_message = "FB Notification failed: ".$res["error"]["message"].".";
                        }
                    }
                    catch( Exception $e )
                    {
                        cli::write( "                    |      * Couldn't send success notification to $recipient->name. ".$res["error"]["message"].".\n", cli::$forecolor_brown );
                        $recipient_notification_message = "FB Notification failed: ".$e->getMessage().". No more notifications for recipient will be delivered.";
                        @mysql_query("update ".$config->db_tables["account"]." set receive_notifications = 'false' where id_account = '$recipient->id_account'");
                    } # end try...catch

                    $query = "
                        update ".$config->db_tables["log"]." set
                        api_extended_info = concat(api_extended_info, '\n[RECIPIENT] $recipient_notification_message\n')
                        where op_id = '$op_id'
                    ";
                    mysql_query($query);

                    if( ! empty($action->notify_to_facebook_id) ) $tipper_facebook_id = $action->notify_to_facebook_id;
                    else                                          $tipper_facebook_id = $raw_sender_row->facebook_id;
                    $notifications_pool[$tipper_facebook_id][$action->coin_name][] = (object) array(
                        "sender_id_account"     => $raw_sender_row->id_account,
                        "sender_name"           => $raw_sender_row->name,
                        "is_bot_switch"         => $action->notify_to_is_bot_switch,
                        "recipient_facebook_id" => $recipient->facebook_id,
                        "coin_name"             => $action->coin_name,
                        "coins"                 => $action->coins,
                        "op_message_id"         => $op_id
                    );

                    if( ! empty($extensions) )
                    {
                        foreach($extensions as $extension)
                        {
                            cli::write( "                    |      * Invoking $extension...\n", cli::$forecolor_light_blue );
                            try
                            {
                                include $extension;
                                cli::write( "                    |        ~ Exited.\n", cli::$forecolor_light_blue );
                            }
                            catch( Exception $e )
                            {
                                cli::write( "                    |        ! Can't invoke the extension! Exception: ". $e->getMessage(), cli::$forecolor_blue, cli::$backcolor_light_gray );
                                cli::write( "\n");
                            } # end try... catch
                        } # end foreach
                    } # end if

                } # end if

            } # end foreach $actions

            #==========================#
            # Notifications to senders #
            #==========================#

            if( count($notifications_pool) > 0 )
            {
                cli::write( "                    .--- Starting notifications to tippers --------\n", cli::$forecolor_yellow );
                foreach($notifications_pool as $tipper_facebook_id => $per_coin_notifications)
                {
                    foreach($per_coin_notifications as $coin_name => $notifications_to_deliver)
                    {
                        $sender_id_account            = $notifications_to_deliver[0]->sender_id_account;
                        $sender_name                  = $notifications_to_deliver[0]->sender_name;
                        $notify_to_is_bot_switch      = $notifications_to_deliver[0]->is_bot_switch;
                        $op_message_ids               = array();
                        $tipped_coins                 = 0;

                        if( count($notifications_to_deliver) == 1 )
                        {
                            $tipped_recipient_caption = "@[".$notifications_to_deliver[0]->recipient_facebook_id."]";
                            $tipped_coins             = "with ".$notifications_to_deliver[0]->coins. " ".get_coin_plural_by_name($coin_name);
                            $op_message_ids[]         = $notifications_to_deliver[0]->op_message_id;
                        }
                        else
                        {
                            $tipped_recipient_caption = count($notifications_to_deliver) . " people";
                            foreach($notifications_to_deliver as $notification_data)
                            {
                                $tipped_coins     += $notification_data->coins;
                                $op_message_ids[]  = $notification_data->op_message_id;
                            } # end if
                            $tipped_coins = "a total of ".$tipped_coins." ".get_coin_plural_by_name($coin_name);
                        } # end if

                        $xsender_notification_message = "";
                        try
                        {
                            $tipping_provider_keyname = get_tipping_provider_keyname_by_coin($coin_name);
                            $config->current_tipping_provider_keyname = $tipping_provider_keyname;
                            $config->current_coin_name                = $coin_name;
                            $config->current_tipping_provider_data    = $config->tipping_providers_database[$tipping_provider_keyname];
                            $config->current_coin_data                = $config->current_tipping_provider_data["per_coin_data"][$config->current_coin_name];
                            $tipping_provider = new tipping_provider( $config->current_coin_data["api_url"],
                                                                      $config->current_tipping_provider_data["public_key"] ,
                                                                      $config->current_tipping_provider_data["secret_key"] );
                            $tipping_provider->cache_api_responses_time = 0;
                            $bres = $tipping_provider->get_balance($sender_id_account);
                            if( $bres->message == "OK" )
                            {
                                if( $notify_to_is_bot_switch )
                                    $message = "You've successfully tipped $tipped_recipient_caption $tipped_coins from Tipping Bot's pool. New pool balance is ".$bres->data." ".get_coin_plural_by_name($coin_name);
                                else
                                    $message = "You've successfully tipped $tipped_recipient_caption $tipped_coins. Your new balance is ".$bres->data." ".get_coin_plural_by_name($coin_name);
                            }
                            else
                            {
                                cli::write( "                    | ! Couldn't get sender's balance. Response from API: $bres->message.\n", cli::$forecolor_yellow );
                                if( $notify_to_is_bot_switch )
                                    $message = "You've successfully tipped $tipped_recipient_caption $tipped_coins from Tipping Bot's pool.";
                                else
                                    $message = "You've successfully tipped $tipped_recipient_caption $tipped_coins.";
                            }
                            $params  = array("template" => $message, "href" => "?coming_from=sender_tipping_notification");
                            $url = "/$tipper_facebook_id/notifications";
                            $res = $facebook->api($url, "POST", $params);
                            if( $res["success"] )
                            {
                                cli::write( "                    | * Notification successfully sent to $sender_name. That's all.\n", cli::$forecolor_light_green );
                                $xsender_notification_message = "FB Notification sent.";
                            }
                            else
                            {
                                cli::write( "                    | * Couldn't send success notification to $sender_name. Error: ".$res["error"]["message"].".\n", cli::$forecolor_brown );
                                $xsender_notification_message = "FB Notification failed: ".$res["error"]["message"].". No more notifications for sender will be delivered.";
                                @mysql_query("update ".$config->db_tables["account"]." set receive_notifications = 'false' where id_account = '$sender_id_account'");
                            } # end if
                        }
                        catch( Exception $e )
                        {
                            cli::write( "                    | * Couldn't send success notification to $sender_name. Error: ".$res["error"]["message"].".\n", cli::$forecolor_brown );
                            $xsender_notification_message = "FB Notification failed: ".$res["error"]["message"].".";
                            @mysql_query("update ".$config->db_tables["account"]." set receive_notifications = 'false' where id_account = '$sender_id_account'");
                        } # end try...catch

                        $query = "
                            update ".$config->db_tables["log"]." set
                            api_extended_info = concat(api_extended_info, '\n[SENDER] $xsender_notification_message\n')
                            where op_id in ('".implode("','", $op_message_ids)."')
                        ";
                        # cli::write( "                    | ".preg_replace('/\n\s+/', ' ', $query).".\n", cli::$forecolor_light_purple );
                        mysql_query($query);
                    } # end foreach $per_coin_notifications
                } # end foreach $notifications_pool
                cli::write( "                    '--- Notifications to tippers loop finished ---\n", cli::$forecolor_yellow );
            } # end if
        } # end if
        cli::write( "                    '- Action loop finished in " . number_format(time() - $start2) . " seconds.\n", cli::$forecolor_light_cyan );

        if( empty($arg["--no-flags-update"]) )
            set_flag_value("$object_key/$object_edge:last_read_date", $new_last_read_date);
        cli::write( "                    Last read date set to $new_last_read_date on flags table.\n");

        cli::write( "                    All operations ended in " . number_format(time() - $start) . " seconds.\n");
        cli::write("\n");
        unlink( LOCK_FILE );
        die();
    } # end action loop
