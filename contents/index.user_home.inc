<?php
    load_extensions("index_user_home_pre_contents", $root_url);
    
    if( $_REQUEST["show"] == "multicoin_dashboard" ||
        (
            $_SESSION[$config->session_vars_prefix."current_coin_name"] == "_none_"
            && empty($_REQUEST["switch_coin"])
        ) ||
        (
            $config->user_home_shows_by_default == "multicoin_dashboard"
            && $_REQUEST["coming_from"] == "sender_tipping_notification"
        )
    )
        include dirname(__FILE__)."/index.user_home.multicoin_dashboard.inc";
    else
        include dirname(__FILE__)."/index.user_home.coin_dashboard.inc";
