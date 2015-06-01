<?
    /**
     * Unclaimed tips returner
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

    set_time_limit( 3600 );
    ini_set("error_reporting", E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED & ~E_WARNING );

    if( isset($_SERVER["HTTP_HOST"])) die("<h3>This script is not ment to be called through a web browser. You must invoke it through a command shell or a cron job.</h3>");
    if( ! is_file("config.php") ) die("ERROR: config file not found.");
    include "config.php";
    include "functions.php";
    include "lib/cli_helper_class.php";
    include "models/tipping_provider.php";
    include "models/account.php";
    db_connect();

    include "bot_functions/generic.php";
    include "bot_functions/process_group_entry.php";
    include "bot_functions/process_user_entry.php";
    include "bot_functions/process_comments_entry.php";

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

    $transactions_per_account_cache = array();
    function get_transactions( $id_account )
    {
        global $transactions_per_account_cache, $tipping_provider;

        if( isset($transactions_per_account_cache[$id_account]) )
            return $transactions_per_account_cache[$id_account];

        $res = $tipping_provider->list_transactions($id_account);
        if( $res->message != "OK" ) return $res->message;

        $transactions_per_account_cache[$id_account] = $res->data;
        return $res->data;
    } # end function

    function has_outgoing_transactions( $transaction_list )
    {
        if( count($transaction_list) == 0 ) return false;
        foreach($transaction_list as $transaction)
            if($transaction->amount < 0 ) return true;
    } # end function

    function has_deposits( $transaction_list )
    {
        if( count($transaction_list) == 0 ) return false;
        foreach($transaction_list as $transaction)
            if($transaction->category == "receive") return true;
    } # end function

    function count_incoming_tips_from( $id_account, $transaction_list )
    {
        if( count($transaction_list) == 0 ) return 0;

        $return = 0;
        foreach($transaction_list as $transaction)
        {
            $parts = explode(".", $transaction->otheraccount);
            $otheraccount = end($parts);
            if($transaction->category == "move" && $otheraccount == $id_account)
                $return++;
        } # end foreach
        return $return;
    } # end function

    include "facebook-php-sdk/facebook.php";
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
        $facebook->setExtendedAccessToken();
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
        die();
    } # end try...catch

    $start   = time();
    cli::write("\n");
    cli::write( date("Y-m-d H:i:s") . " .--- Starting unclaimed transactions scanner\n");

    #################
    # Analysis loop #
    #################
    {
        $limit = date("Y-m-d 23:59:59", strtotime("today - $config->tipping_return_days days"));
        $query = "
            select
                " . $config->db_tables["log"] . ".*,
                ".$config->db_tables["account"].".date_created               as to_date_created,
                ".$config->db_tables["account"].".last_update                as to_last_update,
                ".$config->db_tables["account"].".last_activity              as to_last_activity,
                ".$config->db_tables["account"].".email                      as to_email
            #   ".$config->db_tables["account"].".wallet_address             as to_wallet_address
            from
                ".$config->db_tables["log"].",
                ".$config->db_tables["account"]."
            where
                ".$config->db_tables["log"].".state            = 'OK' and
                ".$config->db_tables["log"].".to_id_account    = ".$config->db_tables["account"].".id_account    and
                ".$config->db_tables["account"].".date_created = ".$config->db_tables["account"].".last_activity and
                date_processed <= '$limit'
            order by
                op_id asc
        ";
        $res0   = mysql_query($query);
        if( mysql_num_rows($res0) == 0 )
        {
            cli::write( "                    | Nothing to analyze. Exiting.\n", cli::$forecolor_light_green );
            cli::write( "                    '--- Scanner finished.\n");
            cli::write( "                    All operations ended in " . number_format(time() - $start) . " seconds.\n");
            cli::write("\n");
            mysql_free_result($res0);
            die();
        }

        $successful_returns = 0;
        $total_coins_returned = 0;
        $per_tipper_notifications_sent = array();
        cli::write( "                    | ".mysql_num_rows($res0)." Records behind $limit to be checked. Starting!\n", cli::$forecolor_white );
        while( $row = mysql_fetch_object($res0) )
        {
            $config->current_coin_name                = $row->coin_name;
            $config->current_tipping_provider_keyname = get_tipping_provider_keyname_by_coin($config->current_coin_name);
            $config->current_tipping_provider_data    = $config->tipping_providers_database[$config->current_tipping_provider_keyname];
            $config->current_coin_data                = $config->current_tipping_provider_data["per_coin_data"][$config->current_coin_name];

            $to_account_tmp = new account($row->to_id_account);
            $claiming_flag = "ok"; $color = cli::$forecolor_green; # To be returned
            if(     $row->to_email != "" && $to_account_tmp->wallet_address != "" )                                           { $claiming_flag = "ko";      $color = cli::$forecolor_red;   } # Can't be reclaimed
            elseif( $row->to_email == "" && $to_account_tmp->wallet_address != "" )                                           { $claiming_flag = "check";   $color = cli::$forecolor_brown; } # Transaction check needed
            cli::write( "                    | " );
            $from  = new account($row->from_id_account);
            $to    = new account($row->to_id_account);
            $op_id = sprintf("%06.0f", $row->op_id);
            cli::write( "> [#$op_id] @ $row->date_processed ($row->entry_type) $row->coins coins from [$row->from_id_account] $from->name to [$row->to_id_account] $to->name is $claiming_flag\n", $color );

            $tipping_provider = new tipping_provider( $config->current_coin_data["api_url"]    ,
                                                      $config->current_tipping_provider_data["public_key"] ,
                                                      $config->current_tipping_provider_data["secret_key"] );
            $tipping_provider->cache_api_responses_time = 0;

            ##########################
            if($claiming_flag == "ko")
            ##########################
            {
                # Let's do a small check on the target account. If it has at least one outgoing, we ping the account so it goes off the list.

                # First let's download recipient's transactions.
                cli::write( "                    |   Getting $to->name's transaction list... ", cli::$forecolor_green );
                $transactions = get_transactions($to->id_account);
                if( is_array($transactions) )
                {
                    cli::write( "OK. " . count($transactions) . " transactions downloaded and cached.\n", cli::$forecolor_light_green );
                }
                else
                {
                    cli::write( "\n" );
                    cli::write( "                    |   Couldn't get transactions! API Message: $transactions - ignoring\n", cli::$forecolor_light_red );
                    continue;
                } # end if

                # Now we check
                if( has_outgoing_transactions($transactions) )
                {
                    cli::write( "                    |   $to->name has outgoing transactions!!! Pinging the account and skipping entry.\n", cli::$forecolor_light_red );
                    $to->ping(); # Here we ping the account
                    continue;
                }
                else
                {
                    # Let's check if it has deposits...
                    if( has_deposits($transactions) )
                    {
                        cli::write( "                    |   $to->name has no outgoing transactions but has deposits!!! Pinging the account and skipping entry.\n", cli::$forecolor_light_red );
                        $to->ping(); # Here we ping the account
                        continue;
                    }
                    else
                    {
                        cli::write( "                    |   $to->name doesn't have outgoing transactions or deposits!!! Changing flag to 'check' so it is reprocessed.", cli::$forecolor_yellow, cli::$backcolor_red );
                        cli::write( "\n" );
                        # cli::write( "                    |   transactions := " . str_replace("\n", "\n                    |                   ", print_r($transactions, true)) . "\n", cli::$forecolor_light_red );
                        $claiming_flag = "check";
                    } # end if
                } # end if

                # If the flag didn't change, we skip the record.
                if($claiming_flag == "ko") continue;
            } # end if

            #############################
            if($claiming_flag == "check")
            #############################
            {
                #=====================================
                # Step 0: get account transaction list
                #=====================================

                cli::write( "                    |   Getting $to->name's transaction list... ", cli::$forecolor_green );
                $transactions = get_transactions($to->id_account);
                if( is_array($transactions) )
                {
                    cli::write( "OK. " . count($transactions) . " transactions downloaded and cached.\n", cli::$forecolor_light_green );
                }
                else
                {
                    cli::write( "\n" );
                    cli::write( "                    |   Couldn't get transactions! API Message: $transactions - ignoring\n", cli::$forecolor_light_red );
                    continue;
                } # end if

                $checked = false;

                #==============================================================
                # Step 1: check if the target account has a single transaction.
                # If single transaction and comes from the tipper, the tip is returned.
                #======================================================================

                if( count($transactions) == 1 )
                {
                    # print_r($transactions); die();
                    $parts = explode(".", $transactions[0]->otheraccount);
                    $otheraccount = end($parts);
                    # cli::write( "                    |   Other account (".$transactions[0]->otheraccount.") is $otheraccount\n", cli::$forecolor_light_purple );
                    if( $otheraccount == $from->id_account )
                    {
                        cli::write( "                    |   [Case 1] $to->name has only one transaction and it is from $from->name. Return proceeds.\n", cli::$forecolor_light_green );
                        $checked = true;
                    }
                    else
                    {
                        $other = new account($otheraccount);
                        cli::write( "                    |   [Case 1] Recipient's transaction is not from $from->name! it is from [$otheraccount] $other->name! Skipping entry.\n", cli::$forecolor_light_red );
                        continue;
                    } # end if
                } # end if

                #========================
                # Prep for next two steps
                #========================

                $tips_from_sender = count_incoming_tips_from($from->id_account, $transactions);
                # cli::write( "                    |   $to->name Has $tips_from_sender transactions coming from $from->name.\n", cli::$forecolor_light_purple );

                #==================================================================
                # Step 2: multiple transactions, all incoming from the same tipper.
                # If all the transactions are from the same tipper, the tip is returned.
                #=======================================================================

                if( ! $checked )
                {
                    if( count($transactions) > 1 && $tips_from_sender == count($transactions) )
                    {
                        cli::write( "                    |   [Case 2] All the transactions of $to->name have been sent from $from->name. Return proceeds.\n", cli::$forecolor_light_green );
                        $checked = true;
                    } # end if
                } # end if

                #===================================================================
                # Step 3: multiple transactions, all incoming from different tippers
                # The tip is not returned --- it was originally returned!
                #========================================================

                if( ! $checked )
                {
                    if( count($transactions) > 1 && $tips_from_sender >= 1 )
                    {
                        cli::write( "                    |   [Case 3] Some of the transactions of $to->name have been sent from $from->name. Pinging $to->name's account and skipping.\n", cli::$forecolor_yellow );
                        $to->ping();
                        continue;
                    } # end if
                } # end if

                #=================================================================
                # Step 4: multiple transactions, incoming/outgoing - most unlikely
                # The tip is NOT returned. The target account is pinged.
                #=================================================================

                if( ! $checked )
                {
                    if( has_outgoing_transactions($transactions) )
                    {
                        cli::write( "                    |   [Case 4] $to->name has outgoing transactions!!! Skipping entry.\n", cli::$forecolor_light_red );
                        $to->ping(); # Here we ping the account
                        continue;
                    } # end if
                } # end if

                if( ! $checked )
                {
                    cli::write( "                    |   [Cases checked] All cases evaluated, but couldn't detect an issue. Please review $to->name's account. Transaction list:\n", cli::$forecolor_light_red );
                    cli::write( "                    |   [Cases checked] " . str_replace("\n", "\n                    |                   ", print_r($transactions, true)) . "\n", cli::$forecolor_light_red );
                    continue;
                } # end if
            } # end if
            # continue;

            # The tip went thru, but the user never authorized the app. Tip can be returned safely.
            # Proceed to transfer funds
            cli::write( "                    |   Issuing send(sender:$to->id_account, recipient:$from->id_account, coins:$row->coins) \n", cli::$forecolor_light_green );
            $res = $tipping_provider->send($to->id_account, $from->id_account, $row->coins);
            if( stristr($res->message, "ERROR") !== false )
            {
                cli::write( "                    |   Can't transfer the coins! Message: $res->message. Logging and skipping.\n", cli::$forecolor_light_red );
                $query = "
                    insert into ".$config->db_tables["log"]." set
                    entry_type        = '".$row->entry_type."',
                    from_handler      = '".$row->from_handler."',
                    entry_id          = '".$row->entry_id."',
                    action_type       = 'return',
                    from_facebook_id  = '".$to->facebook_id."',
                    from_id_account   = '".$to->id_account."',
                    coin_name         = '".$config->current_coin_name."'
                    coins             = '".$row->coins."',
                    to_facebook_id    = '".$from->facebook_id."',
                    to_id_account     = '".$from->id_account."',
                    message           = '".addslashes($row->message)."',
                    state             = 'RETURN:ERROR',
                    info              = 'UNABLE_TO_SEND_COINS',
                    date_analyzed     = '$row->date_analyzed',
                    api_call_message  = '$res->message',
                    api_extended_info = '$res->extended_info',
                    date_processed    = '".date("Y-m-d H:i:s")."'
                ";
                mysql_query($query);
                cli::write( "                    |   Skipping record.\n", cli::$forecolor_yellow );
                continue;
            } # end if

            # Adding to log
            cli::write( "                    |   Coins sent flawlessly!\n", cli::$forecolor_light_green );
            $query = "
                insert into ".$config->db_tables["log"]." set
                entry_type        = '".$row->entry_type."',
                from_handler      = '".$row->from_handler."',
                entry_id          = '".$row->entry_id."',
                action_type       = 'return',
                from_facebook_id  = '".$to->facebook_id."',
                from_id_account   = '".$to->id_account."',
                coin_name         = '".$config->current_coin_name."'
                coins             = '".$row->coins."',
                to_facebook_id    = '".$from->facebook_id."',
                to_id_account     = '".$from->id_account."',
                message           = '".addslashes($row->message)."',
                state             = 'RETURN:OK',
                info              = '',
                date_analyzed     = '$row->date_analyzed',
                api_call_message  = '$res->message',
                api_extended_info = '$res->extended_info',
                date_processed    = '".date("Y-m-d H:i:s")."'
            ";
            mysql_query($query);
            $from->ping();

            # Flagging the original as returned
            $query = "
                update ".$config->db_tables["log"]." set
                state = 'RETURNED'
                where op_id = '$row->op_id'
            ";
            mysql_query($query);

            # End of story!
            $successful_returns++;
            $total_coins_returned += $row->coins;

            # Send notification if it wasn't already sent
            if( in_array($from->id_account, $per_tipper_notifications_sent) ) continue;
            try
            {
                $message = "You're receiving tip returns. Please check the OpsLog on your dashboard in order to get full details.";
                $params  = array("template" => $message, "href" => "?tab=7");
                $graph_api_destination = $from->facebook_id;
                $url = "/$graph_api_destination/notifications";
                $res = $facebook->api($url, "POST", $params);
                if( $res["success"] )
                {
                    cli::write( "                    |   Single shot notification successfully sent to $from->name. That's all.\n", cli::$forecolor_light_green );
                    $notification_message = "FB Notification sent.";
                }
                else
                {
                    cli::write( "                    |   Couldn't send success notification to $from->name. Error: ".$res["error"]["message"].".\n", cli::$forecolor_brown );
                    $notification_message = "FB Notification failed: ".$res["error"]["message"].".";
                } # end if
            }
            catch( Exception $e )
            {
                cli::write( "                    |   Couldn't send success notification to $from->name. Error: ".$res["error"]["message"].".\n", cli::$forecolor_brown );
                $notification_message = "FB Notification failed: ".$res["error"]["message"].".";
            } # end try...catch

            $query = "
                update ".$config->db_tables["log"]." set
                api_extended_info = concat(api_extended_info, '\n[RETURNEE] $notification_message')
                where op_id = '$row->op_id'
            ";
            mysql_query($query);
            $per_tipper_notifications_sent[] = $from->id_account;

        } # end while
        mysql_free_result($res0);

    } # end analysis loop

    cli::write( "                    '--- Scanner finished. $successful_returns transactions worth $total_coins_returned coins have been returned.\n");
    cli::write( "                    All operations ended in " . number_format(time() - $start) . " seconds.\n");
    cli::write("\n");
