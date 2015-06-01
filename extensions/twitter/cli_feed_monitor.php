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
    define( "LOCK_FILE", sprintf("/tmp/%s%s.pid", $config->session_vars_prefix, "twitter_feed_monitor") );
    db_connect();

    include "$root_url/bot_functions/generic.php";
    include "process_tweet.inc";

    #########
    # Inits #
    #########

    $arg         = extract_parameters($argv);
    $object_key  = "twitter";
    $object_edge = "mentions";
    $twitter_options = array(
        'oauth_access_token'        => $config->twitter_access_token,
        'oauth_access_token_secret' => $config->twitter_token_secret,
        'consumer_key'              => $config->twitter_consumer_key,
        'consumer_secret'           => $config->twitter_consumer_secret
    );

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

    #######################
    # Twitter SDK loading #
    #######################
    {
        # https://github.com/J7mbo/twitter-api-php
        include("lib/TwitterAPIExchange.php");
        $last_read_tweet = get_flag_value("$object_key/$object_edge:last_read_tweet");
        $start   = time();
        cli::write("\n");
        cli::write( date("Y-m-d H:i:s") . " .- Starting feed analysis for ".cli::color("$object_key/$object_edge", cli::$forecolor_light_cyan) );
        cli::write("\n");
    } # end continuity block

    #################
    # Analysis loop #
    #################
    {
        $actions = array();

        $api_call = empty($last_read_tweet)
                  ? "?count=200"
                  : "?count=200&since_id=$last_read_tweet";

        cli::write( "                    |  Invoking (".cli::color($api_call, cli::$forecolor_white).")...\n");
        try
        {
            $twitter = new TwitterAPIExchange($twitter_options);

            $res = $twitter
                   ->setGetfield($api_call)
                   ->buildOauth("https://api.twitter.com/1.1/statuses/mentions_timeline.json", "GET")
                   ->performRequest();
            $res = json_decode($res);
        }
        catch(Exception $e)
        {
            cli::write( "                    |  Exception raised: " .  $e->getMessage() . " - Aborting run.\n", cli::$forecolor_light_red );
            unlink( LOCK_FILE );
            die();
        } # end try...catch
        if( $arg["--raw"] ) print_r($res);
        if( $arg["--die-after-raw"] )
        {
            cli::write( "                    |  Dying after outputting raw response.\n", cli::$forecolor_yellow );
            unlink( LOCK_FILE );
            die();
        } # end if
        cli::write( "                    |  Done. " . count($res) . " entries downloaded.\n");

        if( count($res) == 0 )
        {
            cli::write( "                    |  Nothing to do here.\n", cli::$forecolor_yellow);
        }
        else
        {
            cli::write( "                    |  Data loop start\n" );
            foreach($res as $key => $obj)
            {
                $last_read_tweet = $obj->id > $last_read_tweet ? $obj->id : $last_read_tweet;
                $res = process_tweet($obj);
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
            cli::write( "                    |  Data loop end. ".count($actions)." actions in the package.\n" );
        } # end if
        cli::write( "                    '- Analysis finished in " . number_format(time() - $start) . " seconds.\n");
    } # end analysis loop

    # This is the notification to the person being tipped
    # echo "> $command | from $from to $to\n";
    # $twitter = new TwitterAPIExchange($twitter_options);
    # $response = $twitter
    #             ->buildOauth("https://api.twitter.com/1.1/statuses/update.json", "POST")
    #             ->setPostfields( array( "status" => "@$to ~ @$from asked me to say something to you."
    #                                   , "in_reply_to_status_id" => $entry_id
    #                                   ) )
    #             ->performRequest();

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
                {
                    # Note: $config->tippingbot_id_acount is not set on unidash!
                    # if( $action->to_id_account == $config->tippingbot_id_acount )
                    # {
                    #     cli::write( "                    |  Action #$action_index ~ Tip to the bot account detected ({$action->to_id_account} --> {$config->tippingbot_id_acount}). Ignoring entry.\n", cli::$forecolor_light_red );
                    #     continue;
                    # } # end if
                }

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

                if( empty($action->from_twitter_id) || empty($action->from_id_account) )
                {
                    cli::write( "                    |  * Problem with sender info: Name:='", cli::$forecolor_light_purple );
                    cli::write( $action->from_name, cli::$forecolor_purple );
                    cli::write( "', twitter_id:'", cli::$forecolor_light_purple );
                    cli::write( $action->from_twitter_id, cli::$forecolor_purple );
                    cli::write( "', id_account:'", cli::$forecolor_light_purple );
                    cli::write( $action->from_id_account, cli::$forecolor_purple );
                    cli::write( "'. Entry ignored.\n", cli::$forecolor_purple );
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

                $fb_id            = $action->to_id_account;
                $recipient        = new account($fb_id);
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

                        # Let's reset $action->to_twitter_id and $action->to_screen_name
                        $query = "select * from {$config->db_tables["twitter"]} where id_account = '{$recipient->id_account}'";
                        $res   = mysql_query($query);
                        if( mysql_num_rows($res) > 0 )
                        {
                            $row = mysql_fetch_object($res);
                            if( ! empty($row->access_token) )
                            {
                                cli::write( "                    |      Notifications will be delivered to {$row->twitter_id} (@{$row->screen_name}).", cli::$forecolor_white, cli::$backcolor_magenta );
                                cli::write( "\n" );
                                $action->to_twitter_id  = $row->twitter_id;
                                $action->to_screen_name = $row->screen_name;
                                $rerouting_notification_target_changed = true;
                            } # end if
                        } # end if
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
                    $recipient->id_account       = $config->twitter_account_prefix . base_convert($action->to_twitter_id, 10, 26);
                    $recipient->save();
                    cli::write( "                    |      * Target account inserted in the database. New user id is {$recipient->id_account}.\n", cli::$forecolor_light_green );

                    $query = "
                        insert into ".$config->db_tables["twitter"]." set
                            twitter_id   = '{$action->to_twitter_id}',
                            screen_name  = '{$action->to_screen_name}',
                            access_token = '',
                            token_secret = '',
                            id_account   = '{$recipient->id_account}'
                    ";
                    mysql_query($query);
                    cli::write( "                    |      * Target account twitter data inserted into twitter control table.\n", cli::$forecolor_light_green );
                } # end if

                # Check if there's no double spending during the last time...
                {
                    if( $rerouting_in_effect )
                        $query = "
                            select * from ".$config->db_tables["log"] . " where
                            entry_type            = 'tweet'
                            and entry_id          = '".$action->entry_id."'
                            and from_id_account   = '".$action->from_id_account."'
                            and to_id_account     in ('{$recipient->id_account}', '{$original_recipient->id_account}')
                            # and coin_name         = '".$action->coin_name."'
                            # and coins             = '".$coins."'
                        ";
                    else
                        $query = "
                            select * from ".$config->db_tables["log"] . " where
                            entry_type            = 'tweet'
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
                    }
                    else
                    {
                        try
                        {
                            $balance = $recipient->get_balance();
                            if( $rerouting_in_effect && ! $rerouting_notification_target_changed )
                                $message = "{$recipient->name} has received $action->coins ".$config->current_coin_data["coin_name_plural"]." "
                                         . "from @{$action->from_screen_name} through @{$action->to_screen_name}! "
                                         . "{$recipient->name} new balance is $balance ".$config->current_coin_data["coin_name_plural"]."";
                            else
                                $message = "You've received $action->coins ".$config->current_coin_data["coin_name_plural"]." "
                                         . "from @{$action->from_screen_name}! "
                                         . "Your new balance is $balance ".$config->current_coin_data["coin_name_plural"]."";

                            $twitter = new TwitterAPIExchange($twitter_options);
                            $response = $twitter
                                        ->buildOauth("https://api.twitter.com/1.1/direct_messages/new.json", "POST")
                                        ->setPostfields( array( "user_id" => $action->to_twitter_id
                                                              , "text" => $message
                                                              ) )
                                        ->performRequest();
                            $response = json_decode($response);

                            if( empty($response->errors) )
                            {
                                cli::write( "                    |      * Notification successfully sent to $recipient->name. That's all.\n", cli::$forecolor_light_green );
                                $recipient_notification_message = "Notification sent.";
                            }
                            else
                            {
                                cli::write( "                    |      * Couldn't send success notification to $recipient->name. ".$response->errors[0]->message.".\n", cli::$forecolor_brown );
                                $recipient_notification_message = "Notification failed: ".$response->errors[0]->message.".";
                            }
                        }
                        catch( Exception $e )
                        {
                            cli::write( "                    |      * Couldn't send success notification to $recipient->name. ".$response->errors[0]->message.".\n", cli::$forecolor_brown );
                            $recipient_notification_message = "Notification failed: ".addslashes($e->getMessage()).". No more notifications for recipient will be delivered.";
                            @mysql_query("update ".$config->db_tables["account"]." set receive_notifications = 'false' where id_account = '$recipient->id_account'");
                        } # end try...catch
                    } # end if

                    $query = "
                        update ".$config->db_tables["log"]." set
                        api_extended_info = concat(api_extended_info, '\n[RECIPIENT] $recipient_notification_message\n')
                        where op_id = '$op_id'
                    ";
                    mysql_query($query);

                    $tipper_twitter_id = $action->from_twitter_id;
                    $notifications_pool[$tipper_twitter_id][$action->coin_name][] = (object) array(
                        "sender_id_account"     => $raw_sender_row->id_account,
                        "sender_name"           => $raw_sender_row->name,
                        "is_bot_switch"         => $action->notify_to_is_bot_switch,
                        "recipient_twitter_id"  => $action->to_twitter_id,
                        "recipient_screen_name" => $action->to_screen_name,
                        "coin_name"             => $action->coin_name,
                        "coins"                 => $action->coins,
                        "op_message_id"         => $op_id
                    );

                } # end if

            } # end foreach $actions

            #==========================#
            # Notifications to senders #
            #==========================#

            if( count($notifications_pool) > 0 )
            {
                cli::write( "                    .--- Starting notifications to tippers --------\n", cli::$forecolor_yellow );
                foreach($notifications_pool as $tipper_twitter_id => $per_coin_notifications)
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
                            $tipped_recipient_caption = "@".$notifications_to_deliver[0]->recipient_screen_name."";
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
                            } # end if

                            $twitter = new TwitterAPIExchange($twitter_options);
                            $response = $twitter
                                        ->buildOauth("https://api.twitter.com/1.1/direct_messages/new.json", "POST")
                                        ->setPostfields( array( "user_id" => $tipper_twitter_id
                                                              , "text" => $message
                                                              ) )
                                        ->performRequest();
                            $response = json_decode($response);
                            if( count($response->errors) == 0 )
                            {
                                cli::write( "                    | * Notification successfully sent to $sender_name. That's all.\n", cli::$forecolor_light_green );
                                $xsender_notification_message = "Notification sent.";
                            }
                            else
                            {
                                cli::write( "                    | * Couldn't send success notification to $sender_name. Error: ".$response->errors[0]->message.".\n", cli::$forecolor_brown );
                                $xsender_notification_message = "Notification failed: ".$response->errors[0]->message.". No more notifications for sender will be delivered.";
                                @mysql_query("update ".$config->db_tables["account"]." set receive_notifications = 'false' where id_account = '$sender_id_account'");
                            } # end if
                        }
                        catch( Exception $e )
                        {
                            cli::write( "                    | * Couldn't send success notification to $sender_name. Error: ".$e->getMessage().".\n", cli::$forecolor_brown );
                            $xsender_notification_message = "Notification failed: ".addslashes($e->getMessage()).".";
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
            set_flag_value("$object_key/$object_edge:last_read_tweet", $last_read_tweet);
        cli::write( "                    Last read tweet set to $last_read_tweet on flags table.\n");

        cli::write( "                    All operations ended in " . number_format(time() - $start) . " seconds.\n");
        cli::write("\n");
        unlink( LOCK_FILE );
        die();
    } # end action loop
