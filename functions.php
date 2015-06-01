<?php
    /**
     * Common functions from different sources
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

    /**
     * RIJNDAEL_256 decrypter
     *
     * @requires mcrypt package
     * @param string $key
     * @param string $string_to_decrypt
     *
     * @returns mixed
     */
    function decryptRJ256($key, $string_to_decrypt)
    {
        $string_to_decrypt = base64_decode($string_to_decrypt);
        $md5_key = md5($key);
        $iv      = md5($md5_key);
        $rtn     = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $md5_key, $string_to_decrypt, MCRYPT_MODE_CBC, $iv);
        $rtn     = rtrim($rtn, "\0\4");
        return($rtn);
    } # end function

    /**
     * RIJNDAEL_256 encrypter
     *
     * @requires mcrypt package
     * @param mixed  $key
     * @param string $string_to_encrypt
     * @returns string base64 encoded
     */
    function encryptRJ256($key, $string_to_encrypt)
    {
        $md5_key = md5($key);
        $iv      = md5($md5_key);
        $rtn     = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $md5_key, $string_to_encrypt, MCRYPT_MODE_CBC, $iv);
        $rtn     = base64_encode($rtn);
        return($rtn);
    } # end function

    /**
     * Check if the specified address is a valid wallet address
     *
     * @param string $address
     * @returns boolean
     */
    function is_wallet_address($address)
    {
        return preg_match('/[a-zA-Z0-9]{25,34}/', $address);
    } # end function

    /**
     * Connects to database ans sets the handler resource to $config->db_handler
     */
    function db_connect()
    {
        global $config;
        $config->db_handler = mysql_connect($config->db_host, $config->db_user, $config->db_password);
        if( ! is_resource($config->db_handler) ) die("Couldn't connect to database!");
        mysql_select_db($config->db_db);
    } # end function

    /**
     * Returns the offset from the origin timezone to the remote timezone, in seconds.
     *
     * @see http://www.php.net/manual/en/function.timezone-offset-get.php
     * @param $remote_tz;
     * @param $origin_tz; If null the servers current timezone is used as the origin.
     * @return int;
     */
    function get_timezone_offset($remote_tz, $origin_tz = null)
    {
        if($origin_tz === null) {
            if(!is_string($origin_tz = date_default_timezone_get())) {
                return false; // A UTC timestamp was returned -- bail out!
            }
        }
        $origin_dtz = new DateTimeZone($origin_tz);
        $remote_dtz = new DateTimeZone($remote_tz);
        $origin_dt = new DateTime("now", $origin_dtz);
        $remote_dt = new DateTime("now", $remote_dtz);
        $offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
        return $offset;
    } # end functino


    /**
     * Fetch user wallet transactions data.
     * Note: the first call is cached.
     *
     * @param string $return_type counts | data
     *
     * @returns array( $tips_in, $tips_out, $deposits, $withdrawals, $all_activity )
     */
    function fetch_wallet_transactions_table($return_type = "data")
    {
        global $account;

        $tips_in = $tips_out = $deposits = $withdrawals = $all_activity = array();
        $x_all_activity = $account->list_transactions();
        if( ! is_array($x_all_activity) ) $x_all_activity = array();

        if( count($x_all_activity) > 0 )
        {
            foreach($x_all_activity as $key => $transaction)
            {
                $t = clone $transaction;
                if( $transaction->category == "send" )
                {
                    $t->category = "withdrawal";
                    unset($t->otheraccount);
                    $withdrawals[] = clone $t;
                }
                elseif( $transaction->category == "receive" && ! empty($transaction->txid) )
                {
                    $t->category = "deposit";
                    unset($t->otheraccount);
                    $deposits[] = clone $t;
                }
                elseif( $transaction->category == "move" && $transaction->amount < 0 )
                {
                    $t->category = "sent";
                    unset($t->address, $t->confirms, $t->txid);
                    $tips_out[] = clone $t;
                } # end if
                elseif( $transaction->category == "move" && $transaction->amount > 0 )
                {
                    $t->category = "received";
                    unset($t->address, $t->confirms, $t->txid);
                    $tips_in[] = clone $t;
                } # end if
                $all_activity[] = clone $t;
            } # end foreach
        } # end if

        # header("X-Source-Data-Counts: " . json_encode(array(count($tips_in), count($tips_out), count($deposits), count($withdrawals), count($all_activity))));
        if( $return_type == "counts" )
            return array( count($tips_in), count($tips_out), count($deposits), count($withdrawals), count($all_activity) );
        else
            return array( $tips_in, $tips_out, $deposits, $withdrawals, $all_activity );
    } # end function

    /**
     * Renderer for wallet transaction tables.
     * Note: It should be called after fetch_wallet_transactions_table to ensure caching!
     *
     * @param string $category tips_in | tips_out | deposits | withdrawals | all_activity
     */
    function render_wallet_transactions_table($category)
    {
        global $config, $account;

        list($tips_in, $tips_out, $deposits, $withdrawals, $all_activity) = fetch_wallet_transactions_table();
        # header("X-Target-Data-Counts: " . json_encode(array(count($tips_in), count($tips_out), count($deposits), count($withdrawals), count($all_activity))));
        switch($category)
        {
            case "tips_in":
                $columns = array( "account", "otheraccount", "blockhash", "blockindex",
                                  "blocktime", "time", "amount" );
                break;
            case "tips_out":
                $columns = array( "account", "otheraccount", "blockhash", "blockindex",
                                  "blocktime", "time", "amount" );
                break;
            case "deposits":
                $columns = array( "address", "confirmations", "blockhash", "blockindex",
                                  "blocktime", "txid", "time", "timereceived", "amount", "fee" );
                break;
            case "withdrawals":
                $columns = array( "address", "confirmations", "blockhash", "blockindex",
                                  "blocktime", "txid", "time", "timereceived", "amount", "fee" );
                break;
            default:
                $columns = array( "account", "otheraccount", "address", "confirmations", "blockhash", "blockindex",
                                  "blocktime", "txid", "time", "timereceived", "amount", "fee" );
                break;
        } # end switch

        $data = ${$category};
        # echo "<pre>\$category := " . print_r($category, true) . "</pre>";
        # echo "<pre>\$data := " . print_r($data, true) . "</pre>";
        # echo "<pre>count(\$data) := " . print_r(count($data), true) . "</pre>";
        # header("X-Data-Category-Source: $category");
        # header("X-Data-Category-Size: ".count($data));
        if( count($data) == 0 ): ?>

            <div class="message_box ui-state-highlight ui-corner-all">
                <span class="ui-icon embedded ui-icon-info"></span>
                There is no activity to show here. If you expect a transaction to be shown, please give it some minutes and reload this page.
            </div>

            <? return; ?>
        <? endif; ?>

        <div class="table_wrapper">

            <table class="tablesorter" width="100%" cellpadding="2" cellspacing="1" border="0">
                <thead>
                    <tr>
                        <? foreach( $columns as $key ) { ?>
                            <?
                                if($key == "account")       continue;
                                if($key == "blockhash")     continue;
                                if($key == "blocktime")     continue;
                                if($key == "blockindex")    continue;
                                if($key == "timereceived")  continue;
                                if($key == "otheraccount")  $key = "To/From";
                                if($key == "confirmations") $key = "confirms";
                                if($key == "timereceived")  $key = "Received";
                                if($key == "time")          $key = "When";
                            ?>
                            <th><?= ucwords($key) ?></th>
                        <? } # end foreach ?>
                    </tr>
                </thead>
                <tbody>
                    <? $amount_total = $fee_total = 0; ?>
                    <? foreach( $data as $row ) { ?>
                        <tr>
                            <? foreach($columns as $key) { ?>
                                <?
                                    $val = $row->{$key};
                                    $align = "left";
                                    if($key == "account")       continue;
                                    if($key == "blockhash")     continue;
                                    if($key == "blocktime")     continue;
                                    if($key == "blockindex")    continue;
                                    if($key == "timereceived")  continue;
                                    if($key == "time")          $val = "<span title='".date("Y-m-d H:i:s", $val)."'>" . time_elapsed_string(date("Y-m-d H:i:s", $val)) . "</span>";
                                    if($key == "amount")        { $align = "right"; $val = "<div class='fixed_font coin_signed'>" . number_format($val, 8) . "</div>"; }
                                    if($key == "fee")           { $align = "right"; $val = "<div class='fixed_font coin_signed'>" . number_format($val, 8) . "</div>"; }
                                    if($key == "confirmations") $align = "right";
                                    if($key == "otheraccount")
                                    {
                                        $vals = explode(".", $val);
                                        $val = end($vals);
                                        if($val == $account->id_account)
                                        {
                                            $val = "(You)";
                                        }
                                        else
                                        {
                                            $tmp_account = new account($val);
                                            if( $tmp_account->exists )
                                            {
                                                $val = "[$tmp_account->id_account] <a href='https://www.facebook.com/".$tmp_account->facebook_id."' target='_blank'>$tmp_account->name</a>";
                                            } # end if
                                        } # end if
                                    } # end if
                                ?>
                                <? if( empty($val) ) { ?>
                                    <td>&ndash;</td>
                                <? } else { ?>
                                    <? if($key == "address" || $key == "txid") { ?>
                                        <td align="<?=$align?>">
                                            <span class="pseudo_link" title="Click to see" onclick="prompt('Your TXid:', '<?=$val?>')">
                                                <?= substr($val, 0, 4) ?>...<?= substr($val, -4) ?>
                                            </span>
                                        </td>
                                    <? } else { ?>
                                        <td align="<?=$align?>"><?=$val?></td>
                                    <? } # end if ?>
                                <? } # end if ?>
                            <? } # end foreach $columns ?>
                        </tr>
                        <? $amount_total += $row->amount; ?>
                        <? $fee_total    += $row->fee; ?>
                    <? } # end foreach $data ?>
                </tbody>
                <tfoot>
                    <tr>
                        <? foreach( $columns as $key ) { ?>
                            <?
                                if($key == "account")       continue;
                                if($key == "blockhash")     continue;
                                if($key == "blocktime")     continue;
                                if($key == "blockindex")    continue;
                                if($key == "timereceived")  continue;
                            ?>
                            <? if($key == "amount") { ?>
                                <td align="right"><div class="fixed_font coin_signed"><?= number_format($amount_total, 8) ?></div></td>
                            <? } elseif($key == "fee") { ?>
                                <td align="right"><div class="fixed_font coin_signed"><?= number_format($fee_total, 8) ?></div></td>
                            <? } else { ?>
                                <td>&nbsp;</td>
                            <? } # end if ?>
                        <? } # end foreach ?>
                    </tr>
                </tfoot>
            </table>

        </div><!-- /.table_wrapper -->

        <?
    } # end function

    /**
     * Convert date to "time ago" string
     *
     * @see http://stackoverflow.com/questions/1416697/converting-timestamp-to-time-ago-in-php-e-g-1-day-ago-2-days-ago
     *
     * @param date $date
     * @returns string
     */
    function time_elapsed_string($date)
    {
        $ptime = strtotime($date);

        $etime = time() - $ptime;

        if ($etime < 1)
        {
            return '0 seconds';
        }

        $a = array( 12 * 30 * 24 * 60 * 60  =>  'year',
                    30 * 24 * 60 * 60       =>  'month',
                    24 * 60 * 60            =>  'day',
                    60 * 60                 =>  'hour',
                    60                      =>  'min',
                    1                       =>  'second'
                    );

        foreach ($a as $secs => $str)
        {
            $d = $etime / $secs;
            if ($d >= 1)
            {
                $r = round($d);
                return $r . ' ' . $str . ($r > 1 ? 's' : '') . ' ago';
            }
        }
    } # end function

    /**
     * Random password generator
     *
     * @see http://stackoverflow.com/questions/6101956/generating-a-random-password-in-php
     */
    function randomPassword($length = 12)
    {
        $alphabet = "abcdefghijklmnopqrstuwxyz0123456789";
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < $length; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    } # end function

    /**
     * Fractions counter
     *
     * @author ggrenier<http://stackoverflow.com/users/476260/ggreiner>
     * @see http://stackoverflow.com/questions/10419501/use-php-to-generate-random-decimal-beteween-two-decimals
     *
     * @param number $x
     *
     * @returns number
     */
    function count_fractions($x)
    {
       return  strlen(substr(strrchr($x+"", "."), 1));
    } # end function

    /**
     * Random number with fractions
     *
     * @author ggrenier<http://stackoverflow.com/users/476260/ggreiner>
     * @see http://stackoverflow.com/questions/10419501/use-php-to-generate-random-decimal-beteween-two-decimals
     *
     * @param number $min
     * @param number $max
     *
     * @returns number
     */
    function random_with_fractions($min, $max)
    {
       $decimals = max(count_fractions($min), count_fractions($max));
       $factor = pow(10, $decimals);
       return rand($min*$factor, $max*$factor) / $factor;
    } # end function

    function get_tipping_provider_keyname_by_coin($searched_coin_name)
    {
        global $config;

        foreach($config->tipping_providers_database as $provider_keyname => $provider_data)
        {
            foreach($provider_data["per_coin_data"] as $coin_name => $coin_data)
            {
                if($coin_name == $searched_coin_name) return $provider_keyname;
            } # end foreach
        } # end foreach

        return "";
    } # end function

    function get_coin_sign_by_name($searched_coin_name)
    {
        global $config;

        foreach($config->tipping_providers_database as $provider_keyname => $provider_data)
        {
            foreach($provider_data["per_coin_data"] as $coin_name => $coin_data)
            {
                if($coin_name == $searched_coin_name) return $coin_data["coin_sign"];
            } # end foreach
        } # end foreach

        return "";
    } # end function

    function get_coin_plural_by_name($searched_coin_name)
    {
        global $config;

        foreach($config->tipping_providers_database as $provider_keyname => $provider_data)
        {
            foreach($provider_data["per_coin_data"] as $coin_name => $coin_data)
            {
                if($coin_name == $searched_coin_name) return $coin_data["coin_name_plural"];
            } # end foreach
        } # end foreach

        return "";
    } # end function

    function number_format_crypto($amount, $round_decimals = 0)
    {
        if( ! is_numeric($amount) ) return "N/A";
        $whole = number_format($amount, 8);
        $parts = explode(".", $whole);
        $integer = $parts[0];
        $decimals = rtrim($parts[1], "0");
        if( strlen($decimals) > 0 ) $decimals = ".$decimals";
        if( $round_decimals > 0 ) $decimals = ltrim(round("0".$decimals, $round_decimals), "0");
        return $integer . $decimals;
    } # end function

    function round_crypto($amount, $round_decimals = 0)
    {
        $res = number_format_crypto($amount, $round_decimals);
        return( str_replace(",", "", $res) );
    } # end function

    function rounded_trimming($amount, $digits = 0, $comma_separated = false)
    {
        $amount = number_format($amount, $digits);
        if( ! $comma_separated ) $amount = str_replace(",", "", $amount);
        if( stristr($amount, ".") !== false ) $amount = rtrim($amount, "0");
        $amount = rtrim($amount, ".");
        if( $amount == "" ) $amount = 0;
        return $amount;
    } # end function

    function number_format_crypto_condensed($amount, $round_decimals = 0)
    {
        if( ! is_numeric($amount) ) return "N/A";
        if( $amount == 0 ) return $amount;

        if( $amount <  .000001 ) return number_format($amount, 8, ".", "");
        if( $amount <  .00001  ) return number_format($amount, 6, ".", "");
        if( $amount <  .0001   ) return number_format($amount, 5, ".", "");
        if( $amount <  .001    ) return number_format($amount, 3, ".", "");
        if( $amount <  1       ) return number_format_crypto($amount, 5);
        if( $amount <  1000    ) return number_format_crypto($amount, 3);
        if( $amount >= 1000000 ) return number_format(($amount / 1000000), $round_decimals) . "M";
        if( $amount >= 1000    ) return number_format(($amount / 1000),    $round_decimals) . "K";
    } # end function

    function get_flag_value($flag)
    {
        global $config;
        $query = "select value from ".$config->db_tables["flags"]. " where flag = '$flag'";
        # echo "$query\n";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 )
        {
            return "";
        }
        else
        {
            $row = mysql_fetch_object($res);
            return $row->value;
        } # end if
        mysql_free_result($res);
        # echo "$query\n";
    } # end function

    function set_flag_value($flag, $value)
    {
        global $config;
        $value = addslashes($value);
        $query = "update ".$config->db_tables["flags"]. " set value = '$value' where flag = '$flag'";
        # echo "$query\n";
        mysql_query($query);

        if( mysql_affected_rows() == 0 )
        {
            $query = "insert into ".$config->db_tables["flags"]. " set value = '$value', flag = '$flag'";
            # echo "$query\n";
            mysql_query($query);
        } # end if
    } # end function

    function load_extensions($location_case, $basedir = ".")
    {
        global $config, $is_admin, $admin_impersonization_in_effect, $root_url, $account;
        if( ! is_dir("$basedir/extensions") ) return;
        $files = glob("$basedir/extensions/*/bootstrap.inc");
        if( count($files) == 0 ) return;
        foreach($files as $this_file) include $this_file;
    } # end function

    function is_robot()
    {
        $botlist = array("googlebot", "slurp", "msnbot", "mediapartners-google", "yahoo-mmcrawler",
                         "bingbot", "spider", "crawl", "ia_archiver");
        $ua      = strtolower($_SERVER["HTTP_USER_AGENT"]);
        $is_bot  = false;
        foreach($botlist as $this_bot) {
            if(stristr($ua, $this_bot) !== false) {
                $is_bot = true; break;
            } # end if
        } # end foreach
        return $is_bot;
    } # end function

    /**
     * Echoes a fake "401 - Unauthorized" error and quits the program.
     */
    function throw_fake_401()
    {
        header("Content-Type: text/html; charset=utf-8");
        header("HTTP/1.0 401 Unauthorized");
        # echo "<pre>\$_SERVER := " . print_r($_SERVER, true) . "</pre>";
        die('<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
            <html><head>
            <title>401 Unauthorized</title>
            </head><body>
            <h1>Unauthorized</h1>
            <p>You are trying to access a page using an invalid login method.</p>
            <hr>
            <address>'.trim($_SERVER["SERVER_SIGNATURE"]).'</address>
            </body></html>');
    } # end function

    function get_online_user_id( $throw_exception_as_plain_text = false )
    {
        global $config, $facebook, $session_from_cookie, $root_url;

        $cookie_name         = $config->session_vars_prefix . $config->cookie_session_identifier;
        $session_from_cookie = false;
        if( ! empty($_COOKIE[$cookie_name]) )
        {
            $user_id = decryptRJ256($config->tokens_encryption_key, $_COOKIE[$cookie_name]);
            if( ! empty($user_id) )
            {
                $session_from_cookie = true;
                return $user_id;
            } # end if
        } # end if

        $fb_params = array(
            'appId'              => $config->facebook_app_id,
            'secret'             => $config->facebook_app_secret,
            'fileUpload'         => false,
            'allowSignedRequest' => true
        );
        $facebook = new Facebook($fb_params);

        $access_token = $facebook->getAccessToken();
        $facebook->setExtendedAccessToken();
        $access_token = $facebook->getAccessToken();

        try
        {
            $user_id = $facebook->getUser();
            header("X-FB-API-This-App-Id: {$config->facebook_app_id}");
            header("X-FB-API-Provided-User-Id: $user_id");
        }
        catch(Exception $e)
        {
            if( $throw_exception_as_plain_text ) die("Can't load Facebook SDK! Exception: " . $e->getMessage() . ". Please try again.");
            include "$root_url/{$config->contents["welcome"]}";
            die();
        } // end try...catch

        $tmp = new account($user_id);
        if( ! $tmp->exists )
        {
            # Here is where we check the user id against all apps...
            if( ! empty($config->business_mapped_app_ids) )
            {
                try
                {
                    $res = $facebook->api("/me/ids_for_business");
                    $user_maps_per_app = array();
                    if( $res["data"] )
                        foreach($res["data"] as $this_mapping)
                            $user_maps_per_app[] = array("app_id" => $this_mapping["app"]["id"], "user_id" => $this_mapping["id"]);

                    header("FB-IDs-For-Business: ~ ".json_encode($res));
                    header("User-Maps-Per-App: ~ ".json_encode($user_maps_per_app));
                    if( count($user_maps_per_app) > 1 )
                    {
                        # There is more than one app authorized.
                        # We need to loop through the apps to get the id for the user.
                        # This is where the business_mapped_app_ids array must be set from the oldest to the newest.
                        foreach( $user_maps_per_app as $user_maps_data )
                        {
                            $searching_app_id     = $user_maps_data["app_id"];
                            $searching_user_fb_id = $user_maps_data["user_id"];
                            header("X-$searching_app_id-1-Begin: ~ $searching_user_fb_id");

                            foreach( $config->business_mapped_app_ids as $index => $app_data )
                            {
                                $app_id = $app_data["id"];
                                if( $app_id == $searching_app_id ) break;
                            } # end foreach

                            header("X-$searching_app_id-2-Gotcha: $app_id");
                            # $user_id = $user_maps_per_app[$app_id];
                            # break;

                            # The user authorized another app. We query that app to get his internal ID
                            $id_account = get_id_account_from_app($app_id, $searching_user_fb_id);
                            if( ! empty($id_account) )
                            {
                                # The user authorized another app. His $user_id remains, but
                                # we need to pre-insert him into the database.
                                $t2 = new account($id_account);

                                # The account is not registered here. We'll insert it with
                                # the app-scoped FB id and the provided id account
                                try
                                {
                                    $user_profile = $facebook->api("/me");
                                }
                                catch( Exception $e )
                                {
                                    $error_to_throw = "Can't query FB for your user data! Exception: " . $e->getMessage() . ". Please try again.";
                                    if( $throw_exception_as_plain_text ) die($error_to_throw);
                                    include "$root_url/{$config->contents["welcome"]}";
                                    die();
                                } # end try...catch

                                $t2->id_account                 = $id_account;
                                $t2->facebook_id                = $user_id;
                                $t2->facebook_user_access_token = $access_token;
                                $t2->name                       = $user_profile["name"];
                                $t2->timezone                   = $user_profile["timezone"];
                                $t2->tipping_provider           = $config->current_tipping_provider_keyname;
                                $t2->date_created               =
                                $t2->last_update                =
                                $t2->last_activity              = date("Y-m-d H:i:s");

                                if( $config->facebook_login_enforced )
                                {
                                    $t2->email = $user_profile["email"];
                                }
                                else
                                {
                                    # Let's see if the email is already used somewhere else
                                    $query = "select * from ".$config->db_tables["account"]."
                                              where email = '".addslashes($user_profile["email"])."'
                                              or    alternate_email = '".addslashes($user_profile["email"])."'";
                                    $res   = mysql_query($query);
                                    if( mysql_num_rows($res) == 0 ) $t2->email = $user_profile["email"];
                                    mysql_free_result($res);
                                } # end if

                                if( count($config->current_tipping_provider_data["per_coin_data"]) == 1 )
                                    $t2->wallet_address = $t2->register();

                                $t2->save();
                                $user_id = $id_account;
                            } # end if
                        } # end foreach
                    } # end if
                }
                catch(Exception $e)
                {
                    if( $throw_exception_as_plain_text ) die("Can't load Facebook SDK! Exception: " . $e->getMessage() . ". Please try again.");
                    include "$root_url/{$config->contents["welcome"]}";
                    die();
                } // end try...catch

            } # end if
        } # end if

        $cookie_name  = $config->session_vars_prefix . $config->cookie_session_identifier;
        $cookie_value = encryptRJ256($config->tokens_encryption_key, $user_id);
        setcookie($cookie_name, $cookie_value, strtotime("now + 30 days"), "/", $config->cookie_domain);
        $_COOKE[$cookie_name] = $cookie_value;

        return $user_id;
    } # end function

    function get_id_account_from_app($app_id, $fb_id_account)
    {
        global $config;
        if( empty($fb_id_account) ) return "";

        foreach($config->business_mapped_app_ids as $index => $app_data)
            if( $app_data["id"] == $app_id ) break;

        header("X-$app_id-3-App-Id: ~ [$app_id]");
        header("X-$app_id-4-FB-ID-Account: ~ [$fb_id_account]");
        header("X-$app_id-5-App-Ids: ~ ".str_replace("\n", " ", print_r($config->business_mapped_app_ids, true)));
        header("X-$app_id-6-This-Data: ~ ".str_replace("\n", " ", print_r($app_data, true)));

        $url = $app_data["url"]
             . "toolbox.php?mode=get_id_account_from_fb_account"
             . "&id_account=" . urlencode(encryptRJ256($config->interapps_exchange_key, $fb_id_account))
             ;

        header("X-$app_id-7-Invoking-Validator-Getting-URL: ~ $url");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($curl);
        header("X-$app_id-8-Invoking-Validator-Raw-Response: ~ ".str_replace("\n", " ", $data));

        curl_close($curl);
        if( substr($data, 0, 3) !== "OK:" ) return "";

        $data = str_replace("OK:", "", $data);
        $data = decryptRJ256($config->interapps_exchange_key, $data);
        header("X-$app_id-9-Invoking-Validator-Decrypted-Response: ~ $data");
        return $data;
    } # end function

    function get($url, $params = "" )
    {
        if( is_array($params) ) $url .= "?" . $params = http_build_query($params);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,            $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($curl);

        if( curl_error($curl) ) return array("ERROR", curl_error($curl));
        else                    return array("OK", $data);
    } # end function

    function http_delete($url, $params = "" )
    {
        if( is_array($params) ) $url .= "?" . $params = http_build_query($params);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST,  "DELETE");
        curl_setopt($curl, CURLOPT_URL,            $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($curl);

        if( curl_error($curl) ) return array("ERROR", curl_error($curl));
        else                    return array("OK", $data);
    } # end function

    function post($url, $params = "" )
    {
        if( is_array($params) ) $params = http_build_query($params);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,            $url);
        curl_setopt($curl, CURLOPT_POST,           1);
        curl_setopt($curl, CURLOPT_POSTFIELDS,     $params);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($curl);

        if( curl_error($curl) ) return array("ERROR", curl_error($curl));
        else                    return array("OK", $data);
    } # end function

    function send_notification($to, $message, $link)
    {
        global $config;

        $fb_params = array(
            'appId'              => $config->facebook_app_id,
            'secret'             => $config->facebook_app_secret,
            'fileUpload'         => false,
            'allowSignedRequest' => true
        );
        $facebook = new Facebook($fb_params);

        $to_account = new account($to);
        if( empty($to_account->facebook_id) ) return "Target account $to doesn't have a FB user id.";
        # if( empty($to_account->facebook_user_access_token) ) return "Target account $to doesn't have a FB access token.";

        try
        {
            $previous_access_token = $facebook->getAccessToken();
            $facebook->setAccessToken("{$config->facebook_app_id}|{$config->facebook_app_secret}");

            $params  = array("template" => $message, "href" => $link);
            $url = "/{$to_account->facebook_id}/notifications";
            $res = $facebook->api($url, "POST", $params);
            if( $res["success"] )
            {
                $xsender_notification_message = "OK";
            }
            else
            {
                $xsender_notification_message = "FB Notification failed: ".$res["error"]["message"].".";
                # @mysql_query("update {$config->db_tables["account"]} set receive_notifications = 'false' where id_account = '{$to_account->id_account}'");
            } # end if
        }
        catch( Exception $e )
        {
            $xsender_notification_message = "FB exception raised: ".$e->getMessage().". Access token: $access_token";
            # @mysql_query("update {$config->db_tables["account"]} set receive_notifications = 'false' where id_account = '{$to_account->id_account}'");
        } # end try...catch

        $facebook->setAccessToken($previous_access_token);
        return $xsender_notification_message;
    } # end function

    function normalize_filename($filename)
    {
        $invalids = array( "'", '"', "`",
                           "/", "|", "\\",
                           "%", "&", " ", "+", "=", "\$", "#", "^",
                           "?", "!", "*", "~", "@",
                           ".", ":", ";", "-",
                           "<", ">", "[", "]", "{", "}", "(", ")", );
        $pass1    = str_replace($invalids, "_", $filename);
        $pass2    = preg_replace('/_+/',   "_", $pass1);
        return $pass2;
    } # end function

    /**
     * Send a notification through twitter or email
     *
     * @param string $to_id_account Target id account
     * @param string $message       Message to deliver
     * @param string $subject       (Optional) subject for e-mail
     *
     * @return string "OK" or error message
     */
    function send_message($to_id_account, $message, $subject = "")
    {
        global $config, $root_url;

        if( empty($to_id_account) ) return "Error: target account not specified.";

        # Let's see if it has twitter
        $query = "select * from {$config->db_tables["twitter"]} where id_account = '{$to_id_account}'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 ) return "Error: target account is not present on Twitter control table.";
        $row = mysql_fetch_object($res);
        if( empty($row->access_token) ) return "Error: target account has not authorized the app.";
        mysql_free_result( $res );

        $twitter_options = array(
            'oauth_access_token'        => $config->twitter_access_token,
            'oauth_access_token_secret' => $config->twitter_token_secret,
            'consumer_key'              => $config->twitter_consumer_key,
            'consumer_secret'           => $config->twitter_consumer_secret
        );

        if( ! class_exists("TwitterAPIExchange") )
            include_once "$root_url/extensions/twitter/lib/TwitterAPIExchange.php";

        $twitter = new TwitterAPIExchange($twitter_options);
        $response = $twitter
                    ->buildOauth("https://api.twitter.com/1.1/direct_messages/new.json", "POST")
                    ->setPostfields( array( "user_id" => $row->twitter_id
                                          , "text" => $message
                                          ) )
                    ->performRequest();
        $response = json_decode($response);

        if( empty($response->errors) ) return "OK";
        else                           return "Notification failed: {$response->errors[0]->message}";
    } # end function
