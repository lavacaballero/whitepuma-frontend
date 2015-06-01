<?php
    /**
     * Cron Monitor launcher for Facebook Groups -- DEPRECATED
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

    chdir( dirname(__FILE__) );

    if( ! is_file("config.php") ) die("ERROR: config file not found.");
    include "config.php";

    echo "Starting launcher.\n";
    foreach($config->facebook_monitor_objects as $key => $data)
    {
        echo "- Launching monitor for $key... ";
        $cmd = "php -q cli_feed_monitor.php --object=$key >> logs/$key-".date("Ymd").".log 2>&1 &";
        shell_exec($cmd);
        echo "ok.\n";
    } # end foreach
    echo "Finished.\n";
