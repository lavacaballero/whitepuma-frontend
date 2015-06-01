<?
    /**
     * Group count cache maker
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

    set_time_limit( 3600 );
    ini_set("error_reporting", E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED & ~E_WARNING );

    if( isset($_SERVER["HTTP_HOST"])) die("<h3>This script is not ment to be called through a web browser. You must invoke it through a command shell or a cron job.</h3>");
    if( ! is_file("config.php") ) die("ERROR: config file not found.");
    include "config.php";
    include "functions.php";
    include "lib/cli_helper_class.php";
    db_connect();

    #############
    # Prechecks #
    #############
    {
        if( ! $config->engine_enabled )
        {
            cli::write("\n");
            cli::write( date("Y-m-d H:i:s") . " Engine is disabled. Exiting.", cli::$forecolor_black, cli::$backcolor_red );
            cli::write("\n");
            die();
        } # end if
    } # end prechecks block

    #######################
    # Facebok SDK loading #
    #######################
    {
        include "facebook-php-sdk/facebook.php";
        $fb_params = array(
            'appId'              => $config->facebook_app_id,
            'secret'             => $config->facebook_app_secret,
            'fileUpload'         => false, // optional
            'allowSignedRequest' => true,  // optional, but should be set to false for non-canvas apps
        );

        try
        {
            $facebook = new Facebook($fb_params);
            $access_token = $facebook->getAccessToken();
            if($reset_access_token) $facebook->setExtendedAccessToken();
        }
        catch( Exception $e )
        {
            cli::write("\n");
            cli::write("Fatal error! Can't load Facebook SDK!\n", cli::$forecolor_light_red);
            cli::write("\n");
            cli::write("Exception: " . $e->getMessage() . "\n", cli::$forecolor_light_red);
            cli::write("\n");
            cli::write("Program terminated abnormally.\n");
            cli::write("\n");
            die();
        } # end try...catch
    } # end continuity block

    #############
    # Main loop #
    #############

    cli::write("\n");
    cli::write( date("Y-m-d H:i:s") . " Starting group members cache updater..." );
    cli::write("\n");

    foreach($config->facebook_monitor_objects as $key => $data)
    {
        # $xdata = array();
        try
        {
            $res = $facebook->api($data["id"]."/members?limit=20000");
            $this_group_count = count($res["data"]);
            # foreach($res["data"] as $rkey => $obj)
            #     $xdata[] = array(
            #         "id"   => $obj["id"],
            #         "name" => $obj["name"]
            #     );
        }
        catch( Exception $e )
        {
            cli::write("                    ! ". $e->getMessage() . "\n", cli::$forecolor_light_red);
            $this_group_count = "N/A";
        } # end try...catch

        # $xdata = json_encode($xdata);
        echo "                    • $key: $this_group_count\n";
        set_flag_value("group_counts:$key",  $this_group_count);
        # set_flag_value("group_members:$key", $xdata);
    } # end foreach

    cli::write( date("Y-m-d H:i:s") . " Finished." );
    cli::write("\n");
