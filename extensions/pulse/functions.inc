<?php
    function get_pulse_user_preference($key, $id_account = "")
    {
        global $config, $account;
        if( empty($id_account) ) $id_account = $account->id_account;
        if( ! is_resource($config->db_handler) ) db_connect();

        $query = "select value from ".$config->db_tables["pulse_user_preferences"]. " where id_account = '$id_account' and `key` = '$key'";
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

    function set_pulse_user_preference($key, $value, $id_account = "")
    {
        global $config, $account;
        if( empty($id_account) ) $id_account = $account->id_account;
        if( ! is_resource($config->db_handler) ) db_connect();

        $value = addslashes(trim(strip_tags(stripslashes($value))));
        $query = "
            insert into {$config->db_tables["pulse_user_preferences"]}
            set
                `id_account` = '$id_account',
                `key`        = '$key',
                `value`      = '$value'
            on duplicate key update
                `value`      = '$value'
        ";
        mysql_query($query);
    } # end function

    function convert_comments($comments_array)
    {
        global $config;
        if( empty($comments_array) ) return array();
        $final_array = array();
        foreach($comments_array as $comment)
        {
            $comment->content = preg_replace('@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" target="_blank">$1</a>',                                        $comment->content);
            $comment->content = preg_replace('@<a href=".*" target="_blank">(.*(\.png|\.gif|\.jpg|\.jpeg).*)</a>@',   '<a class="wpulse_img_wrapper" href="$1" target="_blank"><img src="$1"></a>', $comment->content);
            if( is_string($comment->metadata) ) $comment->metadata = json_decode($comment->metadata);
            $comment->timestamp = strtotime($comment->created);
            if( ! empty($comment->picture) )
                $comment->content .= "<div class='wpulse_image_container'><img src='"
                                  .  str_replace("local://", "{$config->website_pages["root_url"]}pulse_files/", $comment->picture)
                                  .  "'></div>";

            $author = new account($comment->id_author);
            $comment->author_data = (object) array(
                "name"        => $author->name,
                "image"       => "https://graph.facebook.com/{$author->facebook_id}/picture",
                "profile_url" => "https://www.facebook.com/{$author->facebook_id}",
                "signature"   => ""
            );
            $signature = get_pulse_user_preference("signature", $comment->id_author);

            if( ! empty($signature) )
                $comment->author_data->signature
                    = preg_replace( '@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@',
                                    '<a href="$1" target="_blank">$1</a>',
                                    $signature );

            if( ! empty($comment->metadata->mentions) )
            {
                foreach($comment->metadata->mentions as $mentioned_id_account => $mentioned_name)
                {
                    $tmp = new account($mentioned_id_account);
                    $comment->content = str_replace($mentioned_name, "<a href='https://www.facebook.com/{$tmp->facebook_id}' target='_blank'>$mentioned_name</a>", $comment->content);
                } # end if
            } # end if
            $comment->content = preg_replace('/\n+/', "\n", $comment->content);
            $comment->content = nl2br($comment->content);
            $final_array[] = $comment;
        } # end while
        return $final_array;
    } # end function

    /**
     * Post converter to final data for rendering.
     *
     * @param pulse_post $post
     * @param boolean    $load_comments
     *
     * @return pulse_post
     */
    function convert_post($post, $load_comments = true)
    {
        global $config;

        $post->content   = preg_replace('@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" target="_blank">$1</a>',                                        $post->content);
        $post->caption   = preg_replace('@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" target="_blank">$1</a>',                                        $post->caption);
        $post->signature = preg_replace('@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" target="_blank">$1</a>',                                        $post->signature);
        $post->content   = preg_replace('@<a href=".*" target="_blank">(.*(\.png|\.gif|\.jpg|\.jpeg).*)</a>@',   '<a class="wpulse_img_wrapper" href="$1" target="_blank"><img src="$1"></a>', $post->content);

        if( substr($post->picture, 0, 8) == "local://" )
            $post->picture = str_replace("local://", "{$config->website_pages["root_url"]}pulse_files/", $post->picture);

        if( $post->type == "photo" || $post->type == "link" || $post->type == "rain" )
            if( ! empty($post->picture) )
                $post->content .= "<div class='wpulse_img_wrapper'><img src='{$post->picture}'></div>";

        if( $post->type == "video" )
        {
            $post->link = str_replace("http://",                  "//",                     $post->link);
            $post->link = str_replace("https://",                 "//",                     $post->link);
            $post->link = str_replace("youtu.be/",                "www.youtube.com/embed/", $post->link);
            $post->link = str_replace("www.youtube.com/watch?v=", "www.youtube.com/embed/", $post->link);
            $post->link = str_replace("youtube.com/watch?v=",     "www.youtube.com/embed/", $post->link);
            $post->content = "<iframe class='wpulse_video_container' src='".$post->link."' frameborder='0' allowfullscreen></iframe>";
        } # end if

        $author = new account($post->id_author);
        $post->author_data = (object) array(
            "name"        => $author->name,
            "image"       => "https://graph.facebook.com/{$author->facebook_id}/picture",
            "profile_url" => "https://www.facebook.com/{$author->facebook_id}"
        );

        if( $load_comments )
        {
            $post->load_comments();
            $post->comments = convert_comments($post->comments);
        } # end if

        if( ! empty($post->target_feed) )
        {
            $post->feed_data = (object) array(
                "name" => $config->facebook_monitor_objects[$post->target_feed]["name"],
                "url"  => $config->facebook_monitor_objects[$post->target_feed]["url"]
            );
        } # end foreach

        if( ! empty($post->metadata->tipback_data->coin_name) )
        {
            $post->metadata->tipback_data->coin_image
                = $config->current_tipping_provider_data["per_coin_data"][$post->metadata->tipback_data->coin_name]["coin_image"];
            $post->metadata->tipback_data->coin_name_plural
                = $config->current_tipping_provider_data["per_coin_data"][$post->metadata->tipback_data->coin_name]["coin_name_plural"];
        } # end if

        if( ! empty($post->metadata->content_mentions) )
        {
            foreach($post->metadata->content_mentions as $mentioned_id_account => $mentioned_name)
            {
                $tmp = new account($mentioned_id_account);
                $post->content = str_replace($mentioned_name, "<a href='https://www.facebook.com/{$tmp->facebook_id}' target='_blank'>$mentioned_name</a>", $post->content);
            } # end if
        } # end if

        if( ! empty($post->metadata->caption_mentions) )
        {
            foreach($post->metadata->caption_mentions as $mentioned_id_account => $mentioned_name)
            {
                $tmp = new account($mentioned_id_account);
                $post->caption = str_replace($mentioned_name, "<a href='https://www.facebook.com/{$tmp->facebook_id}' target='_blank'>$mentioned_name</a>", $post->caption);
            } # end if
        } # end if

        if( empty($post->signature) )
        {
            $signature = get_pulse_user_preference("signature", $post->id_author);
            if( ! empty($signature) )
                $post->signature
                    = preg_replace( '@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@',
                                    '<a href="$1" target="_blank">$1</a>',
                                    $signature );
        } # end if

        $post->content = preg_replace('/\n+/', "\n", $post->content);
        $post->content = nl2br($post->content);

        return $post;
    } # end function
