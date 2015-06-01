<?php
    /**
     * Extended account data
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

    class account_extensions
    {
        /**
        * Id of the account
        *
        * @var string
        */
        var $id_account;

        /**
        * Data from where the account was created
        *
        * @var string ip; country; region; city
        */
        var $created_from;

        /**
        * From websites module
        *
        * @var string
        */
        var $referer_website_key;

        var $referer_button_website_key;
        var $referer_button_id;
        var $referer_button_referral_code;

        /**
        * Account type
        *
        * @var string standard, vip, premium
        */
        var $account_class = "standard";

        /**
        * Where the tips are going delivered?
        *
        * @var string
        */
        var $reroute_to;

        /**
        * For storage of specific profile info
        *
        * @var string json object
        */
        var $public_profile_data;

        /**
        * Internal
        *
        * @var boolean
        */
        var $exists = false;

        function __construct($user_id = "")
        {
            global $config;
            if( ! is_resource($config->db_handler) ) db_connect();

            if( is_object($user_id) )
            {
                $this->assign_from_object($user_id);
                $this->exists = true;
                return $this;
            }

            if( empty($user_id) )
            {
                $this->exists = false;
                return $this;
            } # end if

            $query = "select * from ".$config->db_tables["account_extensions"]." where id_account = '$user_id'";
            $res   = mysql_query($query);
            if( mysql_num_rows($res) )
            {
                $row = mysql_fetch_object($res);
                $this->assign_from_object($row);
                $this->exists = true;
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
            $this->id_account                   = $object->id_account                      ;
            $this->created_from                 = $object->created_from                    ;
            $this->referer_website_key          = $object->referer_website_key             ;
            $this->account_class                = $object->account_class                   ;
            $this->reroute_to                   = $object->reroute_to                      ;
            $this->public_profile_data          = json_decode($object->public_profile_data);

            return $this;
        } # end function

        /**
         * Saves account to database
         */
        function save()
        {
            global $config;
            if( ! is_resource($config->db_handler) ) db_connect();

            if( ! $this->exists )
            {
                $query = "
                    insert into ".$config->db_tables["account_extensions"]." set
                        id_account                   = '".addslashes($this->id_account)."',
                        created_from                 = '".addslashes($this->created_from)."',
                        referer_website_key          = '".addslashes($this->referer_website_key)."',
                        referer_button_website_key   = '".addslashes($this->referer_button_website_key)."',
                        referer_button_id            = '".addslashes($this->referer_button_id)."',
                        referer_button_referral_code = '".addslashes($this->referer_button_referral_code)."',
                        account_class                = '".addslashes($this->account_class)."',
                        reroute_to                   = '".addslashes($this->reroute_to)."',
                        public_profile_data          = '".json_encode($this->public_profile_data)."'
                ";
            }
            else
            {
                $query = "
                    update ".$config->db_tables["account_extensions"]." set
                        referer_website_key          = '".addslashes($this->referer_website_key)."',
                        referer_button_website_key   = '".addslashes($this->referer_button_website_key)."',
                        referer_button_id            = '".addslashes($this->referer_button_id)."',
                        referer_button_referral_code = '".addslashes($this->referer_button_referral_code)."',
                        account_class                = '".addslashes($this->account_class)."',
                        reroute_to                   = '".addslashes($this->reroute_to)."',
                        public_profile_data          = '".json_encode($this->public_profile_data)."'
                    where
                        id_account                   = '".addslashes($this->id_account)."'
                ";
            } # end if
            mysql_query($query);
        } # end function

    }
