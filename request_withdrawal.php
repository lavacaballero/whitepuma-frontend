<?
    /**
     * Withdrawal request script
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
     *
     * @param           number $amount    Used on first call to send an email with $auth_key to the account
     * @param           string $address   Used on first call to send an email with $auth_key to the account
     * @param           number $coin_name Used on first call to send an email with $auth_key to the account
     * @param encrypted string $auth_key  "$amount,$address,$linit,$coin_name" to proceed with withdraw
     */

    $root_url = ".";
    if( ! is_file("config.php") ) die("ERROR: config file not found.");
    include "config.php";
    include "functions.php";
    include "models/tipping_provider.php";
    include "models/account.php";

    include "facebook-php-sdk/facebook.php";
    $fb_params = array(
        'appId'              => $config->facebook_app_id,
        'secret'             => $config->facebook_app_secret,
        'fileUpload'         => false,
        'allowSignedRequest' => true
    );
    $facebook = new Facebook($fb_params);
    # $access_token = $facebook->getAccessToken();
    # $facebook->setExtendedAccessToken();

    if( $config->facebook_login_enforced )
    {
        $user_id = get_online_user_id(true);
    }
    else
    {
        $cookie_name         = $config->session_vars_prefix . $config->cookie_session_identifier;
        $session_from_cookie = false;
        if( ! empty($_COOKIE[$cookie_name]) )
        {
            $user_id = decryptRJ256($config->tokens_encryption_key, $_COOKIE[$cookie_name]);
        } # end if
    } # end if

    header("Content-Type: text/plain; charset=utf-8");
    if( empty($user_id) )
        die("Can't get your user id. Please try again or re-login.");

    $account = new account($user_id);
    if( ! $account->exists )
        die("Your user account data is not present in the database. Please try again or reauthorize the app.");

    ############################################
    # Sending authorization email -- dialog call
    ############################################
    if( trim($_REQUEST["auth_key"]) == "" )
    #######################################
    {
        # Prechecks
        if( trim($_REQUEST["coin_name"]) == "")
            die("Coin hasn't been specified. Please re-submit your withdrawal request.");
        if( trim($_REQUEST["amount"]) == "" || trim($_REQUEST["address"]) == "" )
            die("Please specify a valid amount and withdrawal address.");
        if( ! (is_numeric($_REQUEST["amount"]) || $_REQUEST["amount"] == "all") )
            die("Please specify a valid amount to transfer.");
        if( ! is_wallet_address($_REQUEST["address"]) )
            die("Please specify a valid wallet address.");
        if( get_tipping_provider_keyname_by_coin($_REQUEST["coin_name"]) == "" )
            die("You've specified an non-supported coin.");

        # Presets
        $config->current_coin_name                = trim($_REQUEST["coin_name"]);
        $config->current_tipping_provider_keyname = get_tipping_provider_keyname_by_coin($config->current_coin_name);
        $config->current_tipping_provider_data    = $config->tipping_providers_database[$config->current_tipping_provider_keyname];
        $config->current_coin_data                = $config->current_tipping_provider_data["per_coin_data"][$config->current_coin_name];
        $min_withdraw   = $config->current_coin_data["min_withdraw_amount"];
        $network_tx_fee = $config->current_coin_data["system_transaction_fee"];
        $withdraw_fee   = $config->current_coin_data["withdraw_fee"];
        $account        = new account($user_id);
        $encryption_key = $account->date_created;

        $limit    = date("Y-m-d H:i:s", strtotime("now + 30 minutes"));
        $amount   = trim($_REQUEST["amount"]);
        $address  = trim($_REQUEST["address"]);

        $balance = $account->get_balance();
        # if( $config->current_coin_data["coin_disabled"] )
        #    die("The coin support is currently disabled. Please check your dashboard and the news page to get more details about it.");
        if( empty($balance) )
            die("Can't get your updated balance! Please reload the page and try again.");
        if( $amount == "all" && $balance < $min_withdraw )
            die("Your balance is below the minimum withdraw amount. You can't withdraw it at this moment.");
        if( is_numeric($amount) && $amount < $min_withdraw )
            die("You can't withdraw less than $min_withdraw ".$config->current_coin_data["coin_name_plural"].".");
        if( is_numeric($amount) && $amount > $balance )
            die("You can't withdraw more than your balance.");

        if( $amount == "all" ) $amount = $balance;

        $auth_key     = encryptRJ256($encryption_key, "$amount,$address,$limit,$config->current_coin_name");
        $url          = $config->website_pages["withdrawal_requests"] . "?auth_key=" . urlencode($auth_key);

        $ip           = $_SERVER['REMOTE_ADDR'];
        $hostname     = gethostbyaddr($_SERVER['REMOTE_ADDR']);
        $fecha_envio  = date("Y-m-d H:i:s");
        $mail_from    = "$config->mail_sender_name<$config->mail_sender_address>";
        $mail_to      = ( empty($account->email) ? "$account->name alternate email<$account->alternate_email>" : "$account->name<$account->email>");

        $mail_subject = $config->app_display_shortname . " - Fund withdrawal request";
        $mail_body = "A ".$config->current_coin_data["coin_name"]." withdrawal has been issued from your account.\r\n"
                   . "\r\n"
                   . "Amount issued:     $amount ".$config->current_coin_data["coin_name_plural"]."\r\n"
                   . "Recipient address: $address\r\n"
                   . "Current balance:   $balance ".$config->current_coin_data["coin_name_plural"]."\r\n"
                   . "\r\n"
                   . "If you've placed this request, please follow the next link\r\n"
                   . "or copy/paste it in the same browser where you have your session opened:\r\n"
                   . "$url\r\n"
                   . "\r\n"
                   . "Note: the link will expire in 30 mins. After that, you'll need\r\n"
                   . "to issue another transfer.\r\n"
                   . "\r\n"
                   . "If you didn't place this request, please take proper measures with\r\n"
                   . "your Facebook account: change the password, change the email, etc.\r\n"
                   . "and disregard this email.\r\n"
                   . "\r\n"
                   . "Feel free to contact us if you have any doubt by following the next link:\r\n"
                   . $config->website_pages["support"] . "\r\n"
                   . "\r\n"
                   . "Regards,\r\n"
                   . $config->app_display_longname . "'s Team\r\n"
                   . "\r\n"
                   . "-----------------------------\r\n"
                   . "Date/Time:   $fecha_envio\r\n"
                   . "IP:          $ip\r\n"
                   . "Host:        $hostname\r\n"
                   . "Browser:     ".$_SERVER["HTTP_USER_AGENT"]."\r\n"
                   ;
        @mail(
            $mail_to, $mail_subject, $mail_body,
            "From: ".$mail_from . "\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-Type: text/plain; charset=utf-8\r\n" .
            ( empty($account->email) ? "" : (empty($account->alternate_email) ? "" : "Cc: $account->name alternate email<$account->alternate_email>\r\n") )
        );
        die("OK");

    } # end if

    #############################
    # Receiving authorization key
    #############################

    $account        = new account($user_id);
    $encryption_key = $account->date_created;

    $title = $message = $message_class = "";
    $continue = true;
    $decrypted_auth_key = decryptRJ256($encryption_key, trim($_REQUEST["auth_key"]));
    list($amount, $address, $limit, $coin_name) = explode(",", $decrypted_auth_key);
    if( empty($limit) )
    {
        $message_class  = "ui-state-error";
        $title          = "Invalid authorization key";
        $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                        . "The authorization key you've provided is invalid. It may be too old or you're calling this page from a browser window without an open dashboard session.<br>\n"
                        . "Please issue another transfer. If the problem persists, please contact us ASAP by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                        ;
        $continue = false;
    } # end if
    if( $continue && preg_match('/[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/', $limit) == 0 )
    {
        $message_class  = "ui-state-error";
        $title          = "Invalid authorization key";
        $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                        . "The authorization key you've provided is invalid.<br>\n"
                        . "Please issue another transfer. If the problem persists, please contact us ASAP by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                        ;
        $continue = false;
    } # end if
    if( $continue && date("Y-m-d H:i:s") > $limit )
    {
        $message_class  = "ui-state-error";
        $title          = "Authorization key expired";
        $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                        . "The authorization key you've provided alredy expired. Authorization keys have a life of 30 minutes.<br>\n"
                        . "Please issue another transfer. If you need assistance, please contact us ASAP by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                        ;
        $continue = false;
    } # end if
    if( $continue && ! is_numeric($amount) )
    {
        $message_class  = "ui-state-error";
        $title          = "Invalid amount specified";
        $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                        . "The amount of coins to transfer extracted from the authorization key you provided is invalid.<br>\n"
                        . "Please try again. If the problem persists, please contact us ASAP by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                        ;
        $continue = false;
    } # end if
    if( $continue && ! is_wallet_address($address) )
    {
        $message_class  = "ui-state-error";
        $title          = "Invalid address specified";
        $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                        . "The wallet address extracted from the authorization key you provided is invalid.<br>\n"
                        . "Please try again. If the problem persists, please contact us ASAP by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                        ;
        $continue = false;
    } # end if
    if( $continue && empty($coin_name) )
    {
        $message_class  = "ui-state-error";
        $title          = "Coin not specified";
        $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                        . "Your token doesn't include the coin from which you're going to withdraw.<br>\n"
                        . "Please resubmit your withdrawal request. If the problem persists, please contact us ASAP by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                        ;
        $continue = false;
    } # end if
    if( $continue && get_tipping_provider_keyname_by_coin($coin_name) == "" )
    {
        $message_class  = "ui-state-error";
        $title          = "Coin not specified";
        $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                        . "You're requiring a withdrawal request for a non-supported coin.<br>\n"
                        . "Please resubmit your withdrawal request. If the problem persists, please contact us ASAP by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                        ;
        $continue = false;
    } # end if

    if( $continue )
    {
        $config->current_coin_name                = trim($coin_name);
        $config->current_tipping_provider_keyname = get_tipping_provider_keyname_by_coin($config->current_coin_name);
        $config->current_tipping_provider_data    = $config->tipping_providers_database[$config->current_tipping_provider_keyname];
        $config->current_coin_data                = $config->current_tipping_provider_data["per_coin_data"][$config->current_coin_name];
        $min_withdraw   = $config->current_coin_data["min_withdraw_amount"];
        $network_tx_fee = $config->current_coin_data["system_transaction_fee"];
        $withdraw_fee   = $config->current_coin_data["withdraw_fee"];
        $account        = new account($user_id);

        /*
        if( $config->current_coin_data["coin_disabled"] )
        {
            $message_class  = "ui-state-error";
            $title          = "Coin support disabled!";
            $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                            . $config->current_coin_data["coin_disabled_message"]."<br>\n"
                            . "Please check back on your dashboard for further information or check <a href='".$config->facebook_app_page."'>The app news page</a>."
                            ;
        } # end if
        */

        $amount = $amount - $withdraw_fee - $network_tx_fee;
    } # end if

    if( $continue )
    {
        $balance = $account->get_balance();
        if( ! is_numeric($balance) )
        {
            $message_class  = "ui-state-error";
            $title          = "Can't get your balance!";
            $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                            . "It seems to be a problem to get your balance. Please reload this page.<br>\n"
                            . "If the problem persists, please contact us ASAP by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
            $continue = false;
        } # end if
    } # end if

    if( $continue && $balance < $amount )
    {
        $message_class  = "ui-state-error";
        $title          = "You have not enough funds!";
        $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                        . "Your current balance is $balance ".$config->current_coin_data["coin_name_plural"].". You can't withdraw more than that.<br>\n"
                        . "Please try again. If the problem persists, please contact us ASAP by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                        ;
        $continue = false;
    } # end if

    # if($balance == $amount) $amount = $balance - 1;

    if( $continue )
    {
        $tipping_provider      = new tipping_provider( $config->current_coin_data["api_url"]    ,
                                                       $config->current_tipping_provider_data["public_key"] ,
                                                       $config->current_tipping_provider_data["secret_key"] );
        $tipping_provider->cache_api_responses_time = 0;
        $res = $tipping_provider->withdraw($account->id_account, $address, $amount);
        if( stristr($res->message, "error") !== false )
        {
            $message_class  = "ui-state-error";
            $title          = "Couldn't transfer the funds!";
            $message        = "<span class='ui-icon embedded ui-icon-alert'></span>"
                            . "The next debug data has been returned by the platform provider:\n"
                            . "<code>".nl2br(print_r($res, true))."</code>\n"
                            . "Please try again. If the problem persists, please contact us ASAP by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a>."
                            ;
        }
        else
        {
            $message_class  = "ui-state-highlight";
            $title          = "Funds transferred!";
            $tx_fees_part   = ($network_tx_fee + $withdraw_fee) == 0 ? ""
                            : "<br>The actual amount may vary in ".($network_tx_fee + $withdraw_fee)." ".$config->current_coin_data["coin_name_plural"]." due to transaction fees.";
            $message        = "<span class='ui-icon embedded ui-icon-info'></span>"
                            . "<b>$amount ".$config->current_coin_data["coin_name_plural"]."</b> have been queued for transfer from your account to the next address: <b>$address</b>. $tx_fees_part<br>"
                            . "Please give the daemon a few mins to broadcast the transaction over the network.<br>"
                            . "You should have your wallet client opened and check for the confirmations from the network, which will begin to flow shortly.<br>"
                            . "You can also check your $config->app_diplay_shortname's dashboard and see the entry in the transaction log.<br>"
                            . "<b>Warning! do not reload this page!</b> If you do, a new withdrawal request will be triggered, and if you have enough balance, the transaction will be reprocessed.<br>"
                            . "Feel free to contact us by visiting our <a href='".$config->website_pages["support"]."'>Help &amp; Support page</a> if you experience any long delay on your transaction."
                            ;
        } # end if
    } # end if

    header("Content-Type: text/html; charset=utf-8");
    header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
    header('Pragma: no-cache'); // HTTP 1.0.
    header('Expires: 0'); // Proxies.
?>
<html>
    <head>
        <title><?=$config->app_display_longname?> - <?=$title?></title>
        <meta name="viewport"                   content="width=device-width" />
        <meta http-equiv="X-UA-Compatible"      content="IE=Edge" />
        <link rel="icon"                        href="<?= $config->favicon ?>">
        <link rel="shortcut icon"               href="<?= $config->favicon ?>">
        <link rel="stylesheet" type="text/css"  href="//code.jquery.com/ui/1.10.4/themes/<?=$config->current_coin_data["jquery_ui_theme"]?>/jquery-ui.css">
        <link rel="stylesheet" type="text/css"  href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
        <link rel="stylesheet" type="text/css"  href="misc/styles.css?v=<?=$config->scripts_and_styles_version?>">
        <style type="text/css">
            <?= $config->current_coin_data["body_font_definition"] ?>
            <?= $config->current_coin_data["ui_font_definition"] ?>
            .coin_signed:after { content: ' <?=$config->current_coin_data["coin_name_plural"]?>'; }
        </style>
    </head>
    <body>
        <h1><?=$title?></h1>
        <div class="message_box <?=$message_class?> ui-corner-all"><?= $message ?></div>
    </body>
</html>
