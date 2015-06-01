<?php
    /**
     * Platform Extension: Websites / Websites addition/edition dialog
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
<div id="website_form_container" style="display: none;">

    <div id="website_form_target" style="display: none"></div>

    <form name="website_form" id="website_form" method="post" action="<?=$_SERVER["PHP_SELF"]?>?wasuuup=<?=md5(mt_rand(1,65535))?>">

        <div class="dialog_section ui-widget-content message_box ui-corner-all">

            <h2 class="new_title ui-widget-header message_box ui-corner-all"  style="margin-top: 0;">New website submission form</h2>
            <h2 class="edit_title ui-widget-header message_box ui-corner-all" style="margin-top: 0; display: none;">Editing website</h2>

            <input type="hidden" name="mode" value="insert_website">

            <div class="intro ui-state-highlight message_box ui-corner-all">
                <span class="fa fa-info-circle"></span>
                In order to allow users to send you coins to you or even to other users through comments systems,
                you need to register a website and get public and private keys to access our APIs.<br>
                <b>Socially known website registration attempts will be investigated.</b>
            </div>

            <div class="narrow_fields_ahead">
                <div class="narrow_field_container">
                    <div class="creation_caption">
                        Your website name:
                    </div>
                    <div class="editing_caption">
                        Website name:
                    </div>
                    <div class="field">
                        <input type="text" name="name">
                    </div>
                </div>

                <div class="narrow_field_container disable_for_jars">
                    <div class="creation_caption">
                        Select the closest matching category:
                    </div>
                    <div class="editing_caption">
                        Category:
                    </div>
                    <div class="field">
                        <select name="category">
                            <option>General</option>
                            <option>Adult / NSFW</option>
                            <option>Business &amp; finance</option>
                            <option>Contests &amp; trivia</option>
                            <option>Cryptocurrencies</option>
                            <option>Education</option>
                            <option>Entertainment</option>
                            <option>Fashion</option>
                            <option>Food &amp; drink</option>
                            <option>Gaming</option>
                            <option>Health &amp; fitness</option>
                            <option>Legal</option>
                            <option>Local</option>
                            <option>Marketing &amp; sales</option>
                            <option>Movies &amp; TV</option>
                            <option>Music &amp; audio</option>
                            <option>News</option>
                            <option>Personal</option>
                            <option>Photo &amp; video</option>
                            <option>Reading &amp; writing</option>
                            <option>Shopping</option>
                            <option>Social interaction</option>
                            <option>Software</option>
                            <option>Technology</option>
                            <option>Travel</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="narrow_fields_ahead">
                <div class="narrow_field_container">
                    <div class="creation_caption">
                        Type a shorthand identifier for your website:
                    </div>
                    <div class="editing_caption">
                        Your public key:
                        <button class="smallest secret_key" onclick="prompt('Your website secret key:', $(this).attr('secret_key')); return false;"
                            secret_key="">
                            <span class="fa fa-eye"></span>
                            View secret key
                        </button>
                    </div>
                    <div class="field">
                        <div class="intro ui-state-highlight message_box ui-corner-all">
                            <span class="fa fa-info-circle"></span>
                            Use a explicit string with lower case letters, dots, numbers, dashes and underscores (i.e. "my_main_website", "my-domain.com").
                            Do not use any symbol or accented character.
                        </div>
                        <input type="text" name="public_key" maxlength="32">
                    </div>
                </div>

                <div class="narrow_field_container disable_for_jars">
                    <div class="creation_caption">
                        Please type the main URL for your website:
                    </div>
                    <div class="editing_caption">
                        Website main URL:
                    </div>
                    <div class="field">
                        <input type="text" name="main_url">
                    </div>
                </div>

                <div class="narrow_field_container disable_for_jars">
                    <div class="creation_caption">
                        (Optional) Paste the URL of a 256x256 pixels icon or logo for your website (jpg/png/gif):
                    </div>
                    <div class="editing_caption">
                        Website icon/logo URL:
                    </div>
                    <div class="field">
                        <input type="text" name="icon_url">
                    </div>
                </div>

                <div class="narrow_field_container">
                    <div class="creation_caption">
                        Type a brief description for our listing:
                    </div>
                    <div class="editing_caption">
                        Description:
                    </div>
                    <div class="field">
                        <textarea name="description" class="expandible_textarea" maxlength="1000"></textarea>
                        <div class="ui-state-highlight message_box ui-corner-all">
                            <span class="fa fa-info-circle"></span>
                            1000 characters max. HTML tags allowed: &lt;b&gt; &lt;i&gt; &lt;u&gt; &lt;a&gt;
                        </div>
                    </div>
                </div>
            </div>

            <div class="disable_for_jars">
                <div class="creation_caption">
                    Specify the URL host/directory variants your website may be invoked from, one per line:
                    <button class="smallest" onclick="$('#valid_url_examples').toggle(); return false;">
                        <span class="fa fa-info-circle"></span>
                        Show/hide examples
                    </button>
                </div>
                <div class="editing_caption">
                    Valid URL patterns (one per line):
                    <button class="smallest" onclick="$('#valid_url_examples').toggle(); return false;">
                        <span class="fa fa-info-circle"></span>
                        Show/hide examples
                    </button>
                </div>
                <div class="field">
                    <div class="ui-state-highlight message_box ui-corner-all">
                        <span class="fa fa-info-circle"></span>
                        Note: when leeching is allowed, this field is ignored.
                    </div>
                    <div id="valid_url_examples" class="ui-state-highlight message_box ui-corner-all" style="display: none;">
                        <span class="fa fa-info-circle"></span>
                        Examples:<br>
                        <ul style="margin-top: 0; margin-bottom: 0;">
                            <li><u>https://www.some-domain.com</u> To allow the whole website of "some-domain.com" only by HTTPS.</li>
                            <li><u>www.some-domain.com</u> To allow any protocol on the whole website of "some-domain.com".</li>
                            <li><u>some-domain.com/my_directory/</u> To allow any protocol and any host on "some-domain.com" as long as it is pointing to your home directory.</li>
                            <li><u>my-blog.blogspot.</u> To allow anything on "my-blog" on Blogger.
                                Please note that Blogger may suffix a country TLD (i.e. "my-blog.blogspot.mx" for Mexican visitors), so leaving the period at the end is crucial if you don't want to limit your traffic.</li>
                            <li><u>my-blog.blogspot.com</u>  Following the previous example, this will only allow specific traffic to this blogger host.
                        </ul>
                        <span class="fa fa-warning"></span> If you leave this blank, anyone knowing your public key may impersonate your website.
                        This may be good or bad depending on the perspective. If you don't provide it, you will allow leech buttons, and
                        this may represent an income for you.
                    </div>
                    <textarea name="valid_urls" class="expandible_textarea"></textarea>
                </div>
            </div>

            <div class="disable_for_jars">

                <h3 class="ui-widget-header message_box ui-corner-all">Leeching controls</h3>

                <div class="narrow_fields_ahead">
                    <div class="narrow_field_container">
                        Allow leeching on your website?
                        <div class="field">

                            <label><input type="radio" name="allow_leeching" value="0">No:
                            Only I can embed buttons in my website.</label>
                            <span class="pseudo_link fa fa-info-circle" title="If you allow leeching, you may get some revenue!"></span><br>

                            <label><input type="radio" checked name="allow_leeching" value="1">Yes:
                            I want other users of the platform to be available to embed their own buttons on my website for a fee.</label>
                            <span class="pseudo_link fa fa-info-circle" title="When the leeching users receive coins from the button they embed in your website, <?=$config->buttons_leeching_fee?> of it will go to your account as a compensation, and it will be recorded on the OpsLog as 'embedding fee'."></span><br>

                        </div>

                        Enforce leeching buttons to this type:
                        <span class="pseudo_link fa fa-info-circle" title="Button types can be previewed on the buttons form."></span>
                        <div class="field">
                            <select name="leech_button_type">
                                <option selected value=""             >&lt;No override&gt;</option>
                                <option          value="round_button" >Round button (adaptive width)</option>
                                <option          value="round_bar"    >Round bar (full width)</option>
                                <option          value="round_table"  >Round table (full width with details)</option>
                                <option          value="square_button">Square button (adaptive width)</option>
                                <option          value="square_bar"   >Square bar (full width)</option>
                                <option          value="square_table" >Square table (full width with details)</option>
                                <option          value="thin_bar"     >Thin bar (full width)</option>
                            </select>
                        </div>

                        Enforce leeching buttons to this color scheme:
                        <span class="pseudo_link fa fa-info-circle" title="Color schemes can be previewed on the buttons form."></span>
                        <div class="field">
                            <select name="leech_color_scheme">
                                <option selected value=""          >&lt;No override&gt;</option>
                                <option          value="red"       >Red</option>
                                <option          value="orange"    >Orange</option>
                                <option          value="yellow"    >Yellow</option>
                                <option          value="green"     >Green</option>
                                <option          value="light_blue">Light Blue (default)</option>
                                <option          value="blue"     >Blue</option>
                                <option          value="indigo"   >Indigo</option>
                                <option          value="violet"   >Violet</option>
                                <option          value="light"    >Light grey</option>
                                <option          value="dark"     >Dark grey</option>
                            </select>
                        </div>
                    </div>

                    <div class="narrow_field_container">
                        Banned websites list:
                        <div class="ui-state-highlight message_box ui-corner-all">
                            <span class="fa fa-info-circle"></span>
                            If you allow leeching, specify here all the website public keys you <u>do not want</u> to
                            be embedded in your website separating them with commas.<br>
                            Example: <span class="ui-widget-content ui-corner-all">some_website, some_competition_website, some_other_website</span>
                        </div>
                        <textarea name="banned_websites" class="expandible_textarea"></textarea>
                    </div>
                </div>

            </div>

        </div><!-- /.dialog_section -->

        <div class="creation_caption ui-state-error message_box ui-corner-all">
            <span class="fa fa-warning"></span>
            <b>Important:</b> by submitting this form, you agree that the website you're adding is of your own or you have
            full permission from the website owners or representatives to register it with us.<br>
            We are always monitoring website submissions, and if we detect an impersonation attempt or we receive a
            claim from the website, we will immediately delete it and get your account locked out.
        </div>

        <div align="center">
            <button type="submit">
                <span class="fa fa-check"></span>
                Submit
            </button>
            <button type="reset" onclick="hide_website_form_dialog()">
                <span class="fa fa-ban"></span>
                Cancel
            </button>
        </div>

    </form>

</div><!-- /#website_form_container -->
