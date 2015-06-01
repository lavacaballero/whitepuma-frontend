<?php
    /**
     * Access to backend API
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

    class tipping_provider
    {
        var $api_url;
        var $public_key;
        var $secret_key;

        var $message;
        var $message_extra_info;

        /**
        * standard|data_only: returns json object or just the data
        * @var string
        */
        var $calling_type = "standard";

        /**
        * Time (in minutes) the API responses are cached
        *
        * @var integer
        */
        var $cache_api_responses_time = 0;

        protected $outgoing_params;

        var $tipping_provider_res;

        function __construct($api_url, $public_key, $secret_key)
        {
            $this->api_url    = $api_url;
            $this->public_key = $public_key;
            $this->secret_key = $secret_key;
        } # end function

        function register($account_id)
        {
            $this->outgoing_params = array(
                "public_key"    => $this->public_key,
                "action"        => "register",
                "account"       => $account_id
            );
            return $this->fetch_and_throw();
        } # end function

        function get_address($account_id)
        {
            $this->outgoing_params = array(
                "public_key"    => $this->public_key,
                "action"        => "get_address",
                "account"       => $account_id
            );
            return $this->fetch_and_throw();
        } # end function

        function get_balance($account_id)
        {
            $this->outgoing_params = array(
                "public_key"    => $this->public_key,
                "action"        => "get_balance",
                "account"       => $account_id
            );
            return $this->fetch_and_throw();
        } # end function

        function list_transactions($account_id)
        {
            $this->outgoing_params = array(
                "public_key"    => $this->public_key,
                "action"        => "list_transactions",
                "account"       => $account_id
            );
            return $this->fetch_and_throw();
        } # end function

        function send($sender_account_id, $target_account_id, $amount, $is_fee = "")
        {
            $this->outgoing_params = array(
                "public_key"    => $this->public_key,
                "action"        => "send",
                "account"       => $sender_account_id,
                "target"        => $target_account_id,
                "amount"        => $amount,
                "is_fee"        => $is_fee
            );
            return $this->fetch_and_throw();
        } # end function

        function withdraw($sender_account_id, $target_address, $amount)
        {
            $this->outgoing_params = array(
                "public_key"    => $this->public_key,
                "action"        => "withdraw",
                "account"       => $sender_account_id,
                "target"        => $target_address,
                "amount"        => $amount
            );
            return $this->fetch_and_throw();
        } # end function

        /**
         * Sends the request and returns the result according to the calling type and the message itself.
         * @returns mixed
         */
        protected function fetch_and_throw()
        {
            $res = $this->send_request();
            $this->tipping_provider_res = $res;
            # echo "<code>res := " . nl2br(print_r($res, true)) . "</code>\n";
            $this->message = $res->message;
            if( $this->calling_type == "standard" )
            {
                return $res;
            }
            else
            {
                $this->message_extra_info = $res->extra_info;
                if( $res->message == "OK" ) return $res->data;
                return "";
            }
        } # end function

        protected function send_request()
        {
            global $config;

            # Cache check
            if( $this->cache_api_responses_time > 0 &&
              ! in_array($this->outgoing_params["action"], array("register","withdraw", "send", "get_balance")) )
            {
                session_start();
                $session_key     = "tprc:" . $config->current_coin_name . ":" . implode(",", $this->outgoing_params);
                $downloaded_time = $_SESSION[$session_key]->downloaded_time;
                $time_limit      = date("Y-m-d H:i:s", strtotime("now - $this->cache_api_responses_time minutes"));
                # echo "<pre>$session_key ~ downloaded := $downloaded_time - umbral := $time_limit " . ($downloaded_time > $time_limit ? "// CACHE HIT!" : "") . "</pre>";
                if( isset($_SESSION[$session_key]) )
                    if( $downloaded_time > $time_limit )
                        return $_SESSION[$session_key]->response_data;
            } # end if

            # Data preparation
            # echo "<code>raw params := " . nl2br(print_r($this->outgoing_params, true)) . "</code>\n";
            $pre_params = $this->outgoing_params;
            foreach($this->outgoing_params as $key => $val)
                if( $key != "public_key" )
                    $this->outgoing_params[$key] = encryptRJ256($this->secret_key, trim($val));
            $encoded_params = http_build_query($this->outgoing_params);
            # echo "<code>encrypted params := " . nl2br(print_r($this->outgoing_params, true)) . "</code>\n";
            # echo "<code>encoded params := " . nl2br(print_r($encoded_params, true)) . "</code>\n";
            # echo "<code>url := " . nl2br(print_r($this->api_url, true)) . "</code>\n";
            # echo "<code>public_key := " . nl2br(print_r($this->public_key, true)) . "</code>\n";
            # echo "<code>secret_key := " . nl2br(print_r($this->secret_key, true)) . "</code>\n";

            # Random pick for provider APIs
            # if( ! empty($config->current_coin_data["api_multiserver_urls"]) )
            # {
            #     $x = array_rand($config->current_coin_data["api_multiserver_urls"]);
            #     $this->api_url = $config->current_coin_data["api_multiserver_urls"][$x];
            # } # end if
            # header("X-This-API-URL: ~$this->api_url");

            # Sending
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,            $this->api_url);
            curl_setopt($ch, CURLOPT_POST,           count($this->outgoing_params));
            curl_setopt($ch, CURLOPT_POSTFIELDS,     $encoded_params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $res = curl_exec($ch);
            # echo "<code>raw res := " . nl2br(print_r($res, true)) . "</code>\n";

            if( curl_errno($ch) )
            {
                $return = (object) array(
                    "message" => "ERROR:COMM_ERROR",
                    "extra_info" => (object) array(
                        "url"           => $this->api_url,
                        "raw_post_data" => $pre_params,
                        "post_data"     => $this->outgoing_params,
                        "response"      => $res,
                        "curl_error"    => curl_error($ch)
                    )
                );
                curl_close($ch);
                # echo "<pre>" . print_r($return, true) . "</pre>";
                return $return;
            } # end if

            curl_close($ch);

            $res = json_decode($res);
            # echo "<code>decoded res := " . nl2br(print_r($res, true)) . "</code>\n";
            if( $res->data && ! is_object($res->data) && ! is_array($res->data) )
                $res->data = decryptRJ256($this->secret_key, $res->data);
            if( ! is_array($res->data) && ! is_object($res->data) )
                if( preg_match('/[\{\[:]/', $res->data) )
                    $res->data = json_decode($res->data);
            # try { $tmp = json_decode($res->data); if( is_object($tmp) || is_array($tmp) ) $res->data = $tmp; }
            # catch( Exeption $e ) { }
            # $tmp = json_decode($res->data); if( is_object($tmp) || is_array($tmp) ) $res->data = $tmp;
            # echo "<code>decrypted res := " . nl2br(print_r($res, true)) . "</code>\n";

            if( $this->cache_api_responses_time > 0 && !
                in_array($this->outgoing_params["action"], array("register","withdraw", "send", "get_balance")) )
            {
                session_start();
                $_SESSION[$session_key] = (object) array(
                    "downloaded_time" => date("Y-m-d H:i:s"),
                    "response_data"   => $res
                );
            } # end if

            return $res;
        } # end function

    }
