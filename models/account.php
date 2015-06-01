<?php
    /**
     * User account class
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

    class account extends tipping_provider
    {
        /**
        * shorter version of $facebook_id
        * @var string
        */
        var $id_account;

        /**
        * Facebook user id
        * @var number
        */
        var $facebook_id;

        /**
        * User access token for offline access
        * @var number
        */
        var $facebook_user_access_token;

        /**
        * Facebook user name - it may change!
        * @var string
        */
        var $name;

        /**
        * User's email
        * @var string
        */
        var $email;

        /**
        * Email for alternate authentication method
        * @var string
        */
        var $alternate_email;

        /**
        * Password for alternate authentication method
        * @var string
        */
        var $alternate_password;

        /**
        * UTC offset returned by Facebook
        *
        * @var number
        */
        var $timezone;

        /**
        * This account's provider timezone
        *
        * @var string
        */
        var $provider_timezone_offset;

        /**
        * Tipping provider, taken from $config->tipping_provider
        * @var string
        */
        var $tipping_provider;

        /**
        * Interface to the tipping provider API
        *
        * @var tipping_provider
        */
        # var $api;

        /**
        * Coin wallet address
        *
        * @var string
        */
        var $wallet_address;

        /**
        * Check for the account to receive notifications
        * @var enum nothing, true, false
        */
        var $receive_notifications;

        /**
        * Record creation date
        * @var date_time
        */
        var $date_created;

        /**
        * Date/time of last update to the profile
        * @var date_time
        */
        var $last_update;

        /**
        * Last activity date
        * @var date_time
        */
        var $last_activity;

        /**
        * Internal existing flag
        * @var boolean
        */
        var $exists = false;

        ###################################
        function __construct($user_id = "")
        ###################################
        {
            global $config;
            if( ! is_resource($config->db_handler) ) db_connect();

            $this->load_api();
            if( is_object($user_id) )
            {
                $this->assign_from_object($user_id);
                $this->exists = true;
                $this->load_api();
                return $this;
            }

            if( empty($user_id) )
            {
                $this->exists = false;
                return $this;
            } # end if

            $query = "select * from ".$config->db_tables["account"]." where id_account = '$user_id' or facebook_id = '$user_id'";
            $res   = mysql_query($query);
            if( mysql_num_rows($res) )
            {
                $row = mysql_fetch_object($res);
                $this->assign_from_object($row);
                $this->exists = true;
                $this->load_api();
                return $this;
            } # end if
            mysql_free_result($res);
        } # end function

        /**
         * Assigns the current class properties from an incoming database query
         *
         * @param object $object
         *
         * @return $this
         */
        function assign_from_object($object)
        {
            global $config;
            $this->id_account                   = $object->id_account                  ;
            $this->facebook_id                  = $object->facebook_id                 ;
            $this->facebook_user_access_token   = $object->facebook_user_access_token  ;
            $this->name                         = $object->name                        ;
            $this->email                        = $object->email                       ;
            $this->alternate_email              = $object->alternate_email             ;
            $this->alternate_password           = $object->alternate_password          ;
            $this->timezone                     = $object->timezone                    ;
            $this->tipping_provider             = $object->tipping_provider            ;
            # $this->wallet_address               = $this->load_wallet_address_from_db() ;
            $this->receive_notifications        = $object->receive_notifications       ;
            $this->date_created                 = $object->date_created                ;
            $this->last_update                  = $object->last_update                 ;
            $this->last_activity                = $object->last_activity               ;

            if($this->date_created  == "0000-00-00 00:00:00") $this->date_created  = "";
            if($this->last_update   == "0000-00-00 00:00:00") $this->last_update   = "";
            if($this->last_activity == "0000-00-00 00:00:00") $this->last_activity = "";

            $this->load_wallet_address_from_db();
            # $this->provider_timezone_offset = (get_timezone_offset($config->current_tipping_provider_data["timezone"]) / 3600) + $this->timezone;
            # echo "<pre>" . print_r($this, true) . "</pre>";
            return $this;
        } # end function

        /**
         * "Loads" the provider API
         */
        function load_api()
        {
            global $config;
            $this->api_url                  = $config->current_coin_data["api_url"];
            $this->public_key               = $config->current_tipping_provider_data["public_key"];
            $this->secret_key               = $config->current_tipping_provider_data["secret_key"];
            $this->calling_type             = "data_only";
            $this->cache_api_responses_time = $config->provider_response_cache;
        } # end function

        /**
         * Override for tipping provider API
         * @return mixed (string) account address
         */
        function register()
        {
            return parent::register($this->id_account);
        } # end function

        /**
         * Override for tipping provider API
         * @returns number
         */
        function get_balance()
        {
            return parent::get_balance($this->id_account);
        } # end function

        /**
         * Override for tipping provider API
         * @returns array
         */
        function list_transactions()
        {
            return parent::list_transactions($this->id_account);
        } # end function

        /**
         * Override for tipping provider API
         *
         * @param $target_account_id
         * @param $amount
         *
         * @return mixed (number) account balance
         */
        function send_to($target_account_id, $amount)
        {
            return parent::send($this->id_account, $target_account_id, $amount);
        } # end function

        /**
         * Override for tipping provider API
         *
         * @param $target_address
         * @param $amount
         *
         * @return mixed (string) transaction id
         */
        function withdraw_to($target_address, $amount)
        {
            return parent::withdraw($this->id_account, $target_address, $amount);
        } # end function

        /**
         * Converts self::$facebook_id to a shorter value
         */
        function make_id_account()
        {
            $this->id_account = base_convert($this->facebook_id, 10, 26);
        } # end function

        /**
         * Sets the last_activity of the account
         *
         * @param boolean $echo_query For debugging only
         */
        function ping($echo_query = false)
        {
            global $config;
            if( ! is_resource($config->db_handler) ) db_connect();

            $this->last_activity = date("Y-m-d H:i:s");
            $query = "
                update ".$config->db_tables["account"]." set
                    last_activity    = '$this->last_activity'
                where
                    id_account       = '$this->id_account'
            ";
            if($echo_query) echo $query;
            mysql_query($query);
        } # end function

        /**
         * Saves account to database
         */
        function save()
        {
            global $config;
            if( ! is_resource($config->db_handler) ) db_connect();

            # session_start();
            # echo "<pre>\$account := " . print_r($this, true) . "</pre>";
            # echo "<pre>\$_SESSION := " . print_r($_SESSION, true) . "</pre>";

            if( ! $this->exists )
            {
                $query = "
                    insert into ".$config->db_tables["account"]." set
                        id_account       = '".addslashes($this->id_account)."',
                        facebook_id      = '".addslashes($this->facebook_id)."',
                        facebook_user_access_token = '".addslashes($this->facebook_user_access_token)."',
                        name             = '".addslashes($this->name)."',
                        email            = '".addslashes($this->email)."',
                        alternate_email    = '".addslashes($this->alternate_email)."',
                        alternate_password = '".addslashes($this->alternate_password)."',
                        timezone         = '".addslashes($this->timezone)."',
                        tipping_provider = '".addslashes($this->tipping_provider)."',
                    #   wallet_address   = '".addslashes($this->wallet_address)."',
                        receive_notifications  = 'true',
                        date_created     = '".addslashes($this->date_created)."',
                        last_update      = '".addslashes($this->last_update)."',
                        last_activity    = '".addslashes($this->last_activity)."'
                ";
            }
            else
            {
                $query = "
                    update ".$config->db_tables["account"]." set
                        facebook_user_access_token = '".addslashes($this->facebook_user_access_token)."',
                        name             = '".addslashes($this->name)."',
                        email            = '".addslashes($this->email)."',
                        alternate_email    = '".addslashes($this->alternate_email)."',
                        alternate_password = '".addslashes($this->alternate_password)."',
                        timezone         = '".addslashes($this->timezone)."',
                    #   wallet_address   = '".addslashes($this->wallet_address)."',
                        receive_notifications  = '".addslashes($this->receive_notifications)."',
                        last_update      = '".addslashes($this->last_update)."',
                        last_activity    = '".addslashes($this->last_activity)."'
                    where
                        id_account       = '".addslashes($this->id_account)."'
                ";
            } # end if
            $this->save_wallet_address_to_db();
            mysql_query($query);
        } # end function

        function get_wallet_address_for_coin($coin_name)
        {
            global $config;
            if( ! is_resource($config->db_handler) ) db_connect();

            $query = "
                select * from ".$config->db_tables["account_wallets"]."
                where id_account = '".$this->id_account."' and coin_name = '$coin_name'
            ";
            $res = mysql_query($query);
            if( mysql_num_rows($res) == 0 )
            {
                mysql_free_result($res);
                return "";
            } # end if

            $row = mysql_fetch_object($res);
            mysql_free_result($res);
            return $row->address;
        } # end function

        protected function load_wallet_address_from_db()
        {
            global $config;
            if( ! is_resource($config->db_handler) ) db_connect();

            $query = "
                select * from ".$config->db_tables["account_wallets"]."
                where id_account = '".$this->id_account."' and coin_name = '".$config->current_coin_name."'
            ";
            $res = mysql_query($query);
            if( mysql_num_rows($res) == 0 )
            {
                mysql_free_result($res);
                $this->wallet_address = "";
                return $this;
            } # end if

            $row = mysql_fetch_object($res);
            mysql_free_result($res);
            $this->wallet_address = $row->address;
            return $this;
        } # end function

        protected function save_wallet_address_to_db()
        {
            global $config;
            if( ! is_resource($config->db_handler) ) db_connect();

            $query = "
                update ".$config->db_tables["account_wallets"]."
                set address = '".$this->wallet_address."'
                where id_account = '".$this->id_account."' and coin_name = '".$config->current_coin_name."'
            ";
            mysql_query($query);

            if( mysql_affected_rows() > 0 ) return $this;

            $query = "
                insert into ".$config->db_tables["account_wallets"]." set
                address     = '".$this->wallet_address."',
                id_account  = '".$this->id_account."',
                coin_name   = '".$config->current_coin_name."'
            ";
            mysql_query($query);
        } # end functino

    } # end class
