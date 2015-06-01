<?php
    #######################################################################
    function process_comments_entry($entry, $edge = "", $skip_user_id = "")
    #######################################################################
    {
        global $config, $facebook;
        
        $action          = new action();
        $from            = (object) $entry["from"];
        $msg             = $entry["message"];
        $entry_type      = "comment"; # $entry["type"];
        $recipient       = null;
        $entry_id        = $entry["id"];
        
        list($action_type, $coins) = check_action_type_from_message($msg);
        if( $config->current_coin_data["coin_disabled"] ) $action_type = "coin_disabled";
        
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
        
        $account = new account($from->id);
        if( ! $account->exists )
        {
            cli::write( "                    |  .----------------------------------------------------------\n", cli::$forecolor_light_red );
            cli::write( "                    |  | Message: ".substr(str_replace("\n", "", $msg), 0, 100)."...\n" );
            cli::write( "                    |  | From:        [$from->id] $from->name\n" );
            cli::write( "                    |  | The source account doesn't exist in DB. Entry ignored.\n", cli::$forecolor_light_red );
            cli::write( "                    |  '----------------------------------------------------------\n", cli::$forecolor_light_red );
            $action->state = "ERROR";
            $action->info  = "SENDER_NOT_REGISTERED";
            return $action;
        }
        
        $action->from_facebook_id = $from->id;
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
            # print_r($entry);
            $target_users = array();
            if( ! empty($entry["message_tags"]) )
            {
                foreach($entry["message_tags"] as $data)
                    if( empty($skip_user_id) || ( ! empty($skip_user_id) && $data["id"] != $skip_user_id) )
                        $target_users[] = (object) $data;
                cli::write( "                    |  | Tagged:      .-------------------------------------------\n", cli::$forecolor_yellow );
                foreach($target_users as $this_target_user)
                {
                    cli::write( "                    |  |              | - [$this_target_user->id] $this_target_user->name\n", cli::$forecolor_yellow );
                    $recipients[] = $this_target_user;
                } # end foreach
                cli::write( "                    |  |              '-------------------------------------------\n", cli::$forecolor_yellow );
            } # end if
            # print_r($tagged_users); die();
        }
        #========================================
        elseif( $action_type = "give_to_author" )
        #========================================
        {
            # Let's try to get the object id
            # print_r($entry);
            if( empty($entry["object_id"]) )
            {
                cli::write( "                    |  +----------------------------------------------------------\n", cli::$forecolor_light_red );
                cli::write( "                    |  | Couldn't find referenced object id. Ignoring entry.\n", cli::$forecolor_light_red );
                cli::write( "                    |  '----------------------------------------------------------\n", cli::$forecolor_light_red );
                $action->state = "ERROR";
                $action->info  = "REFERENCED_OBJECT_UNREACHABLE:ID";
                return $action;
            }
            $object_id = $entry["object_id"];
            cli::write( "                    |  +----------------------------------------------------------\n", cli::$forecolor_brown );
            cli::write( "                    |  | Object id found: $object_id\n", cli::$forecolor_brown );
            $res = $facebook->api("/$object_id");
            if( empty($res) )
            {
                cli::write( "                    |  +----------------------------------------------------------\n", cli::$forecolor_light_red );
                cli::write( "                    |  | Couldn't fetch object. Ignoring entry.\n", cli::$forecolor_light_red );
                cli::write( "                    |  '----------------------------------------------------------\n", cli::$forecolor_light_red );
                $action->state = "ERROR";
                $action->info  = "REFERENCED_OBJECT_UNREACHABLE:OBJECT_ITSELF";
                return $action;
                return;
            } # end if
            unset($res["images"], $res["tags"], $res["likes"]);
            if( empty($res["from"]) )
            {
                cli::write( "                    |  +----------------------------------------------------------\n", cli::$forecolor_light_red );
                cli::write( "                    |  | Referenced object doesn't have an author. Ignoring entry.\n", cli::$forecolor_light_red );
                cli::write( "                    |  '----------------------------------------------------------\n", cli::$forecolor_light_red );
                $action->state = "ERROR";
                $action->info  = "REFERENCED_OBJECT_UNREACHABLE:AUTHOR";
                return $action;
            } # end if
            
            $recipients[] = (object) $res["from"];
            cli::write( "                    |  | Author found: [".$res["from"]["id"]."] ".$res["from"]["name"]."\n", cli::$forecolor_brown );
        } # end if
        
        ##################
        # Recipient loop #
        ##################
        
        cli::write( "                    |  +----------------------------------------------------------\n", cli::$forecolor_light_green );
        cli::write( "                    |  | Going to send: $coins ".$config->current_coin_data["coin_name_plural"]."\n", cli::$forecolor_light_green );
        cli::write( "                    |  | From:          [$account->id_account] $account->name\n", cli::$forecolor_light_green );
        
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
            
            if($account->facebook_id == $recipient->id)
            {
                cli::write( "                    |  | To:            Recipient #".($index+1)." is the same as the tipper. Ignored.\n", cli::$forecolor_light_red );
                $action->state = "IGNORE";
                $action->info  = "SELF_TIPPING_NOT_ALLOWED";
                $continue = false;
            } # end if
            
            if( $continue )
            {
                $recipient_account = new account($recipient->id);
                $to_color = cli::$forecolor_light_green;
                if( ! $recipient_account->exists )
                {
                    $to_color = cli::$forecolor_light_blue;
                    $recipient_account->facebook_id = $recipient->id;
                    $recipient_account->name        = $recipient->name;
                } # end if
                
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
?>
