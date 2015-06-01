<?php
    ############
    class action
    ############
    {
        var $message;
        var $entry_type;
        var $entry_id;
        var $action_type;
        var $state;
        var $info;
        var $coin_name;
        var $coins;
        var $from_facebook_id,      $from_name,      $from_id_account;
        var $notify_to_facebook_id, $notify_to_name, $notify_to_id_account, $notify_to_is_bot_switch;
        var $to_facebook_id,        $to_name,        $to_id_account;
    } # end class
    
    ########################################
    function extract_parameters($ARGUMENTOS)
    ########################################
    {
        $parametros = Array(); $k=0;
        if( ! is_array($ARGUMENTOS) ) return $parametros;
        while (list ($key, $val) = each ($ARGUMENTOS)) {
            if($k > 0){
                // recibimos en forma n=valor
                $ps = explode("=",$val);
                if(!isset($ps[1])) $ps[1]=1;
                $parametros[$ps[0]]=$ps[1];
             } // end if
             $k++;
        } // end while
        return $parametros;
    } // end function
    
    ######################################
    function output_help($message, $color)
    ######################################
    {
        if( ! empty($message) )
        {
            cli::write("\n");
            cli::write($message, $color);
        } # end if
        
        cli::write("\n");
        cli::write("Usage:\n");
        cli::write("\n");
        cli::write("# php -q cli_feed_monitor.php --object=", cli::$forecolor_white);
        cli::write("<object_key>", cli::$forecolor_yellow);
        # cli::write(" [--alt=<alt_entity_keyname>]");
        cli::write(" [-h|--help]");
        # cli::write(" [--what=<facebook_table>]");
        cli::write(" [--raw]");
        cli::write(" [--die-after-raw]");
        cli::write(" [--no-actions-loop]");
        cli::write(" [--no-comments-loop]");
        cli::write(" [--no-date-limit]");
        cli::write(" [--no-flags-update]");
        cli::write("\n");
        cli::write("Where <feed_key> is one of the specified in config's \$facebook_monitor_feeds variable. \n");
        cli::write("\n");
    } # end function
    
    #############################################
    function check_action_type_from_message($msg)
    #############################################
    {
        global $config;
        
        foreach($config->tipping_providers_database as $tipping_provider_keyname => $tipping_provider_data)
        {
            foreach($tipping_provider_data["per_coin_data"] as $coin_name => $coin_data)
            {
                # Inits
                $config->current_tipping_provider_keyname = $tipping_provider_keyname;
                $config->current_coin_name                = $coin_name;
                $config->current_tipping_provider_data    = $tipping_provider_data;
                $config->current_coin_data                = $coin_data;
                
                # Presets
                $coin_names = $config->current_coin_data["coin_name_singular"]."|".$config->current_coin_data["coin_name_plural"];
                $coin_sign  = $config->current_coin_data["coin_sign"];
    
                #---------
                # Commands
                #---------
                
                # give <amount> <coin_name> to author
                $pattern = '/give\s+([0-9\.]{1,30})\s+('.$coin_names.')\s+to\s+author/i';
                preg_match($pattern, $msg, $matches);
                if( count($matches) ) return array("give_to_author", $matches[1]);
                
                # give <amount> <coin_name> to <user>
                $pattern = '/give\s+([0-9\.]{1,30})\s+('.$coin_names.')\s+to\s+(.*)/i';
                preg_match($pattern, $msg, $matches);
                if( count($matches) ) return array("give_to_tagged", $matches[1]);
                
                # give <amount> <coin_name>
                $pattern = '/give\s+([0-9\.]{1,30})\s+('.$coin_names.')/i';
                preg_match($pattern, $msg, $matches);
                if( count($matches) ) return array("give_to_author", $matches[1]);
                
                #---------------------
                # Using alternate verb
                #---------------------
                
                # tip <amount> <coin_name> to author
                $pattern = '/tip\s+([0-9\.]{1,30})\s+('.$coin_names.')\s+to\s+author/i';
                preg_match($pattern, $msg, $matches);
                if( count($matches) ) return array("give_to_author", $matches[1]);
                
                # tip <amount> <coin_name> to <user>
                $pattern = '/tip\s+([0-9\.]{1,30})\s+('.$coin_names.')\s+to\s+(.*)/i';
                preg_match($pattern, $msg, $matches);
                if( count($matches) ) return array("give_to_tagged", $matches[1]);
                
                # tip <amount> <coin_name>
                $pattern = '/tip\s+([0-9\.]{1,30})\s+('.$coin_names.')/i';
                preg_match($pattern, $msg, $matches);
                if( count($matches) ) return array("give_to_author", $matches[1]);
                
                #----------------
                # Using coin sign
                #----------------
                
                # give <coin_sign> <amount> to author
                $pattern = '/give\s+('.$coin_sign.')\s*([0-9\.]{1,30})\s+to\s+author/i';
                preg_match($pattern, $msg, $matches);
                if( count($matches) ) return array("give_to_author", $matches[2]);
                
                # give <coin_sign> <amount> to <user>
                $pattern = '/give\s+('.$coin_sign.')\s*([0-9\.]{1,30})\s+to\s+(.*)/i';
                preg_match($pattern, $msg, $matches);
                if( count($matches) ) return array("give_to_tagged", $matches[2]);
                
                # give <coin_sign> <amount>
                $pattern = '/give\s+('.$coin_sign.')\s*([0-9\.]{1,30})/i';
                preg_match($pattern, $msg, $matches);
                if( count($matches) ) return array("give_to_author", $matches[2]);
                
                #-----------------------------------
                # Using coin sign and alternate verb
                #-----------------------------------
                
                # tip <coin_sign> <amount> to author
                $pattern = '/tip\s+('.$coin_sign.')\s*([0-9\.]{1,30})\s+to\s+author/i';
                preg_match($pattern, $msg, $matches);
                if( count($matches) ) return array("give_to_author", $matches[2]);
                
                # give <coin_sign> <amount> to <user>
                $pattern = '/tip\s+('.$coin_sign.')\s*([0-9\.]{1,30})\s+to\s+(.*)/i';
                preg_match($pattern, $msg, $matches);
                if( count($matches) ) return array("give_to_tagged", $matches[2]);
                
                # give <coin_sign> <amount>
                $pattern = '/tip\s+('.$coin_sign.')\s*([0-9\.]{1,30})/i';
                preg_match($pattern, $msg, $matches);
                if( count($matches) ) return array("give_to_author", $matches[2]);
                
                #-----------------
                # Other variations
                #-----------------
                
                # tip <user> with <amount> <coin_name>
                $pattern = '/tip\s+(.*)\s+with\s+([0-9\.]{1,30})\s+('.$coin_names.')/i';
                preg_match($pattern, $msg, $matches);
                if( count($matches) ) return array("give_to_tagged", $matches[2]);
                
            } # end foreach $tipping_provider_data["per_coin_data"]
        } # end foreach $config->tipping_providers_database
        
        return array("not_found", 0);
    } # end function
?>