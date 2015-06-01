<?php
    /**
     * Platform Extension: Websites / iframed widget / Welcome page for non-logged users
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

    parse_str($_SERVER["QUERY_STRING"], $params);
    $params["wasuuup"] = md5(mt_rand(1,65535));
    $params["mode"]    = "do_facebook_login";
    $facebook_login_link = (empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?" . http_build_query($params);
    unset($params["mode"]);
    $standard_login_link = (empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?" . http_build_query($params);

    if( empty($widget_files_rel_path) ) $widget_files_rel_path = "./";
?>
<style type="text/css">
    #supported_coins ul   { margin: 0; padding: 0; }
    #supported_coins li   { display: inline-block; margin: 5px; padding: 5px; background-color: #BBDDFF; border: 1px solid #42A0FF; border-radius: 5px; }
    #supported_coins li a { color: navy; }

    #top_banner { background: url('<?=$widget_files_rel_path?>media/coins.jpg?v=3') center center no-repeat; background-size: cover;
                  padding: 40px 0; text-align: center; font-size: 24pt; color: white; font-weight: bold;
                  text-shadow: 0px 0px 5px rgba(0, 0, 0, 1); }
</style>
<script type="text/javascript">
    ///////////////////////////////////////
    function process_login_result(response)
    ///////////////////////////////////////
    {
        if( response != 'OK' )
        {
            alert( 'There has been a problem:\n\n'
                 + response + '\n\n'
                 + 'Please try again. If you need assistance, please click the "Help & support" link on the page and post a support request.'
                 );
            return;
        } // end function

        if( $('#standard_login_form input[name=mode]').val() == 'request_standard_password_reset' )
        {
            alert( 'An email has been sent to the address you\'ve specified. Please check your inbox -and your spam box- and follow instructions.\n\n'
                 + 'If you can\'t get the email after a few minutes, please look for the "Help & support" link on and post a support request.'
                 );
            return;
        } // end if

        location.href = '<?=$standard_login_link?>';
    } // end function

    //////////////////////////////
    function check_password_lost()
    //////////////////////////////
    {
        if( $('#standard_login_form input[name=email]').val().trim() == '' )
        {
            alert( 'If you don\'t remember your password, please type your email\n'
                 + 'in the corresponding textbox and click on this button again.\n\n'
                 + 'A message will be sent to you with instructions to\n'
                 + 'reset your password.'
                 );
            return false;
        } // end if

        $('#standard_login_form input[name=mode]').val('request_standard_password_reset');
        $('#standard_login_form').submit();
    } // end function

    /////////////////////////////////////
    function do_register_facebook_login()
    /////////////////////////////////////
    {
        var access_token = FB.getAuthResponse()['accessToken'];
        location.href    = '<?=$facebook_login_link?>&fbat=' + escape(access_token);
    } // end function

    ////////////////////////////
    $(document).ready(function()
    ////////////////////////////
    {
        $('#standard_login_form').ajaxForm({
            target:  '#standard_login_result',
            success: process_login_result
        }); // end ajaxForm
    }); // end function
</script>
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&appId=<?=$config->facebook_app_id?>&version=v2.0";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>

<div class="ui-state-error message_box ui-corner-all" style="font-size: 12pt; padding: 10px; text-align: center; font-weight: bold;">
   <span class="fa fa-warning"></span>
   Please login with your Email or Facebook/Twitter account to continue
</div>

<div id="standard_login_result" style="display: none;"></div>

<div id="login_form" class="ui-state-active message_box ui-corner-all" style="margin-top: 0;">

    <div style="float: right; margin-left: 5px;">
        <div class="fb-login-button" data-max-rows="1" data-size="large" data-show-faces="false" data-auto-logout-link="false"
             onlogin="do_register_facebook_login()" data-scope="<?=$config->facebook_auth_scope?>" title="Connect/login with your Facebook account">Connect</div>
    </div>

    <? load_extensions("websites_widgets_universal_index_welcome_login_form", $root_url); ?>

    <form name="standard_login_form" id="standard_login_form" method="post" style="display: inline;"
          action="<?=$root_url?>/toolbox.php?wasuuup=<?=md5(mt_rand(1,65535))?>">
        <input type="hidden" name="mode" value="do_standard_login">

        <div style="display: inline-block">
            <span class="fa fa-envelope-o"></span>
            <input type="text" name="email" style="width: 150px;" title="Enter your email" placeholder="Email">
        </div>

        <div style="display: inline-block">
            <span class="fa fa-key"></span>
            <input type="password" name="password" style="width: 100px;" title="Enter your password" placeholder="Password">
        </div>

        <button type="submit">
            <span class="fa fa-sign-in"></span>
            Login
        </button>

        <button type="button" onclick="check_password_lost(); return false;" title="Request a password reset">
            Password
            <span class="fa fa-question"></span>
        </button>

    </form>

</div><!-- /#login_form -->

<div id="top_banner">
    Tip, donate, send, pay<br>
    with <b>up to <?=count($config->tipping_providers_database["wpnet_fbtipbot"]["per_coin_data"])?></b> cryptocurrencies<br>
    in <u>one single</u> order
</div>

<div align="center">

    <p>
        <? if( substr($website->public_key, 0, 3) == "lj." ): ?>
            <? $website_owner = new account($website->id_account); ?>
            <?=$website_owner->name?> is requesting you to send cryptocurrencies through <a href="<?=$config->powered_by_link?>" target="_blank"><?=$config->app_powered_by?>.</a>
        <? else: ?>
            <?=$website->name?> is requesting you to send cryptocurrencies through <a href="<?=$config->powered_by_link?>" target="_blank"><?=$config->app_powered_by?>.</a>
        <? endif; ?>
        You need to have an active account to continue.
    </p>

    <p style="font-size: 10pt;"><u>Tip</u>, <u>pay for services</u> and <u>purchase goods</u>
    with <?=$config->contents_supported_coin_names_text?> by registering on the
    <a href="<?=$config->website_pages["about"]?>" target="_blank"><?=$config->app_display_shortname?></a> and
    depositing coins on the wallets automatically made for you.<br>
    <u>Accept tips, donations or payments</u> on <b>your own website</b> with our
    <a href="<?=$config->website_pages["buttons_widget_page"]?>" target="_blank"><?=$config->buttons_buttonizer_rootname?></a>!</p>

    <a class="ui-state-default message_box ui-corner-all"
       style="font-size: 14pt; display: block; margin: 20px; margin-bottom: 0; padding: 10px; text-align: center; text-decoration: none;"
       onmouseover="$(this).toggleClass('ui-state-highlight')" onmouseout="$(this).toggleClass('ui-state-highlight')"
       href="<?=$config->website_pages["about"]?>" target="_blank">
       Register now your <u>free</u> account
    </a>

</div>
