<?php
    /**
     * Platform Extension: Instagram / Feed activity receiver
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

    $root_url = "../..";
    if( ! is_file("$root_url/config.php") ) die("ERROR: config file not found.");
    include "$root_url/config.php";
    include "$root_url/functions.php";

    include "$root_url/models/tipping_provider.php";
    include "$root_url/models/account.php";
    session_start();
    db_connect();

    header("Content-Type: text/plain; charset=utf-8");

    ##############################
    function get_instagram_items()
    ##############################
    {
        global $config;
        $url    = $config->instagram_subscriptions_data["tags_getter_url"];
        $params = array(
            "access_token"  => $config->instagram_client_info["access_token"],
            "count"         => $config->instagram_client_info["results_per_update"]
        );
        $previous_tag_id = get_flag_value("instagram:last_read_tag_id");
        if( ! empty($previous_tag_id)    ) $params["min_tag_id"] = $previous_tag_id;
        if( ! empty($_GET["count"])      ) $params["count"]      = $_GET["count"];
        if( ! empty($_GET["min_tag_id"]) ) $params["min_tag_id"] = $_GET["min_tag_id"];
        list($res, $data) = get($url, $params);
        $data = json_decode($data);
        foreach($data->data as $index => $entry)
        {
            unset(
                $data->data[$index]->attribution,
                $data->data[$index]->tags,
                $data->data[$index]->type,
                $data->data[$index]->location,
                $data->data[$index]->comments,
                $data->data[$index]->filter,
                $data->data[$index]->likes,
                $data->data[$index]->users_in_photo,
                $data->data[$index]->caption,
                $data->data[$index]->user_has_liked
            );
        } # end foreach
        return $data;
    } # end function

    ############################################
    function process_instagram_items($data = "")
    ############################################
    {
        global $config;

        $date = date("Y-m-d H:i:s");
        if( empty($data) ) $data = get_instagram_items();
        foreach($data->data as $index => $entry)
        {
            $query = "
                insert into {$config->db_tables["instagram_items"]} set
                `item_id`         = '{$entry->id}',
                `author_id`       = '{$entry->user->id}',
                `author_username` = '{$entry->user->username}',
                `monitor_start`   = '$date',
                `link`            = '{$entry->link}',
                `thumbnail`       = '{$entry->images->thumbnail->url}'
            ";
            mysql_query($query);
            if( $_GET["process"] == "true" ) echo $query;
        } # end foreach

        # Let's update the data for the next call
        if( ! empty($data->pagination->min_tag_id) )
            set_flag_value("instagram:last_read_tag_id", $data->pagination->min_tag_id);
    } # end function

    ################################################
    if( $_GET["mode"] == "setup_hashtag_listening" )
    ################################################
    {
        $user_id = get_online_user_id();
        if( empty($user_id) ) throw_fake_401();
        $account  = new account($user_id);
        $is_admin = isset($config->sysadmins[$account->id_account]);
        if( ! $is_admin )  throw_fake_401();

        $url = $config->instagram_subscriptions_data["maker"];
        $params = array(
            "client_id"     => $config->instagram_client_info["client_id"],
            "client_secret" => $config->instagram_client_info["client_secret"],
            "object"        => "tag",
            "aspect"        => "media",
            "verify_token"  => md5($config->cookie_encryption_key),
            "object_id"     => $config->instagram_subscriptions_data["hashtag"],
            "callback_url"  => $config->instagram_subscriptions_data["listener"]
        );
        list($res, $data) = post($url, $params);

        print_r( json_decode($data) );
        die();
    } # end if

    ###########################################
    if( $_GET["mode"] == "show_subscriptions" )
    ###########################################
    {
        $user_id = get_online_user_id();
        if( empty($user_id) ) throw_fake_401();
        $account  = new account($user_id);
        $is_admin = isset($config->sysadmins[$account->id_account]);
        if( ! $is_admin )  throw_fake_401();

        $url = $config->instagram_subscriptions_data["maker"];
        $params = array(
            "client_id"     => $config->instagram_client_info["client_id"],
            "client_secret" => $config->instagram_client_info["client_secret"]
        );
        list($res, $data) = get($url, $params);

        print_r( json_decode($data) );
        die();
    } # end if

    ############################################
    if( $_GET["mode"] == "delete_subscription" )
    ############################################
    {
        if( empty($_GET["id"]) ) die("Error: please provide a subscription id");

        $user_id = get_online_user_id();
        if( empty($user_id) ) throw_fake_401();
        $account  = new account($user_id);
        $is_admin = isset($config->sysadmins[$account->id_account]);
        if( ! $is_admin )  throw_fake_401();

        $url = $config->instagram_subscriptions_data["maker"];
        $params = array(
            "client_id"     => $config->instagram_client_info["client_id"],
            "client_secret" => $config->instagram_client_info["client_secret"],
            "id"            => $_GET["id"]
        );
        list($res, $data) = http_delete($url, $params);

        print_r( $res );
        die();
    } # end if

    ##################################
    if( $_GET["mode"] == "get_items" )
    ##################################
    {
        $user_id = get_online_user_id();
        if( empty($user_id) ) throw_fake_401();
        $account  = new account($user_id);
        $is_admin = isset($config->sysadmins[$account->id_account]);
        if( ! $is_admin )  throw_fake_401();

        $data = get_instagram_items();
        if( $_GET["process"] == "true" ) process_instagram_items( $data );
        print_r($data);
        die();
    } # end if

    #####################################
    if( $_GET["mode"] == "get_comments" )
    #####################################
    {
        $user_id = get_online_user_id();
        if( empty($user_id) ) throw_fake_401();
        $account  = new account($user_id);
        $is_admin = isset($config->sysadmins[$account->id_account]);
        if( ! $is_admin )  throw_fake_401();

        if( empty($_GET["media_id"]) ) die("Please provide a media_id");

        $url = str_replace('{$media_id}', $_GET["media_id"], $config->instagram_subscriptions_data["comments_getter_url"]);
        $params = array(
            "access_token"  => $config->instagram_client_info["access_token"]
        );

        list($res, $data) = get($url, $params);

        print_r( json_decode($data) );
        die();
    } # end if

    ################################
    if( ! empty($_GET["hub_mode"]) )
    ################################
    {
        if( $_GET["hub_verify_token"] != md5($config->cookie_encryption_key) ) die( "ERROR: verify token invalid!" );
        die( $_GET["hub_challenge"] );
    } # end if

    #################
    # Standard flow #
    #################

    # http://stackoverflow.com/questions/20203878/instagram-real-time-updates-tag-getting-empty-data-why
    $contents = file_get_contents('php://input');
    $updates  = json_decode($contents);
    foreach($updates as $this_update)
        if( $this_update->object_id == $config->instagram_subscriptions_data["hashtag"] )
            process_instagram_items( get_instagram_items() );
?>
