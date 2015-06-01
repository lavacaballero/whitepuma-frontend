<?php
    /**
     * Pulse post class
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

    class pulse_post
    {
        var $created      = "";
        var $id           = "";
        var $type         = "";
        var $target_coin  = "";
        var $target_feed  = "";

        var $id_author    = "";

        var $caption      = "";
        var $content      = "";
        var $picture      = "";
        var $link         = "";

        var $signature    = "";

        var $edited       = "";
        var $edited_by    = "";
        var $last_update  = "";

        var $admin_notes  = "";
        var $hidden       = "";

        var $metadata     = "";
        var $views        = "";
        var $clicks       = "";

        var $comments     = array();

        var $exists       = false;

        ################################################################
        function __construct($id_or_object = "", $load_comments = false)
        ################################################################
        {
            global $config;
            if( ! is_resource($config->db_handler) ) db_connect();

            if( is_object($id_or_object) )
            {
                $this->assign_from_object($id_or_object);
                if($load_comments) $this->load_comments();
                $this->exists = true;
                return $this;
            }

            if( empty($id_or_object) )
            {
                $this->exists = false;
                return $this;
            } # end if

            $query = "select * from ".$config->db_tables["pulse_posts"]." where id = '$id_or_object'";
            $res   = mysql_query($query);
            if( mysql_num_rows($res) )
            {
                $row = mysql_fetch_object($res);
                $this->assign_from_object($row);
                if($load_comments) $this->load_comments();
                $this->exists = true;
                return $this;
            } # end if
            mysql_free_result($res);
        } # end function

        /**
        * Assigns the current class properties from an incoming database query
        * @param object $object
        #################################### */
        function assign_from_object($object)
        ####################################
        {
            $this->created     = $object->created    ;
            $this->id          = $object->id         ;
            $this->type        = $object->type       ;
            $this->target_coin = $object->target_coin;
            $this->target_feed = $object->target_feed;
            $this->id_author   = $object->id_author  ;
            $this->caption     = $object->caption    ;
            $this->content     = $object->content    ;
            $this->picture     = $object->picture    ;
            $this->link        = $object->link       ;
            $this->signature   = $object->signature  ;
            $this->edited      = $object->edited     ;
            $this->edited_by   = $object->edited_by  ;
            $this->last_update = $object->last_update ;
            $this->admin_notes = $object->admin_notes;
            $this->hidden      = $object->hidden     ;
            $this->metadata    = $object->metadata   ;
            $this->views       = $object->views      ;
            $this->clicks      = $object->clicks     ;

            if( is_string($this->metadata) ) $this->metadata = json_decode($this->metadata);

            if( ! empty($this->metadata->caption_mentions) )
                $this->metadata->caption_mentions = (array) $this->metadata->caption_mentions;
            if( ! empty($this->metadata->content_mentions) )
                $this->metadata->content_mentions = (array) $this->metadata->content_mentions;

            if( $this->edited  == "0000-00-00 00:00:00" )
                $this->edited  = "";

            return $this;
        } # end function

        ##################################
        function assign_from_posted_form()
        ###################################
        {
            global $account;
            $this->type        = $_POST["type"];
            $this->target_coin = $_POST["target_coin"];
            $this->target_feed = $_POST["target_feed"];
            $this->id_author   = $account->id_account;
            $this->caption     = trim(strip_tags(stripslashes($_POST["caption_text"])));
            $this->content     = trim(strip_tags(stripslashes($_POST["content_text"])));
            $this->picture     = trim(stripslashes($_POST["photo_url"]));
            $this->link        = trim(stripslashes($_POST["link"]));
            $this->signature   = trim(strip_tags(stripslashes($_POST["signature"])));
            $this->metadata    = (object) array(
                                     "tipback_data" => (object) array(
                                         "coin_name" => $_POST["tipback_coin"],
                                         "users"     => $_POST["tipback_users"],
                                         "tip_size"  => $_POST["tipback_coins"],
                                     ),
                                     "caption_mentions" => json_decode(stripslashes($_POST["caption_mentions"])),
                                     "content_mentions" => json_decode(stripslashes($_POST["content_mentions"]))
                                 );
            if(empty($this->metadata->tipback_data->coin_name))     unset( $this->metadata->tipback_data->coin_name );
            if(empty($this->metadata->tipback_data->users))         unset( $this->metadata->tipback_data->users );
            if(empty($this->metadata->tipback_data->tip_size))      unset( $this->metadata->tipback_data->tip_size );
            if(json_encode($this->metadata->tipback_data) == "{}")  unset( $this->metadata->tipback_data );
            if(empty($this->metadata->caption_mentions))            unset( $this->metadata->caption_mentions );
            if(empty($this->metadata->content_mentions))            unset( $this->metadata->content_mentions );

            if( ! empty($this->metadata->caption_mentions) )
            {
                $new_mentions = array();
                foreach($this->metadata->caption_mentions as $this_mention)
                    $new_mentions[$this_mention->id] = $this_mention->name;
                $this->metadata->caption_mentions = $new_mentions;
            } # end if

            if( ! empty($this->metadata->content_mentions) )
            {
                $new_mentions = array();
                foreach($this->metadata->content_mentions as $this_mention)
                    $new_mentions[$this_mention->id] = $this_mention->name;
                $this->metadata->content_mentions = $new_mentions;
            } # end if
        } # end function

        ########################
        function load_comments()
        ########################
        {
            global $config;
            if( ! is_resource($config->db_handler) ) db_connect();

            $comments = array();
            $query = "
                select * from {$config->db_tables["pulse_comments"]}
                where parent_post = '{$this->id}' and hidden = 0
                order by created desc
            ";
            $res = mysql_query($query);
            while($row = mysql_fetch_object($res))
            {
                $row->metadata = json_decode($row->metadata);
                $comments[] = $row;
            } # end while
            $this->comments = $comments;
            return $this;
        } # end function

        /**
        * Saves account to database
        ############### */
        function save()
        ###############
        {
            global $config;
            if( ! is_resource($config->db_handler) ) db_connect();

            # session_start();
            # echo "<pre>\$account := " . print_r($this, true) . "</pre>";
            # echo "<pre>\$_SESSION := " . print_r($_SESSION, true) . "</pre>";

            $metadata = json_encode($this->metadata);

            if( ! $this->exists )
            {
                $this->last_update =
                $this->created     = date("Y-m-d H:i:s");
                $this->id          = uniqid(true);
                $query = "
                    insert into ".$config->db_tables["pulse_posts"]." set
                        created      = '" .            $this->created      . "',
                        id           = '" .            $this->id           . "',
                        type         = '" .            $this->type         . "',
                        target_coin  = '" .            $this->target_coin  . "',
                        target_feed  = '" .            $this->target_feed  . "',
                        id_author    = '" .            $this->id_author    . "',
                        caption      = '" . addslashes($this->caption    ) . "',
                        content      = '" . addslashes($this->content    ) . "',
                        picture      = '" . addslashes($this->picture    ) . "',
                        link         = '" . addslashes($this->link       ) . "',
                        signature    = '" . addslashes($this->signature  ) . "',
                        edited       = '" .            $this->edited       . "',
                        edited_by    = '" .            $this->edited_by    . "',
                        last_update  = '" .            $this->last_update  . "',
                        admin_notes  = '" . addslashes($this->admin_notes) . "',
                        hidden       = '" .            $this->hidden       . "',
                        metadata     = '" .            $metadata           . "'
                ";
            }
            else
            {
                $this->last_update = date("Y-m-d H:i:s");
                $query = "
                    update ".$config->db_tables["pulse_posts"]." set
                        created      = '" . date("Y-m-d H:i:s")            . "',
                        type         = '" . addslashes($this->type       ) . "',
                        caption      = '" . addslashes($this->caption    ) . "',
                        content      = '" . addslashes($this->content    ) . "',
                        picture      = '" . addslashes($this->picture    ) . "',
                        link         = '" . addslashes($this->link       ) . "',
                        signature    = '" . addslashes($this->signature  ) . "',
                        edited       = '" . addslashes($this->edited     ) . "',
                        edited_by    = '" . addslashes($this->edited_by  ) . "',
                        last_update  = '" .            $this->last_update  . "',
                        admin_notes  = '" . addslashes($this->admin_notes) . "',
                        hidden       = '" .            $this->hidden       . "',
                        metadata     = '" .            $metadata           . "',
                    where
                        id           = '" .            $this->id           . "'

                ";
            } # end if
            mysql_query($query);
            $this->exists = true;
        } # end function

        ###############
        function bump()
        ###############
        {
            global $config;

            $this->last_update = date("Y-m-d H:i:s");
            $query = "
                update ".$config->db_tables["pulse_posts"]." set
                    last_update  = '" . $this->last_update  . "'
                where
                    id           = '" . $this->id           . "'
            ";
            mysql_query($query);
        } # end function
    } # end class
