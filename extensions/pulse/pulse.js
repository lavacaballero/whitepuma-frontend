/**
 * Pulse JS functions
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

////////////
var wpulse =
////////////
{
    selector:                 '#wpulse',
    refreshing_seconds:       10,
    refreshing_interval:      null,

    /* Mandatory */
    cookie_prefix:            '',
    cookie_domain:            '',
    id_account:               '',
    account_image:            '',
    link_images_proxy:        '',
    posts_root_url:           '',
    toolbox_script:           '',

    /* Display defaults */
    type:                     '',
    position:                 '',
    comments_state:           'collapsed',
    disable_display_control : '',
    base_coin_filter:         '',
    allow_display_more:       'false',

    /* Content templates */
    _contents: { post_body: '', comment_body: '' },

    /* Etc */
    initializing:         true,
    show_single_post:     '',
    highlight_comment:    '',
    single_post_rendered: false,

    //////////////////////////
    init: function( settings )
    //////////////////////////
    {
        var myself = this;
        if( settings ) for( var i in settings ) myself[i] = settings[i];

        if( myself.type == '' )
        {
            if( $.cookie(myself.cookie_prefix + 'wpulse_layout_type') )
            {
                myself.type = $.cookie(myself.cookie_prefix + 'wpulse_layout_type');
            }
            else
            {
                myself.type = 'block';
            } // end if
        } // end if

        if( myself.position == '' )
        {
            if( $.cookie(myself.cookie_prefix + 'wpulse_layout_position') )
            {
                myself.position = $.cookie(myself.cookie_prefix + 'wpulse_layout_position');
            }
            else
            {
                myself.position = 'default';
            } // end if
        } // end if

        if( myself.comments_state == '' ) myself.comments_state = 'collapsed';

        $(myself.selector).find('input[name="wpulse_control_display"][value="' + myself.type + ':' + myself.position + '"]').prop('checked', true);

        if( myself.disable_display_control == 'true' )
            $(myself.selector).find('.wpulse_controls .wpulse_control_button[toggles="display"]').addClass('disabled');

        if( myself.allow_display_more == 'true' )
            $(myself.selector).find('.wpulse_load_more').show();

        if( myself.id_account == '' )
            $(myself.selector).find('.wpulse_controls .wpulse_control_button[offline_hide="true"]').hide();

        myself.check_mobile_exclussions();
        myself.render();

        myself.refreshing_interval = setInterval('wpulse.refresh()', myself.refreshing_seconds * 1000);
    }, // end function

    ////////////////////////////////////
    check_mobile_exclussions: function()
    ////////////////////////////////////
    {
        var myself = this;

        var is_mobile = /ios|android|blackberry|(windows.*phone)/i;
        if( is_mobile.test( navigator.userAgent ) )
        {
            $(myself.selector).find('.mobile_visible').show();
            $(myself.selector).find('.mobile_placeholder_added').each(function()
            {
                var new_placeholder = $(this).attr('moblie_placeholder');
                $(this).attr('placeholder', new_placeholder);
            }); // end function
        }
        else
        {
            $(myself.selector).find('.mobile_visible').remove();
        } // end if
    }, // end function

    //////////////////
    render: function()
    //////////////////
    {
        var myself = this;
        $(myself.selector).attr('type', myself.type).attr('position', myself.position);

        if( myself.type == 'block' )
            $('body').toggleClass('pulsed_at_right pulsed_at_left', false);
        else
            $('body').toggleClass('pulsed_at_right pulsed_at_left', false).toggleClass('pulsed_at_' + myself.position, true);

        $(myself.selector).find('button').button('destroy');

        $(myself.selector).find('.wpulse_control_buttons .wpulse_control_button[toggles]').click( function() { myself.expand_controls_drawer( $(this) ) } );

        // Interactivity for controls
        if(FB) if(FB.XFBML) FB.XFBML.parse();
        $(myself.selector).find('.wpulse_controls .expandible_textarea').expandingTextArea();
        $(myself.selector).find('.wpulse_controls .timeago').timeago();
        $(myself.selector).find('.wpulse_controls .wpulse_taggable')
            .expandingTextArea()
            .mentionsInput({
                minChars: 3,
                elastic: false,
                onDataRequest: function(mode, query, callback)
                {
                    var url = myself.toolbox_script + '?mode=find_users&q=' + escape(query) + '&wasuuup=' + parseInt(Math.random() * 1000000000000000);
                    $.getJSON(url, function(responseData)
                    {
                      responseData = _.filter(responseData, function(item) { return item.name.toLowerCase().indexOf(query.toLowerCase()) > -1 });
                      callback.call(this, responseData);
                    });
                }
            });

        // Event handlers
        $(myself.selector).find('form[name="wpulse_post_post"]').ajaxForm({
            target: '#wpulse_post_post_target',
            beforeSubmit: wpulse.validate_composer_submission,
            success:      wpulse.process_composer_submission_response
        });

        // Finalization
        $(myself.selector).show();
        myself.refresh();
    }, // end function

    refreshing:             false,
    last_refresh_timestamp: 0,
    allow_fallback_updates: false,
    /////////////////////////
    refresh: function(forced)
    /////////////////////////
    {
        var myself = this;
        if( forced ) myself.refreshing = false;
        if( myself.refreshing ) return;

        myself.refreshing = true;

        $(myself.selector).find('.wpulse_controls .wpulse_control_refresh').toggleClass('disabled', true);
        $(myself.selector).find('.wpulse_controls .wpulse_control_refresh span.fa').addClass('fa-spin');

        var fallbacks = '';
        if( myself.allow_fallback_updates )
        {
            myself.allow_fallback_updates = false;
            fallbacks = '&allow_fallback_uptades=true';
        } // end if

        var single_post_rendered = myself.single_post_rendered ? '&single_post_rendered=true' : '';
        if( myself.show_single_post != '' )
            var url = myself.toolbox_script + '?mode=get_updates'
                                            + '&post='    + myself.show_single_post
                                            + '&since='   + (myself.single_post_rendered ? myself.last_refresh_timestamp : 0)
                                            + single_post_rendered
                                            + '&wasuuup=' + parseInt(Math.random() * 1000000000000000);
        else
            var url = myself.toolbox_script + '?mode=get_updates'
                                            + '&since='            + myself.last_refresh_timestamp
                                            + '&base_coin_filter=' + myself.base_coin_filter
                                            + fallbacks
                                            + '&wasuuup=' + parseInt(Math.random() * 1000000000000000);
        $.getJSON(url, function(data)
        {
            $(myself.selector).find('.wpulse_controls .wpulse_control_refresh').toggleClass('disabled', false);
            $(myself.selector).find('.wpulse_controls .wpulse_control_refresh span.fa').removeClass('fa-spin');
            myself.refreshing = false;

            if( typeof data == 'undefined' ) return;

            myself.last_refresh_timestamp = data.last_timestamp;

            if( data.posts.length == 0 && data.comments.length == 0 ) return;

            if( data.posts.length > 0 )
            {
                data.posts.reverse();
                for( var i in data.posts )
                {
                    var post_data = data.posts[i]
                    if( $(myself.selector).find('.wpulse_post[post_id="'+post_data.id+'"]').length == 0 )
                        myself.add_post( post_data );
                } // end for
            } // end if

            if( data.comments.length > 0 )
            {
                data.comments.reverse();
                for( var i in data.comments )
                    myself.render_new_comments( data.comments[i].parent_post, [data.comments[i]] );
            } // end if

            if( myself.initializing ) myself.initializing = false;

            // Addition for Widget
            if( typeof wpuni_mcwidget != 'undefined' )
            {
                wpuni_mcwidget.translate_links();
                wpuni_mcwidget.render();
            } // end if
        }); // end .getJSON
    }, // end function

    //////////////////////////////////////////
    prepare_comments: function(comments_array)
    //////////////////////////////////////////
    {
        var myself = this;

        if( comments_array.length == 0 ) return [];

        var comments = [];
        for(var i in comments_array)
        {
            var comment = comments_array[i];
            if( $(myself.selector).find('.wpulse_post_comments_entry[post_id="' + comment.parent_post + '"][comment_id="' + comment.id + '"]').length > 0 )
                continue;

            var comment_html = myself._contents.comment_body;
            comment_html = comment_html.replace( /\{\$comment_id\}/g,                   comment.id );
            comment_html = comment_html.replace( /\{\$post_id\}/g,                      comment.parent_post );
            comment_html = comment_html.replace( /\{\$comment_timestamp\}/g,            comment.timestamp );
            comment_html = comment_html.replace( /\{\$comment_author_image\}/g,         comment.author_data.image );
            comment_html = comment_html.replace( /\{\$comment_author_name\}/g,          comment.author_data.name );
            comment_html = comment_html.replace( /\{\$comment_author_profile_url\}/g,   comment.author_data.profile_url );
            comment_html = comment_html.replace( /\{\$comment_date\}/g,                 comment.created.replace(" ", "T") );
            comment_html = comment_html.replace( /\{\$comment_content\}/g,              comment.content );
            comment_html = comment_html.replace( /\{\$comment_url\}/g,                  myself.posts_root_url + '?post=' + comment.parent_post + '&comment=' + comment.id );

            if( comment.author_data.signature == '' )
                comment_html = comment_html.replace( /\{\$comment_author_signature_div\}/g, '' );
            else
                comment_html = comment_html.replace( /\{\$comment_author_signature_div\}/g, '<div class="wpulse_comment_author_signature">' + comment.author_data.signature + '</div>' );

            if( myself.show_single_post == comment.parent_post && myself.highlight_comment == comment.id )
                comment_html = comment_html.replace( /\{\$comment_highlighted\}/g, 'wpulse_highlighed' );
            else
                comment_html = comment_html.replace( /\{\$comment_highlighted\}/g, '' );

            comments[comments.length] = comment_html;
        } // end for

        return comments;
    }, // end function

    ////////////////////////////////////////
    add_post: function(post_data, put_below)
    ////////////////////////////////////////
    {
        var myself = this;

        // Comments building
        var comments = myself.prepare_comments(post_data.comments);

        // Body building
        var post_body = myself._contents.post_body;

        if( typeof post_data.feed_data != 'undefined' )
        {
            post_body = post_body.replace( /\{\$post_destination\}/g,           post_data.feed_data.name );
            post_body = post_body.replace( /\{\$post_destination_url\}/g,       post_data.feed_data.url );
        } // end if

        var type_icon = post_data.type;
        if( type_icon == 'link' )
            if( typeof post_data.metadata != 'undefined' )
                if( typeof post_data.metadata.tipback_data != 'undefined' )
                    type_icon = 'link_tipback';

        post_body = post_body.replace( /\{\$root_url\}/g,                   root_url );
        post_body = post_body.replace( /\{\$post_id\}/g,                    post_data.id );
        post_body = post_body.replace( /\{\$post_type\}/g,                  post_data.type );
        post_body = post_body.replace( /\{\$post_type_icon\}/g,             type_icon );
        post_body = post_body.replace( /\{\$post_link\}/g,                  post_data.link );
        post_body = post_body.replace( /\{\$post_author_name\}/g,           post_data.author_data.name );
        post_body = post_body.replace( /\{\$post_author_image\}/g,          post_data.author_data.image );
        post_body = post_body.replace( /\{\$post_author_profile_url\}/g,    post_data.author_data.profile_url );
        post_body = post_body.replace( /\{\$post_created\}/g,               post_data.created );
        post_body = post_body.replace( /\{\$post_last_update\}/g,           post_data.last_update );
        post_body = post_body.replace( /\{\$post_date\}/g,                  post_data.created.replace(" ", "T") );
        post_body = post_body.replace( /\{\$post_caption\}/g,               post_data.caption );
        post_body = post_body.replace( /\{\$post_content\}/g,               post_data.content );
        post_body = post_body.replace( /\{\$post_signature\}/g,             post_data.signature );
        post_body = post_body.replace( /\{\$post_url\}/g,                   myself.posts_root_url + '?post=' + post_data.id );
        post_body = post_body.replace( /\{\$post_comment_count\}/g,         post_data.comments.length );
        post_body = post_body.replace( /\{\$toolbox_script\}/g,             myself.toolbox_script );
        post_body = post_body.replace( /\{\$_post_comments\}/g,             comments.join('\n\n') );
        post_body = post_body.replace( /\{\$comments_state\}/g,             myself.comments_state );

        if( post_data.target_coin == '' )
        {
            post_body = post_body.replace( /\{\$post_coin_icon_as_bgimage\}/g, 'none' );
        }
        else
        {
            post_body = post_body.replace( /\{\$target_coin\}/g, post_data.target_coin );
            var coin_image = $(myself.selector).find('.wpulse_coin_images_per_name div[coin_name="' + post_data.target_coin + '"]').text();
            post_body = post_body.replace( /\{\$post_coin_icon_as_bgimage\}/g, 'url(\'' + coin_image + '\')' );
        } // end if

        if( myself.id_account == '' )
            post_body = post_body.replace( '<img src="{$current_user_image}">', '' );
        else
            post_body = post_body.replace( /\{\$current_user_image\}/g,         myself.account_image );

        if( type_icon == 'rain' || type_icon == 'link' || type_icon == 'link_tipback' )
            post_body = post_body.replace( /\{\$goto_post_link\}/g, 'Go to ' + post_data.link );
        else
            post_body = post_body.replace( /\{\$goto_post_link\}/g, '' );

        if( type_icon == 'link_tipback' )
        {
            if( typeof post_data.metadata.tipback_data.tip_size == 'undefined' )
            {
                post_body = post_body.replace( /\{\$post_tipback_info\}/g, '' );
            }
            else
            {
                post_body = post_body.replace( /\{\$is_tipback\}/g, 'true' );
                var tipback_info = '<div class="wpost_tipback_info" style="background-image: url(\'' + post_data.metadata.tipback_data.coin_image + '\')">'
                                 +     'Follow this link to earn '
                                 +     post_data.metadata.tipback_data.tip_size + ' '
                                 +     post_data.metadata.tipback_data.coin_name_plural + '!'
                                 + '</div>';
                post_body = post_body.replace( /\{\$post_tipback_info\}/g, tipback_info );
            } // end if
        }
        else
        {
            post_body = post_body.replace( /\{\$is_tipback\}/g, '' );
            post_body = post_body.replace( /\{\$post_tipback_info\}/g, '' );
        } // end if

        if( typeof post_data.feed_data == 'undefined' )
            post_body = post_body.replace('<div class="wpulse_post_destinfo">', '<div class="wpulse_post_destinfo" style="display: none">');
        if( post_data.caption == '' )
            post_body = post_body.replace('<div class="wpulse_post_caption">', '<div class="wpulse_post_caption" style="display: none">');

        if( put_below ) $(myself.selector).find('.wpulse_contents_wrapper').append(post_body);
        else            $(myself.selector).find('.wpulse_contents_wrapper').prepend(post_body);

        // Interactivity additions

        myself.check_mobile_exclussions();
        if( post_data.target_coin == '' )
            $(myself.selector).find('.wpulse_post[post_id="'+post_data.id+'"] .wpulse_post_heading .wpulse_coin_icon').remove();
        if(FB) if(FB.XFBML) FB.XFBML.parse();
        $(myself.selector).find('.wpulse_post[post_id="'+post_data.id+'"] .expandible_textarea').expandingTextArea();
        $(myself.selector).find('.wpulse_post[post_id="'+post_data.id+'"] .timeago').timeago();
        $(myself.selector).find('.wpulse_post[post_id="'+post_data.id+'"] .wpulse_taggable')
            .expandingTextArea()
            .mentionsInput({
                minChars: 3,
                elastic: false,
                onDataRequest: function(mode, query, callback)
                {
                    var url = myself.toolbox_script + '?mode=find_users&q=' + escape(query) + '&wasuuup=' + parseInt(Math.random() * 1000000000000000);
                    $.getJSON(url, function(responseData)
                    {
                      responseData = _.filter(responseData, function(item) { return item.name.toLowerCase().indexOf(query.toLowerCase()) > -1 });
                      callback.call(this, responseData);
                    });
                }
            })
            ;
        if( type_icon == 'rain' || type_icon == 'link' || type_icon == 'link_tipback' )
            $(myself.selector).find('.wpulse_post[post_id="'+post_data.id+'"] .wpulse_post_content_wrapper').click(
                function() { window.open( wpulse.toolbox_script + '?go=' + escape($(this).closest('.wpulse_post').attr('href')) );
            }); // end .click
        if( myself.id_account == '' )
            $(myself.selector).find('.wpulse_post[post_id="'+post_data.id+'"] form.wpulse_comments_form').hide();

        // Event handlers
        $(myself.selector).find('.wpulse_post[post_id="'+post_data.id+'"] form.wpulse_comments_form').ajaxForm({
            target: '#wpulse_post_comment_target',
            beforeSubmit: wpulse.validate_comment_submission,
            success:      wpulse.process_comment_submission_response
        });

        if( myself.comments_state == 'expanded' )
            $(myself.selector).find('.wpulse_post[post_id="'+post_data.id+'"] .wpulse_post_comments_trigger')
                .removeClass('pseudo_link collapsed')
                .attr('title', '')
                .attr('onclick', '');

        myself.single_post_rendered = true;
        if( myself.show_single_post != '' && myself.highlight_comment != '' )
            $.scrollTo('.wpulse_post_comments_entry[post_id="'+myself.show_single_post+'"][comment_id="'+myself.highlight_comment+'"]', 'fast');
        myself.pulsate( $(myself.selector).find('.wpulse_post[post_id="'+post_data.id+'"]') );
    }, // end function

    loading_more: false,
    /////////////////////
    load_more: function()
    /////////////////////
    {
        var myself = this;
        if( myself.loading_more ) return;
        myself.loading_more = true;

        var last_post_date = $(myself.selector).find('.wpulse_contents_wrapper .wpulse_post:last').attr('last_update');

        var url = myself.toolbox_script + '?mode=get_updates'
                                        + '&until_date='       + last_post_date
                                        + '&base_coin_filter=' + myself.base_coin_filter
                                        + '&only_posts='       + 'true'
                                        + '&wasuuup='          + parseInt(Math.random() * 1000000000000000);
        $(myself.selector).find('.wpulse_load_more').toggleClass('disabled', true);
        $(myself.selector).find('.wpulse_loading').show();
        $.getJSON(url, function(data)
        {
            myself.loading_more = false;
            $(myself.selector).find('.wpulse_load_more').toggleClass('disabled', false);
            $(myself.selector).find('.wpulse_loading').hide();

            if( data.posts.length == 0 && data.comments.length == 0 )
            {
                $(myself.selector).find('.wpulse_load_more').hide();
                return;
            } // end if

            if( data.posts.length > 0 )
            {
                for( var i in data.posts )
                {
                    var post_data = data.posts[i]
                    if( $(myself.selector).find('.wpulse_post[post_id="'+post_data.id+'"]').length == 0 )
                        myself.add_post( post_data, true );
                } // end for
            } // end if

            // Addition for Widget
            if( typeof wpuni_mcwidget != 'undefined' )
            {
                wpuni_mcwidget.translate_links();
                wpuni_mcwidget.render();
            } // end if
        }); // end .getJSON
    }, // end function

    ///////////////////////////////////////////////////
    expand_controls_drawer: function( $button_clicked )
    ///////////////////////////////////////////////////
    {
        if( $button_clicked.hasClass('selected') ) return;
        if( $button_clicked.hasClass('disabled') ) return;

        $button_clicked.closest('.wpulse_control_buttons').find('.wpulse_control_button').toggleClass('selected', false);
        $button_clicked.toggleClass('selected', true);

        var control_to_display = $button_clicked.attr('toggles');
        $button_clicked.closest('.wpulse_controls').toggleClass('expanded', true);
        $button_clicked.closest('.wpulse_controls').find('.wpulse_control_settings:not([toggle_for="'+control_to_display+'"])').toggleClass('selected', false);
        $button_clicked.closest('.wpulse_controls').find('.wpulse_control_settings[toggle_for="'+control_to_display+'"]').toggleClass('selected', true);

        if(FB) if(FB.XFBML) FB.XFBML.parse();
    }, // end function

    ////////////////////////////////////
    collapse_controls_drawer: function()
    ////////////////////////////////////
    {
        var myself = this;

        $(myself.selector).find('.wpulse_control_buttons .wpulse_control_button[toggles].selected').toggleClass('selected', false);
        $(myself.selector).find('.wpulse_control_settings').toggleClass('selected', false);
        $(myself.selector).find('.wpulse_controls').toggleClass('expanded', false);
    }, // end function

    ////////////////////////////////////
    switch_layout: function( new_style )
    ////////////////////////////////////
    {
        var myself = this;
        var parts  = new_style.split(':');
        myself.collapse_controls_drawer();

        myself.type     = parts[0];
        myself.position = parts[1];

        $.cookie( myself.cookie_prefix + 'wpulse_layout_type',     myself.type,     { expires: 365, path: '/', domain: myself.cookie_domain } );
        $.cookie( myself.cookie_prefix + 'wpulse_layout_position', myself.position, { expires: 365, path: '/', domain: myself.cookie_domain } );

        myself.render();
    }, // end function

    ///////////////////////////////////
    toggle_comments: function( source )
    ///////////////////////////////////
    {
        var myself = this;
        var $post  = $(source).closest('.wpulse_post');

        $post.find('.wpulse_post_comments').toggleClass('expanded collapsed');
        if( $post.find('.wpulse_post_comments:visible').length > 0 )
            $post.attr('comments_visible', 'true');
        else
            $post.attr('comments_visible', '');
        $(source).toggleClass('expanded collapsed');

        if(FB) if(FB.XFBML) FB.XFBML.parse();
    }, // end function

    //////////////////////////////////////////
    toggle_composer: function( composer_case )
    //////////////////////////////////////////
    {
        var myself = this;

        $(myself.selector).find('.wpulse_control_settings[toggle_for="composer"] .wpulse_post_type_toggler:not([toggles="' + composer_case + '"])').toggleClass('selected', false);
        $(myself.selector).find('.wpulse_control_settings[toggle_for="composer"] .wpulse_post_type_toggler[toggles="' + composer_case + '"]').toggleClass('selected', true);

        $(myself.selector).find('.wpulse_control_settings[toggle_for="composer"] .field').each( function()
        {
            var show_for = $(this).attr('for');
            if( typeof show_for == 'undefined' ) return;

            if( show_for.indexOf(composer_case) >= 0 ) $(this).show();
            else                                       $(this).hide();
        }); // end .each

        $(myself.selector).find('form[name="wpulse_post_post"] input[name="type"]').val(composer_case);
    }, // end function

    ////////////////////////////////////
    remove_all_filters: function( what )
    ////////////////////////////////////
    {
        var myself = this;
        $(myself.selector).find('form[name="wpulse_settings_filter_' + what + 's"] input[name="wp_user_settings[remove_all_' + what + '_filters]"]').val('true');
        $(myself.selector).find('form[name="wpulse_settings_filter_' + what + 's"] input[type="checkbox"]').prop('checked', false);
    }, // end function

    ///////////////////////////////////////////////////////
    validate_composer_submission: function(formData, $form)
    ///////////////////////////////////////////////////////
    {
        $form.find('.wpulse_taggable').mentionsInput('getMentions', function(data)
        {
            var field_name         = $(this).attr('name');
            var mentions_container = field_name.replace('_text', '_mentions');
            var final_data         = JSON.stringify(data);
            // console.log( mentions_container, ' :~ ', final_data );
            for(var i in formData)
                if( formData[i].name == mentions_container ) formData[i].value = final_data;
        }); // end .each

        $form.find('button[type="submit"]')
            .prop('disabled', true)
            .find('.fa')
                .removeClass('fa-play')
                .addClass('fa-spinner fa-spin');
    }, // end function

    ////////////////////////////////////////////////////////////////////////////
    process_composer_submission_response: function(response, status, xhr, $form)
    ////////////////////////////////////////////////////////////////////////////
    {
        var myself = wpulse;

        $form.find('button[type="submit"]')
            .prop('disabled', false)
            .find('.fa')
                .removeClass('fa-spinner fa-spin')
                .addClass('fa-play');

        if( response.indexOf('OK') < 0 )
        {
            alert( response );
            return;
        } // end if

        var parts = response.split('|');
        if( parts[1] != '' && parts[1].indexOf('OK') < 0 ) alert( parts[1] );

        myself.refresh();
        myself.reset_composer();
        myself.collapse_controls_drawer();
    }, // end function

    //////////////////////////////////////////////////////
    validate_comment_submission: function(formData, $form)
    //////////////////////////////////////////////////////
    {
        $form.find('.wpulse_taggable').mentionsInput('getMentions', function(data)
        {
            var field_name         = $(this).attr('name');
            var mentions_container = field_name.replace('_text', '_mentions');
            var final_data         = JSON.stringify(data);
            // console.log( mentions_container, ' :~ ', final_data );
            for(var i in formData)
                if( formData[i].name == mentions_container ) formData[i].value = final_data;
        }); // end .each

        $form.find('button[type="submit"]')
            .prop('disabled', true)
            .find('.fa')
                .removeClass('fa-play')
                .addClass('fa-spinner fa-spin');
    }, // end function

    ///////////////////////////////////////////////////////////////////////////
    process_comment_submission_response: function(response, status, xhr, $form)
    ///////////////////////////////////////////////////////////////////////////
    {
        var myself = wpulse;

        $form.find('button[type="submit"]')
            .prop('disabled', false)
            .find('.fa')
                .removeClass('fa-spinner fa-spin')
                .addClass('fa-play');

        if( response != "OK" )
        {
            alert( response );
            return;
        } // end if

        $form[0].reset();
        var post_id = $form.attr('post_id');
        myself.refresh_comments_for_post(post_id);
    }, // end function

    //////////////////////////////////////////////
    refresh_comments_for_post: function( post_id )
    //////////////////////////////////////////////
    {
        var myself = this;

        var $comments_container    = $(myself.selector).find('.wpulse_post[post_id="'+post_id+'"] .wpulse_post_comments_container');
        var last_comment_timestamp = $comments_container.find('.wpulse_post_comments_entry:first').attr('comment_timestamp');
        if( typeof last_comment_timestamp == 'undefined' ) last_comment_timestamp = '';

        var url = myself.toolbox_script + '?mode=get_comments'
                                        + '&parent_post=' + post_id
                                        + '&since='       + last_comment_timestamp
                                        + '&wasuuup='     + parseInt(Math.random() * 1000000000000000);

        $(myself.selector).find('.wpulse_post[post_id="'+post_id+'"] .wpulse_comments_loading_spinner').show();
        $.getJSON(url, function(data)
        {
            $(myself.selector).find('.wpulse_post[post_id="'+post_id+'"] .wpulse_comments_loading_spinner').hide();
            if( data.message != 'OK' ) return;
            myself.render_new_comments(post_id, data.comments);
        }); // end .getJSON
    }, // end function

    ////////////////////////////////////////////////
    render_new_comments: function(post_id, comments)
    ////////////////////////////////////////////////
    {
        var myself = this;

        var $comments_container    = $(myself.selector).find('.wpulse_post[post_id="'+post_id+'"] .wpulse_post_comments_container');
        comments = myself.prepare_comments(comments);
        $comments_container.prepend( comments.join('\n') );

        // Interaction
        if(FB) if(FB.XFBML) FB.XFBML.parse();
        $comments_container.find('.timeago').timeago();

        $(myself.selector).find('.wpulse_post[post_id="'+post_id+'"] .wpulse_post_comments_trigger .wpulse_post_comments_count')
            .text( $comments_container.find('.wpulse_post_comments_entry').length );

        // Addition for Widget
        if( typeof wpuni_mcwidget != 'undefined' )
        {
            wpuni_mcwidget.translate_links();
            wpuni_mcwidget.render();
        } // end if

        myself.pulsate( $(myself.selector).find('.wpulse_post[post_id="'+post_id+'"]') );
    }, // end function

    //////////////////////////
    reset_composer: function()
    //////////////////////////
    {
        var myself = this;

        $(myself.selector).find('form[name="wpulse_post_post"]')[0].reset();
        $(myself.selector).find('.wpulse_control_settings[toggle_for="composer"] .wpost_to_facebook').hide();
        myself.toggle_composer('text');
    }, // end function

    grabbing_url: false,
    //////////////////////////////////////////
    fill_composer_fields_from_link: function()
    //////////////////////////////////////////
    {
        var myself = this;
        // if( myself.grabbing_url ) return;

        var previous_url = $(myself.selector).find('form[name="wpulse_post_post"] input[name="link"]').attr('previous_url');
        var grabbing_url = $(myself.selector).find('form[name="wpulse_post_post"] input[name="link"]').val().trim();
        // if( grabbing_url == previous_url ) return;
        if( grabbing_url.length < 11 )
        {
            $(myself.selector).find('form[name="wpulse_post_post"] .link_field .wpulse_error_grabbing_url').show();
            $(myself.selector).find('form[name="wpulse_post_post"] .link_field .wpulse_error_contents').html("URL too short").show();
            return;
        } // end if
        $(myself.selector).find('form[name="wpulse_post_post"] input[name="link"]').attr('previous_url', grabbing_url);

        myself.grabbing_url = true;
        var url = myself.toolbox_script + '?mode=grab_page_data&url=' + escape(grabbing_url) + '&wasuuup=' + parseInt(Math.random() * 1000000000000000);
        $(myself.selector).find('form[name="wpulse_post_post"] .link_field .loading_switcher .normal').hide();
        $(myself.selector).find('form[name="wpulse_post_post"] .link_field .loading_switcher .loading').show();
        $(myself.selector).find('form[name="wpulse_post_post"] .link_field .wpulse_error_grabbing_url').hide();
        $(myself.selector).find('form[name="wpulse_post_post"] .link_field .wpulse_error_contents').html('').hide();
        $.get(url, function(response)
        {
            myself.grabbing_url = false;
            $(myself.selector).find('form[name="wpulse_post_post"] .link_field .loading_switcher .loading').hide();
            $(myself.selector).find('form[name="wpulse_post_post"] .link_field .loading_switcher .normal').show();

            if( response.indexOf('OK') < 0 )
            {
                $(myself.selector).find('form[name="wpulse_post_post"] .link_field .wpulse_error_grabbing_url').show();
                $(myself.selector).find('form[name="wpulse_post_post"] .link_field .wpulse_error_contents').html(response).show();
            } // end if

            var parts = response.split('\t');

            if( parts[1] != '' )
            {
                $(myself.selector).find('form[name="wpulse_post_post"] textarea[name="caption_text"]').val( parts[1] );
                myself.pulsate( $(myself.selector).find('form[name="wpulse_post_post"] textarea[name="caption_text"]') );
            } // end if
            if( parts[2] != '' )
            {
                $(myself.selector).find('form[name="wpulse_post_post"] textarea[name="content_text"]').val( parts[2] );
                myself.pulsate( $(myself.selector).find('form[name="wpulse_post_post"] textarea[name="content_text"]') );
            } // end if
            if( parts[3] != '' )
            {
                $(myself.selector).find('form[name="wpulse_post_post"] input[name="photo_url"]').val( parts[3] );
                myself.pulsate( $(myself.selector).find('form[name="wpulse_post_post"] input[name="photo_url"]') );
            } // end if
        }); // end function

    }, // end function

    //////////////////////////
    pulsate: function( $what )
    //////////////////////////
    {
        var myself = wpulse;
        if( myself.initializing ) return;
        $what.addClass('wpulse_highlighed', 100)
             .delay(50)
             .removeClass('wpulse_highlighed', 100);
    }, // end function

    ////////////////////////////////////////////////////////////////////////////////
    // http://stackoverflow.com/questions/1219860/html-encoding-in-javascript-jquery
    ////////////////////////////////////////////////////////////////////////////////
    htmlEscape: function(str)
    /////////////////////////
    {
        return String(str)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
        ;
    }, // end function

    /////////////////////////////////////////////////////////////////////////////////////////////////
    search_tip_recipients: function( $textbox, $ajax_target, $added_target_div, $added_control_list )
    /////////////////////////////////////////////////////////////////////////////////////////////////
    {
        var myself = this;

        var url = myself.toolbox_script
                + '?mode=find_tipping_recipients'
                + '&q='       + escape($('#wpulse_tip_attachments_query').val())
                + '&wasuuup=' + parseInt( Math.random() * 1000000000000000 )
                ;
        $ajax_target
            .load(url, function()
            {
                $ajax_target.find('.item').each(function()
                {
                    $(this).append('<span class="fa fa-plus"></span>');
                    $(this).click(function() {
                        myself.add_tip_recipient($(this), $ajax_target, $added_target_div, $added_control_list);
                    });
                });
            })
            .show();
        $textbox.val('');
    }, // end function

    ///////////////////////////////////////////////////////////////////////////////////////////////
    add_tip_recipient: function($source, $source_container, $added_target_div, $added_control_list)
    ///////////////////////////////////////////////////////////////////////////////////////////////
    {
        var myself = this;
        var id_account = $source.attr('id_account');
        if( $added_target_div.find('.idem[id_account="' + id_account + '"]').length > 0 )
        {
            $source.remove();
            return;
        } // end if

        $source.find('span.fa').remove();
        $added_target_div.find('span.none').hide();
        var html = '<span class="item unprocessed" id_account="'+$source.attr('id_account')+'">'
                 + $source.html()
                 + '<span class="fa fa-times"></span>'
                 + '</span>';
        $source.remove();
        $added_target_div.append(html);
        $added_target_div.find('.item.unprocessed').each(function()
        {
            $(this).click(function()
            {
                myself.remove_tip_recipient($(this), $added_target_div, $added_control_list);
                $(this).removeClass('unprocessed');
            });
        }); // end function
        if( $source_container.find('.item').length == 0 )
            $source_container.hide();

        myself.recalc_tip_recipients($added_target_div, $added_control_list);
    }, // end function

    ///////////////////////////////////////////////////////////////////////////////
    remove_tip_recipient: function($source, $added_target_div, $added_control_list)
    ///////////////////////////////////////////////////////////////////////////////
    {
        var myself = this;
        $source.remove();
        myself.recalc_tip_recipients($added_target_div, $added_control_list);
        if( $added_target_div.find('.item').length == 0 )
            $added_target_div.find('span.none').show();
    },

    ///////////////////////////////////////////////////////////////////////
    recalc_tip_recipients: function($added_target_div, $added_control_list)
    ///////////////////////////////////////////////////////////////////////
    {
        var final_list = []
        $added_target_div.find('.item').each(function()
        {
            var id_account = $(this).attr('id_account');
            var name       = $(this).text();
            final_list[final_list.length] = {'id_account': id_account, 'name': name}
        }); // end function

        $added_control_list.val( JSON.stringify(final_list) );
    },

} // end var wpulse
