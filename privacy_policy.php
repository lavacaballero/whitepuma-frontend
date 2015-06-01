<?
    /**
     * Privacy Policy template
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

    $root_url = ".";
    if( ! is_file("config.php") ) die("ERROR: config file not found.");
    include "config.php";

    header("Content-Type: text/html; charset=utf-8");

    if($admin_impersonization_in_effect)
    {
        $jquery_ui_theme = $config->jquery_ui_theme_for_admin_impersonation;
        $title_append = "Privacy Policy";
    }
    else
    {
        if( $config->user_home_shows_by_default == "multicoin_dashboard" )
        {
            $jquery_ui_theme = $config->user_home_jquery_ui_theme;
        }
        else
        {
            if( $session_from_cookie ) $jquery_ui_theme = $config->current_coin_data["jquery_ui_theme_for_alternate_login"];
            else                       $jquery_ui_theme = $config->current_coin_data["jquery_ui_theme"];
        } # end if
        $title_append = "Privacy Policy";
    } # end if
    if( ! $config->engine_enabled ) $jquery_ui_theme = $config->engine_disabled_ui_theme;
?>
<html xmlns:fb="http://ogp.me/ns/fb#">
    <head>
        <title><?=$config->app_display_longname?> - <?=$title_append?></title>
        <meta name="viewport"                       content="width=device-width" />
        <meta http-equiv="X-UA-Compatible"          content="IE=Edge" />
        <link rel="icon"                            href="<?= $config->favicon ?>">
        <link rel="shortcut icon"                   href="<?= $config->favicon ?>">

        <? if( ! empty($config->google_analytics_id) ): ?>
            <!-- Google Analytics -->
            <script>
              (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
              (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
              m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
              })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
              ga('create', '<?=$config->google_analytics_id?>', 'auto');
              ga('send', 'pageview');
            </script>
        <? endif; ?>

        <script                                     src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
        <link rel="stylesheet" type="text/css"      href="//code.jquery.com/ui/1.10.4/themes/<?=$jquery_ui_theme?>/jquery-ui.css">
        <script                                     src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
        <link rel="stylesheet" type="text/css"      href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
        <script type="text/javascript"              src="<?=$config->commons_url?>/lib/jquery.blockUI.js"></script>
        <link rel="stylesheet" type="text/css"      href="misc/styles.css?v=<?=$config->scripts_and_styles_version?>">
        <style type="text/css">
            <?= $config->current_coin_data["body_font_definition"] ?>
            <?= $config->current_coin_data["ui_font_definition"] ?>
            .coin_signed:after { content: ' <?=$config->current_coin_data["coin_name_plural"]?>'; }
        </style>
        <script type="text/javascript"              src="misc/functions.js?v=<?=$config->scripts_and_styles_version?>"></script>
        <script type="text/javascript"              src="<?=$config->commons_url?>/lib/jquery.timeago.js"></script>
        <script type="text/javascript">$(function() { $( "#tabs" ).tabs(); $('.tablesorter').tablesorter(); $('.timeago').timeago(); }); </script>
        <style type="text/css">
            pre { font-family: 'Lucida Console', 'Courier New', Courier, monospace; font-size: 10pt; padding: 10px; }
        </style>
    </head>
    <body>

        <div id="fb-root"></div>
        <script>
          window.fbAsyncInit = function() {
              FB.init({
                appId      : '<?= $config->facebook_app_id ?>',
                version    : 'v2.0',
                status     : true,
                cookie     : true,
                xfbml      : true  // parse XFBML
              });
              FB.Canvas.setAutoGrow();
          };

          (function(d, s, id){
             var js, fjs = d.getElementsByTagName(s)[0];
             if (d.getElementById(id)) {return;}
             js = d.createElement(s); js.id = id;
             js.src = "//connect.facebook.net/en_US/sdk.js";
             fjs.parentNode.insertBefore(js, fjs);
           }(document, 'script', 'facebook-jssdk'));
        </script>

        <h1>
            <?=$config->app_display_longname?> v<?=$config->app_version?> ~ Privacy Policy
            <button onclick="location.href = 'index.php?wasuuup=<?=md5(mt_rand(1,65535))?>';">
                <span class="ui-icon embedded ui-icon-arrowreturnthick-1-w"></span>
                Back to Dashboard
            </button>
        </h1>

        <div class="links_bar ui-widget-content ui-corner-all" style="text-align: right;">
            <div style="float: left;">
                <fb:like size="large" href="<?= $config->fb_like_button_link ?>" layout="button_count" action="like" width="100%" show_faces="true" share="true"></fb:like>
                <a href="https://twitter.com/share" class="twitter-share-button" data-url="<?= $config->twitter_tweet_button_link ?>" data-via="whitepuma_net"
                   data-text="<?=$config->twitter_tweet_button_text?>">Tweet</a>
                <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>
            </div>

            <a                 href="<?="$root_url/"?>changelog.php"><span class="ui-icon embedded ui-icon-note"></span>Changelog</a>
            <a target="_blank" href="<?=$config->website_pages["about"]?>"><span class="ui-icon embedded ui-icon-info"></span>About...</a>
            <a target="_top"   href="<?= $config->facebook_app_page ?>"><span class="ui-icon embedded ui-icon-document"></span>News page</a>
            <?if( ! empty($config->facebook_app_group) ): ?>
                <a target="_top" href="<?= $config->facebook_app_group ?>"><span class="ui-icon embedded ui-icon-heart"></span>Tipping Group</a>
            <? endif; ?>
            <a target="_blank" href="<?=$config->website_pages["terms_of_service"]?>"><span class="ui-icon embedded ui-icon-script"></span>TOS</a>
            <a target="_blank" href="<?=$config->website_pages["privacy_policy"]?>"><span class="ui-icon embedded ui-icon-key"></span>Privacy Policy</a>
            <a target="_blank" href="<?=$config->website_pages["faq"]?>"><span class="ui-icon embedded ui-icon-star"></span>FAQ</a>
            <a target="_blank" href="<?=$config->website_pages["support"]?>"><span class="ui-icon embedded ui-icon-help"></span>Help &amp; Support</a>
            &nbsp;
        </div><!-- /.links_bar -->

        <p>
        <strong>What information do we collect?</strong>
        </p><p>
        We collect information from you when you register on our website either directly or through our different applications and Application Programming Interfaces.
        </p><p>
        When registering on our website, as appropriate, you may be asked to enter your: name or e-mail address. You may, however, visit our site anonymously. But by using our online services, you're asked to either enter an e-mail address and a password among other information pieces or connect any account you have with third party services.
        </p><p>
        Google, as a third party vendor, uses cookies to serve ads on our site. Google's use of the DART cookie enables it to serve ads to our users based on their visit to our site and other sites on the Internet. Users may opt out of the use of the DART cookie by visiting the Google ad and content network privacy policy..
        </p><p>
        <strong>What do we use your information for?</strong>
        </p><p>
        Any of the information we collect from you may be used in one of the following ways:
        </p><p>
        ; To identify you as user
        </p><p>
        ; To personalize your experience
        (your information helps us to better respond to your individual needs)
        </p><p>
        ; To improve customer service
        (your information helps us to more effectively respond to your customer service requests and support needs)
        </p><p>
        ; To process transactions
        </p>
        <blockquote>Your information, whether public or private, will not be sold, exchanged, transferred, or given to any other company for any reason whatsoever, without your consent, other than for the express purpose of delivering the service requested.</blockquote>
        <p>
        <strong>How do we protect your information?</strong>
        </p><p>
        We implement a variety of security measures to maintain the safety of your personal information when you place an order or enter, submit, or access your personal information.
        </p><p>
        <strong>Do we use cookies?</strong>
        </p><p>
        Yes (Cookies are small files that a site or its service provider transfers to your computers hard drive through your Web browser (if you allow) that enables the sites or service providers systems to recognize your browser and capture and remember certain information
        </p><p>
        We use cookies to understand and save your preferences for future visits and compile aggregate data about site traffic and site interaction so that we can offer better site experiences and tools in the future.
        </p><p>
        <strong>Do we disclose any information to outside parties?</strong>
        </p><p>
        We do not sell, trade, or otherwise transfer to outside parties your personally identifiable information. We may release your information when we believe release is appropriate to comply with the law, enforce our site policies, or protect ours or others rights, property, or safety.
        </p><p>
        <strong>California Online Privacy Protection Act Compliance</strong>
        </p><p>
        Because we value your privacy we have taken the necessary precautions to be in compliance with the California Online Privacy Protection Act. We therefore will not distribute your personal information to outside parties without your consent.
        </p><p>
        As part of the California Online Privacy Protection Act, all users of our site may make any changes to their information at anytime by logging into their dashboard and going to the 'Edit my Profile' page when not using a third party account connector.
        </p><p>
        <strong>Childrens Online Privacy Protection Act Compliance</strong>
        </p><p>
        We are in compliance with the requirements of COPPA (Childrens Online Privacy Protection Act), we do not collect any information from anyone under 13 years of age.
        </p><p>
        <strong>Online Privacy Policy Only</strong>
        </p><p>
        This online privacy policy applies only to information collected through our website and not to information collected offline, since we do not collect such information.
        </p><p>
        <strong>Your Consent</strong>
        </p><p>
        By using our site and/or software, you consent to our <span style="text-decoration: none; color: #3c3c3c;">online privacy policy</span>.
        </p><p>
        <strong>Changes to our Privacy Policy</strong>
        </p><p>
        If we decide to change our privacy policy, we will post those changes on this page, send an email notifying you of any changes, and/or update the Privacy Policy modification date below.
        </p><p>
        This policy was last modified on April 27, 2014.
        </p><p>
        <strong>Contacting Us</strong>
        </p><p>
        If there are any questions regarding this privacy policy you may contact us <a href="<?=$config->website_pages["support"]?>">through our support area</a>.
        </p>
    </body>
</html>
