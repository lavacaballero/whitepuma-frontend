<?php
    function process_comment($entry, $source_item)
    {
        global $config;

        $action          = new action();
        $from            = $entry->from;
        $msg             = $entry->text;
        $entry_type      = "comment";
        $recipient       = null;

        list($action_type, $coins) = check_action_type_from_message($msg);
        if( $config->current_coin_data["coin_disabled"] ) $action_type = "coin_disabled";
        $entry_id = $entry->id;

        $action->message     = $msg;
        $action->entry_type  = $entry_type;
        $action->entry_id    = $entry_id;
        $action->action_type = $action_type;

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

        $query   = "select * from {$config->db_tables["instagram_users"]} where user_id = '$from->id'";
        $res     = mysql_query($query);
        if( mysql_num_rows($res) == 0 )
        {
            cli::write( "                    |  .----------------------------------------------------------\n", cli::$forecolor_light_red );
            cli::write( "                    |  | Message: ".substr(str_replace("\n", "", $msg), 0, 100)."...\n" );
            cli::write( "                    |  | From:        [$from->id] @$from->user_name $from->full_name\n" );
            cli::write( "                    |  | The source account doesn't exist in DB. Entry ignored.\n", cli::$forecolor_light_red );
            cli::write( "                    |  '----------------------------------------------------------\n", cli::$forecolor_light_red );
            $action->state = "ERROR";
            $action->info  = "SENDER_NOT_REGISTERED";
            return $action;
        } # end if
        $row = mysql_fetch_object($res);
        mysql_free_result($res);
        $account = new account($row->id_account);

        $action->from_instagram_id       = $from->id;
        $action->from_instagram_username = $from->username;
        $action->from_name               = $account->name;
        $action->from_id_account         = $account->id_account;

        cli::write( "                    |  .----------------------------------------------------------\n" );
        cli::write( "                    |  | Entry:       [$entry_type] id: $entry_id\n" );
        cli::write( "                    |  | Message:     ".str_replace("\n", "", $msg)."...\n" );
        cli::write( "                    |  | From:        [".$action->from_id_account."] @".$action->from_instagram_username." $action->from_name\n" );
        cli::write( "                    |  | Action type: ".cli::color($action_type, cli::$forecolor_light_purple)."\n" );

        $recipients = array();
        #===================================
        if($action_type == "give_to_tagged")
        #===================================
        {
            if( preg_match_all('/(\@[a-zA-Z0-9\._]*\b)/', $action->message, $matches) == 0 )
            {
                cli::write( "                    |  | No tagged recipients found.\n", cli::$forecolor_light_red );
                cli::write( "                    |  '----------------------------------------------------------\n" );
                $action->state = "ERROR";
                $action->info  = "NO_TAGGED_RECIPIENTS";
                return $action;
            } # end if

            cli::write( "                    |  | Tagged:      .-------------------------------------------\n", cli::$forecolor_yellow );
            foreach($matches[1] as $index => $this_match)
            {
                cli::write( "                    |  |              | - $this_match\n", cli::$forecolor_yellow );
                $recipients[] = str_replace("@", "", $this_match);
            } # end if
            cli::write( "                    |  |              '-------------------------------------------\n", cli::$forecolor_yellow );
            # print_r($tagged_users); die();
        } # end if

        ##################
        # Recipient loop #
        ##################

        cli::write( "                    |  +----------------------------------------------------------\n", cli::$forecolor_light_green );
        cli::write( "                    |  | Going to send: $coins ".$config->current_coin_data["coin_name_plural"]."\n", cli::$forecolor_light_green );
        cli::write( "                    |  | From:          [$account->id_account] @$action->from_instagram_username $account->name\n", cli::$forecolor_light_green );

        $returning_actions = array();
        foreach($recipients as $index => $recipient)
        {
            $continue = true;
            if( $continue )
            {
                $recipient_account->id_account         = "";
                $recipient_account->name               = "Instagram: $recipient";
                $recipient_account->instagram_username = $recipient;

                $query   = "select * from {$config->db_tables["instagram_users"]} where user_name = '$recipient'";
                $res     = mysql_query($query);
                if( mysql_num_rows($res) > 0 )
                {
                    $row = mysql_fetch_object($res);
                    $recipient_account->id_account = $row->id_account;
                    $recipient_account->name       = $row->full_name;
                    $to_color = cli::$forecolor_light_green;
                }
                else
                {
                    $to_color = cli::$forecolor_light_blue;
                } # end if
                mysql_free_result($res);

                if($account->id_account == $recipient_account->id_account)
                {
                    cli::write( "                    |  | To:            Recipient #".($index+1)." is the same as the tipper. Ignored.\n", cli::$forecolor_light_red );
                    $action->state = "IGNORE";
                    $action->info  = "SELF_TIPPING_NOT_ALLOWED";
                    $continue = false;
                } # end if
            } # end if

            if( $continue )
            {
                cli::write( "                    |  | To:            Recipient #".($index+1)." [{$recipient_account->id_account}] @{$recipient_account->instagram_username} {$recipient_account->name}\n", $to_color );
                $action->state = "OK";
                $action->to_name               = $recipient_account->name;
                $action->to_instagram_username = $recipient_account->instagram_username;
                $action->to_id_account         = $recipient_account->id_account;
            } # end if

            $returning_actions[] = clone $action;
        } # end foreach
        cli::write( "                    |  '----------------------------------------------------------\n", cli::$forecolor_light_green );

        return $returning_actions;
    } # end function
