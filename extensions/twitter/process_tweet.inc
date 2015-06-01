<?php
    function process_tweet($entry)
    {
        global $config, $facebook, $feed_comments;

        $action          = new action();
        $from            = (object) $entry->user;
        $msg             =          $entry->text;
        $entry_type      = "tweet";
        $recipient       = null;

        if( $entry->retweeted )
        {
            cli::write( "                    |  .----------------------------------------------------------\n", cli::$forecolor_light_red );
            cli::write( "                    |  | Message: ".substr(str_replace("\n", "", $msg), 0, 100)."...\n" );
            cli::write( "                    |  | Is a retweet. Entry ignored.\n", cli::$forecolor_light_red );
            cli::write( "                    |  '----------------------------------------------------------\n", cli::$forecolor_light_red );
            $action->state = "ERROR";
            $action->info  = "INVALID_MESSAGE_TYPE:RETWEETED";
            return $action;
        } # end if

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

        $query   = "select * from {$config->db_tables["twitter"]} where twitter_id = '$from->id'";
        $res     = mysql_query($query);
        if( mysql_num_rows($res) == 0 )
        {
            cli::write( "                    |  .----------------------------------------------------------\n", cli::$forecolor_light_red );
            cli::write( "                    |  | Message: ".substr(str_replace("\n", "", $msg), 0, 100)."...\n" );
            cli::write( "                    |  | From:        [$from->id] @$from->screen_name $from->name\n" );
            cli::write( "                    |  | The source account doesn't exist in DB. Entry ignored.\n", cli::$forecolor_light_red );
            cli::write( "                    |  '----------------------------------------------------------\n", cli::$forecolor_light_red );
            $action->state = "ERROR";
            $action->info  = "SENDER_NOT_REGISTERED";
            return $action;
        } # end if
        $row = mysql_fetch_object($res);
        mysql_free_result($res);
        $account = new account($row->id_account);

        $action->from_twitter_id  = $from->id;
        $action->from_screen_name = $from->screen_name;
        $action->from_name        = $account->name;
        $action->from_id_account  = $account->id_account;

        cli::write( "                    |  .----------------------------------------------------------\n" );
        cli::write( "                    |  | Entry:       [$entry_type] id: $entry_id\n" );
        cli::write( "                    |  | Message:     ".str_replace("\n", "", $msg)."...\n" );
        cli::write( "                    |  | From:        [".$action->from_id_account."] @".$action->from_screen_name." $action->from_name\n" );
        cli::write( "                    |  | Action type: ".cli::color($action_type, cli::$forecolor_light_purple)."\n" );

        $recipients = array();
        #===================================
        if($action_type == "give_to_tagged")
        #===================================
        {
            # print_r($entry["message_tags"]);
            $target_users = array();
            if( ! empty($entry->entities->user_mentions) )
            {
                foreach($entry->entities->user_mentions as $data)
                    if($data->id != $config->twitter_account_id)
                        $target_users[] = $data;
                cli::write( "                    |  | Tagged:      .-------------------------------------------\n", cli::$forecolor_yellow );
                foreach($target_users as $this_target_user)
                {
                    cli::write( "                    |  |              | - [$this_target_user->id] @$this_target_user->screen_name\n", cli::$forecolor_yellow );
                    $recipients[] = $this_target_user;
                } # end foreach
                cli::write( "                    |  |              '-------------------------------------------\n", cli::$forecolor_yellow );
            } # end if
            # print_r($tagged_users); die();
        } # end if

        ##################
        # Recipient loop #
        ##################

        cli::write( "                    |  +----------------------------------------------------------\n", cli::$forecolor_light_green );
        cli::write( "                    |  | Going to send: $coins ".$config->current_coin_data["coin_name_plural"]."\n", cli::$forecolor_light_green );
        cli::write( "                    |  | From:          [$account->id_account] @$action->from_screen_name $account->name\n", cli::$forecolor_light_green );

        $returning_actions = array();
        foreach($recipients as $index => $recipient)
        {
            $continue = true;
            if( ! is_object($recipient) )
            {
                cli::write( "                    |  | To:            Recipient #".($index+1)." can't be determined. Ignored.\n", cli::$forecolor_light_red );
                $action->state = "ERROR";
                $action->info  = "INVALID_RECIPIENT";
                $continue = false;
            } # end if

            if( $continue )
            {
                $recipient_account->id_account  = "";
                $recipient_account->twitter_id  = $recipient->id;
                $recipient_account->screen_name = $recipient->screen_name;
                $recipient_account->name        = $recipient->name;

                $query   = "select * from {$config->db_tables["twitter"]} where twitter_id = '$recipient->id'";
                $res     = mysql_query($query);
                if( mysql_num_rows($res) > 0 )
                {
                    $row = mysql_fetch_object($res);
                    $recipient_account->id_account = $row->id_account;
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
                cli::write( "                    |  | To:            Recipient #".($index+1)." [{$recipient_account->id_account}] @{$recipient_account->screen_name} {$recipient_account->name}\n", $to_color );

                # Check if there's no double spending during the last time...
                /*
                {
                    $query = "
                        select * from ".$config->db_tables["log"] . " where
                        entry_type            = 'tweet'
                        and entry_id          = '".$entry_id."'
                        # and from_id_account   = '".$action->from_id_account."'
                        # and to_id_account     = '".$recipient_account->id_account."'
                        # and coin_name         = '".$action->coin_name."'
                        # and coins             = '".$coins."'
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
                */
            } # end if

            if( $continue )
            {
                $action->state = "OK";
                $action->to_twitter_id  = $recipient_account->twitter_id;
                $action->to_name        = $recipient_account->name;
                $action->to_screen_name = $recipient_account->screen_name;
                $action->to_id_account  = $recipient_account->id_account;
            } # end if

            $returning_actions[] = clone $action;
        } # end foreach
        cli::write( "                    |  '----------------------------------------------------------\n", cli::$forecolor_light_green );

        return $returning_actions;
    } # end function
