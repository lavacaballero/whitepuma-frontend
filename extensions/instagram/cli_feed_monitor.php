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
    include "$root_url/models/account_extensions.php";
    include("$root_url/lib/self_running_checker.inc");
    define( "LOCK_FILE", sprintf("/tmp/%s%s.pid", $config->session_vars_prefix, "instagram_feed_monitor") );
    db_connect();

    include "$root_url/bot_functions/generic.php";
    include "process_comment.inc";

    #########
    # Inits #
    #########

    $arg         = extract_parameters($argv);
    $object_key  = "instagram";
    $object_edge = "comments";

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

    ####################
    # Continuity block #
    ####################
    {
        # $last_read_tweet = get_flag_value("$object_key/$object_edge:last_read_tweet");
        $start    = time();
        $timespan = date("Y-m-d H:i:s", strtotime("now - {$config->instagram_item_lifespan} days"));
        cli::write("\n");
        cli::write( date("Y-m-d H:i:s") . " .- Starting feed analysis after $timespan on ".cli::color("$object_key/$object_edge", cli::$forecolor_light_cyan) );
        cli::write("\n");
    } # end continuity block

    #################
    # Analysis loop #
    #################
    {
        $last_checked_time = date("Y-m-d H:i:s");
        $query = "
            select * from {$config->db_tables["instagram_items"]}
            where monitor_start >= '$timespan'
            order by monitor_start desc
            # limit {$config->instagram_read_items_limit}
        ";
        $res0 = mysql_query($query);
        $actions = array();

        while( $item = mysql_fetch_object($res0) )
        {
            $uquery = "select user_name, access_token from {$config->db_tables["instagram_users"]} where user_id = '{$item->author_id}'";
            $ures   = mysql_query($uquery);
            if( mysql_num_rows($ures) == 0 )
            {
                $token       = $config->instagram_client_info["access_token"];
                $which_token = "default";
            }
            else
            {
                $urow        = mysql_fetch_object($ures);
                $token       = $urow->access_token;
                $which_token = "{$urow->user_name}'s";
            } # end if
            mysql_free_result($ures);
            $api_call = str_replace('{$media_id}', $item->item_id, $config->instagram_subscriptions_data["comments_getter_url"]);
            $params   = array(
                "access_token"  => $token
            );
            cli::write( "                    |  Invoking using $which_token token (".cli::color($api_call, cli::$forecolor_white).")...\n");
            list($res, $data) = get($api_call, $params);
            if( $res != "OK" )
            {
                cli::write( "                    |  Error raised: {$data} - Aborting run.\n", cli::$forecolor_light_red );
                unlink( LOCK_FILE );
                die();
            } # end if
            $data = json_decode($data);
            if( $arg["--raw"] ) print_r($data);
            cli::write( "                    |  Done. " . count($data->data) . " entries downloaded.\n");

            if( count($data->data) == 0 )
            {
                cli::write( "                    |  Nothing to do with item {$item->item_id}.\n", cli::$forecolor_yellow);
            }
            elseif( count($data->data) == $item->comment_count && empty($arg["--skip-counters-check"]) )
            {
                cli::write( "                    |  No new messages on item {$item->item_id}.\n", cli::$forecolor_yellow);
            }
            else
            {
                cli::write( "                    |  Data loop for item {$item->item_id} start\n" );
                foreach($data->data as $key => $obj)
                {
                    if( $obj->created_time < strtotime($item->last_checked) )
                    {
                        cli::write( "                    |  Comment {$obj->id} previously checked. Skipping it.\n" );
                        continue;
                    } # end if
                    $res = process_comment($obj, $item);
                    if( ! is_array($res) )
                    {
                        $res->date_analyzed = date("Y-m-d H:i:s");
                        $actions[] = $res;
                    }
                    else
                    {
                        foreach($res as $index => $xaction)
                        {
                            $xaction->date_analyzed = date("Y-m-d H:i:s");
                            $actions[] = $xaction;
                        } # end if
                    } # end if
                } # end foreach
                cli::write( "                    |  Data loop for item {$item->item_id} end. ".count($actions)." actions in the package.\n" );
                $query = "
                    update {$config->db_tables["instagram_items"]}
                    set comment_count = ".count($data->data).",
                        last_checked  = '$last_checked_time'
                    where item_id = '{$item->item_id}'
                ";
                mysql_query($query);
                cli::write( "                    |  Updated comments count in database record.\n" );
            } # end if
        } # end while
        cli::write( "                    '- Analysis finished in " . number_format(time() - $start) . " seconds.\n");
        mysql_free_result($res0);
    } # end analysis loop

    if( $arg["--print-actions"] ) print_r($actions);

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
                    #   from_facebook_id  = '".$action->from_facebook_id."',
                        from_id_account   = '".$action->from_id_account."',
                        message           = '".addslashes($action->message)."',
                        coin_name         = '".$action->coin_name."',
                        coins             = '".$action->coins."',
                    #   to_facebook_id    = '".$action->to_facebook_id."',
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

                # We need to lookup by instagram user name
                $action->to_instagram_user_id = 0;
                if( empty($action->to_id_account) )
                {
                    $uquery   = "select * from {$config->db_tables["instagram_users"]} where user_name = '{$action->to_instagram_username}'";
                    $ures     = mysql_query($uquery);
                    if( mysql_num_rows($ures) > 0 )
                    {
                        $urow = mysql_fetch_object($ures);
                        $action->to_instagram_user_id = $urow->user_id;
                        $action->to_id_account = $urow->id_account;
                    } # end if
                    mysql_free_result($ures);
                } # end if

                $recipient        = new account($action->to_id_account);
                # echo "Recipient: [$fb_id] := " . print_r($recipient, true);
                $recipient_name   = $recipient->exists ? $recipient->name : "(NEW) " . $action->to_name;
                $is_new_recipient = ! $recipient->exists;
                cli::write( "                    | Entry $action->entry_type #$action->entry_id valid.\n", cli::$forecolor_light_green );
                cli::write( "                    | '--> Going to send $action->coins $action->coin_name from [".$action->from_id_account."](".$raw_sender_row->name.") to [".$recipient->id_account."] $recipient_name...\n", cli::$forecolor_light_green );

                # Let's check if it is an existing recipient and has re-routing...
                $rerouting_in_effect = false;
                $rerouting_notification_target_changed = false;
                $original_recipient  = clone $recipient;
                if( $recipient->exists )
                {
                    $account_extensions = new account_extensions($recipient->id_account);
                    if( ! empty($account_extensions->reroute_to) && $account_extensions->reroute_to != $recipient->id_account )
                    {
                        $rerouting_in_effect = true;
                        $recipient = new account($account_extensions->reroute_to);
                        if( ! $recipient->exists )
                        {
                            cli::write( "                    |      Invalid re-routing detected! {$account_extensions->reroute_to}. Logging and discarding.", cli::$forecolor_white, cli::$backcolor_magenta );
                            cli::write( "\n" );
                            $query = "
                                insert into ".$config->db_tables["log"]." set
                                entry_type        = '".$action->entry_type."',
                                from_handler      = '".$object_key."',
                                entry_id          = '".$action->entry_id."',
                                action_type       = '".$action->action_type."',
                            #   from_facebook_id  = '".$action->from_facebook_id."',
                                from_id_account   = '".$action->from_id_account."',
                                message           = '".addslashes($action->message)."',
                                coin_name         = '".$action->coin_name."',
                                coins             = '".$action->coins."',
                            #   to_facebook_id    = '".$action->to_facebook_id."',
                                to_id_account     = '".$action->to_id_account."',
                                state             = 'ERROR:INVALID_TARGET_ACCOUNT',
                                info              = '".$action->info."',
                                date_analyzed     = '$action->date_analyzed',
                                api_call_message  = '',
                                api_extended_info = '',
                                date_processed    = '".date("Y-m-d H:i:s")."'
                            ";
                            mysql_query($query);
                            continue;
                        } # end if

                        cli::write( "                    |      Re-routing detected! Recipient reset to [{$recipient->id_account}] {$recipient->name}.", cli::$forecolor_white, cli::$backcolor_magenta );
                        cli::write( "\n" );
                        $rerouting_notification_target_changed = true;
                        mysql_free_result($res);
                    } # end if
                } # end if

                # New recipient. Let's insert it into the DB.
                if( ! $recipient->exists )
                {
                    $recipient->name             = $action->to_name;
                    $recipient->tipping_provider = $config->current_tipping_provider_keyname;
                    $recipient->date_created     =
                    $recipient->last_update      =
                    $recipient->last_activity    = date("Y-m-d H:i:s");
                    $recipient->id_account       = empty($action->to_instagram_user_id)
                                                 ? $config->custom_account_creation_prefix . uniqid(true)
                                                 : $config->instagram_account_prefix . base_convert($action->to_instagram_user_id, 10, 26);
                    $recipient->save();
                    cli::write( "                    |      * Target account inserted in the database. New user id is {$recipient->id_account}.\n", cli::$forecolor_light_green );

                    $query = "
                        insert into ".$config->db_tables["instagram_users"]." set
                            user_name    = '{$action->to_instagram_username}',
                            id_account   = '{$recipient->id_account}'
                    ";
                    mysql_query($query);
                    cli::write( "                    |      * Target account instagram data inserted into instagram control table.\n", cli::$forecolor_light_green );
                } # end if

                # Check if there's no double spending during the last time...
                {
                    if( $rerouting_in_effect )
                        $query = "
                            select * from ".$config->db_tables["log"] . " where
                            from_handler          = 'instagram'
                            and entry_type        = 'comment'
                            and entry_id          = '".$action->entry_id."'
                            and from_id_account   = '".$action->from_id_account."'
                            and to_id_account     in ('{$recipient->id_account}', '{$original_recipient->id_account}')
                            # and coin_name         = '".$action->coin_name."'
                            # and coins             = '".$coins."'
                        ";
                    else
                        $query = "
                            select * from ".$config->db_tables["log"] . " where
                            from_handler          = 'instagram'
                            and entry_type        = 'comment'
                            and entry_id          = '".$action->entry_id."'
                            and from_id_account   = '".$action->from_id_account."'
                            and to_id_account     = '".$recipient->id_account."'
                            # and coin_name         = '".$action->coin_name."'
                            # and coins             = '".$coins."'
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
                            cli::write( "                    |        ! Can't register account! Message got: $res->message\n", cli::$forecolor_light_red );
                            $query = "
                                insert into ".$config->db_tables["log"]." set
                                entry_type        = '".$action->entry_type."',
                                from_handler      = '".$object_key."',
                                entry_id          = '".$action->entry_id."',
                                action_type       = '".$action->action_type."',
                            #   from_facebook_id  = '".$raw_sender_row->facebook_id."',
                                from_id_account   = '".$raw_sender_row->id_account."',
                                message           = '".addslashes($action->message)."',
                                coin_name         = '".$action->coin_name."',
                                coins             = '".$action->coins."',
                            #   to_facebook_id    = '".$recipient->facebook_id."',
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

                        cli::write( "                    |        * OK! Account registered! Yet, wallet address $res->data will NOT be inserted at this time.\n", cli::$forecolor_light_green );
                        # $recipient->wallet_address = $res->data;
                        # $recipient->save();
                    }
                    else
                    {
                        cli::write( "                    |        ! Can't register account! Message got: $res->message\n", cli::$forecolor_light_red );
                        $query = "
                            insert into ".$config->db_tables["log"]." set
                            entry_type        = '".$action->entry_type."',
                            from_handler      = '".$object_key."',
                            entry_id          = '".$action->entry_id."',
                            action_type       = '".$action->action_type."',
                        #   from_facebook_id  = '".$raw_sender_row->facebook_id."',
                            from_id_account   = '".$raw_sender_row->id_account."',
                            message           = '".addslashes($action->message)."',
                            coin_name         = '".$action->coin_name."',
                            coins             = '".$action->coins."',
                        #   to_facebook_id    = '".$recipient->facebook_id."',
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
                    #   from_facebook_id  = '".$raw_sender_row->facebook_id."',
                        from_id_account   = '".$raw_sender_row->id_account."',
                        coin_name         = '".$action->coin_name."',
                        coins             = '".$action->coins."',
                    #   to_facebook_id    = '".$recipient->facebook_id."',
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
                    $api_extended_info = ! $rerouting_in_effect ? ""
                                       : "Re-routed from {$original_recipient->id_account} to {$recipient->id_account}";
                    $query = "
                        insert into ".$config->db_tables["log"]." set
                        entry_type        = '".$action->entry_type."',
                        from_handler      = '".$object_key."',
                        entry_id          = '".$action->entry_id."',
                        action_type       = '".$action->action_type."',
                    #   from_facebook_id  = '".$raw_sender_row->facebook_id."',
                        from_id_account   = '".$raw_sender_row->id_account."',
                        coin_name         = '".$action->coin_name."',
                        coins             = '".$action->coins."',
                    #   to_facebook_id    = '".$recipient->facebook_id."',
                        to_id_account     = '".$recipient->id_account."',
                        message           = '".addslashes($action->message)."',
                        state             = 'OK',
                        info              = '$api_extended_info',
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
                    if( $is_new_recipient )
                    {
                        $recipient_notification_message = "Can't send notification to Instagram.";
                        /*
                        $notification = str_replace(
                            array("@recipient",               "@sender"),
                            array("@$action->to_screen_name", "@$action->from_screen_name"),
                            $config->twitter_invitation_template
                        );
                        $twitter = new TwitterAPIExchange($twitter_options);
                        $response = $twitter
                                    ->buildOauth("https://api.twitter.com/1.1/statuses/update.json", "POST")
                                    ->setPostfields( array( "status" => $notification
                                                          , "in_reply_to_status_id" => $action->entry_id
                                                          ) )
                                    ->performRequest();
                        $response = json_decode($response);
                        if( empty($response->errors) )
                        {
                            $recipient_notification_message = "Invitation to recipient sent.";
                            cli::write( "                    |      * Invitation sent to recipient.\n", cli::$forecolor_green );
                        }
                        else
                        {
                            $recipient_notification_message = "Invitation to recipient failed: " . $response->errors[0]->message;
                            cli::write( "                    |      * Couldn't send invitation to recipient: ".$response->errors[0]->message.".\n", cli::$forecolor_brown );
                        } # end if
                        */
                    }
                    else
                    {
                        $balance = $recipient->get_balance();
                        if( $rerouting_in_effect && ! $rerouting_notification_target_changed )
                            $message = "{$recipient->name} has received $action->coins ".$config->current_coin_data["coin_name_plural"]." "
                                     . "from {$action->from_instagram_username} through {$action->to_instagram_username} at Instagram! "
                                     . "{$recipient->name} new balance is $balance ".$config->current_coin_data["coin_name_plural"]."";
                        else
                            $message = "You've received $action->coins ".$config->current_coin_data["coin_name_plural"]." "
                                     . "from {$action->from_instagram_username} at Instagram! "
                                     . "Your new balance is $balance ".$config->current_coin_data["coin_name_plural"]."";
                        $mres = send_message($recipient->id_account, $message, "[{$config->app_display_shortname}] Incoming tip from Instagram");
                        if( $mres == "OK" )
                        {
                            cli::write( "                    |      * Notification successfully sent to $recipient->name. That's all.\n", cli::$forecolor_light_green );
                            $recipient_notification_message = "Notification sent.";
                        }
                        else
                        {
                            cli::write( "                    |      * Couldn't send success notification to $recipient->name. $mres.\n", cli::$forecolor_brown );
                            $recipient_notification_message = "Notification failed: $mres.";
                        } # end if
                    } # end if

                    $query = "
                        update ".$config->db_tables["log"]." set
                        api_extended_info = concat(api_extended_info, '\n[RECIPIENT] $recipient_notification_message\n')
                        where op_id = '$op_id'
                    ";
                    mysql_query($query);

                    $notifications_pool[$raw_sender_row->id_account][$action->coin_name][] = (object) array(
                        "sender_id_account"             => $raw_sender_row->id_account,
                        "sender_name"                   => $raw_sender_row->name,
                        "is_bot_switch"                 => $action->notify_to_is_bot_switch,
                        "recipient_instagram_username"  => $action->to_instagram_username,
                        "coin_name"                     => $action->coin_name,
                        "coins"                         => $action->coins,
                        "op_message_id"                 => $op_id
                    );

                } # end if

            } # end foreach $actions

            #==========================#
            # Notifications to senders #
            #==========================#

            if( count($notifications_pool) > 0 )
            {
                cli::write( "                    .--- Starting notifications to tippers --------\n", cli::$forecolor_yellow );
                foreach($notifications_pool as $sender_id_account => $per_coin_notifications)
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
                            $tipped_recipient_caption = "".$notifications_to_deliver[0]->recipient_instagram_username."";
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
                                $message = "You've successfully tipped $tipped_recipient_caption $tipped_coins over Instagram from Tipping Bot's pool. New pool balance is ".$bres->data." ".get_coin_plural_by_name($coin_name);
                            else
                                $message = "You've successfully tipped $tipped_recipient_caption $tipped_coins over Instagram. Your new balance is ".$bres->data." ".get_coin_plural_by_name($coin_name);
                        }
                        else
                        {
                            cli::write( "                    | ! Couldn't get sender's balance. Response from API: $bres->message.\n", cli::$forecolor_yellow );
                            if( $notify_to_is_bot_switch )
                                $message = "You've successfully tipped $tipped_recipient_caption $tipped_coins over Instagram from Tipping Bot's pool.";
                            else
                                $message = "You've successfully tipped $tipped_recipient_caption $tipped_coins over Instagram.";
                        } # end if

                        $response = send_message($sender_id_account, $message, "[{$config->app_display_shortname}] Tip sent to Instagram user(s)");
                        if( $response == "OK" )
                        {
                            cli::write( "                    | * Notification successfully sent to $sender_name. That's all.\n", cli::$forecolor_light_green );
                            $xsender_notification_message = "Notification sent.";
                        }
                        else
                        {
                            cli::write( "                    | * Couldn't send success notification to $sender_name. Error: $response.\n", cli::$forecolor_brown );
                            $xsender_notification_message = "Notification failed: $response. No more notifications for sender will be delivered.";
                            @mysql_query("update ".$config->db_tables["account"]." set receive_notifications = 'false' where id_account = '$sender_id_account'");
                        } # end if

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

        # if( empty($arg["--no-flags-update"]) )
        #     set_flag_value("$object_key/$object_edge:last_read_tweet", $last_read_tweet);
        # cli::write( "                    Last read tweet set to $last_read_tweet on flags table.\n");

        cli::write( "                    All operations ended in " . number_format(time() - $start) . " seconds.\n");
        cli::write("\n");
        unlink( LOCK_FILE );
        die();
    } # end action loop
