<?
    /**
     * Pulse toolbox
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
     * @returns string Requested info or error message in human-readable format.
     */

    $root_url = "../..";
    if( ! is_file("$root_url/config.php") ) die("ERROR: config file not found.");
    include "$root_url/config.php";
    include "$root_url/functions.php";
    include "$root_url/models/tipping_provider.php";
    include "$root_url/models/account.php";
    include "model_post.php";
    include "html2text.php";
    include_once "functions.inc";

    header("Content-Type: text/plain; charset=utf-8");
    # if( empty($_REQUEST["mode"]) ) die("ERROR: operating mode not provided.");

    db_connect();

    #########################################
    if( $_REQUEST["mode"] == "get_comments" )
    #########################################
    {
        if( empty($_GET["parent_post"]) ) die("{message: 'Error: no parent post specified'}");

        $post = new pulse_post($_GET["parent_post"]);
        if( ! $post->exists ) die("{message: 'Error: parent post not found in database.'}");

        if( ! empty($_GET["since"]) && ! is_numeric($_GET["since"]) ) die("{message: 'Error: \'since\' parameter is not a unix timestamp.'}");

        $created_addition = empty($_GET["since"]) ? ""
                          : "and created > '".date("Y-m-d H:i:s", $_GET["since"])."'";
        $comments = array();
        $query = "
            select * from {$config->db_tables["pulse_comments"]}
            where parent_post = '{$post->id}' and hidden = 0
            $created_addition
            order by created desc
        ";
        $res = mysql_query($query);
        while($row = mysql_fetch_object($res)) $comments[] = $row;
        mysql_free_result($res);
        $comments = convert_comments($comments);
        die(json_encode(array(
            "message" => "OK",
            "comments" => $comments
        )));
    } # end if

    ########################################
    if( $_REQUEST["mode"] == "get_updates" )
    ########################################
    {
        header("Content-Type: application/json; charset=utf-8");
        $new_timestamp = time();

        if( ! empty($_GET["since"]) && is_numeric($_GET["since"]) )
        {
            $since_date          =
            $comments_since_date = date("Y-m-d H:i:s", $_GET["since"]);
            $limit               = "";
        }
        else
        {
            $since_date          = "2014-01-01 00:00:00";
            $comments_since_date = date("Y-m-d H:i:s");
            $limit               = "limit 8";
        } # end if

        if( empty($_GET["post"]) )
        {
            $until_date  = empty($_GET["until_date"]) ? date("Y-m-d H:i:s", time()+1) : $_GET["until_date"];
            $coin_filter = empty($_GET["base_coin_filter"]) ? "" : "and target_coin = '{$_GET["base_coin_filter"]}'";
            $query = "
                select * from {$config->db_tables["pulse_posts"]}
                where hidden = 0
                and last_update > '$since_date'
                and last_update < '$until_date'
                $coin_filter
                order by last_update desc
                $limit
            ";
            $res = mysql_query($query);
            if( mysql_num_rows($res) < 8 && $_GET["allow_fallback_uptades"] )
            {
                $query = "
                    select * from {$config->db_tables["pulse_posts"]}
                    where hidden = 0
                    $coin_filter
                    order by last_update desc
                    limit 8
                ";
                $res = mysql_query($query);
            } # end if
        }
        else # post being passed
        {
            if( empty($_GET["single_post_rendered"]) )
                $query = "
                    select * from {$config->db_tables["pulse_posts"]}
                    where id = '{$_GET["post"]}'
                    and hidden = 0
                ";
            else
                $query = "
                    select * from {$config->db_tables["pulse_posts"]}
                    where id = '_null_'
                ";
            $res = mysql_query($query);
        } # end if

        $new_posts = $new_post_ids = array();
        if( mysql_num_rows($res) > 0 )
        {
            while($row = mysql_fetch_object($res))
            {
                $post           = new pulse_post($row);
                $post           = convert_post($post);
                $new_posts[]    = clone $post;
                $new_post_ids[] = "'{$post->id}'";
            } # end while
        } # end if
        mysql_free_result( $res );

        $new_comments = array();
        if( ! empty($_GET["only_posts"]) )
        {
            if( empty($_GET["post"]) )
            {
                $query = "
                    select * from {$config->db_tables["pulse_comments"]}
                    where hidden = 0
                    and   created >= '$comments_since_date'
                    order by created desc
                ";
            }
            else
            {
                $query = "
                    select * from {$config->db_tables["pulse_comments"]}
                    where parent_post = '{$_GET["post"]}'
                    and   hidden = 0
                    and   created >= '$comments_since_date'
                    order by created desc
                ";
            } # end if

            $res = mysql_query($query);
            while($row = mysql_fetch_object($res)) $new_comments[] = $row;
            mysql_free_result($res);
            $new_comments = convert_comments($new_comments);
        } # end if

        $return = (object) array(
            "message"        => "OK",
            "posts"          => $new_posts,
            "comments"       => $new_comments,
            "last_timestamp" => $new_timestamp
        );
        die( json_encode($return) );
    } # end if

    ##############################
    if( ! empty($_REQUEST["go"]) )
    ##############################
    {
        header("Location: {$_REQUEST["go"]}");
        die("<a href='{$_REQUEST["go"]}'>Click here to continue...</a>");
    } # end if

    include "$root_url/facebook-php-sdk/facebook.php";
    $fb_params = array(
        'appId'              => $config->facebook_app_id,
        'secret'             => $config->facebook_app_secret,
        'fileUpload'         => false,
        'allowSignedRequest' => true
    );
    $facebook = new Facebook($fb_params);

    $user_id = get_online_user_id(true);
    if( empty($user_id) ) die("ERROR: can't get your user id! Please login!");

    $account = new account($user_id);
    if( ! $account->exists ) die("ERROR: Account doesn't exist in DB! (App not authorized? Please authorize it!)");

    $is_admin = isset($config->sysadmins[$account->id_account]);

    #######################################
    if( $_REQUEST["mode"] == "find_users" )
    #######################################
    {
        header("Content-Type: application/json; charset=utf-8");

        if( empty($_REQUEST["q"]) ) die("{ message: 'ERROR: Please provide something to search.' }");

        $query = "
            select * from ".$config->db_tables["account"]."
            where name like '%{$_REQUEST["q"]}%'
            and   id_account <> '{$account->id_account}'
            order by name asc, date_created asc
        ";
        $res = mysql_query($query);
        if( mysql_num_rows($res) == 0 )  die("[]");

        $return = array();

        /** @var account */
        while( $row = mysql_fetch_object($res) )
        {
            $return[] = array(
                "id"     => $row->id_account,
                "name"   => $row->name,
                "avatar" => "https://graph.facebook.com/{$row->facebook_id}/picture",
                "type"   => "contact"
            );
        } # end if

        mysql_free_result($res);
        die( json_encode($return) );
    } # end if

    ####################################################
    if( $_REQUEST["mode"] == "find_tipping_recipients" )
    ####################################################
    {
        header("Content-Type: text/html; charset=utf-8");

        if( empty($_REQUEST["q"]) ) die("Type something to search.");

        $query = "
            select * from ".$config->db_tables["account"]."
            where name like '%{$_REQUEST["q"]}%'
            and   id_account <> '{$account->id_account}'
            order by name asc, date_created asc
        ";
        $res = mysql_query($query);
        if( mysql_num_rows($res) == 0 )  die("No users found.");

        $return = array();

        /** @var account */
        while( $row = mysql_fetch_object($res) )
            $return[] = "<span class='item' id_account='{$row->id_account}'>"
                      . "<img src='https://graph.facebook.com/{$row->facebook_id}/picture'>{$row->name}"
                      . "</span>";

        mysql_free_result($res);
        die( implode("\n", $return) );
    } # end if

    ###########################################
    if( $_REQUEST["mode"] == "grab_page_data" )
    ###########################################
    {
        if( trim(stripslashes($_GET["url"]))  == "" )
            die("ERROR: Please specify a URL to get data from.");
        if( filter_var(trim(stripslashes($_GET["url"])), FILTER_VALIDATE_URL) === false )
            die("ERROR: Please specify a valid URL to get data from.");

        $return = (object) array(
            "title"       => "",
            "description" => "",
            "image"       => ""
        );

        # http://stackoverflow.com/questions/3711357/get-title-and-meta-tags-of-external-site/3711554#3711554

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HEADER,         0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL,            trim(stripslashes($_GET["url"])));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $html = curl_exec($ch);
        if( curl_error($ch) ) die("{ message: '".addslashes(curl_error($ch))."' }");
        curl_close($ch);

        $doc = new DOMDocument();
        @$doc->loadHTML($html);

        $metas = $doc->getElementsByTagName('meta');

        # First round
        for ($i = 0; $i < $metas->length; $i++)
        {
            $meta = $metas->item($i);
            if($meta->getAttribute('property') == 'og:title')
                $return->title = trim($meta->getAttribute('content'));
            if($meta->getAttribute('property') == 'og:description')
                $return->description = trim($meta->getAttribute('content'));
            if($meta->getAttribute('property') == 'og:image')
                $return->image = trim($meta->getAttribute('content'));
        } # end if

        # Second round
        for ($i = 0; $i < $metas->length; $i++)
        {
            $meta = $metas->item($i);
            if(empty($return->description) && $meta->getAttribute('name') == 'description')
                $return->description = trim($meta->getAttribute('content'));
        } # end if

        # Final title check
        if( empty($return->title) )
        {
            $nodes = $doc->getElementsByTagName('title');
            $return->title = trim($nodes->item(0)->nodeValue);
        } # end if

        # Final description check
        if( empty($return->description) )
        {
            # $text = convert_html_to_text($html);
            $text = trim($html);
            $text = preg_replace('#<style.*/style>#',   ' ',  $text);
            $text = preg_replace('#<script.*/script>#', ' ',  $text);

            $text = strip_tags($text, "<p><b><a><i><div><span>");
            $text = convert_html_to_text($html);
            $text = preg_replace('/\r?\n\s*/',          ' ',  $text);
            $text = preg_replace('/\s+/',               ' ',  $text);
            $text = substr($text, 0, 255);
            $text = substr($text, 0, strrpos($text, " ") - 1);
            $return->description = $text;
        } # end if

        die( "OK\t" . implode("\t", (array) $return) );
    } # end if

    #########################################
    if( $_REQUEST["mode"] == "receive_post" )
    #########################################
    {
        # Per case validations
        switch( $_POST["type"] )
        {

            case "text":
                if( trim(stripslashes($_POST["content_text"]))  == "" )
                    die("Please type something to post!");
                break;

            case "link":
                if( trim(stripslashes($_POST["link"]))  == "" )
                    die("Please specify a URL to post.");
                if( filter_var(trim(stripslashes($_POST["link"])), FILTER_VALIDATE_URL) === false )
                    die("Please specify a valid URL, starting with http:// or https://");
                if( trim(stripslashes($_POST["caption_text"]))  == "" )
                    die("Please type a caption (title) for the link.");
                if( ! empty($_POST["tipback_coin"]) )
                {
                    if( ! is_numeric($_POST["tipback_users"]) && ! is_numeric($_POST["tipback_coins"]) )
                        die("If you set tipback data for the link, you must specify the size of the tip and a minimum amount of unique users to tip.");
                    if( empty($_POST["tipback_users"]) ) die("Please set a valid amount of unique users to tipback.");
                    if( empty($_POST["tipback_coins"]) ) die("Please set a valid amount of coins to give to users.");
                    $coin_name        = $_POST["tipback_coin"];
                    $specified_amount = $_POST["tipback_coins"];
                    $coin_minimum     = $config->current_tipping_provider_data["per_coin_data"][$coin_name]["min_transaction_amount"];
                    if( $specified_amount < $coin_minimum )
                        die("You must set a tip size of at least ".$coin_minimum);
                } # end if
                break;

            case "photo":
                if( empty($_FILES["photo_file"]) )
                    die("Please select a file to upload and make sure it is GIF, JPEG or PNG.");
                break;

            case "video":
                if( trim(stripslashes($_POST["link"]))  == "" )
                    die("Please specify the URL of a YouTube-hosted video.");
                if( filter_var(trim(stripslashes($_POST["link"])), FILTER_VALIDATE_URL) === false )
                    die("Please specify a valid URL, starting with http:// or https://");
                if( preg_match('#(youtu.be|youtube.com)/#', trim(stripslashes($_POST["link"]))) == 0 )
                    die("Please specify the URL of a YouTube-hosted video.");
                $_POST["content_text"] = "";
                break;

            # end cases

        } # end switch

        # Extra validations
        if( ! empty($_FILES["photo_file"]) )
        {
            if( ! is_uploaded_file($_FILES['photo_file']['tmp_name']) )
                die("Upload of file failed. Please try again.");
            if( ! in_array($_FILES["photo_file"]["type"], array("image/png", "image/gif", "image/jpeg")) )
                die("Please upload a GIF, JPEG or PNG image.");
            if( filesize($_FILES['photo_file']['tmp_name']) == 0 )
                die("You've uploaded an empty file. Your browser may be having issues. Please try with another browser.");
            $target_dir = "$root_url/pulse_files/" . $account->id_account;
            if( ! is_dir($target_dir) ) { @mkdir($target_dir); @chmod($target_dir, 0777); }
            list($image, $extension) = explode("/", $_FILES["photo_file"]["type"]);
            if($extension == "jpeg") $extension = "jpg";
            $filename  = $_FILES["photo_file"]["name"];
            $parts     = explode(".", $filename); array_pop($parts);
            $filename  = implode(".", $parts);
            $filename  = normalize_filename($filename);
            $filename .= "." . date("YmdHis") . ".$extension";
            move_uploaded_file($_FILES['photo_file']['tmp_name'], "$target_dir/$filename");
            @chmod("$target_dir/$filename", 0777);
            $_POST["photo_url"] = "local://{$account->id_account}/".urlencode($filename);
        } # end if
        if( ! empty($_POST["attached_tip_recipients"]) )
        {
            if( empty($_POST["attached_tip_coin"]) ) die("Please select a coin to tip for the recipients.");
            if( empty($_POST["attached_tip_size"]) ) die("Please specify the amount of coins to tip.");
            if( ! is_numeric($_POST["attached_tip_size"]) ) die("Please specify a valid amount of coins to tip.");

            $coin_data = $config->current_tipping_provider_data["per_coin_data"][$_POST["attached_tip_coin"]];
            if( ! isset($coin_data) ) die("The coin you specified doesn't exist.");
            if( $coin_data["coin_disabled"] ) die("The coin you specified is currently disabled.");
            if( $_POST["attached_tip_size"] < $coin_data["min_transaction_amount"] ) die("Please specify a tip size of at least {$coin_data["min_transaction_amount"]} {$coin_data["coin_sign"]}");
        } # end if

        $post = new pulse_post();
        $post->assign_from_posted_form();

        # Pre-assignments for FB posting
        $post_content = $post->content;
        $post_caption = $post->caption;

        # Additions for mobile support
        if( ! empty($_POST["attached_tip_recipients"]) )
        {
            $attached_recipients = array();
            $incoming_recipients = (array) json_decode(stripslashes($_POST["attached_tip_recipients"]));
            foreach( $incoming_recipients as $this_recipient ) $attached_recipients[$this_recipient->id_account] = $this_recipient->name;
            $post->content .= "\nGive {$_POST["attached_tip_size"]} {$coin_data["coin_name_plural"]} to " . implode(", ", $attached_recipients);
            if( empty($post->metadata->content_mentions) )
                $post->metadata->content_mentions = $attached_recipients;
            else
                $post->metadata->content_mentions = array_merge($post->metadata->content_mentions, $attached_recipients);
        } # end if

        # Da-save!
        $post->save();

        # Post to Facebook?
        $post_to_fb_response = "";
        if( ! empty($_POST["target_feed"]) && ! empty($_POST["post_to_facebook"]) )
        {
            $group_fb_id = $config->facebook_monitor_objects[$_POST["target_feed"]]["id"];
            $name        = empty($post_caption) ? $post_content : $post_caption;
            $description = empty($post_caption) ? "{$config->app_display_shortname} Community Pulse" : $post_content;
            if( empty($description) ) $description = "{$config->app_display_shortname} Community Pulse";

            if( $post->type == "video" )
            {
                $message_params = array(
                    "message"      => $name,
                    "link"         => $post->link
                );
            }
            else
            {
                $message_params = array(
                    "link"         => "{$config->facebook_canvas_page}?mode=show_pulse_post&post={$post->id}",
                    "picture"      => str_replace("local://", "{$config->website_pages["root_url"]}pulse_files/", $post->picture),
                    "name"         => $name,
                    "description"  => $description,
                    "caption"      => $config->app_root_domain,
                );
            } # end if

            try
            {
                $access_token = $facebook->getAccessToken();
                $facebook->setExtendedAccessToken();

                $ret = $facebook->api("/$group_fb_id/feed", 'POST', $message_params);
                list($gid, $pid) = explode("_", $ret["id"]);
                sleep(1);
                $ret = $facebook->api("/{$account->facebook_id}/feed", 'POST', $message_params);
                $post_to_fb_response = "OK";
            }
            catch(Exception $e)
            {
                $post_comment = false;
                $post_to_fb_response = "Can't post notification to the group! Facebook says: " . $e->getMessage() . ".\n"
                                     . "You need to authorize the app to post on your name before publishing to Facebook.\n"
                                     . "Please hit the '(Re)authorize now' button the next time.";
            } # end try...catch
        } # end if

        # Let's save the signature (if any provided)
        if( ! empty($_POST["signature"]) )
            set_pulse_user_preference("signature", $signature);

        # Let's send notifications to tagged users
        $mentions_list = array();
        if( ! empty($post->metadata->caption_mentions) ) $mentions_list = array_merge($mentions_list, $post->metadata->caption_mentions);
        if( ! empty($post->metadata->content_mentions) ) $mentions_list = array_merge($mentions_list, $post->metadata->content_mentions);
        header("X-Notification-mentions-count: ".count($mentions_list));
        if( ! empty($mentions_list) )
        {
            $message_addition = empty($post->content) ? $post->caption : $post->content;
            if( ! empty($message_addition) ) $message_addition = ": $message_addition";
            $c = 1;
            foreach($mentions_list as $mentioned_id_account => $mentioned_name)
            {
                if( $mentioned_id_account == $post->id_author ) continue;
                $message = substr("@[{$account->facebook_id}] has mentioned you on a Pulse {$post->type}{$message_addition}", 0, 180);
                $link    = "?mode=show_pulse_post&post={$post->id}";
                $res = send_notification($mentioned_id_account, $message, $link);
                header("X-Notification-To-Mention-{$c}-Result: $res");
                $c++;
            } # end foreach
        } # end if

        die("OK|$post_to_fb_response");
    } # end if

    ############################################
    if( $_REQUEST["mode"] == "receive_comment" )
    ############################################
    {
        if( empty($_POST["content_text"]) && empty($_FILES["photo_file"]) )
            die("Please type a comment or upload a reaction image!");

        $post = new pulse_post($_POST["parent_post"]);
        if( ! $post->exists ) die("Post id {$_POST["parent_post"]} doesn't exist.");

        if( ! empty($_FILES["photo_file"]) )
        {
            if( ! is_uploaded_file($_FILES['photo_file']['tmp_name']) )
                die("Upload of file failed. Please try again.");
            if( ! in_array($_FILES["photo_file"]["type"], array("image/png", "image/gif", "image/jpeg")) )
                die("Please upload a GIF, JPEG or PNG image.");
            if( filesize($_FILES['photo_file']['tmp_name']) == 0 )
                die("You've uploaded an empty file. Your browser may be having issues. Please try with another browser.");
            $target_dir = "$root_url/pulse_files/" . $account->id_account;
            if( ! is_dir($target_dir) ) { @mkdir($target_dir); @chmod($target_dir, 0777); }
            list($image, $extension) = explode("/", $_FILES["photo_file"]["type"]);
            if($extension == "jpeg") $extension = "jpg";
            $filename  = $_FILES["photo_file"]["name"];
            $parts     = explode(".", $filename); array_pop($parts);
            $filename  = implode(".", $parts);
            $filename  = normalize_filename($filename);
            $filename .= "." . date("YmdHis") . ".$extension";
            move_uploaded_file($_FILES['photo_file']['tmp_name'], "$target_dir/$filename");
            @chmod("$target_dir/$filename", 0777);
            $_POST["photo_url"] = "local://{$account->id_account}/".urlencode($filename);
        } # end if

        $_POST["content_text"] = addslashes(strip_tags(stripslashes($_POST["content_text"])));

        # Let's check if the comment is not repeated
        $query = "
            select content from {$config->db_tables["pulse_comments"]}
            where content     = '{$_POST["content_text"]}'
            and   parent_post = '{$_POST["parent_post"]}'
            and   id_author   = '{$account->id_account}'
            and   created     > '".date("Y-m-d H:i:s", time() - 60)."'
            order by created desc
            limit 1
        ";
        $res = mysql_query($query);
        if( mysql_num_rows($res) )
            die("You already sent that message within the last minute.");

        $metadata = (object) array();
        if( ! empty($_POST["content_mentions"]) )
        {
            $content_mentions = json_decode(stripslashes($_POST["content_mentions"]));
            $new_mentions = array();
            foreach($content_mentions as $this_mention)
                $new_mentions[$this_mention->id] = $this_mention->name;
            $metadata->mentions = $new_mentions;
        } # end if

        $id_comment = uniqid(true);
        $query = "
            insert into {$config->db_tables["pulse_comments"]} set
            created     = '".date("Y-m-d H:i:s")."',
            id          = '{$id_comment}',
            parent_post = '{$_POST["parent_post"]}',
            id_author   = '{$account->id_account}',
            content     = '{$_POST["content_text"]}',
            picture     = '{$_POST["photo_url"]}',
            metadata    = '".json_encode($metadata)."'
        ";
        mysql_query($query);
        $post->bump();

        # Let's notify the post author
        if( $post->id_author != $account->id_account )
        {
            $message = substr("@[{$account->facebook_id}] has commented on your Pulse {$post->type}: " . stripslashes($_POST["content_text"]), 0, 180);
            $link    = "?mode=show_pulse_post&post={$post->id}&comment={$id_comment}";
            $res = send_notification($post->id_author, $message, $link);
            header("X-Notification-To-Author-Result: $res");
        } # end if

        # Let's send notifications to tagged users of the comment
        if( ! empty($metadata->mentions) )
        {
            $c = 1;
            foreach($metadata->mentions as $mentioned_id_account => $mentioned_name)
            {
                if( $mentioned_id_account == $post->id_author ) continue;
                $message = substr("@[{$account->facebook_id}] has mentioned you on a Pulse comment: " . stripslashes($_POST["content_text"]), 0, 180);
                $link    = "?mode=show_pulse_post&post={$post->id}&comment={$id_comment}";
                $res = send_notification($mentioned_id_account, $message, $link);
                header("X-Notification-To-Mention-{$c}-Result: $res");
                $c++;
            } # end foreach
        } # end if

        # Now let's notify followers of the thread
        $users_to_notify = array();
        $query = "
            select distinct id_author from {$config->db_tables["pulse_comments"]}
            where parent_post = '{$post->id}'
            and   hidden = 0
            order by created asc
        ";
        # header("X-Notification-To-Thread-Followers-Query: ".str_replace("\n", " ", $query));
        $res = mysql_query($query);
        if( mysql_num_rows($res) )
        {
            header("X-Notification-To-Thread-Followers-Count: ".mysql_num_rows($res));
            $c = 1;
            while($row = mysql_fetch_object($res))
            {
                if( isset($metadata->mentions[$row->id_author]) ) continue;
                if( $row->id_author == $post->id_author ) continue;
                if( $row->id_author == $account->id_account ) continue;

                $message = substr("@[{$account->facebook_id}] has commented on a Pulse {$post->type} you're following: " . stripslashes($_POST["content_text"]), 0, 180);
                $link    = "?mode=show_pulse_post&post={$post->id}&comment={$id_comment}";
                $res2 = send_notification($row->id_author, $message, $link);
                header("X-Notification-To-Thread-Follower-{$c}-Result: $res2");
                $c++;
            } # end while
        } # end if
        mysql_free_result($res);

        die("OK");
    } # end if

    die("ERROR: Invalid method call");
