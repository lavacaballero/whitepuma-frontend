<?php
    /**
     * Sample Configuration file for the frontend - edit and save as config.php!
     *
     * @package    WhitePuma OpenSource Platform
     * @subpackage Frontend / base fileset
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

    class config
    {
        var $engine_enabled           = true;    # False to only allow withdrawals
        var $engine_disabled_message  = "";      # What to show when the engine is disabled
        var $engine_disabled_ui_theme = "vader"; # See themes at http://jqueryui.com/themeroller/
        var $engine_global_message    = "";      # What to show at the top of all pages. Use HTML as needed.
        var $facebook_login_enforced  = true;    # Set to false if Facebook isn't the primary login method

        var $app_version = "1.0";                # You should bump this as needed
        
        # Data for the Terms of Service template
        var $company_name     = "ACME, LLC";      # Put your name or company name here
        var $company_location = "Somewhere, USA"; # Put a generic location or a specific address here

        #================================#
        # Database settings - Customize! #
        #================================#

        # Set the next vars accordingly
        var $db_handler        = null; # Leave this one as null
        var $db_host           = "localhost";
        var $db_user           = "db_user";
        var $db_password       = "db_password";
        var $db_db             = "db_db";

        # Don't change these once you've installed the package
        var $cookie_encryption_key  = "some_random_string";
        var $session_handler_prefix = "some_prefix_to_set_";
        var $db_tables = array(

            # Base tables
            "account"                => "account",
            "account_extensions"     => "account_extensions",
            "account_wallets"        => "account_wallets",
            "flags"                  => "flags",
            "log"                    => "log",

            # Rains - set as empty to disable them
            "tip_batches"            => "tip_batches",
            "tip_batch_submissions"  => "tip_batch_submissions",

            # Ticker extension - set as empty to disable it
            "coin_prices"            => "coin_prices",

            # Pulse extension - set as empty to disable it
            "pulse_posts"            => "pulse_posts",
            "pulse_comments"         => "pulse_comments",
            "pulse_user_preferences" => "pulse_user_preferences",

            # Websites extension - set as empty to disable it
            "websites"               => "websites",
            "website_buttons"        => "website_buttons",
            "website_button_log"     => "website_button_log",

            # Twitter extension - set as empty to disable it
            "twitter"                => "twitter",

            # Instagram extension - set as empty to disable it
            "instagram_users"        => "instagram_users",
            "instagram_items"        => "instagram_items",

        );

        # Comma separated options: groups, logs
        var $admin_tab_functions_disabled = "";

        #====================================#
        # Facebook app settings - Customize! #
        #====================================#

        var $facebook_app_id            = "";
        var $facebook_app_secret        = "";
        var $facebook_canvas_page       = "https://apps.facebook.com/my_facebook_app/";
        var $facebook_canvas_image      = "https://www.domain.com/some_256x256px_image.png";
        var $facebook_app_page          = "https://www.facebook.com/my_app_page";
        var $facebook_app_page_id       = "";
        var $facebook_app_group         = "https://www.facebook.com/groups/my_group/";
        var $facebook_auth_scope        = "email";
        var $facebook_admin_scope       = "email,publish_actions";   # Only used by admin_authorize.php for yourself
        var $facebook_token_cache_mins  = 10;                        # Increase it to cache tokens for longer times
        var $commons_url                = "https://www.domain.com/"; # Path for common scripts - use an alternate path if needed

        # Complying with FB API v2.0 - Add apps from the oldest to the newest.
        # If you only have one app, then leave only one entry.
        var $business_mapped_app_ids = array(
            array("id" =>  "numeric_app_id", "name" => "My App",  "url" => "http://www.domain.com/"),
        );

        var $interapps_exchange_key = "some_random_string"; # Only to be set defined once
        var $tokens_encryption_key  = "some_random_string"; # Only to be set defined once

        # For navigation bar
        var $website_pages = array(
            
            # Point these properly
            "root_url"            => "https://www.domain.com/",
            "withdrawal_requests" => "https://www.domain.com/request_withdrawal.php",
            "toolbox"             => "https://www.domain.com/toolbox.php",
            
            # Review these (included to save you time) and point them properly
            "privacy_policy"      => "https://www.domain.com/privacy_policy.php",
            "terms_of_service"    => "https://www.domain.com/terms_of_service.php",
            
            # You must setup these
            "support"             => "https://www.domain.com/support-forum/",
            "faq"                 => "https://www.domain.com/faq.html",
            "about"               => "https://www.dpmain.com/about.html",
            
            # For Websites & buttons extension - set as empty strings if unused
            "buttons_widget_page"       => "http://www.domain.com/widget_info.html",
            "premium_account_info_page" => "http://www.domain.com/premium_accounts_info.html",

            # For Ticker extension - set as empty string if undefined
            "fees_and_coins_info"       => "http://www.domain.com/coins_info.html",
        );

        # Used only if custom account creation is used.
        # Must be blank if Facebook is not the primary login method.
        var $custom_account_creation_prefix = "uni_";

        # Array of admins and coadmins:
        # key   => account id as generated by the system
        # value => root | coadmin
        var $sysadmins = array(
            "account_id"       => "root",
            "other_account_id" => "coadmin",
        );

        #================================================#
        # Tipbot account specs - leave empty if unneeded #
        #================================================#

        var $tippingbot_id_acount       = ""; # Changed depending on the group being monitored
        var $tippingbot_fb_id_account   = ""; # Changed depending on the group being monitored
        var $string_for_botpool_tipping = ""; # keyphrase used by admins to give tips from tippingbot's pool, i.e. "summoning bot"

        # Please see at the end of this file
        var $facebook_monitor_objects = array();

        #==================================#
        # Platform provider (backend) info #
        #==================================#

        var $tipping_providers_database = array(
            "provider_keyname" => array(
                "keyname"                   => "provider_keyname", # The same as above
                "name"                      => "My App name",      # Internal reference
                "shortname"                 => "My App",           # Idem
                "public_key"                => "provider_keyname", # The key again
                "secret_key"                => "secret_key",       # As defined in the backend config file

                "per_coin_data"   => array(

                    # Sammple coin
                    "BitcoinBITS"  => array(
                        #=======================================================#
                        # IMPORTANT!!!! THIS IS TREATED IN BITS (100 SATOSHIS)! #
                        #=======================================================#
                        # CONVERSION IS DONE BY THE BACKEND PART!
                        "official_url"                        => "https://bitcoin.org",
                        # Availability
                        "exclude_from_global_balance"         => true,  # Used to hide instances managed by multipliers
                        "coin_disabled"                       => false, # True to only allow withdrawals
                        "coin_disabled_message"               => "",    # Message to show at the top of the coin dashboard if it is disabled
                        "coin_trailing_message"               => "",    # Message to show at the top of the coin dashboard all the time
                        # Appi stuff
                        "api_url"                             => "http://ip-or-hostname-of-backend-host/api/BitcoinBITS/",
                        # Reference data set on the backend and wallet endpoint config files
                        # Note: remember that this Bitcoin sample uses "bits" as base and not BTC!
                        "fees_account"                        => "_txfees",
                        "transaction_fee"                     =>       0, # You can set a number or a percent. Only used by the widget on the websites extension
                                                                          # Fees are deducted on the receiving side, so minimum tip must be above the minimum of the wallet
                        "min_transaction_amount"              =>       1, # satoshis (.00000001 BTC)
                        "system_transaction_fee"              =>     500, # satoshis (.0005 BTC) -
                        "withdraw_fee"                        =>       0, # This is applied by you, and is added to the network fee
                        "min_withdraw_amount"                 =>    3000, # satoshis (.003 BTC)
                        "timezone"                            => "America/Mexico_City", # http://en.wikipedia.org/wiki/List_of_tz_database_time_zones
                        # Customization stuff
                        "wallet_daemon_info"                  => "Typoe here wallet version and last update date",
                        "jquery_ui_theme"                     => "ui-lightness", # http://jqueryui.com/themeroller/
                        "jquery_ui_theme_for_alternate_login" => "ui-lightness", # http://jqueryui.com/themeroller/
                        "coin_sign"                           => "bits",         # This is the coin symbol
                        "coin_name"                           => "BitcoinBITS",  # Must be the same as the key above
                        "coin_name_singular"                  => "bit",
                        "coin_name_plural"                    => "bits",
                        "tip_rain_link_image"                 => "https://www.domain.com/some_470x246px_image.jpg",
                        "coin_image"                          => "https://www.domain.com/img/256x256_image.png",
                        "coin_fan_name_singular"              => "bitlover",  # Use your imagination here unless the coin community calls themselves something like "shibes" or "reddheads"
                        "coin_fan_name_plural"                => "bitlovers", # Idem
                        "body_font_definition"                => "body, td   { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; }", # Mandatory! You could use different fonts per coin
                        "ui_font_definition"                  => ".ui-widget { font-family: Arial, Helvetica, sans-serif; }",                  # Use the same font as above, not the size
                        "dashboard_tab_names"                 => "News,Incoming,Outgoing,Deposits,Withdrawals,All trans,Returns,OpsLog,Rains,Admin", # Don't remove tabs. Just rename them if needed.
                    ),

                    # Another sample coin
                    "BitcoinBTC"  => array(
                        "official_url"                        => "https://bitcoin.org",
                        # Availability
                        "exclude_from_global_balance"         => false, # Used to hide instances managed by multipliers
                        "coin_disabled"                       => false, # True to only allow withdrawals
                        "coin_disabled_message"               => "",    # Message to show at the top of the coin dashboard if it is disabled
                        "coin_trailing_message"               => "",    # Message to show at the top of the coin dashboard all the time
                        # Appi stuff
                        "api_url"                             => "http://ip-or-hostname-of-backend-host/api/BitcoinBITS/",
                        # Reference data set on the backend and wallet endpoint config files
                        # Note: remember that this Bitcoin sample uses "bits" as base and not BTC!
                        "fees_account"                        => "_txfees",
                        "transaction_fee"                     => 0.00000000, # You can set a number or a percent. Only used by the widget on the websites extension
                                                                             # Fees are deducted on the receiving side, so minimum tip must be above the minimum of the wallet
                        "min_transaction_amount"              => 0.00000100,
                        "system_transaction_fee"              => 0.00050000,
                        "withdraw_fee"                        => 0.00000000, # This is applied by you, and is added to the network fee
                        "min_withdraw_amount"                 => 0.00300000, # satoshis (.003 BTC)
                        "timezone"                            => "America/Mexico_City", # http://en.wikipedia.org/wiki/List_of_tz_database_time_zones
                        # Customization stuff
                        "wallet_daemon_info"                  => "Typoe here wallet version and last update date",
                        "jquery_ui_theme"                     => "ui-lightness", # http://jqueryui.com/themeroller/
                        "jquery_ui_theme_for_alternate_login" => "ui-lightness", # http://jqueryui.com/themeroller/
                        "coin_sign"                           => "bits",         # This is the coin symbol
                        "coin_name"                           => "BitcoinBITS",  # Must be the same as the key above
                        "coin_name_singular"                  => "bit",
                        "coin_name_plural"                    => "bits",
                        "tip_rain_link_image"                 => "https://www.domain.com/some_470x246px_image.jpg",
                        "coin_image"                          => "https://www.domain.com/img/256x256_image.png",
                        "coin_fan_name_singular"              => "bitlover",  # Use your imagination here unless the coin community calls themselves something like "shibes" or "reddheads"
                        "coin_fan_name_plural"                => "bitlovers", # Idem
                        "body_font_definition"                => "body, td   { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; }", # Mandatory! You could use different fonts per coin
                        "ui_font_definition"                  => ".ui-widget { font-family: Arial, Helvetica, sans-serif; }",                  # Use the same font as above, not the size
                        "dashboard_tab_names"                 => "News,Incoming,Outgoing,Deposits,Withdrawals,All trans,Returns,OpsLog,Rains,Admin", # Don't remove tabs. Just rename them if needed.
                    ),
                )
            )
        );

        # Defaults
        var $current_tipping_provider_keyname = "provider_keyname"; # The first from above
        var $current_coin_name                = "BitcoinBITS";      # Any you prefer from above

        # These are defined below
        var $current_tipping_provider_data    = array();
        var $current_coin_data                = array();

        #===========================#
        # Other customization stuff #
        #===========================#

        var $app_root_domain                         = "www.domain.com";
        var $cookie_domain                           = ".domain.com";
        var $tipping_return_days                     = 5;                       # How much days before missed transactions are sent?
        var $session_vars_prefix                     = "something1_";           # Increment this to force session info rebuilding
        var $cookie_session_identifier               = "something1_";           # Increment this to force users to re-login
        var $jquery_ui_theme_for_admin_impersonation = "dot-luv";               # http://jqueryui.com/themeroller/
        var $app_display_shortname                   = "My App";
        var $app_display_longname                    = "My amazing app";
        var $scripts_and_styles_version              = 1;                       # Increment if you touch anything in the "misc" directory
        var $cache_headers_ttl                       = 300;                     # Seconds to stay in the browser's cache
        var $provider_response_cache                 = 3;                       # Minutes to cache responses
        var $mail_sender_name                        = "My app notifications";
        var $mail_sender_address                     = "noreply@domain.com";
        var $mail_recipient_for_alerts               = "your@email.com";
        var $app_requests_message                    = "";                      # This is set below

        var $tip_rain_submissions_per_minute         = 50;

        #===========================================================================#
        # Vars for multiple coins (multi-wallet home + independent coin dashboards) #
        #===========================================================================#

        var $user_home_shows_by_default              = "multicoin_dashboard";   # options: coin_dashboard | multicoin_dashboard
        var $user_home_jquery_ui_theme               = "sunny";                 # http://jqueryui.com/themeroller/
        var $user_home_body_font_definition          = "body, td   { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; }"; # Similar to per-coin body_font_definition
        var $user_home_ui_font_definition            = ".ui-widget { font-family: Arial, Helvetica, sans-serif; }";                  # Similar to per-coin ui_font_definition

        var $contents_supported_coin_names_text      = "";
        var $contents_supported_coin_fan_names_text  = "crypto fan";
        var $contents_instructions_prepend_string    = ""; # You can set here any HTML to show on the instructions page, like some sidebar with news.
        var $contents_below_coin_switcher            = ""; # Set here a link to your coin addition requests form

        var $fb_like_button_link                     = "https://www.facebook.com/my_app_page";      # Usually your app page url
        var $twitter_tweet_button_link               = "https://www.domain.com/about.html";         # Usually the about page
        var $twitter_tweet_button_text               = ""; # Defined below

        var $favicon                                 = "https://www.domain.com/favicon.ico";        # You should provide this

        # Used for multicoins support
        var $multicoin_home_welcome                  = "Welcome aboard! Please wait for the addresses/balance info to get loaded before jumping to any coin dashboard. This will allow your account to receive tips/rain drops as soon as a new coin is released on the groups you've subscribed to.";

        # Set your publisher key here if used
        var $goggle_analytics_id                    = "";

        # Leave this unless you know what you're doing
        var $contents = array(
            "welcome"      => "contents/index.welcome.inc",
            "instructions" => "contents/index.instructions.inc",
        );

        #===========================================#
        # Chatango extension - see www.chatango.com #
        #===========================================#

        var $chatango_dimensions                    = "width:300px;height:500px;";
        var $chatango_handler                       = "_your_handler_here_"; # Leave empty to disable it
        var $chatango_trigger_pos                   = "br";
        var $chatango_background_color              = "3366FF";
        var $chatango_foreground_color              = "ffffff";

        #========#
        # Ticker #
        #========#

        # Used by the ticker index to dump the data (for usage in JSON requests)
        var $ticker_dump_passphrase = "somerandomphrasewithoutspaces";

        #============
        # Newcomers #
        #===========#

        # If false, newcomers page will be publicly visible
        var $newcomers_for_admins_only = true;

        #====================#
        # Websites & buttons #
        #====================#

        # Misc
        var $app_powered_by                          = "Powered by MyApp";
        var $app_powered_by_link                     = "https://www.domain.com";
        var $app_single_word_name                    = "MyApp";

        # Informative
        var $buttons_leeching_fee                   = "1%";
        var $default_buttons_website                = "domain.com";
        var $default_buttons_referer_url            = "https://www.domain.com";
        var $interactive_buttons_hover_title        = "Click here to send coins using domain.com!";
        var $buttons_zerocounters_markup            = "<i>Powered by domain.com</i>"; # Announce yourself here and below
        var $buttons_empty_table_markup             = "<i>Register at <a href='https://www.domain.com' target='_blank'>domain.com</a> and make your own button to receive tips and payments with %coins_amount% different cryptocurrencies!</i>";
        var $buttons_buttonizer_rootname            = "MyApp Widget";
        var $buttons_buttonizer_heading_caption     = "MyApp Widget";
        var $buttons_buttonizer_direct_caption      = "MyApp Widget (Stand-alone)";
        var $buttons_buttonizer_invocator_caption   = "Click here to send me some cryptos through MyApp!";
        var $buttons_about_widget_link              = "https://www.domain.com/about_myapp_widget.html";
        var $buttons_buttonizer_website_url         = "https://www.domain.com";
        var $buttons_aux_images_path                = "https://www.domain.com/"; # Leave it as it is unless you decide to use a CDN mirror
        var $buttons_enabled_websites_page          = "https://www.domain.com/enabled_websites_list.html"; # You should build this!

        # Root stuff
        var $buttons_default_selector               = ".wpuni_mcwidget"; # If you change this, you'll need to change styles ans javascript selectors
        var $buttons_default_selector_raw_classname = "wpuni_mcwidget";  # If you change this, you'll need to change styles ans javascript selectors
        var $buttons_api_location                   = "https://www.domain.com/extensions/websites/api/";
        var $buttons_buttonizer_invocator_url       = "https://domain.com/buttonizer"; # This one may not need to exist physically
        var $buttons_buttonizer_selector            = 'a[href*="https://domain.com/buttonizer?"],a[href*="https://www.domain.com/buttonizer?"]';
        var $buttons_buttonizer_iframe_src          = "https://www.domain.com/extensions/websites/widgets/universal/";
        var $buttons_analytics_root                 = "/analytics";
        var $buttons_analytics_absolute             = "https://www.domain.com/analytics";

        # For CDN
        var $buttons_leech_jar_image_url            = "https://www.domain.com/img/etc/PiggyBank-256.png";
        var $buttons_leech_jar_public_url           = "https://www.domain.com/img/etc/PiggyBank-256.png";
        var $buttons_default_website_logo           = "https://www.domain.com/img/etc/website_logo.png";
        var $buttons_default_custom_logo            = "https://www.domain.com/img/etc/custom_logo.png";
        var $buttons_buttonizer_iframe_loader       = "https://www.domain.com/img/progress_16x16_gray.gif";
        var $buttons_buttonizer_website_logo        = "https://www.domain.com/img/some_64x64px_image.png";
        var $buttons_libpath                        = "https://www.domain.com/lib/";
        var $buttons_universal_absolute_css_url     = "https://www.domain.com/extensions/websites/widgets/universal/button.css";

        # For supporting buttons in user signatures over the pulse, you need to
        # define your own website name and type the name here:
        var $buttons_self_website_name_for_pulse    = "domain.com";

        #===================#
        # Twitter extension #
        #===================#

        var $twitter_account_prefix                 = "tw_";
        var $twitter_consumer_key                   = "some_string";
        var $twitter_consumer_secret                = "some_string";
        var $twitter_account_id                     = "some_number";
        var $twitter_screen_name                    = "mytipbot";
        var $twitter_access_token                   = "some_string";
        var $twitter_token_secret                   = "some_string";
        var $twitter_invitation_template            = "Greetings, @recipient! @sender has tipped you. Please visit domain.com/about_twitter.html within 5 days to claim it and learn about me.";
                                                    # Note: the line above has 5 days limit... The number here must reflect $config->tipping_return_days
        var $twitter_welcome_message                = "I've been added to your friends so you can use me. Instructions: domain.com/extensions/twitter/ • Feel free to mute me unless you want to know who's new :)";
        var $twitter_about_page                     = "https://www.domain.com/about_twitter.html";
        var $twitter_instructions_page              = "https://www.domain.com/extensions/twitter/";
        var $twitter_news_account_link              = "<a href='https://twitter.com/news_account_handler' target='_blank'>@news_account_handler</a>";

        #=====================#
        # Instagram extension #
        #=====================#

        var $instagram_account_prefix     = "ig_";
        var $instagram_about_page         = "https://www.domain.com/about_instagram.html";
        var $instagram_monitor_heartbeat  = 5; # Minutes the cron job is set
        var $instagram_read_items_limit   = 0; # Is set ahead
        var $instagram_min_hours_per_item = 0; # Is set ahead
        var $instagram_item_lifespan      = 7; # Days
        var $instagram_client_info = array(
            "client_id"     => "some_string",
            "client_secret" => "some_string",
            "access_token"  => "some_string",
        );
        var $instagram_api_urls = array(
            "clientside_authorization"=> "https://api.instagram.com/oauth/authorize/",   # As defined by Instagram
            "access_token_grabbing"   => "https://api.instagram.com/oauth/access_token", # As defined by Instagram
        );
        var $instagram_subscriptions_data = array(
            "hashtag"               => "myhashtag", # The hashtag to monitor
            "results_per_update"    => 1000,        # Do not set more than 1k or the app will be banned
            "maker"                 => "https://api.instagram.com/v1/subscriptions/",
            "listener"              => "http://www.domain.com/extensions/instagram/subscriptions_listener.php",
            "tags_getter_url"       => "https://api.instagram.com/v1/tags/myhashtag/media/recent", # hashtag here!
            "comments_getter_url"   => 'https://api.instagram.com/v1/media/{$media_id}/comments',  # watch out with the {$media_id}
        );

    } # end class

    $config = new config();

    session_start();

    ###############################################################
    # Default backend provider - set the first from the config list
    #################################################################################
    if( ! isset($_SESSION[$config->session_vars_prefix."current_tipping_provider"]) )
        $_SESSION[$config->session_vars_prefix."current_tipping_provider"] = "provider_keyname";
    ############################################################################################

    ##################################################################################
    # Default coin - set your preference or leave as _none_ if multiple coins are used
    ##################################################################################
    if( empty($_SESSION[$config->session_vars_prefix."current_coin_name"]) )
        $_SESSION[$config->session_vars_prefix."current_coin_name"] = "_none_";
    ###########################################################################

    # Leave these untouched
    $config->current_tipping_provider_keyname = $_SESSION[$config->session_vars_prefix."current_tipping_provider"];
    $config->current_coin_name                = $_SESSION[$config->session_vars_prefix."current_coin_name"];
    $config->current_tipping_provider_data    = $config->tipping_providers_database[$config->current_tipping_provider_keyname];
    $config->current_coin_data                = $config->current_tipping_provider_data["per_coin_data"][$config->current_coin_name];

    # Description updates - Modify as needed
    $coins_count = count($config->current_tipping_provider_data["per_coin_data"]);
    $config->contents_supported_coin_names_text = "up to {$coins_count} different coins (including Bitcoin)";
    $config->app_requests_message               = "I'm using this app to send/receive tips in cryptocurrencies through Facebook! Join and get/give coins to/from your friends and hundred of users!";
    $config->twitter_tweet_button_text          = "Join {$config->app_display_longname} and tip/get tipped on Facebook with "
                                                . (
                                                    count($config->current_tipping_provider_data["per_coin_data"]) == 1
                                                    ? $config->current_coin_name
                                                    : count($config->current_tipping_provider_data["per_coin_data"]) . " cryptocurrencies"
                                                  );

    # FB groups data loading
    $file = __DIR__ . "/groups.dat";
    $groups_data = file($file);
    foreach($groups_data as $line)
    {
        if(trim($line) == "") continue;
        list($since, $key, $name, $type, $edge, $url, $id, $tippingbot_id_acount, $tippingbot_fb_id_account) = explode("\t", trim($line));
        $config->facebook_monitor_objects[$key] = array(
            "since"                     => trim($since),
            "key"                       => trim($key),
            "name"                      => trim($name),
            "type"                      => trim($type),
            "edge"                      => trim($edge),
            "url"                       => trim($url),
            "id"                        => trim($id),
            "tippingbot_id_acount"      => trim($tippingbot_id_acount),
            "tippingbot_fb_id_account"  => trim($tippingbot_fb_id_account),
        );
    } # end foreach
