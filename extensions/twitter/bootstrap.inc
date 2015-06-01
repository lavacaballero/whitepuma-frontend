<?php
    /**
     * Platform Extension: Twitter
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
     * Trailing vars:
     * --------------
     * @var string  $location_case                   Insertion placement
     * @var object  $config                          Configuration per-se.
     * @var boolean $is_admin
     * @var boolean $admin_impersonization_in_effect
     */

    if( ! class_exists("account_extensions") ) include "$root_url/models/account_extensions.php";

    ###################
    # Requisite check #
    ##################################################
    if( empty($config->db_tables["twitter"]) ) return;
    ##################################################

    ##################################
    # Heading main buttons rendering #
    ##############################################
    if( $location_case == "heading_main_buttons" )
    ##############################################
    {
        ?>
        <a href="<?="$basedir/extensions/twitter/?wasuuup=".md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>"
           class="buttonized">
            <span class="fa fa-twitter"></span>
            Twitter Tipping
        </a>
        <?
        return;
    } # end if

    #####################################
    # Additions to account editing page #
    ###############################################
    if( $location_case == "edit_account_addition" )
    ###############################################
    {
        global $account;
        include dirname(__FILE__) . "/form.inc";
        return;
    } # end if

    #############################
    # Additions to welcome page #
    #################################################
    if( $location_case == "welcome_page_login_area" )
    #################################################
    {
        ?>

        <!-- ================== -->
        <!-- Twitter connection -->
        <!-- ================== -->

        <div class="connect_segment twitter_connect ui-widget-content ui-corner-all">
            <h2 class="ui-widget-header ui-corner-all" style="padding: 5px; margin-top: 0;">Sign in with Twitter</h2>
            <div align="center" style="font-size: 11pt;">
                If you have a Twitter account, you can use it to sign in or create your <?=$config->app_single_word_name?> account
                and use our <a href="https://twitter.com/<?=$config->twitter_screen_name?>" target="_blank">Twitter TipBot</a>!
                <a href="<?=$config->twitter_about_page?>" target="_blank">Learn more.</a>
                <br>
                <div id="twitter_warning_to_confirm" class="ui-state-highlight message_box ui-corner-all" style="margin-top: 5px; margin-bottom: 5px; color: maroon;">
                    If you already have an account on <?=$config->app_single_word_name?> and want to link it with Twitter,
                    <b>login first with your email or Facebook</b>, then <b>edit your account</b> and link it from there <u>or you'll get a new unlinked account</u>.
                </div>
                <a href="<?=$root_url?>/extensions/twitter/connect.php?ignore_session=true&return_to=<?=urlencode((empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?wasuuup=".md5(mt_rand(1,65535)))?>"
                disabled_onclick="return confirm( $('#twitter_warning_to_confirm').text().replace(/\s+/g, ' ') + '\n\nDo you want to continue signing in with Twitter?' )"><img src="<?=$root_url?>/extensions/twitter/sign-in-with-twitter-gray.png" border="0" width="158" height="28" alt="Sign in with Twitter"></a>

            </div>
        </div><!-- /.twitter_connect -->
        <?
        return;
    } # end if

    ################################################################################
    # Extension for Websites extension on universal widget welcome page / login form
    ################################################################################
    if( $location_case == "websites_widgets_universal_index_welcome_login_form" )
    #############################################################################
    {
        global $standard_login_link, $root_url;
        $return_to = $standard_login_link;
        ?>

        <!-- Twitter sign-in extension -->
        <div style="float: right; margin-left: 5px;">
            <a href="<?=$root_url?>/extensions/twitter/connect.php?ignore_session=true&return_to=<?=urlencode($return_to)?>"><img
               src="<?=$root_url?>/extensions/twitter/sign-in-with-twitter-gray-small.png" border="0" width="79" height="25"
               alt="Sign in with Twitter"></a>
        </div>

        <?
        return;
    } # end if

    #########################################
    # Notification for the existing account #
    ##############################################################
    if( $location_case == "save_account_extensions_notification" )
    ##############################################################
    {
        global $account, $notification, $root_url;

        # Let's see if the account has a twitter tie
        $query = "select * from {$config->db_tables["twitter"]} where id_account = '{$account->id_account}'";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) > 0 )
        {
            $row = mysql_fetch_object($res);
            include "$root_url/extensions/twitter/lib/TwitterAPIExchange.php";

            $twitter_options = array(
                'oauth_access_token'        => $config->twitter_access_token,
                'oauth_access_token_secret' => $config->twitter_token_secret,
                'consumer_key'              => $config->twitter_consumer_key,
                'consumer_secret'           => $config->twitter_consumer_secret
            );
            $twitter = new TwitterAPIExchange($twitter_options);
            $response = $twitter
                        ->buildOauth("https://api.twitter.com/1.1/direct_messages/new.json", "POST")
                        ->setPostfields( array( "user_id" => $row->twitter_id
                                              , "text" => $notification
                                              ) )
                        ->performRequest();
        } # end if
        return;
    } # end if
