<?
    /**
     * Platform Extension: Websites / Embedding script
     *
     * @package    WhitePuma OpenSource Platform
     * @subpackage Frontend
     * @copyright  2014 Alejandro Caballero
     * @author     Alejandro Caballero - acaballero@lavasoftworks.com
     * @license    GNU-GPL v3 (http://www.gnu.org/licenses/gpl.html)
     *
     * Copyright (C) 2014 Alejandro Caballero
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
     * Requirements:
     * @param string  website_public_key Invoking website key
     * @param boolean debug_mode         true for debugging mode
     * @param boolean preload_only       true or preloading and no link conversion/scanning
     */

    header("Content-Type: text/javascript; charset=utf-8");

    if( empty($_GET["website_public_key"]) ) die("if(console) if(console.log) console.log('$config[app_display_shortname] >>> ERROR: website_public_key not provided when calling this script.')");

    $root_url = "../../../..";
    if( ! is_file("$root_url/config.php") ) die("if(console) if(console.log) console.log('$config[app_display_shortname] >>> ERROR: config file not found.')");
    include "$root_url/config.php";
    include "$root_url/functions.php";
    db_connect();

    # Website validation
    $query = "select * from ".$config->db_tables["websites"]." where public_key = '".addslashes($_REQUEST["website_public_key"])."'";
    $res   = mysql_query($query);
    if( mysql_num_rows($res) == 0 ) die("if(console) if(console.log) console.log('$config->app_display_shortname >>> ERROR: Website ".addslashes($_REQUEST["website_public_key"])." not found in our database.')");
    $website = mysql_fetch_object($res);
    if( $website->state == 'disabled' ) die("if(console) if(console.log) console.log('$config->app_display_shortname >>> ERROR: Website $website->name is disabled.')");
    if( $website->state == 'locked'   ) die("if(console) if(console.log) console.log('$config->app_display_shortname >>> ERROR: Website $website->name is locked.')");
    mysql_free_result( $res );

    $coins_data = array();
    foreach($config->current_tipping_provider_data["per_coin_data"] as $coin_name => $coin_data)
        if( ! $coin_data["coin_disabled"] )
            $coins_data[$coin_name] = array(
                "logo"    => $coin_data["coin_image"],
                "symbol"  => strtoupper($coin_data["coin_sign"]),
                "min_tip" => $coin_data["min_transaction_amount"]
                );
    ksort($coins_data);
    $coins_data = array_merge(
        array( "_default_" => array("symbol" => "N/A", "min_tip" => 0, "logo" => $config->facebook_canvas_image)        ),
        array( "_none_"    => array("symbol" => "N/A", "min_tip" => 0, "logo" => $config->facebook_canvas_image)        ),
        array( "_website_" => array("symbol" => "N/A", "min_tip" => 0, "logo" => $config->buttons_default_website_logo) ),
        array( "_custom_"  => array("symbol" => "N/A", "min_tip" => 0, "logo" => $config->buttons_default_custom_logo)  ),
        $coins_data
    );
    $coins_data = json_encode($coins_data);

    $token = encryptRJ256($config->tokens_encryption_key, $_SERVER["HTTP_REFERER"]."\t".$website->public_key."\t".uniqid(true));
    # header("X-WP-GBD-Token-Stage1: " . $token);
    @touch("/tmp/_tk_".md5($token));

    /*
        // http://css-tricks.com/snippets/php/intelligent-php-cache-control/
        // get the last-modified-date of this very file
        $lastModified=max(array(filemtime("$root_url/config.php"), filemtime(__FILE__)));
        // get a unique hash of this file (etag)
        $etagFile = md5(md5_file("$root_url/config.php") . "/" . md5_file(__FILE__));
        // get the HTTP_IF_MODIFIED_SINCE header if set
        $ifModifiedSince=(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false);
        // get the HTTP_IF_NONE_MATCH header if set (etag: unique file hash)
        $etagHeader=(isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);
        // set last-modified header
        header("Last-Modified: ".gmdate("D, d M Y H:i:s", $lastModified)." GMT");
        // set etag-header
        header("Etag: $etagFile");
        // make sure caching is turned on
        header('Cache-Control: public, must-revalidate');
        // check if page has changed. If not, send 304 and exit
        if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])==$lastModified || $etagHeader == $etagFile)
        {
            header("HTTP/1.1 304 Not Modified");
            exit;
        } // end if
    */

    if( empty($_REQUEST["debug_mode"])   ) $_REQUEST["debug_mode"] = "false";
    if( empty($_REQUEST["preload_only"]) ) $_REQUEST["preload_only"] = "false";
?>
// <script type="text/javascript">
    //////////////////////////////
    var wpuni_mcwidget =
    //////////////////////////////
    {
        _debug_mode:                 '<?=$_REQUEST["debug_mode"]?>' == 'true',
        _preload_only:               '<?=$_REQUEST["preload_only"]?>' == 'true',
        _interactive_caption:        '<?= $config->interactive_buttons_hover_title ?>',
        _stylesheet:                 '<?= $config->buttons_universal_absolute_css_url ?>?v=<?=$config->scripts_and_styles_version?>',
        _invoking_website_token:     '<?= $token ?>',
        _invoking_website_token_b64: '<?= base64_encode($token) ?>',
        _zero_counters_markup:       '<?= addslashes($config->buttons_zerocounters_markup) ?>',
        _empty_table_markup:         '<?= addslashes($config->buttons_empty_table_markup) ?>',
        _default_selector:           '<?= $config->buttons_default_selector ?>',
        _default_selector_classname: '<?= $config->buttons_default_selector_raw_classname ?>',
        _api_location:               '<?= $config->buttons_api_location ?>',
        _libpath:                    '<?= $config->buttons_libpath ?>',
        _buttonizer_selector:        '<?= $config->buttons_buttonizer_selector ?>',
        _main_logo:                  '<?= $config->buttons_buttonizer_website_logo ?>',
        _main_url:                   '<?= $config->buttons_buttonizer_website_url ?>',
        _widget_heading_caption:     '<?= $config->buttons_buttonizer_heading_caption ?>',
        _buttonizer_root_url:        '<?= $config->buttons_buttonizer_iframe_src ?>',
        _iframe_loader:              '<?= $config->buttons_buttonizer_iframe_loader ?>',
        _support_link:               '<?= $config->website_pages["support"] ?>',
        _about_widget_link:          '<?= $config->buttons_about_widget_link ?>',
        _aux_images_path:            '<?= $config->buttons_aux_images_path ?>',
        _leech_jar_logo:             '<?= $config->buttons_leech_jar_public_url ?>',
        _analytics_absolute_url:     '<?= $config->buttons_analytics_absolute ?>',

        ///////////////////////////
        translate_links: function()
        ///////////////////////////
        {
            var myself = this;

            if( jQuery(myself._buttonizer_selector).length > 0 )
            {
                jQuery(myself._buttonizer_selector).each(function()
                {
                    var href = jQuery(this).attr('href');
                    if( href.indexOf('&amp;') >= 0 )
                        jQuery(this).attr('href', href.replace('&amp;', '&') );
                    if( href.indexOf('?') < 0 ) return;
                    if( href.indexOf('&') < 0 ) return;

                    var final_params = []
                    var parts        = href.split('?');
                    var params       = parts[1].split('&');
                    for( var i in params )
                    {
                        // console.log( 'i := (', typeof params[i], ') ', params[i] );
                        if( typeof params[i] != 'string' ) continue;
                        var parts       = params[i].split('=');
                        var param_name  = parts[0];
                        var param_value = unescape(parts[1]);
                        final_params[final_params.length] = param_name + '="' + param_value + '"';
                    } // end for

                    var html = '<div class="' + myself._default_selector_classname + '" converted_link ' + final_params.join(' ') + '></div>';
                    jQuery(this).replaceWith(html);
                }); // end .each
            } // end if
        }, // end function

        ////////////////////////////////
        set: function(selector, params )
        ////////////////////////////////
        {
            for( var i in params ) jQuery(selector).attr(i, params[i]);
        }, // end function

        /////////////////////////////////////////
        get_button_data: function($src, callback)
        /////////////////////////////////////////
        {
            var myself = this;
            if( typeof $src == 'undefined' ) return;

            // Let's check if we're on a pre-rendered button
            // console.log( myself.website_public_key );
            // console.log( myself.button_id );
            if( $src.attr('website_public_key') == '' || $src.attr('button_id') == '' || $src.attr('is_static') == 'true' )
            {
                var dummy_counters = {
                    'BitcoinBITS': {symbol: 'BITS', amount: 'XXX'  },
                    'Litecoin':    {symbol: 'LTC',  amount: 'X.XXX'},
                    'Dogecoin':    {symbol: 'DOGE', amount: 'X,XX' }
                };
                var dummy_table_data = [
                    {from: 'Dummy User', coin_name: 'BitcoinBITS',  amount: 'XXX',   symbol: 'BITS', since: 'x days ago'},
                    {from: 'Dummy User', coin_name: 'Litecoin',     amount: 'X.XXX', symbol: 'LTC',  since: 'x days ago'},
                    {from: 'Dummy User', coin_name: 'Dogecoin',     amount: 'XXX',   symbol: 'DOGE', since: 'x days ago'},
                ]
                $src.data( 'table_data',        dummy_table_data );
                $src.data( 'counters',          dummy_counters);
                $src.data( 'allow_intreaction', false );
                $src.attr( 'processed',         true);

                if( typeof callback != 'undefined' ) callback();
                return;
            }
            else
            {
                var ref         = escape( $src.attr('ref') );         if( ref         == 'undefined' ) ref         = '';
                var entry_id    = escape( $src.attr('entry_id') );    if( entry_id    == 'undefined' ) entry_id    = '';
                var target_data = escape( $src.attr('target_data') ); if( target_data == 'undefined' ) target_data = '';

                // If the entry id is not set, we'll set it to the document URL
                if( entry_id == '' ) entry_id = location.href;
                if( entry_id.indexOf('#') >= 0 )
                {
                    var parts = entry_id.split('#');
                    entry_id  = parts[0];
                } // end if

                var params = {
                    token:              myself._invoking_website_token,
                    website_public_key: $src.attr('website_public_key'),
                    button_id:          $src.attr('button_id'),
                    entry_id:           entry_id,
                    ref:                ref,
                    target_data:        target_data
                }
                var url = myself._api_location + 'get_button_data.php?callback=?';
                jQuery.getJSON(url, params, function(data)
                {
                    if( data.message != 'OK' )
                    {
                        // Throwing an error message
                        if(myself._debug_mode && console && console.log)
                            console.log(data.message);
                        $src.attr( 'button_type',          'round_button'   );
                        $src.attr( 'color_scheme',         'red'            );
                        $src.attr( 'caption',              data.message     );
                        $src.attr( 'default_coin',         '_none_'         );
                        $src.attr( 'button_logo',          '_default_'      );
                        $src.attr( 'hide_tips_counter',    'true'           );
                        $src.attr( 'drop_shadow',          'true'           );
                        $src.attr( 'inverted_drop_shadow', 'false'          );
                        $src.data( 'table_data',            null            );
                        $src.data( 'counters',              null            );
                        $src.data( 'allow_intreaction',     false           );
                        $src.data( 'entry_id',              ''              );
                        $src.data( 'entry_title',           ''              );
                        $src.data( 'target_data',           ''              );
                        if( ! myself._debug_mode )  $src.toggleClass(myself._default_selector_classname, false);
                        $src.attr('processed', true);
                        if(myself._debug_mode && console && console.log)
                            console.log( 'not allowed interaction: ' + $src.attr('caption') );
                        return false;
                    }
                    else
                    {
                        // Mapping from incoming data (just appearance)
                        var custom_caption = $src.attr('caption');
                        var button_caption = data.data.properties.caption;
                        if( custom_caption && data.data.owner_class != 'standard' )
                            button_caption = custom_caption;
                        $src.attr( 'button_type',          data.data.type                            );
                        $src.attr( 'color_scheme',         data.data.color_scheme                    );
                        $src.attr( 'caption',              button_caption                            );
                        $src.attr( 'default_coin',         data.data.properties.default_coin         );
                        $src.attr( 'button_logo',          data.data.properties.button_logo          );
                        $src.attr( 'hide_tips_counter',    data.data.properties.hide_tips_counter    );
                        $src.attr( 'drop_shadow',          data.data.properties.drop_shadow          );
                        $src.attr( 'inverted_drop_shadow', data.data.properties.inverted_drop_shadow );
                        $src.data( 'table_data',           data.data.table_data                      );
                        $src.data( 'table_data_tlength',   data.data.table_data_tlength              );
                        $src.data( 'counters',             data.data.counters                        );
                        $src.data( 'allow_intreaction',    true                                      );
                        if(myself._debug_mode && console && console.log)
                            console.log( 'allowed interaction: ' + $src.attr('caption') );
                    } // end if
                    if( $src.data('table_data') == null ) $src.data('table_data', myself._empty_table_markup);
                    $src.attr('processed', true);

                    if( typeof callback != 'undefined' ) callback();
                }); // end .getJSON
            } // end if

        }, // end function

        //////////////////////////
        render: function(selector)
        //////////////////////////
        {
            if( typeof selector == 'undefined' ) selector = this._default_selector;

            var myself = this;

            jQuery(selector).each( function()
            {
                var $this = jQuery(this);

                // Let's enforce the selector class
                $this.toggleClass(myself._default_selector_classname, true);

                // Preprocessing
                if( $this.attr('processed') != 'true' )
                    return myself.get_button_data( $this, function() { myself.render($this) } );

                // Let's build the markup
                var logo = $this.attr('button_logo');
                if(      typeof logo == 'undefined' )                    logo = '';
                else if( logo.indexOf('inset:') >= 0)                    logo = logo.replace('inset:', '');
                else if( typeof myself.coins_data[logo] == 'undefined' ) logo = '';
                else                                                     logo = myself.coins_data[logo]['logo'];
                if( logo == '' )      logo = myself._main_logo;
                else if( logo == '%leech_jar_url%' ) logo = myself._leech_jar_logo;

                // if( $this.find('div').length == 0 )
                // {
                    // Table markup
                    var table_added_class = '';
                    var table_markup = '';
                    if( typeof $this.data('table_data') == 'string' )
                    {
                        table_added_class = 'empty';
                    }
                    else
                    {
                        var table_data = $this.data('table_data');
                        for(var i in table_data)
                        {
                            entry = table_data[i];
                            if( typeof myself.coins_data[entry.coin_name] == 'undefined' ) continue;
                            table_markup = table_markup
                                         + '<span class="wptw_table_entry" style="background-image: url(' + myself.coins_data[entry.coin_name]['logo'] + ')">'
                                         + '' + entry.from + ': <span>' + entry.amount + ' ' + myself.coins_data[entry.coin_name]['symbol'] + '</span> <span>[' + entry.since + ']</span>'
                                         + '</span>'
                                         ;
                        } // end for

                        if( $this.data('table_data_tlength') )
                        {
                            var length = parseInt( $this.data('table_data_tlength') );
                            if( length == 20 )
                            {
                                table_markup = table_markup
                                             + '<a class="wptw_table_entry" style="padding-left: 0; font-weight: bold;" href="' + myself._analytics_absolute_url + '/' + escape($this.attr('website_public_key')) + '/' + escape($this.attr('button_id')) + '/?wasuuup=' + parseInt (Math.random() * 1000000000000000) + '" target="_blank" title="View public analytics">'
                                             + '&gt;&gt; View all analytics'
                                             + '</a>'
                                             ;
                            } // end if
                        } // end if
                    } // end if
                    if( table_markup == '' ) table_markup = myself._empty_table_markup;

                    // Counters markup
                    var counters_markup = '';
                    if( $this.data('counters') )
                    {
                        var counters = $this.data('counters');
                        for(var i in counters)
                        {
                            if( typeof myself.coins_data[i] == 'undefined' ) continue;
                            counters_markup = counters_markup
                                            + '<span class="wptw_coin_counter" style="background-image: url(' + myself.coins_data[i]['logo'] + ')">'
                                            + counters[i]['amount'] + ' '  + myself.coins_data[i]['symbol']
                                            + '</span>'
                                            ;
                        } // end for
                    } // end if
                    if( counters_markup == '' ) counters_markup = myself._zero_counters_markup;

                    // Final steps
                    var html = '<div class="wptw_coin_logo"><img src="' + logo + '"></div>'
                             + '<div class="wptw_caption_area">'
                             +     '<div class="wptw_caption">' + $this.attr('caption') + '</div>'
                             +     '<div class="wptw_counter">' + counters_markup + '</div>'
                             + '</div>'
                             + '<div class="wptw_table_area ' + table_added_class + '">' + table_markup + '</div>'
                             ;
                    $this.html(html);

                    // Are we interactive?
                    if( $this.data('allow_intreaction') )
                    {
                        if(myself._debug_mode && console && console.log)
                            console.log('added interaction to ' + $this.attr('caption'));
                        $this.find('.wptw_coin_logo, .wptw_caption_area').attr('title', myself._interactive_caption);
                        $this.find('.wptw_coin_logo, .wptw_caption_area').toggleClass('interactive', true);
                        $this.find('.wptw_coin_logo, .wptw_caption_area').click( function() { myself.click( $this ); } );
                    } // end if
                // } // end if

                // Caption update
                if( $this.find('.wptw_caption').text() != $this.attr('caption') )
                    $this.find('.wptw_caption').text( $this.attr('caption') );

                // Logo update
                if( $this.find('.wptw_coin_logo img').attr('src') != logo )
                    $this.find('.wptw_coin_logo img').attr('src', logo);
            }); // end each
        }, // end function

        ///////////////////////
        click: function( $src )
        ///////////////////////
        {
            if( jQuery('.wpuni_mcwidget_ia').length > 0 ) jQuery('.wpuni_mcwidget_ia').remove();

            var myself = this;

            var ref           = escape( $src.attr('ref') );         if( ref         == 'undefined' ) ref         = '';
            var entry_id      = escape( $src.attr('entry_id') );    if( entry_id    == 'undefined' ) entry_id    = '';
            var entry_title   = escape( $src.attr('entry_title') ); if( entry_title == 'undefined' ) entry_title = '';
            var target_data   = escape( $src.attr('target_data') ); if( target_data == 'undefined' ) target_data = '';

            // If the entry id is not set, we'll set it to the document URL
            if( entry_id == '' ) entry_id = location.href;

            if( entry_id.indexOf('#') >= 0 )
            {
                var parts = entry_id.split('#');
                entry_id  = parts[0];
            } // end if

            // If the title is not set, let's look for the document meta description
            if( entry_title == ''          ) entry_title = escape( jQuery('meta[name="description"]').attr('content') );
            if( entry_title == 'undefined' ) entry_title = '';

            // Iif the description is not set, let's look for the document title
            if( entry_title == ''          ) entry_title = escape( jQuery('title').text() );
            if( entry_title == 'undefined' ) entry_title = '';

            // Extra stuff
            var amount_in_usd = $src.attr('amount_in_usd') ? '&amount_in_usd=' + escape($src.attr('amount_in_usd')) : '';

            var iframe_url = myself._buttonizer_root_url
                           + '?token='              + escape( myself._invoking_website_token_b64 )
                           + '&website_public_key=' + escape( $src.attr('website_public_key') )
                           + '&button_id='          + escape( $src.attr('button_id') )
                           + '&ref='                + ref
                           + '&entry_id='           + entry_id
                           + '&entry_title='        + entry_title
                           + '&target_data='        + target_data
                           + amount_in_usd
                           ;
            console.log( iframe_url );
            if( navigator.userAgent.toLowerCase().indexOf('iphone') > 0 )
            {
                window.open( iframe_url + '&stand_alone=true&wasuuup=' + parseInt(Math.random() * 1000000000000000) );
                return;
            } // end if

            var html = '<div class="wpuni_mcwidget_ia wpuni_overlay"><div></div></div>'
                     + '<div class="wpuni_mcwidget_ia wpuni_widget" color_scheme="'+$src.attr('color_scheme')+'">'
                     +     '<div class="wpuni_mcwidget_heading">'
                     +         '<div class="wpuni_mcwidget_close_button" '
                     +              'title="Close widget"'
                     +              'onclick="jQuery(\'.wpuni_mcwidget_ia\').remove(); wpuni_mcwidget.refresh();">'
                     +             '<img src="'+myself._aux_images_path+'etc/widget-buttons-close.png" style="border: none; width: 16px; height: 16px; margin: 2px 0 0 0;">'
                     +         '</div>'
                     +         '<div class="wpuni_mcwidget_close_button" style="margin-right: 0;" '
                     +              'title="Reload the widget contents"'
                     +              'onclick="wpuni_mcwidget.cover_iframe(); wpuni_mcwidget_ia_target.location.href = \'' + iframe_url + '&wasuuup=' + parseInt(Math.random() * 1000000000000000) + '\'">'
                     +             '<img src="'+myself._aux_images_path+'etc/widget-buttons-refresh.png" style="border: none; width: 16px; height: 16px; margin: 2px 0 0 0;">'
                     +         '</div>'
                     +         '<a class="wpuni_mcwidget_close_button" style="margin-right: 0;" '
                     +              'title="Get help on this widget"'
                     +              'href="' + myself._support_link + '" target="_blank">'
                     +             '<img src="'+myself._aux_images_path+'etc/widget-buttons-help.png" style="border: none; width: 16px; height: 16px; margin: 2px 0 0 0;">'
                     +         '</a>'
                     +         '<a class="wpuni_mcwidget_close_button" style="margin-right: 0;" '
                     +              'title="About this widget..."'
                     +              'href="' + myself._about_widget_link + '" target="_blank">'
                     +             '<img src="'+myself._aux_images_path+'etc/widget-buttons-info.png" style="border: none; width: 16px; height: 16px; margin: 2px 0 0 0;">'
                     +         '</a>'
                     +         '<a href="' + myself._main_url + '" target="_blank">'
                     +             '<img src="' + myself._main_logo + '">'
                     +             myself._widget_heading_caption
                     +         '</a>'
                     +     '</div>'
                     +     '<iframe name="wpuni_mcwidget_ia_target" src="' + iframe_url + '&wasuuup=' + parseInt(Math.random() * 1000000000000000) + '" '
                     +             'frameborder="0" allowtransparency="true" onload="wpuni_mcwidget.uncover_iframe()"></iframe>'
                     + '</div>'
                     + '<div class="wpuni_mcwidget_ia wpuni_iframe_cover"><div><img src="' + myself._iframe_loader + '"></div></div>'
                     ;
            jQuery('body').append(html);

            myself.relocate($src);
        },

        ////////////////////////
        cover_iframe: function()
        ////////////////////////
        {
            jQuery('.wpuni_mcwidget_ia.wpuni_iframe_cover').show();
        },

        //////////////////////////
        uncover_iframe: function()
        //////////////////////////
        {
            jQuery('.wpuni_mcwidget_ia.wpuni_iframe_cover').hide();
        },

        ///////////////////////////////////
        relocate: function($clicked_object)
        ///////////////////////////////////
        {
            var $widget = jQuery('.wpuni_mcwidget_ia.wpuni_widget');
            if( $widget.length == 0 ) return;

            var $iframe = jQuery('.wpuni_mcwidget_ia.wpuni_widget iframe');
            var offset  = $clicked_object.offset();
            var top     = offset.top + ($clicked_object.height() / 2) - ($iframe.height() / 2);

            var w = Math.max(document.documentElement.clientWidth,  window.innerWidth  || 0);
            var h = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);

            // $widget.css("top",  Math.max(0, ((jQuery(window).height() - $widget.outerHeight()) / 2) + jQuery(window).scrollTop())  + "px");
            // $widget.css("top",  Math.max(0, top)  + "px");
            $widget.css("top",  Math.max(0, ((window.innerHeight - $widget.outerHeight()) / 2) + jQuery(window).scrollTop())  + "px");
            $widget.css("left", Math.max(0, ((window.innerWidth  - $widget.outerWidth())  / 2) + jQuery(window).scrollLeft()) + "px");

            var offset = $iframe.offset();
            var width  = $iframe.width();
            var height = $iframe.height();
            jQuery('.wpuni_mcwidget_ia.wpuni_iframe_cover')
                .css('left',   offset.left)
                .css('top',    offset.top)
                .css('width',  width)
                .css('height', height)
        }, // end function

        ///////////////////
        refresh: function()
        ///////////////////
        {
            jQuery('.wpuni_mcwidget').attr('processed', false);
            this.render();
        }, // end function

        // @var object { coin_name: {logo:string, symbol:string, min_tip:number}, coin_name: {...}, ... }
        coins_data: null

    } // end var

    eval('wpuni_mcwidget.coins_data =  <?= $coins_data ?>');

    ///////////////////////////////
    // Onscreen search & process //
    ///////////////////////////////

    jQuery('<link rel="stylesheet" type="text/css" href="' + wpuni_mcwidget._stylesheet + '" />').appendTo('head');

    jQuery(document).ready(function()
    {
        if( ! wpuni_mcwidget._preload_only)
        {
            wpuni_mcwidget.translate_links();
            wpuni_mcwidget.render();
        } // end if
    }); // end document.ready

    jQuery(window).resize(function() { wpuni_mcwidget.relocate(); });

// </script>
