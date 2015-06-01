
<div class="wpulse_post" post_id="{$post_id}" post_type="{$post_type}" is_tipback="{$is_tipback}" href="{$post_link}" created="{$post_created}" last_update="{$post_last_update}">
    
    <div class="wpulse_post_contents">
        <div class="wpulse_post_heading">
            <div class="wpulse_type_icon wpulse_type_{$post_type_icon}"><a class="wpulse_coin_icon" href="{$root_url}?switch_coin={$target_coin}" style="background-image: {$post_coin_icon_as_bgimage}"></a></div>
            <div class="wpulse_user_icon"><a href="{$comment_author_profile_url}" target="_blank"><img src="{$post_author_image}"></a></div>
            <div class="wpulse_post_info">
                <div class="wpulse_post_author"><a href="{$post_author_profile_url}" target="_blank">{$post_author_name}</a></div>
                <div class="wpulse_post_destinfo"><a href="{$post_destination_url}" target="_blank">{$post_destination}</a></div>
                <div class="wpulse_post_timing timeago" title="{$post_date}"></div>
            </div>
        </div><!-- /.wpulse_post_heading -->
        <div class="wpulse_post_body">
            <div class="wpulse_post_caption">
                {$post_caption}
            </div><!-- /.wpulse_post_caption -->
            <div class="wpulse_post_content">
                <div class="wpulse_post_content_wrapper" title="{$goto_post_link}">
                    {$post_tipback_info}
                    {$post_content}
                </div>
                <div class="wpulse_post_signature">
                    {$post_signature}
                </div>
            </div><!-- /.wpulse_post_content -->
            <div class="wpulse_post_controls">
                <fb:like class="wpulse_fb_button" href="{$post_url}" layout="button_count" action="like" share="true"></fb:like>
                &nbsp;<a class="wpulse_post_control" href="{$post_url}" title="Post permalink">Permalink</a>
                <!--
                <span class="wpulse_post_control wpulse_admin pseudo_link fa fa-times fa-border" title="Delete this post"></span>
                <span class="wpulse_post_control wpulse_admin pseudo_link fa fa-ban   fa-border" title="Ban user and hide all his posts"></span>
                <span class="wpulse_post_control pseudo_link fa fa-exclamation-circle fa-border" title="Report post as spam"></span>
                -->
                <div class="wpulse_post_comments_trigger pseudo_link collapsed" title="Expand/Collapse comments" onclick="wpulse.toggle_comments(this)">
                     <span class="fa fa-comments-o" style="margin-right: 5px;"></span>
                     (<span class="wpulse_post_comments_count">{$post_comment_count}</span>)
                </div>
            </div><!-- /.wpulse_post_controls -->
        </div><!-- /.wpulse_post_body -->
    </div><!-- /.wpulse_post_contents -->
    
    <div class="wpulse_post_comments {$comments_state}">
        
        <form class="wpulse_comments_form" name="wpulse_comments_form_{$post_id}" post_id="{$post_id}" 
              action="{$toolbox_script}" method="post" enctype="multipart/form-data">
            
            <input type="hidden" name="MAX_FILE_SIZE"    value="6000000">
            <input type="hidden" name="mode"             value="receive_comment">
            <input type="hidden" name="parent_post"      value="{$post_id}">
            <input type="hidden" name="content_mentions" value="">
            
            <div class="wpulse_post_comments_entry wpulse_post_comments_input">
                <div class="wpulse_post_comments_author_icon">
                    <img src="{$current_user_image}">
                </div>
                <div class="wpulse_post_comments_body">
                    <div class="wpulse_taggable_wrapper">
                        <textarea class="wpulse_taggable mobile_placeholder_added" name="content_text"
                                  placeholder="Write a comment. Use @ and some letters to tagg app users."
                                  moblie_placeholder="Write a comment. Tagging is not yet possible on mobiles."></textarea>
                    </div>
                    <div class="wpulse_post_comments_post_image">
                        <span class="fa fa-camera fa-2x pull-left"></span>
                        <input type="file" name="photo_file">
                    </div>
                    <div class="wpulse_post_comments_post_button">
                        <button type="submit">
                            Post comment &nbsp;
                            <span class="fa fa-play"></span>
                        </button>
                    </div>
                </div>
            </div><!-- /.wpulse_post_comments_input -->
        </form>
        
        <div class="wpulse_comments_loading_spinner">
            <span class="fa fa-spinner fa-spin fa-lg"></span>
        </div>
        
        <div class="wpulse_post_comments_container">
            {$_post_comments}
        </div>
        
    </div><!-- /.wpulse_post_comments -->
    
</div><!-- /.wpulse_post -->
