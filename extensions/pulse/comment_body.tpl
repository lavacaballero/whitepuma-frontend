
<div class="wpulse_post_comments_entry {$comment_highlighted}" post_id="{$post_id}" comment_id="{$comment_id}" comment_timestamp="{$comment_timestamp}">
    <div class="wpulse_post_comments_author_icon">
        <a href="{$comment_author_profile_url}" target="_blank"><img src="{$comment_author_image}"></a>
    </div>
    <div class="wpulse_post_comments_body">
        <div class="wpulse_post_comments_author_name"><a href="{$comment_author_profile_url}" target="_blank">{$comment_author_name}</a></div>
        {$comment_content}
        {$comment_author_signature_div}
    </div>
    <div class="wpulse_post_comments_controls">
        <div class="wpulse_post_comments_controls_timeago timeago" title="{$comment_date}"></div>
        <!--<fb:like class="wpulse_fb_button" href="{$comment_url}" layout="button_count" action="like"></fb:like>-->
        &nbsp;<a class="wpulse_post_control" href="{$comment_url}" title="Comment permalink">Permalink</a>
        <!--
        <span class="wpulse_post_control wpulse_admin pseudo_link fa fa-times fa-border" title="Delete this post"></span>
        <span class="wpulse_post_control wpulse_admin pseudo_link fa fa-ban   fa-border" title="Ban user and hide all his posts"></span>
        <span class="wpulse_post_control pseudo_link fa fa-exclamation-circle fa-border" title="Report post as spam"></span>
        -->
        <br clear="all">
    </div>
</div><!-- /.wpulse_post_comments_entry -->
