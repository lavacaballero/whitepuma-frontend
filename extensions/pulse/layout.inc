<?
    if( empty($base_coin_filter) ) $key_coin = "*";
    else                           $key_coin = $base_coin_filter;

    $last_pulse_visit = get_pulse_user_preference("last_visit:$key_coin");
    if( empty($last_pulse_visit) ) $last_pulse_visit = 0;
    else                           $last_pulse_visit = strtotime($last_pulse_visit);
    set_pulse_user_preference("last_visit:$key_coin", date("Y-m-d H:i:s"));
?>

<script type="text/javascript"          src="<?=$config->commons_url?>/lib/underscore-min.js"></script>
<script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery.cookie.js"></script>
<link rel="stylesheet" type="text/css" href="<?=$config->commons_url?>/lib/jquery-mentions-input/jquery.mentionsInput.css?v=<?=$config->scripts_and_styles_version?>">
<script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery-mentions-input/jquery.mentionsInput.js?v=<?=$config->scripts_and_styles_version?>"></script>
<script type="text/javascript"          src="<?=$config->commons_url?>/lib/jquery.exptextarea.js"></script>
<link rel="stylesheet" type="text/css" href="<?=$root_url?>/extensions/pulse/pulse_base.css?v=<?=$config->scripts_and_styles_version?>">
<? if( $pulse_standalone_layout ): ?>
    <link rel="stylesheet" type="text/css" href="<?=$root_url?>/extensions/pulse/pulse_standalone.css?v=<?=$config->scripts_and_styles_version?>">
<? else: ?>
    <link rel="stylesheet" type="text/css" href="<?=$root_url?>/extensions/pulse/pulse_embedded.css?v=<?=$config->scripts_and_styles_version?>">
<? endif; ?>
<script type="text/javascript"          src="<?=$root_url?>/extensions/pulse/pulse.js?v=<?=$config->scripts_and_styles_version?>"></script>
<script type="text/javascript"          src="<?=$root_url?>/universal_widget/<?=$config->buttons_self_website_name_for_pulse?>"></script>

<script type="text/javascript">
    var init_wpulse_interval = null;
    //////////////////////
    function init_wpulse()
    //////////////////////
    {
        if( typeof FB == 'undefined' ) return;
        clearInterval(init_wpulse_interval);
        wpulse.init({
            posts_root_url:          '<?=$config->website_pages["root_url"]?>extensions/pulse/',
            cookie_prefix:           '<?=$config->session_vars_prefix?>',
            cookie_domain:           '<?=$config->cookie_domain?>',
            id_account:              '<?=$account->id_account?>',
            account_image:           'https://graph.facebook.com/<?=$account->facebook_id?>/picture',
            link_images_proxy:       '<?=$config->commons_url?>/lib/iproxy/',
            _contents:               {
                                         post_body:    '<?= preg_replace('/\r?\n\s*/', '', trim(file_get_contents(dirname(__FILE__)."/post_body.tpl"))) ?>',
                                         comment_body: '<?= preg_replace('/\r?\n\s*/', '', trim(file_get_contents(dirname(__FILE__)."/comment_body.tpl"))) ?>'
                                     },
            toolbox_script:          '<?=$root_url?>/extensions/pulse/toolbox.php',
            last_refresh_timestamp:  parseInt('<?= $last_pulse_visit ?>'),
            allow_fallback_updates:  true,
            type:                    '<?= empty($pulse_forced_layout) ? "" : $pulse_forced_layout; ?>',
            comments_state:          '<?= $default_comments_state ?>',
            disable_display_control: '<?= $disable_display_control ?>',
            base_coin_filter:        '<?= $base_coin_filter ?>',
            show_single_post:        '<?= $_GET["post"] ?>',
            highlight_comment:       '<?= $_GET["comment"] ?>',
            allow_display_more:      '<?= $allow_display_more ?>'
        });
    } // end function

    ////////////////////////////
    $(document).ready(function()
    ////////////////////////////
    {
        init_wpulse_interval = setInterval('init_wpulse()', 1000);
    }); // end document.ready
</script>

<div id="wpulse">
    <div class="wpulse_coin_images_per_name" style="display: none;">
    <?
        $coin_names = array_keys($config->current_tipping_provider_data["per_coin_data"]); ksort($coin_names);
        foreach($coin_names as $coin_name)
            echo "<div coin_name='$coin_name'>"
            .    $config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_image"]
            .    "</div>\n";
    ?>
    </div>

    <div id="wpulse_post_post_target" style="display: none;"></div>
    <div id="wpulse_post_comment_target" style="display: none;"></div>

    <div class="wpulse_controls collapsed">

        <img class="wpulse_logo_horizontal" src="<?=$root_url?>/extensions/pulse/logo-h.png" border="0" width="270" height="29">
        <img class="wpulse_logo_vertical" src="<?=$root_url?>/extensions/pulse/logo-v.png" border="0" width="32" height="300">

        <div class="wpulse_control_buttons">

            <div class="wpulse_control_button" toggles="about" title="About Community Pulse">
                <span class="fa fa-question-circle fa-2x"></span>
            </div>

            <div class="wpulse_control_button" toggles="composer" offline_hide="true" title="Show post composer">
                <span class="fa fa-pencil-square-o fa-2x"></span>
            </div>

            <div class="wpulse_control_button wpulse_control_refresh" title="Refresh pulse" onclick="wpulse.refresh()">
                <span class="fa fa-refresh fa-2x"></span>
            </div>

            <div class="wpulse_spacer"></div>

            <div class="wpulse_control_button" toggles="display" offline_hide="true" title="Display layout">
                <span class="fa fa-desktop fa-2x"></span>
            </div>

            <div class="wpulse_control_button disabled" toggles="post_filters" offline_hide="true" title="Post type filters">
                <span class="fa fa-filter fa-2x"></span>
            </div>

            <div class="wpulse_control_button disabled" toggles="coin_filter" offline_hide="true" title="Filter posts by coin">
                <span class="fa fa-database fa-2x"></span>
            </div>

            <div class="wpulse_control_button disabled" toggles="channel_filter" offline_hide="true" title="Filter posts by channel">
                <span class="fa fa-rss fa-2x"></span>
            </div>

        </div><!-- /.wpulse_control_buttons -->

        <div class="wpulse_control_settings" toggle_for="post_filters">

            <form name="wpulse_settings_filter_post_types" method="post" action="<?=$root_url?>/extensions/pulse/save_user_settings.php" onsubmit="return false">

                <div class="title">
                    <span class="fa fa-times fa-border pull-right" style="cursor: pointer;" title="Close drawer" onclick="wpulse.collapse_controls_drawer()"></span>
                    Pulse post filters
                </div>

                <div class="wpulse_note">
                    <span class="fa fa-info-circle"></span>
                    Select the types of posts you <b>don't</b> want to see.
                    Settings are saved to your profile.<br>
                    Note: if you're tagged, the filter will be ignored.
                </div>

                <div class="field">
                    <label>
                        <input type="checkbox" name="wp_user_settings[post_filters][text]" value="true">
                        Text-only posts
                    </label>
                </div><!-- /.field -->

                <div class="field">
                    <label>
                        <input type="checkbox" name="wp_user_settings[post_filters][links]" value="true">
                        Links
                    </label>
                </div><!-- /.field -->

                <div class="field">
                    <label>
                        <input type="checkbox" name="wp_user_settings[post_filters][tipback_links]" value="true">
                        Links with tipback
                    </label>
                </div><!-- /.field -->

                <div class="field">
                    <label>
                        <input type="checkbox" name="wp_user_settings[post_filters][photos]" value="true">
                        Photos
                    </label>
                </div><!-- /.field -->

                <div class="field">
                    <label>
                        <input type="checkbox" name="wp_user_settings[post_filters][videos]" value="true">
                        Videos
                    </label>
                </div><!-- /.field -->

                <div class="field">
                    <label>
                        <input type="checkbox" name="wp_user_settings[post_filters][rains]" value="true">
                        Rains
                    </label>
                </div><!-- /.field -->

                <div class="field" align="center">
                    <button type="submit">
                        Save &amp; apply &nbsp;
                        <span class="fa fa-play"></span>
                    </button>
                </div>

            </form>

        </div><!-- /.wpulse_control_settings[toggle_for="post_filters"] -->

        <div class="wpulse_control_settings" toggle_for="display">

            <div class="title">
                <span class="fa fa-times fa-border pull-right" style="cursor: pointer;" title="Close drawer" onclick="wpulse.collapse_controls_drawer()"></span>
                Display layout settings
            </div>

            <div class="wpulse_note">
                <span class="fa fa-info-circle"></span>
                These settings are set on cookies. They will change from browser to browser across your devices.
            </div>

            <div class="field">
                <label>
                    <input type="radio" name="wpulse_control_display" value="block:default" onchange="wpulse.switch_layout( this.value )">
                    Standard block
                </label>
                <div class="info">
                    Show as regular page content in place.
                </div>
            </div><!-- /.field -->

            <div class="field">
                <label>
                    <input type="radio" name="wpulse_control_display" value="sidebar:left" onchange="wpulse.switch_layout( this.value )">
                    Sidebar at left
                </label><br>
                <div class="info">
                    Show as a fixed sidebar, located at the left of the screen.
                </div>
            </div><!-- /.field -->

            <div class="field">
                <label>
                    <input type="radio" name="wpulse_control_display" value="sidebar:right" onchange="wpulse.switch_layout( this.value )">
                    Sidebar at right
                </label>
                <div class="info">
                    Show as a fixed sidebar, located at the right of the screen.
                </div>
            </div><!-- /.field -->

        </div><!-- /.wpulse_control_settings[toggle_for="display"] -->

        <div class="wpulse_control_settings" toggle_for="composer">

            <form name="wpulse_post_post" id="wpulse_post_post" method="post" action="<?=$root_url?>/extensions/pulse/toolbox.php" enctype="multipart/form-data">

                <input type="hidden" name="MAX_FILE_SIZE" value="6000000">
                <input type="hidden" name="mode"          value="receive_post">
                <input type="hidden" name="type"          value="text">

                <div class="title">
                    <span class="fa fa-times fa-border pull-right" style="cursor: pointer;" title="Close drawer" onclick="wpulse.reset_composer(); wpulse.collapse_controls_drawer();"></span>
                    <span class="wpulse_post_type_toggler selected" toggles="text" onclick="wpulse.toggle_composer( $(this).attr('toggles') )">
                        <span class="fa fa-font"></span>
                        Text
                    </span>
                    <span class="wpulse_post_type_toggler" toggles="link" onclick="wpulse.toggle_composer( $(this).attr('toggles') )">
                        <span class="fa fa-link"></span>
                        Link
                    </span>
                    <span class="wpulse_post_type_toggler" toggles="photo" onclick="wpulse.toggle_composer( $(this).attr('toggles') )">
                        <span class="fa fa-photo"></span>
                        Photo
                    </span>
                    <span class="wpulse_post_type_toggler" toggles="video" onclick="wpulse.toggle_composer( $(this).attr('toggles') )">
                        <span class="fa fa-youtube"></span>
                        Video
                    </span>
                </div><!-- /.title -->

                <div class="wpulse_note field" for="link" style="display: none">
                    <span class="fa fa-warning"></span>
                    Referral links or advertising out of our network is forbidden.
                    If you want to promote something, please wait for "tipbacks" to become available.
                </div><!-- /reflink:warning1 -->

                <div class="wpulse_note field" for="text" style="display: none">
                    <span class="fa fa-warning"></span>
                    Referral links or advertising out of our network is forbidden.
                    If you want to promote something, please wait for "tipbacks" to become available.
                </div><!-- /reflink:warning2 -->

                <div class="field link_field" for="link,video" style="display: none">
                    <span class="loading_switcher">
                        <span class="normal fa fa-link"></span>
                        <span class="loading" style="display: none;"><img src="<?=$root_url?>/img/progress_16x16_gray.gif" border="0" align="absbottom"></span>
                    </span>
                    Paste or type a link:
                    <div class="info">
                        <input type="text" name="link" placeholder="Paste or type a valid URL" previous_url="">
                        <div class="wpulse_error wpulse_error_grabbing_url" style="display: none; float: right;">
                            <span class="fa fa-warning"></span>
                            Please try again
                        </div>
                        <span class="pseudo_link" onclick="wpulse.fill_composer_fields_from_link()">
                            Fetch document data (title, image, description)
                        </span>
                        <div class="wpulse_error_contents" style="display: none;"></div>
                    </div>
                </div><!-- /link -->

                <div class="field" for="link" style="display: none">
                    <span class="normal fa fa-photo"></span>
                    Pick photo from URL:
                    <div class="info">
                        <input type="text" name="photo_url" placeholder="Must start with http:// or https://">
                    </div>
                </div><!-- /photo:url -->

                <div class="field" for="link,photo" style="display: none">
                    <span class="fa fa-upload"></span>
                    Upload photo (GIF, JPEG, PNG):<br>
                    <div class="info">
                        <input type="file" name="photo_file">
                    </div>
                </div><!-- /photo:upload -->

                <div class="field" for="link,photo,video" style="display: none">
                    <div class="wpulse_taggable_wrapper">
                        <textarea name="caption_text" class="wpulse_taggable"
                                  placeholder="Type a caption to use as title. Use @ and some letters to tag app users."
                                  moblie_placeholder="Type a caption to use as title. Attach tips using the controls below."></textarea>
                        <input type="hidden" name="caption_mentions" value="">
                    </div>
                </div><!-- /caption -->

                <div class="field" for="link,text">
                    <div class="wpulse_taggable_wrapper">
                        <textarea name="content_text" class="wpulse_taggable mobile_placeholder_added"
                                  placeholder="Write something. Use @ and some letters to tag app users."
                                  moblie_placeholder="Write somehting. Attach tips using the controls below."></textarea>
                        <input type="hidden" name="content_mentions" value="">
                    </div>
                </div><!-- /content -->

                <div class="field mobile_visible" for="text,link,photo,video" style="display: none;">
                    <span class="fa fa-money"></span>
                    Attach tips to post:
                    <div class="info special">
                        <? if( count($config->current_tipping_provider_data["per_coin_data"]) == 1 ): ?>
                            <input type="text" readonly name="target_coin" value="<?=$config->current_coin_name?>">
                        <? else: ?>
                            <select name="attached_tip_coin" onchange="$(this).closest('.field').find('.wpulse_min_tip_size').text( $(this.options[this.selectedIndex]).attr('min_tip_size') )">
                                <option value="" min_tip_size="N/A">&lt;Select a coin&gt;</option>
                                <? $coin_names = array_keys($config->current_tipping_provider_data["per_coin_data"]); asort($coin_names); ?>
                                <? foreach($coin_names as $coin_name): ?>
                                    <? if( $config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_disabled"] ) continue; ?>
                                    <option <? if($coin_name == $base_coin_filter) echo "selected"; ?> min_tip_size="<?=$config->current_tipping_provider_data["per_coin_data"][$coin_name]["min_transaction_amount"]?> <?=$config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_sign"]?>"><?=$coin_name?></option>
                                <? endforeach; ?>
                            </select>
                        <? endif; ?>
                    </div>
                    <div class="info special">
                        <? $min_tip_size = count($config->current_tipping_provider_data["per_coin_data"]) > 1 ? "N/A"
                                         : $config->current_coin_data["min_transaction_amount"]; ?>
                        Amount: (min: <span class="wpulse_min_tip_size"><?=$min_tip_size?></span>):
                        <input type="number" class="wpulse_crypto" name="attached_tip_size">
                    </div>
                    <input type="hidden" name="attached_tip_recipients">
                    <div id="wpulse_tip_attachments_added" class="info special wpulse_note wpulse_spanned_items_container">
                        <span class="none">No recipients selected</span>
                    </div>
                    <div class="info special" style="white-space: nowrap;">
                        <button style="width: 19%; float: right; margin-left: 1%;" onclick="$('#wpulse_tip_attachments_query').val(''); $('#wpulse_tip_attachments_lookup_target').html('').hide(); return false;">
                            Clear
                        </button>
                        <button style="width: 19%; float: right;" onclick="wpulse.search_tip_recipients($('#wpulse_tip_attachments_query'), $('#wpulse_tip_attachments_lookup_target'), $('#wpulse_tip_attachments_added'), $('#wpulse_post_post input[name=attached_tip_recipients]')); return false;">
                            Search
                        </button>
                        <input id="wpulse_tip_attachments_query" type="text" style="width: 60%" name="find_recipients" placeholder="Type some letters of user names" autocomplete="off">
                    </div>
                    <div id="wpulse_tip_attachments_lookup_target" class="info special wpulse_note wpulse_spanned_items_container" style="display: none;">Lookup users using the textbox above and select them from here.</div>
                </div><!-- /caption -->

                <div class="field" for="text,link,video,photo" style="display: none">
                    <div class="wpulse_taggable_wrapper">
                        <textarea name="signature" class="expandible_textarea" style="min-height: 32px;"
                                  placeholder="Set a signature here (no HTML, URLs accepted). If provided, It will be saved in your profile."></textarea>
                    </div>
                </div><!-- /signature -->

                <!--
                    <div class="field" for="link" style="display: none;">
                        <span class="fa fa-rotate-left"></span>
                        Tipback on clicking:
                        <div class="info special">
                            <select name="tipback_coin" onchange="$(this).closest('.field').find('.wpulse_min_tip_size').text( $(this.options[this.selectedIndex]).attr('min_tip_size') )">
                                <option value="" min_tip_size="N/A">&lt;Select a coin&gt;</option>
                                <? $coin_names = array_keys($config->current_tipping_provider_data["per_coin_data"]); asort($coin_names); ?>
                                <? foreach($coin_names as $coin_name): ?>
                                    <? if( $config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_disabled"] ) continue; ?>
                                    <option min_tip_size="<?=$config->current_tipping_provider_data["per_coin_data"][$coin_name]["min_transaction_amount"]?> <?=$config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_sign"]?>"><?=$coin_name?></option>
                                <? endforeach; ?>
                            </select>
                        </div>
                        <div class="info special">
                            Unique users to tipback (min: 10):
                            <input type="number" class="wpulse_tiny" name="tipback_users" value="10">
                        </div>
                        <div class="info special">
                            Coins per user (min: <span class="wpulse_min_tip_size">N/A</span>):
                            <input type="number" class="wpulse_crypto" name="tipback_coins">
                        </div>
                    </div>
                -->

                <div class="field" for="text,link,video,photo">
                    <span class="fa fa-database"></span>
                    Target coin community:
                    <div class="info">
                        <? if( count($config->current_tipping_provider_data["per_coin_data"]) == 1 ): ?>
                            <input type="text" readonly name="target_coin" value="<?=$config->current_coin_name?>">
                        <? else: ?>
                            <select name="target_coin">
                                <option value="">&lt;All coin communities&gt;</option>
                                <? $coin_names = array_keys($config->current_tipping_provider_data["per_coin_data"]); asort($coin_names); ?>
                                <? foreach($coin_names as $coin_name): ?>
                                    <? if( $config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_disabled"] ) continue; ?>
                                    <option <? if($coin_name == $base_coin_filter) echo "selected"; ?>><?=$coin_name?></option>
                                <? endforeach; ?>
                            </select>
                        <? endif; ?>
                    </div>
                </div><!-- /target_coin -->

                <div class="field" for="text,link,video,photo">
                    <span class="fa fa-rss-square"></span>
                    Target channel:
                    <div class="info" style="font-style: normal;">
                        <select name="target_feed" onchange="if($(this.options[this.selectedIndex]).attr('is_facebook_group') != 'true') $(this).closest('div').find('.wpost_to_facebook').hide(); else $(this).closest('div').find('.wpost_to_facebook').show();">
                            <option value="" is_facebook_group="false">&lt;All channels&gt;</option>

                            <? $feed_names = array();
                               foreach( $config->facebook_monitor_objects as $key => $object ) $feed_names[$object["name"]] = $key;
                               ksort($feed_names);
                               foreach($feed_names as $name => $key): ?>

                                <option value="<?=$key?>" is_facebook_group="true"><?= trim(str_replace(">", "", $name)) ?></option>

                            <? endforeach; ?>

                        </select>
                        <div class="wpost_to_facebook" style="display: none; margin-top: 5px;">
                            <div class="info">
                                The next options require you to give <?=$config->app_display_shortname?> extended permissions.
                                Make sure you do this before submitting the post.<br>
                                <fb:login-button class="wpulse_fb_button" scope="<?=$config->facebook_auth_scope?>,publish_actions" style="margin-top: 5px;"
                                     onlogin="alert('Done! Now you can submit your post when ready.')">
                                    (Re)authorize now
                                </fb:login-button>
                            </div>
                            <label>
                                <input type="checkbox" name="post_to_facebook">Post as link to Facebook group
                            </label>
                            <div class="info">
                                If checked, a link to your post on the <a href="<?=$root_url?>/extensions/pulse/">Pulse feed</a> will be published.
                                Make sure you are member of the group.
                            </div>
                            <label>
                                <input type="checkbox" name="post_to_timeline">Also post the link on my own timeline
                            </label>
                            <div class="info">
                                If checked, the same link will be sent to your timeline.
                            </div>
                        </div>
                    </div>
                </div><!--/target_feed -->

                <div class="field" align="right">
                    <button type="submit">
                        Post &nbsp;
                        <span class="fa fa-play"></span>
                    </button>
                </div><!-- /submit -->

            </form>

        </div><!-- /.wpulse_control_settings[toggle_for="composer"] -->

        <div class="wpulse_control_settings" toggle_for="coin_filter">

            <div class="title">
                <span class="fa fa-times fa-border pull-right" style="cursor: pointer;" title="Close drawer" onclick="wpulse.collapse_controls_drawer()"></span>
                Post filterer (by coin)
            </div>

            <div class="wpulse_note">
                <span class="fa fa-info-circle"></span>
                Select those coins for which targeted posts you <b>don't</b> want to see.
                Settings are saved to your profile.<br>
                Note: if you're tagged, the filter will be ignored.
            </div>

            <div class="wpulse_filter_coins">

                <form name="wpulse_settings_filter_coins" method="post" action="<?=$root_url?>/extensions/pulse/save_user_settings.php" onsubmit="return false;">

                    <input type="hidden" name="wp_user_settings[remove_all_coin_filters]" value="">

                    <div class="field" align="center">
                        <button onclick="wpulse.remove_all_filters('coin');">
                            Remove all coin filters
                        </button>
                    </div>

                    <? $coin_names = array_keys($config->current_tipping_provider_data["per_coin_data"]); asort($coin_names); ?>
                    <? foreach($coin_names as $coin_name): ?>
                        <? if( $config->current_tipping_provider_data["per_coin_data"][$coin_name]["coin_disabled"] ) continue; ?>

                        <div class="field">
                            <label>
                                <input type="checkbox" name="wp_user_settings[coin_filters][<?=$coin_name?>]" value="<?= $coin_name ?>">
                                <?= $coin_name ?>
                            </label>
                        </div>

                    <? endforeach; ?>

                    <div class="field" align="center">
                        <button type="submit">
                            Save &amp; apply &nbsp;
                            <span class="fa fa-play"></span>
                        </button>
                    </div>

                </form>

            </div><!-- /.wpulse_filter_coins -->

        </div><!-- /.wpulse_control_settings[toggle_for="coin_filter"] -->

        <div class="wpulse_control_settings" toggle_for="channel_filter">

            <div class="title">
                <span class="fa fa-times fa-border pull-right" style="cursor: pointer;" title="Close drawer" onclick="wpulse.collapse_controls_drawer()"></span>
                Post filterer (by channel)
            </div>

            <div class="wpulse_note">
                <span class="fa fa-info-circle"></span>
                Select those channels for which targeted posts you  <b>don't</b> want to see.
                Settings are saved to your profile.<br>
                Note: if you're tagged, the filter will be ignored.
            </div>

            <div class="wpulse_filter_feeds">

                <form name="wpulse_settings_filter_feeds" method="post" action="<?=$root_url?>/extensions/pulse/save_user_settings.php" onsubmit="return false;">

                    <input type="hidden" name="wp_user_settings[remove_all_feed_filters]" value="">

                    <div class="field" align="center">
                        <button onclick="wpulse.remove_all_filters('feed');">
                            Remove all channel filters
                        </button>
                    </div>

                    <? $feed_names = array();
                       foreach( $config->facebook_monitor_objects as $key => $object ) $feed_names[$object["name"]] = $key;
                       ksort($feed_names);
                       foreach($feed_names as $name => $key): ?>

                        <div class="field as_bulleted">
                            <label>
                                <input type="checkbox" name="wp_user_settings[feed_filters][<?=$key?>]" value="<?= $key ?>">
                                <?= trim(str_replace(">", "", $name)) ?>
                            </label>
                        </div>

                    <? endforeach; ?>

                    <div class="field" align="center">
                        <button type="submit">
                            Save &amp; apply &nbsp;
                            <span class="fa fa-play"></span>
                        </button>
                    </div>

                </form>

            </div><!-- /.wpulse_filter_feeds -->

        </div><!-- /.wpulse_control_settings[toggle_for="channel_filter"] -->

        <div class="wpulse_control_settings" toggle_for="about">
            <div class="title">
                <span class="fa fa-times fa-border pull-right" style="cursor: pointer;" title="Close drawer" onclick="wpulse.collapse_controls_drawer()"></span>
                About Community Pulse
            </div>

            <p><?=$config->app_display_shortname?>'s Community Pulse is a component that listens for
            user posts and spreads out submissions from all the app's user base.</p>

            <p>It is a community of communities inside Facebook.</p>

            <p><b>It is a slimmed down version of Facebook groups</b></p>

            <p>Posting on Pulse is like posting to Facebook groups but with some additions:</p>

            <ul>
                <li>Pulse "channels" are based on Facebook groups, but some external sources may be added.</li>
                <li>Posts submitted from the Pulse composer can also be posted to a Facebook group.</li>
                <li>Posts can be directed to users of a certain coin and/or users of a certain channel.</li>
            </ul>

            <p><b>Tipping goes over posts and comments</b></p>

            <p>Please check the main tab of every coin to look for the tipping commands.
            When you type an @ and then some letters, the composer will bring up
            a list of matching users from our user base.</p>

            <p><b>Do not beg</b></p>

            <p>Begging is discouraged. If you're a constant begger you'll be banned from
            posting on the pulse.</p>

            <p><b>You can promote good causes though.</b> Feel free to post any links
            back to causes either from third parites of from yourself, but be very
            explicit and make sure you're asking for something that worths tipping.</p>

            <p><b>Be warned: no spamming. Unless you pay for it!</b></p>

            <p>Referral links or advertising out of the websites on our network <b>is forbidden</b>.</p>

            <p>If you want to promote something, you can post a link back to the website
            but use the <b>tipback</b> settings to give free coins to the users as soon as they click on it
            from the Pulse feed.<br>
            Any ad links without tipback settings will be deleted as soon as they're discovered.
            </p>

        </div><!-- /.wpulse_control_settings[toggle_for="about"] -->

    </div><!-- /.wpulse_controls -->

    <div class="wpulse_contents_wrapper">
    </div><!-- /.wpulse_contents_wrapper -->

    <div class="wpulse_load_more" onclick="wpulse.load_more()">
        <span class="wpulse_load_caption">Load older posts</span>
        <span class="wpulse_loading fa fa-spinner fa-spin" style="display: none;"></span>
    </div>

</div>
