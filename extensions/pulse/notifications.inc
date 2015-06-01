<?
    global $account, $root_url;
    if( ! is_resource($config->db_handler) ) db_connect();
    include_once "$root_url/extensions/pulse/model_post.php";
    include_once "$root_url/extensions/pulse/functions.inc";
    
    $last_pulse_visit = get_pulse_user_preference("last_visit:~notif");
    if( empty($last_pulse_visit) ) $last_pulse_visit = 0;
    else                           $last_pulse_visit = strtotime($last_pulse_visit);
    set_pulse_user_preference("last_visit:~notif", date("Y-m-d H:i:s"));
    
    if( $last_pulse_visit > 0 )
    {
        $since_date          = 
        $comments_since_date = date("Y-m-d H:i:s", $last_pulse_visit);
        $limit               = "";
    }
    else
    {
        $since_date          = "2014-01-01 00:00:00";
        $comments_since_date = date("Y-m-d H:i:s");
        $limit               = "limit 8";
    } # end if
    
    $query = "
        select * from {$config->db_tables["pulse_posts"]}
        where hidden = 0
        and created > '$since_date'
        order by created desc
        $limit
    ";
    $res = mysql_query($query);
    
    if( mysql_num_rows($res) == 0) return;
?>
<link rel="stylesheet" type="text/css" href="<?=$root_url?>/extensions/pulse/pulse_base.css?v=<?=$config->scripts_and_styles_version?>">
<link rel="stylesheet" type="text/css" href="<?=$root_url?>/extensions/pulse/pulse_embedded.css?v=<?=$config->scripts_and_styles_version?>">
<style type="text/css">
    /** Style overrides */
    #wpulse[type="block"] { max-height: none; display: block; border: none; box-shadow: none; white-space: normal; margin: 0; padding: 0; }
    .wpulse_post_content img { display: none; }
</style>

<div id="wpulse" type="block">
    
    <div class="pulse_notifications ui-widget-content ui-corner-all" style="padding: 5px; margin: 10px 0;">
        
        <h2 class="ui-widget-header ui-corner-all" style="margin: 0 0 10px 0; padding: 5px;">
            <button onclick="$('.wpulse_contents_wrapper').toggle(); $(this).find('.visible, .hidden').toggle()"
                    title="Toggle list on/off" style="height: 24px;">
                <span class="visible"><span class="fa fa-caret-down"></span></span>
                <span class="hidden" style="display: none"><span class="fa fa-caret-right"></span></span>
            </button>
            Recent Pulse posts <? if($last_pulse_visit > 0) echo "(since " . time_elapsed_string(date("Y-m-d H:i:s", $last_pulse_visit)) . ")" ?>
            <a href="<?=$root_url?>/extensions/pulse/" class="buttonized"><span class="fa fa-rss"></span> Go to Pulse Feed</a>
        </h2>
        
        <div class="wpulse_contents_wrapper">
            
            <? while( $row = mysql_fetch_object($res) ): ?>
                
                <?
                    $post = new pulse_post($row);
                    $post = convert_post($post, false);
                    $type_icon = empty($post->metadata->tipback_data) ? $post->type : "link_tipback";
                    $target_coin_style = empty($post->target_coin)
                                       ? "display: none;" 
                                       : ( "background-image: url('" 
                                           . $config->current_tipping_provider_data["per_coin_data"][$post->target_coin]["coin_image"]
                                           . "')"
                                         )
                                       ;
                    $goto_post_link = ($type_icon == 'rain' || $type_icon == 'link' || $type_icon == 'link_tipback') ? "Go to $post->link" : "";
                    /*
                    $post_tipback_info = $type_icon != "link_tipback" ? ""
                                       : "<div class='wpost_tipback_info' style='background-image: url(post_data.metadata.tipback_data.coin_image)'>
                                              Follow this link to earn
                                              post_data.metadata.tipback_data.tip_size
                                              post_data.metadata.tipback_data.coin_name_plural!
                                          </div>";
                    */
                    $post_tipback_info = "";
                ?>
                
                <div class="wpulse_post" post_id="<?=$post->id?>" post_type="<?=$post->type?>" 
                     is_tipback="<? if( ! empty($post->metadata->tipback_data) ) echo "true"; ?>" 
                     href="<?=$post->link?>">
                    
                    <div class="wpulse_post_contents">
                        <div class="wpulse_post_heading">
                            <div class="wpulse_type_icon wpulse_type_<?= $type_icon ?>"><div 
                                 class="wpulse_coin_icon" 
                                 style="<?= $target_coin_style ?>"></div></div>
                            <div class="wpulse_user_icon"><a href="<?= $post->author_data->profile_url ?>"
                                 target="_blank"><img src="<?= $post->author_data->image ?>"></a></div>
                            <div class="wpulse_post_info">
                                <div class="wpulse_post_author"><a href="<?= $post->author_data->profile_url ?>"
                                     target="_blank"><?= $post->author_data->name ?></a></div>
                                <? if( ! empty($post->target_feed) ): ?>
                                    <div class="wpulse_post_destinfo"><a href="<?= $config->facebook_monitor_objects[$post->target_feed]["url"] ?>" 
                                         target="_blank"><?= $config->facebook_monitor_objects[$post->target_feed]["name"] ?></a></div>
                                <? endif; ?>
                                <div class="wpulse_post_timing timeago" title="<?= str_replace(" ", "T", $post->created) ?>"></div>
                            </div>
                        </div><!-- /.wpulse_post_heading -->
                        <div class="wpulse_post_body">
                            <? if( ! empty($post->caption) ): ?>
                                <div class="wpulse_post_caption"><?= $post->caption ?></div><!-- /.wpulse_post_caption -->
                            <? endif; ?>
                            <? if( $post->type != "video" && ! empty($post->content)): ?>
                                <div class="wpulse_post_content">
                                    <div class="wpulse_post_content_wrapper" title="<?= $goto_post_link ?>">
                                        <?= $post_tipback_info ?>
                                        <?= $post->content ?>
                                    </div>
                                </div><!-- /.wpulse_post_content -->
                            <? endif; ?>
                        </div><!-- /.wpulse_post_body -->
                        <a href="<?=$root_url?>/extensions/pulse/?post=<?=$post->id?>" class="buttonized" 
                           style="display: block; padding: 2px 5px; margin: 5px;">Go to post</a>
                    </div><!-- /.wpulse_post_contents -->
                    
                </div><!-- /.wpulse_post -->
                
            <? endwhile; ?>
        </div>
        
    </div><!-- /.wpulse_contents_wrapper -->
    
</div><!-- /#wpluse -->
