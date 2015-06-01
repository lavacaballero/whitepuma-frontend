<?php
    /**
     * Platform Extension: Websites / Code generator
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
?>
<script type="text/javascript">
    //////////////////////////////////////////////////////////////
    function generate_code(website_public_key, button_id, caption)
    //////////////////////////////////////////////////////////////
    {
        $('#code_generator .website_public_key').text(website_public_key);
        $('#code_generator .button_id').text(button_id);
        $('#websites_main_buttons button').button('disable');

        var href = '<?= $config->buttons_buttonizer_invocator_url ?>?website_public_key='+website_public_key+'&button_id='+button_id+'';

        // Let's get the referral codes
        $('#generated_referral_codes').html('');
        var url = 'index.php?mode=get_button_data&website_public_key=' + website_public_key + '&button_id=' + button_id + '&wasuuup=' + (Math.random() * 1000000000000000);
        $.getJSON(url, function(data)
        {
            if( data.message != 'OK' )
            {
                alert(data.message);
                return;
            } // end if

            if( data.data.properties.referral_codes != '' )
            {
                var referral_codes = data.data.properties.referral_codes.replace(/\s*,\s*/g, ',');
                var parts = referral_codes.split(',');
                var html  = 'If you want to set a referral code from your button\'s list, just click on it from the selector below:<br>'
                          + '<span onclick="set_link_ref(this)">(None)</span>';
                for( var i in parts ) html = html + '<span onclick="set_link_ref(this)">' + parts[i] + '</span>';
                $('#generated_referral_codes').html(html);

            } // end if
        }); // end getJSON

        $('#code_generator input[name="source_link"]').val(href);
        $('#code_generator input[name="source_caption"]').val(caption);
        set_generated_codes(href);

        $('#current_websites').hide();
        $('#code_generator').show();
        $.scrollTo(0);
    } // end function

    //////////////////////////////////
    function set_generated_codes(href)
    //////////////////////////////////
    {
        var caption = $('#code_generator input[name="source_caption"]').val();
        $('#code_generator input[name="direct_link"]').val(href);
        $('#code_generator input[name="reddit_link"]').val('['+caption+']('+href+')');
        $('#code_generator input[name="html_link"]').val('<a href="'+href+'">'+caption+'</a>');
        $('#code_generator input[name="bbcode_link1"]').val('[url='+href+']' + caption + '[/url]');
        $('#code_generator input[name="bbcode_link2"]').val('[url]' + href + '[/url]');
    } // end function

    ////////////////////////////////////
    function set_link_ref(source_button)
    ////////////////////////////////////
    {
        var href = $('#code_generator input[name="source_link"]').val();
        var ref  = $(source_button).text() == '(None)' ? '' : escape( $(source_button).text() );
        if( ref == '' ) set_generated_codes(href)
        else            set_generated_codes(href + '&ref=' + ref)
    } // end function

    ///////////////////////////////
    function close_code_generator()
    ///////////////////////////////
    {
        $('#code_generator, #current_websites').toggle();
        $('#websites_main_buttons button').button('enable');
        $.scrollTo(0);
    } // end function
</script>

<style type="text/css">
    #code_generator h4                   { margin-bottom: 0; }
    #code_generator input[type="text"]   { width: 100%; }
    #generated_referral_codes span       { cursor: pointer; font-family: 'Lucida Console', 'Courier new', courier, monospace;
                                           font-size: 10pt; display: inline-block; padding: 5px; margin: 5px;
                                           color: black; background-color: #BBDDFF; border: 1px solid #0070C0; border-radius: 5px; }
    #generated_referral_codes span:hover { background-color: #42A0FF; color: white; border-color: navy; }
</style>

<div id="code_generator" style="display: none;">

    <h2 class="ui-widget-header message_box ui-corner-all">
        Invocation code for button
        <span class="button_id ui-state-highlight ui-corner-all"></span>
        for
        <span class="website_public_key ui-state-highlight ui-corner-all"></span>
    </h2>

    <h3>Requirements</h3>

    <p>If you are going to deploy your button on your own website, you need to add this code to the &lt;head&gt; section:</p>

    <code>
        &lt;script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"&gt;&lt;/script&gt;<br>
        &lt;script type="text/javascript" src="//<?=$config->app_root_domain?>/widget/<span class="website_public_key" style="color: purple; font-weight: bold;">%public_key%</span>"&gt;&lt;/script&gt;
    </code>

    <div class="ui-state-highlight message_box ui-corner-all">
        <span class="fa fa-info-circle"></span>
        If you already use jQuery on your website, use the second line only or you will reload jQuery with the provided version
        from Google and your page may break.
    </div>

    <p>Once you add the invoker, every link detected within the static HTML will be converted into a button.</p>

    <h3>Button links</h3>

    <!--
    <p>Use most appropiate code to embed your button, either on your own website or on any of the WhitePuma Network Supported Websites.</p>
    -->

    <input type="hidden" name="source_link">
    <input type="hidden" name="source_caption">
    <p id="generated_referral_codes"></p>

    <h4>Direct link:</h4>
    <div style="color: #0070C0;"><i>Share it over email, post it on Facebook walls, Tweet it, paste it on Disqus or Facebook comments systems...</i></div>
    <input type="text" name="direct_link">

    <h4>HTML code (for use in HTML source code)
    <span class="ui-state-highlight ui-corner-all"><span class="fa fa-warning"></span> Edit the caption inside the link!</span></h4>
    <div style="color: #0070C0;"><i>Embed it on your own website or blog, paste it on any of our <a href="<?= $config->buttons_enabled_websites_page ?>" target="_blank">Enabled Websites</a>...</i></div>
    <input type="text" name="html_link">

    <h4>Markdown code (for use in Reddit)</h4>
    <div style="color: #0070C0;"><i>Post it on a new thread or in a reply to a comment on your favorite SubReddit</i></div>
    <input type="text" name="reddit_link">

    <!--
    <h4>BBCode (for message boards) <u>with caption</u>
    <span class="ui-state-highlight ui-corner-all"><span class="fa fa-warning"></span> it may not be fully supported</span></h4>
    <div><i>Post it any forum that supports BBCODE</i></div>
    <input type="text" name="bbcode_link1">
    -->

    <h4>BBCode (for message boards) <u style="display: none;">without caption</u></h4>
    <div style="color: #0070C0;"><i>Post it any forum that supports BBCODE, like
    <a href="http://coinforums.org/" target="_blank">Digital Coin Forums</a>
    and <a href="http://community.coldcryptos.com/" target="_blank">ColdCryptos Community</a></i></div>
    <input type="text" name="bbcode_link2">

    <p align="center">
        <button style="font-size: 14pt;" onclick="close_code_generator()">
            <span clas="fa fa-mail-reply"></span>
            Return to websites list
        </button>
    </p>
</div>
