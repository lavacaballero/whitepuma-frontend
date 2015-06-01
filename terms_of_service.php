<?
    /**
     * Terms of service template
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
        $title_append = "Terms of Service";
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
        $title_append = "Terms of Service";
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
            <?=$config->app_display_longname?> v<?=$config->app_version?> ~ Terms of Service
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
        <strong>Introduction</strong>
        </p><p>
        1.1     These terms and conditions shall govern your use of our website and its related systems and services ("our website").
        </p><p>
        1.2     By using our website or our services, you accept these terms and conditions in full; accordingly, if you disagree with these terms and conditions or any part of these terms and conditions, you must not use our website.
        </p><p>
        1.3     If you register with our website, submit any material to our website or use any of our website services, we will ask you to expressly agree to these terms and conditions.
        </p><p>
        1.4     Our website uses cookies; by using our website or agreeing to these terms and conditions, you consent to our use of cookies in accordance with the terms of our <a title="Privacy Policy" href="<?=$config->website_pages["privacy_policy"]?>">privacy policy</a>.
        </p><p>
        1.5    The views expressed by owners, members and gests of any system (included but not limited to Facebook groups and discussion forums) where our website is referenced or our services are being enabled, are their own and shall not be construed in any way as coming from our website. We make no recommendations or endorsements for any views, services, or products mentioned in the enabled groups. Personal perspectives expressed by the producers, writers or editors are out of our reach and will always be presented as such.
        </p><p>
        1.6   This set of forgoing terms and conditions and any rules, policies and or software licenensing are subject to change without notification. you may at any time goto <a title="Terms of Service" href="<?=$config->website_pages["terms_of_service"]?>" target="_blank"><?=$config->website_pages["terms_of_service"]?></a> to see any chnages if and when they occure.
        </p><p>
        1.7 You are still hereby required to follow any changes in terms and conditions set by any third party website or service or Social Network (including but not limited to Facebook, Wordpress, etc.) and abide to them before using our website among them.
        </p><p>
        <strong>2 Credit</strong>
        </p><p>
        2.1     This document was created using a template from <a href="http://www.seqlegal.com/">SEQ Legal</a> (http://www.seqlegal.com).
        </p><p>
        <strong> 3 Copyright notice</strong>
        </p><p>
        3.1     Copyright (c) <?=$config->company_name?>.
        </p><p>
        <strong>4 License to use website</strong>
        </p><p>
        4.1     You may:
        </p><p>
        (a)      view pages from our website in a web browser;
        </p><p>
        (b)      download pages from our website for caching in a web browser or through the use of an offline web page viewer;
        </p><p>
        (c)      print pages from our website;
        </p><p>
        (d)      use our website services by means of a web browser or similar software,
        </p><p>
        subject to the other provisions of these terms and conditions.
        </p><p>
        4.2     Except as expressly permitted by Section 4.1 or the other provisions of these terms and conditions, you must not download any material from our website or save any such material to your computer.
        </p><p>
        4.3     You may only use our website for your own personal and business purposes, and you must not use our website for any other purposes.
        </p><p>
        4.4     Except as expressly permitted by these terms and conditions, you must not edit or otherwise modify any material on our website.
        </p><p>
        4.5     Unless you own or control the relevant rights in the material or have explicit permission from us, you must not:
        </p><p>
        (a)      republish material from our website (including republication on another website);
        </p><p>
        (b)      sell, rent or sub-license material from our website;
        </p><p>
        (c)      show any material from our website in public;
        </p><p>
        (d)      exploit material from our website for a commercial purpose; or
        </p><p>
        (e)      redistribute material from our website.
        </p><p>
        4.6     We reserve the right to restrict access to areas of our website, or indeed our whole website, at our discretion; you must not circumvent or bypass, or attempt to circumvent or bypass, any access restriction measures on our website.
        </p><p>
        <strong>5 Acceptable use</strong>
        </p><p>
        5.1     You must not:
        </p><p>
        (a)      use our website in any way or take any action that causes, or may cause, damage to the website or impairment of the performance, availability or accessibility of the website;
        </p><p>
        (b)      use our website in any way that is unlawful, illegal, fraudulent or harmful, or in connection with any unlawful, illegal, fraudulent or harmful purpose or activity;
        </p><p>
        (c)      use our website to copy, store, host, transmit, send, use, publish or distribute any material which consists of (or is linked to) any spyware, computer virus, Trojan horse, worm, keystroke logger, rootkit or other malicious computer software;
        </p><p>
        (d)      conduct any systematic or automated data collection activities (including without limitation scraping, data mining, data extraction and data harvesting) on or in relation to our website without our express written consent;
        </p><p>
        (e)     violate the directives set out in the robots.txt file for our website; or
        </p><p>
        (f)      use data collected from our website for any direct marketing activity (including without limitation email marketing, SMS marketing, telemarketing and direct mailing).
        </p><p>
        5.2     You must not use data collected from our website to contact individuals, companies or other persons or entities.
        </p><p>
        5.3     You must ensure that all the information you supply to us through our website, or in relation to our website, is true, accurate, current, complete and non-misleading.
        </p><p>
        <strong>6 Registration and accounts</strong>
        </p><p>
        6.1     You may register for an account with our website by completing and submitting the account registration form on our website, and clicking on the verification link in the email that the website will send to you.
        </p><p>
        6.2     You must notify us in writing immediately if you become aware of any unauthorized use of your account.
        </p><p>
        6.3     You must not use any other person's account to access the website, unless you have that person's express permission to do so.
        </p><p>
        <strong>7 User IDs and passwords</strong>
        </p><p>
        7.1     If you register for an account with our website, we will provide you with / you will be asked to choose a user ID and password.
        </p><p>
        7.2     Your user ID must not be liable to mislead and must comply with the content rules set out in Section 9; you must not use your account or user ID for or in connection with the impersonation of any person.
        </p><p>
        7.3     You must keep your password confidential.
        </p><p>
        7.4     You must notify us in writing immediately if you become aware of any disclosure of your password.
        </p><p>
        7.5     You are responsible for any activity on our website arising out of any failure to keep your password confidential, and may be held liable for any losses arising out of such a failure.
        </p><p>
        <strong>8 Cancellation and suspension of account</strong>
        </p><p>
        8.1     We may:
        </p><p>
        (a)      suspend your account; or
        </p><p>
        (b)      cancel your account;
        </p><p>
        if we receive a report from any user or we detect an unwanted behavior in your activities.
        </p><p>
        <strong>9 Your content:</strong>
        </p><p>
        9.1    Any material you send through our website (or where our website in whole or part is referenced or being used), either by using our forum systems, comments systems or external systems is considered your content.
        </p><p>
        9.2    Any place where our systems and services are used (either by working in the background or embedded onto a web page or device) that have access to your contents are also considered into this section.
        </p><p>
        9.3    You warrant and represent that your content will comply with these terms and conditions.
        </p><p>
        9.4    Your content must not be illegal or unlawful, must not infringe any person's legal rights, and must not be capable of giving rise to legal action against any person (in each case in any jurisdiction and under any applicable law).
        </p><p>
        9.5    Your content, and the use of your content by us in accordance with these terms and conditions, must not:
        </p><p>
        (a)      be libellous or maliciously false;
        </p><p>
        (b)      be obscene or indecent;
        </p><p>
        (c)      infringe any copyright, moral right, database right, trade mark right, design right, right in passing off, or other intellectual property right;
        </p><p>
        (d)      infringe any right of confidence, right of privacy or right under data protection legislation;
        </p><p>
        (e)      constitute negligent advice or contain any negligent statement;
        </p><p>
        (f)      constitute an incitement to commit a crime, instructions for the commission of a crime or the promotion of criminal activity;
        </p><p>
        (g)      be in contempt of any court, or in breach of any court order;
        </p><p>
        (h)      be in breach of racial or religious hatred or discrimination legislation;
        </p><p>
        (i)       be blasphemous;
        </p><p>
        (j)      be in breach of official secrets legislation;
        </p><p>
        (k)      be in breach of any contractual obligation owed to any person;
        </p><p>
        (l)       depict violence;
        </p><p>
        (m)     be pornographic, lewd, suggestive or sexually explicit;
        </p><p>
        (n)      be untrue, false, inaccurate or misleading;
        </p><p>
        (o)      consist of or contain any instructions, advice or other information which may be acted upon and could, if acted upon, cause illness, injury or death, or any other loss or damage;
        </p><p>
        (p)      constitute spam;
        </p><p>
        (q)      be offensive, deceptive, fraudulent, threatening, abusive, harassing, anti-social, menacing, hateful, discriminatory or inflammatory.
        </p><p>
        <strong>10 Limited warranties</strong>
        </p><p>
        10.1    No liability, explicit or implied, shall be extended to our website, its services, its owners, employees and collaborators for opinions expressed in which our website and its services are referenced and/or being used.
        </p><p>
        10.2    We do not warrant or represent:
        </p><p>
        (a)      the completeness or accuracy of the information published on our website;
        </p><p>
        (b)      that the material on the website is up to date; or
        </p><p>
        (c)      that the website or any service on the website will remain available.
        </p><p>
        10.3    We reserve the right to discontinue or alter any or all of our website services, and to stop publishing our website, at any time in our sole discretion; and save to the extent expressly provided otherwise in these terms and conditions, you may or may not be entitled to any compensation or other payment upon the discontinuance or alteration of any website services, or if we stop publishing the website.
        </p><p>
        10.4    To the maximum extent permitted by applicable law and subject to Section 12.1, we exclude all representations and warranties relating to the subject matter of these terms and conditions, our website and the use of our website.
        </p><p>
        <strong>11 Limitations and exclusions of liability</strong>
        </p><p>
        11.1    Acceptance of these terms and conditions will:
        </p><p>
        (a)      limit or exclude any liability for death or personal injury resulting from negligence;
        </p><p>
        (b)      limit or exclude any liability for fraud or fraudulent misrepresentation;
        </p><p>
        (c)      limit any liabilities in any way that is not permitted under applicable law; or
        </p><p>
        (d)      exclude any liabilities that may not be excluded under applicable law.
        </p><p>
        11.2    The limitations and exclusions of liability set out in this Section 12 and elsewhere in these terms and conditions:
        </p><p>
        (a)      are subject to Section 12.1; and
        </p><p>
        (b)      govern all liabilities arising under these terms and conditions or relating to the subject matter of these terms and conditions, including liabilities arising in contract, in tort (including negligence) and for breach of statutory duty.
        </p><p>
        11.3    We will not be liable to you in respect of any losses arising out of any event or events beyond our reasonable control.
        </p><p>
        11.4    We will not be liable to you in respect of any business losses, including (without limitation) loss of or damage to profits, income, revenue, use, production, anticipated savings, business, contracts, commercial opportunities or goodwill.
        </p><p>
        11.5    We will not be liable to you in respect of any loss or corruption of any data, database or software.
        </p><p>
        11.6    We will not be liable to you in respect of any special, indirect or consequential loss or damage.
        </p><p>
        11.7    You accept that we have an interest in limiting the personal liability of our officers,employees and collaborators and, having regard to that interest, you acknowledge that we are a limited liability entity; you agree that you will not bring any claim personally against our officers or employees or collaborators in respect of any losses you suffer in connection with the website, the website applications and services or these terms and conditions (this will not, of course, limit or exclude the liability of the limited liability entity itself for the acts and omissions of our officers, employees and collaborators).
        </p><p>
        <strong>12 Breaches of these terms and conditions</strong>
        </p><p>
        12.1    Without prejudice to our other rights under these terms and conditions, if you breach these terms and conditions in any way, or if we reasonably suspect that you have breached these terms and conditions in any way, we may:
        </p><p>
        (a)      send you one or more formal warnings;
        </p><p>
        (b)      temporarily suspend your access to our website;
        </p><p>
        (c)      permanently prohibit you from accessing our website;
        </p><p>
        (d)      block computers using your IP address from accessing our website;
        </p><p>
        (e)      contact any or all your internet service providers and request that they block your access to our website;
        </p><p>
        (f)      commence legal action against you, whether for breach of contract or otherwise; and/or
        </p><p>
        (g)      suspend or delete your account on our website.
        </p><p>
        12.2    Where we suspend or prohibit or block your access to our website or a part of our website, you must not take any action to circumvent such suspension or prohibition or blocking (including without limitation creating and/or using a different account).
        </p><p>
        <strong>13 Our details</strong>
        </p><p>
        13.1    This website is owned and operated by <?=$config->company_name?>.
        </p><p>
        13.3    Our principal place of business is at <?=$config->company_location?>
        </p><p>
        13.4    You can contact us by writing to the business address given above, by using <a href="<?=$config->website_pages["support"]?>">our website contact form</a>.
        </p>

    </body>
</html>
