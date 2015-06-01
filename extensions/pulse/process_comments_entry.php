<?php
    function process_comments_entry($entry)
    {
        global $config;

        $action          = new action();
        $from            = new account($entry->id_author);
        $msg             = trim($entry->content);
        $entry_type      = "pulse_comment"; # $entry["type"];
        $recipient       = null;
        $entry_id        = $entry->parent_id . "/" . $entry->id;

        if( empty($msg) )
        {
            cli::write( "                    |  .----------------------------------------------------------\n", cli::$forecolor_light_red );
            cli::write( "                    |  | Message {$entry->id} has no caption/content. Entry ignored.\n", cli::$forecolor_light_red );
            cli::write( "                    |  '----------------------------------------------------------\n", cli::$forecolor_light_red );
            $action->state = "IGNORE";
            $action->info  = "EMPTY_MESSAGE";
            return $action;
        } # end if

        list($action_type, $coins) = check_action_type_from_message($msg);
        if( $config->current_coin_data["coin_disabled"] ) $action_type = "coin_disabled";

        $action->message     = $msg;
        $action->entry_type  = $entry_type;
        $action->entry_id    = $entry_id;
        $action->action_type = $action_type;

        if( ! empty($entry->metadata) ) $entry->metadata = json_decode($entry->metadata);
        if( ! empty($entry->metadata->mentions) ) $entry->metadata->mentions = (array) $entry->metadata->mentions;

        if( $action_type == "not_found" )
        {
            cli::write( "                    |  .----------------------------------------------------------\n", cli::$forecolor_light_red );
            cli::write( "                    |  | Message: ".substr(str_replace("\n", "", $msg), 0, 100)."...\n" );
            cli::write( "                    |  | Action '$action_type' invalid. Entry ignored.\n", cli::$forecolor_light_red );
            cli::write( "                    |  '----------------------------------------------------------\n", cli::$forecolor_light_red );
            $action->state = "ERROR";
            $action->info  = "INVALID_ACTION_TYPE:$action_type";
            return $action;
        } # end if

        if( $action_type == "coin_disabled" )
        {
            cli::write( "                    |  .----------------------------------------------------------\n", cli::$forecolor_light_red );
            cli::write( "                    |  | Message: ".substr(str_replace("\n", "", $msg), 0, 100)."...\n" );
            cli::write( "                    |  | Coin is disabled. Entry ignored.\n", cli::$forecolor_light_red );
            cli::write( "                    |  '----------------------------------------------------------\n", cli::$forecolor_light_red );
            $action->state = "ERROR";
            $action->info  = "COIN_IS_DISABLED";
            return $action;
        } # end if

        if( empty($coins) )
        {
            cli::write( "                    |  .----------------------------------------------------------\n", cli::$forecolor_light_red );
            cli::write( "                    |  | Message: ".substr(str_replace("\n", "", $msg), 0, 100)."...\n" );
            cli::write( "                    |  | No coins to be sent have been found. Entry ignored.\n", cli::$forecolor_light_red );
            cli::write( "                    |  '----------------------------------------------------------\n", cli::$forecolor_light_red );
            $action->state = "ERROR";
            $action->info  = "NO_COINS_DETECTED";
            return $action;
        } # end if

        $action->coin_name   = $config->current_coin_name;
        $action->coins       = $coins;

        $account = $from;
        if( ! $account->exists )
        {
            cli::write( "                    |  .----------------------------------------------------------\n", cli::$forecolor_light_red );
            cli::write( "                    |  | Message: ".substr(str_replace("\n", "", $msg), 0, 100)."...\n" );
            cli::write( "                    |  | From:        [{$account->id_account}] {$account->name}\n" );
            cli::write( "                    |  | The source account doesn't exist in DB. Entry ignored.\n", cli::$forecolor_light_red );
            cli::write( "                    |  '----------------------------------------------------------\n", cli::$forecolor_light_red );
            $action->state = "ERROR";
            $action->info  = "SENDER_NOT_REGISTERED";
            return $action;
        }

        $action->from_facebook_id = $from->facebook_id;
        $action->from_name        = $from->name;
        $action->from_id_account  = $account->id_account;

        cli::write( "                    |  .----------------------------------------------------------\n" );
        cli::write( "                    |  | Entry:       [$entry_type] id: $entry_id\n" );
        cli::write( "                    |  | From:        [".$action->from_id_account."] ".$action->from_name."\n" );
        cli::write( "                    |  | Message:     ".substr(str_replace("\n", "", $msg), 0, 100)."...\n" );
        cli::write( "                    |  | Action type: ".cli::color($action_type, cli::$forecolor_light_purple)."\n" );

        $recipients = array();
        #===================================
        if($action_type == "give_to_tagged")
        #===================================
        {
            cli::write( "                    |  | Tagged:      .-------------------------------------------\n", cli::$forecolor_yellow );
            if( ! empty($entry->metadata->mentions) )
            {
                foreach($entry->metadata->mentions as $id => $name)
                {
                    cli::write( "                    |  |              | - [$id] $name\n", cli::$forecolor_yellow );
                    $recipients[$id] = $name;
                } # end foreach
            } # end if
            cli::write( "                    |  |              '-------------------------------------------\n", cli::$forecolor_yellow );
        }
        #========================================
        elseif( $action_type = "give_to_author" )
        #========================================
        {
            cli::write( "                    |  +----------------------------------------------------------\n", cli::$forecolor_light_red );
            cli::write( "                    |  | Give to author not supported. Ignoring entry.\n", cli::$forecolor_light_red );
            cli::write( "                    |  '----------------------------------------------------------\n", cli::$forecolor_light_red );
            $action->state = "ERROR";
            $action->info  = "UNSUPPORTED_METHOD:GIVE_TO_AUTHOR";
            return $action;
        } # end if

        ##################
        # Recipient loop #
        ##################

        cli::write( "                    |  +----------------------------------------------------------\n", cli::$forecolor_light_green );
        cli::write( "                    |  | Going to send: $coins ".$config->current_coin_data["coin_name_plural"]."\n", cli::$forecolor_light_green );
        cli::write( "                    |  | From:          [$account->id_account] $account->name\n", cli::$forecolor_light_green );

        $returning_actions = array();
        foreach($recipients as $recipient_id => $recipient_name)
        {
            $continue = true;

            if($account->id_account == $recipient_id)
            {
                cli::write( "                    |  | To:            Recipient #".($index+1)." is the same as the tipper. Ignored.\n", cli::$forecolor_light_red );
                $action->state = "IGNORE";
                $action->info  = "SELF_TIPPING_NOT_ALLOWED";
                $continue = false;
            } # end if

            if( $continue )
            {
                $recipient_account = new account($recipient_id);
                $to_color = cli::$forecolor_light_green;

                cli::write( "                    |  | To:            Recipient #".($index+1)."[$recipient_account->id_account] $recipient_account->name\n", $to_color );

                # Final check: remote control commands
                if( isset($config->sysadmins[$account->id_account]) && stristr($action->message, $config->string_for_botpool_tipping) !== false)
                {
                    cli::write( "                    |  |                Admin $account->name invoked tipping from bot's pool!", cli::$forecolor_purple, cli::$backcolor_yellow );
                    cli::write( "\n" );
                    $bot_account = new account($config->tippingbot_id_acount);
                    if( ! $bot_account->exists )
                    {
                        cli::write( "                    |  |                Can't load tippingbot account! Order will be ignored.", cli::$forecolor_yellow, cli::$backcolor_red );
                        cli::write( "\n" );
                    }
                    else
                    {
                        $action->message = "{By Admin [".$account->id_account."] ".$account->name."} " . $action->message;
                        $action->from_facebook_id        = $bot_account->facebook_id;
                        $action->from_name               = $bot_account->name;
                        $action->from_id_account         = $bot_account->id_account;
                        $action->notify_to_is_bot_switch = true;
                        $action->notify_to_facebook_id   = $account->facebook_id;
                        $action->notify_to_name          = $account->name;
                        $action->notify_to_id_account    = $account->id_account;
                        cli::write( "                    |  |                Switched sender data to tipbot's data.", cli::$forecolor_purple, cli::$backcolor_yellow );
                        cli::write( "\n" );
                        cli::write( "                    |  |                Notification will be sent to Admin $account->id_account ($account->name).", cli::$forecolor_purple, cli::$backcolor_yellow );
                        cli::write( "\n" );
                    } # end if
                } # end if

                # Check if there's no double spending during the last time...
                {
                    $query = "
                        select * from ".$config->db_tables["log"] . " where
                            entry_id          = '".$entry_id."'
                        and from_id_account   = '".$action->from_id_account."'
                        and to_id_account     = '".$recipient_account->id_account."'
                        and coin_name         = '".$action->coin_name."'
                        and coins             = '".$coins."'
                    ";
                    $resx = mysql_query($query);
                    if( mysql_num_rows($resx) > 0 )
                    {
                        cli::write( "                    |  |                Record already registered! skipping it.\n", cli::$forecolor_light_red );
                        mysql_free_result($resx);
                        $action->state = "IGNORE";
                        $action->info  = "";
                        $continue = false;
                    } # end if
                    mysql_free_result($resx);
                } # end block
            } # end if

            if( $continue )
            {
                $action->state = "OK";
                $action->to_facebook_id = $recipient_account->facebook_id;
                $action->to_name        = $recipient_account->name;
                $action->to_id_account  = $recipient_account->id_account;
            } # end if

            $returning_actions[] = clone $action;
        } # end foreach
        cli::write( "                    |  '----------------------------------------------------------\n", cli::$forecolor_light_green );

        return $returning_actions;
    } # end function
