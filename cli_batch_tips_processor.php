<?php
    /**
     * Tip rain processor
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
    include("lib/self_running_checker.inc");
    define( "LOCK_FILE", sprintf("/tmp/%s%s.pid", $config->session_vars_prefix, "batch_tips_processor") );
    db_connect();

    if( ! $config->engine_enabled )
    {
        cli::write("\n");
        cli::write( date("Y-m-d H:i:s") . " Engine is disabled. Exiting.", cli::$forecolor_black, cli::$backcolor_red );
        cli::write("\n");
        die();
    } # end if

    cli::write( date("Y-m-d H:i:s") . " Starting submission loop.\n" );
    $start           = time();
    $processing_date = date("Y-m-d H:i:s");

    #################
    # Overlap check #
    #################
    {
        if( self_running_checker() )
        {
            cli::write( "                    Skipping new run! another instance is running!", cli::$forecolor_white, cli::$backcolor_red );
            cli::write( "\n" );
            die();
        } # end if
    } # end overlap check

    $query = "
        select
            ".$config->db_tables["tip_batch_submissions"].".*,
            ".$config->db_tables["tip_batches"].".date_started        as batch_date_started,
            ".$config->db_tables["tip_batches"].".batch_title         as batch_title,
            ".$config->db_tables["tip_batches"].".creator_facebook_id as batch_creator_facebook_id,
            ".$config->db_tables["tip_batches"].".using_bot_account   as batch_using_bot_account,
            ".$config->db_tables["tip_batches"].".state               as batch_state,
            ".$config->db_tables["tip_batches"].".target_group_id
        from
            ".$config->db_tables["tip_batch_submissions"].",
            ".$config->db_tables["tip_batches"]."
        where
            ".$config->db_tables["tip_batch_submissions"].".batch_id = ".$config->db_tables["tip_batches"].".batch_id and
            ".$config->db_tables["tip_batches"].".state              = 'active' and
            ".$config->db_tables["tip_batch_submissions"].".state    = 'pending'
        order by
            date_created asc, recipient_name asc
        limit $config->tip_rain_submissions_per_minute
    ";
    $res = mysql_query($query);
    if( mysql_num_rows($res) == 0 )
    {
        mysql_free_result($res);
        cli::write( "                    There's nothing to be processed. Finishing.\n\n" );
        unlink( LOCK_FILE );
        die();
    } # end if

    cli::write( "                    Got ".mysql_num_rows($res)." entries.\n" );

    $per_tipper_balances = array();
    while($row = mysql_fetch_object($res))
    {
        # Let's look for the group handler
        $group_handler = "";
        foreach($config->facebook_monitor_objects as $okey => $odata)
        {
            if( $odata["id"] == $row->target_group_id )
            {
                $group_handler = $okey;
                break;
            } # end if
        } # end foreach

        $config->current_coin_name                = $row->coin_name;
        $config->current_tipping_provider_keyname = get_tipping_provider_keyname_by_coin($config->current_coin_name);
        $config->current_tipping_provider_data    = $config->tipping_providers_database[$config->current_tipping_provider_keyname];
        $config->current_coin_data                = $config->current_tipping_provider_data["per_coin_data"][$config->current_coin_name];

        if( $row->batch_date_started == '0000-00-00 00:00:00' )
        {
            $query = "
                update ".$config->db_tables["tip_batches"]."
                set date_started = '$processing_date'
                where batch_id = '$row->batch_id'
            ";
            mysql_query($query);
        } # end if

        $creator_account = new account($row->batch_creator_facebook_id);
        if( $row->batch_using_bot_account ) $tipper_id_account = $config->tippingbot_fb_id_account;
        else                                $tipper_id_account = $row->batch_creator_facebook_id;
        if( ! isset($per_tipper_balances[$tipper_id_account]) )
        {
            $tipper_account = new account($tipper_id_account);
            if( $row->batch_using_bot_account )
                cli::write( "                    [$row->batch_id] Getting $config->current_coin_name balance from bot's account... " );
            else
                cli::write( "                    [$row->batch_id] Getting $config->current_coin_name balance from creator $tipper_account->id_account ($tipper_account->name)... " );
            $balance = $tipper_account->get_balance();
            if( ! is_numeric($balance) )
            {
                cli::write( "\n" );
                cli::write( "                                     Couldn't get $config->current_coin_name balance! delaying execution to next run.\n", cli::$forecolor_red );
                break;
            } # end if
            cli::write( "Got $balance coins.\n", cli::$forecolor_white );
            $per_tipper_balances[$tipper_id_account] = $balance;
        } # end if

        if( $balance < $row->coin_amount )
        {
            cli::write( "                                     NOT ENOUGH FUNDS! Tipper's $config->current_coin_name balance is $balance, $row->coin_amount needed.\n", cli::$forecolor_red );
            $query = "
                update ".$config->db_tables["tip_batch_submissions"]."
                set state = 'failed', date_processed = '$processing_date', api_message = 'INTERNAL:NOT_ENOUGH_FUNDS'
                where batch_id = '$row->batch_id' and state = 'pending'
            ";
            mysql_query($query);
            $query = "
                update ".$config->db_tables["tip_batches"]."
                set state = 'cancelled', date_finished = '$processing_date', cancellation_message = 'Batch cancelled because of lack of funds on creator\'s account'
                where batch_id = '$row->batch_id'
            ";
            mysql_query($query);
            cli::write( "                                     Batch cancelled. Exiting.\n", cli::$forecolor_red );
            break;
        } # end if

        $recipient_account = new account($row->recipient_facebook_id);
        cli::write( "                                     Sending $row->coin_amount $config->current_coin_name from $tipper_account->id_account($tipper_account->name) to $recipient_account->id_account($recipient_account->name)... " );

        $tipping_provider = new tipping_provider( $config->current_coin_data["api_url"]    ,
                                                  $config->current_tipping_provider_data["public_key"] ,
                                                  $config->current_tipping_provider_data["secret_key"] );
        $tipping_provider->cache_api_responses_time = 0;

        $resx = $tipping_provider->send($tipper_account->id_account, $recipient_account->id_account, $row->coin_amount);
        if( stristr($resx->message, "ERROR") !== false )
        {
            cli::write( "\n", cli::$forecolor_light_red );
            cli::write( "                                     Can't transfer $config->current_coin_name! Message: $resx->message. Logging and skipping.\n", cli::$forecolor_light_red );
            $query = "
                insert into ".$config->db_tables["log"]." set
                entry_type        = 'tip_batch',
                from_handler      = '$group_handler',
                entry_id          = '$row->batch_id',
                action_type       = 'give_from_batch',
                from_facebook_id  = '".$tipper_account->facebook_id."',
                from_id_account   = '".$tipper_account->id_account."',
                coin_name         = '".$config->current_coin_name."',
                coins             = '".$row->coin_amount."',
                to_facebook_id    = '".$recipient_account->facebook_id."',
                to_id_account     = '".$recipient_account->id_account."',
                message           = '".addslashes($row->batch_title)."',
                state             = 'ERROR',
                info              = 'UNABLE_TO_SEND_COINS',
                date_analyzed     = '$processing_date',
                api_call_message  = '$resx->message',
                api_extended_info = '$resx->extended_info',
                date_processed    = '".date("Y-m-d H:i:s")."'
            ";
            mysql_query($query);
            if( stristr($resx->message, "NOT_ENOUGH_FUNDS" ) !== false )
            {
                $query = "
                    update ".$config->db_tables["tip_batch_submissions"]."
                    set state = 'failed', date_processed = '$processing_date', api_message = 'INTERNAL:NOT_ENOUGH_FUNDS'
                    where batch_id = '$row->batch_id' and state = 'pending'
                ";
                mysql_query($query);
                $query = "
                    update ".$config->db_tables["tip_batches"]."
                    set state = 'cancelled', date_finished = '$processing_date', cancellation_message = 'Batch cancelled because of lack of funds on creator\'s account'
                    where batch_id = '$row->batch_id'
                ";
                mysql_query($query);
                cli::write( "                                     Batch cancelled because lack of funds on creator's account. Exiting.\n", cli::$forecolor_red );
                break;
            }
            elseif( stristr($resx->message, "MINIMUM_TX_AMOUNT" ) !== false )
            {
                $query = "
                    update ".$config->db_tables["tip_batch_submissions"]."
                    set state = 'failed', date_processed = '$processing_date', api_message = 'INTERNAL:MINIMUM_TX_AMOUNT_NOT_REACHED'
                    where batch_id = '$row->batch_id' and state = 'pending'
                ";
                mysql_query($query);
                $query = "
                    update ".$config->db_tables["tip_batches"]."
                    set state = 'cancelled', date_finished = '$processing_date', cancellation_message = 'Batch cancelled because of lack of funds on creator\'s account'
                    where batch_id = '$row->batch_id'
                ";
                mysql_query($query);
                cli::write( "                                     Batch cancelled because tip was below minimum. Exiting.\n", cli::$forecolor_red );
                break;
            }
            elseif( stristr($resx->message, "UNREGISTERED_TARGET_ACCOUNT" ) !== false )
            {
                $query = "
                    update ".$config->db_tables["tip_batch_submissions"]."
                    set state = 'failed', date_processed = '$processing_date', api_message = 'INTERNAL:UNREGISTERED_TARGET_ACCOUNT'
                    where batch_id = '$row->batch_id' and recipient_facebook_id = '$recipient_account->facebook_id'
                ";
                mysql_query($query);
                cli::write( "                                     Record updated so it isn't rechecked on future batch runs.\n", cli::$forecolor_red );
            } # end if
        }
        else
        {
            $balance = $balance - $row->coin_amount;
            $per_tipper_balances[$tipper_id_account] = $balance;
            cli::write( "OK. New balance is " );
            cli::write( "$balance\n", cli::$forecolor_white );

            $query = "
                update ".$config->db_tables["tip_batch_submissions"]." set state = 'sent', date_processed = '$processing_date'
                where batch_id = '$row->batch_id' and recipient_facebook_id = '$row->recipient_facebook_id'
            ";
            mysql_query($query);
            $query = "
                insert into ".$config->db_tables["log"]." set
                entry_type        = 'tip_batch',
                from_handler      = '$group_handler',
                entry_id          = '$row->batch_id',
                action_type       = 'give_from_batch',
                from_facebook_id  = '".$tipper_account->facebook_id."',
                from_id_account   = '".$tipper_account->id_account."',
                coin_name         = '".$config->current_coin_name."',
                coins             = '".$row->coin_amount."',
                to_facebook_id    = '".$recipient_account->facebook_id."',
                to_id_account     = '".$recipient_account->id_account."',
                message           = '".addslashes($row->batch_title)."',
                state             = 'OK',
                info              = '',
                date_analyzed     = '$processing_date',
                api_call_message  = '',
                api_extended_info = 'No notifications have been sent.',
                date_processed    = '$processing_date'
            ";
            mysql_query($query);
        } # end if

        $query2 = "
            select
                count(recipient_facebook_id) as pending_count
            from
                ".$config->db_tables["tip_batch_submissions"]."
            where
                batch_id = '$row->batch_id' and
                state    = 'pending'
        ";
        $res2 = mysql_query($query2);
        $row2 = mysql_fetch_object($res2);
        if( $row2->pending_count == 0 )
        {
            $query = "
                update ".$config->db_tables["tip_batches"]." set state = 'finished', date_finished = '$processing_date'
                where batch_id = '$row->batch_id'
            ";
            mysql_query($query);
            cli::write( "                                     Batch $row->batch_id flagged as 'finished'.\n", cli::$forecolor_green );
        } # end if
    } # end while

    cli::write( "                    Submission loop finished in " . (time() - $start) . " seconds.\n\n" );
    unlink( LOCK_FILE );
