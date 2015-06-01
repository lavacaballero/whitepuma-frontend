<?php
    /**
     * Platform Extension: Price ticker editor
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
     *
     * Trailing vars:
     * --------------
     * @var string  $location_case                   Insertion placement
     * @var object  $config                          Configuration per-se.
     * @var string  $basedir                         Base location
     * @var boolean $is_admin
     * @var boolean $admin_impersonization_in_effect
     */

    ###################
    # Requisite check #
    #####################################################################################################
    if( ! empty($location_case) && ! empty($config) && empty($config->db_tables["coin_prices"]) ) return;
    #####################################################################################################

    ##################################
    # Heading main buttons rendering #
    ##############################################
    if( $location_case == "heading_main_buttons" )
    ##############################################
    {
        if( basename($_SERVER["PHP_SELF"]) == basename(__FILE__) ) return;
        ?>
        <a href="<?="$basedir/extensions/price_ticker/?wasuuup=".md5(mt_rand(1,65535))?>"
           class="buttonized">
            <span class="fa fa-dollar"></span>
            Ticker
        </a>
        <?
        return;
    } # end if

    ###############################################################
    # Toolbox: get_address_and_balance at the end of calculations #
    #####################################################################
    if( $location_case == "tooolbox_get_address_and_balance_pre_render" )
    #####################################################################
    {
        global $balance, $to_return;

        if( $balance == 0 ) { $to_return .= ":―"; return; }

        $query = "select * from ".$config->db_tables["coin_prices"]." where coin_name = '$config->current_coin_name' order by `date` desc limit 1";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 ) { $to_return .= ":―"; return; };
        $row = mysql_fetch_object($res);
        mysql_free_result($res);

        if( $row->price == 0 ) { $to_return .= ":―"; return; };

        $usdvalue = number_format($balance * $row->price, 8, ".", "");
        $usdvalue = rtrim($usdvalue, "0");
        if( substr($usdvalue, -1) == "." ) $usdvalue .= "0";
        $to_return .= ":\$". $usdvalue . " USD";
        return;
    } # end if

    #############################################
    # Multicoin home scripts at the end of <head>
    #############################################
    if( $location_case == "multicoin_home_head" )
    #############################################
    {
        ?>

        <!-- ######################## -->
        <!-- Extensions: price ticker -->
        <!-- ######################## -->

        <script type="text/javascript">
            
            var indicators_loaded;
            var price_ticker_sum_interval = null;

            //////////////////////////////////
            function check_loaded_indicators()
            //////////////////////////////////
            {
                if( indicators_loaded < indicators_to_load ) return;

                var dollar_value = 0;
                $('.coin_item:not(.disabled) .balance_extra').each(function()
                {
                    if( $(this).hasClass('excluded') ) return;
                    var this_value = parseFloat($(this).text().replace('$', ''));
                    if( ! isNaN(this_value) ) dollar_value = dollar_value + this_value;
                }); // end .each
                $('#all_coins_usd_value_selector .dollar_sum').html( '$' + dollar_value.toFixed(5) );
                clearInterval(price_ticker_sum_interval);
            } // end function

            ////////////////////////////
            $(document).ready(function()
            ////////////////////////////
            {
                var indicators_to_load = $('.coin_item:not(.disabled)').length;
                if( indicators_loaded < indicators_to_load )
                    price_ticker_sum_interval = setInterval('check_loaded_indicators()', 500);
            }); // end function
        </script>

        <!-- ############################### -->
        <!-- End of Extensions: price ticker -->
        <!-- ############################### -->

        <?
        return;
    } # end if

    #############################################
    # Multicoin home scripts at the end of <head>
    #################################################################
    if( $location_case == "multicoin_dashboard_coin_indicators_pre" )
    #################################################################
    {
        ?>

        <!-- ######################## -->
        <!-- Extensions: price ticker -->
        <!-- ######################## -->

        <div class="coin_item disabled" id="all_coins_usd_value_selector">

            <div class="indicators">
                <div class="indicator ui-state-highlight ui-corner-all" style="display: block; width: auto;">

                    <div class="title ui-widget-header ui-corner-all" style="text-align: right; line-height: 32px;">

                        <img src="<?= $config->facebook_canvas_image ?>"
                             border="0" width="32" height="32" style="float: left;">

                        <span style="display: inline; font-weight: normal; font-size: 10pt;">
                            All coins&nbsp;
                        </span>

                        <br clear="all">
                    </div>

                    <div class="content_container">
                        <div style="margin: 10px 0 5px 0;">
                            Your portfolio<br>
                            has an estimated<br>
                            USD value of:<br>
                            <span class="dollar_sum"><img class="spinner" src="img/progress_16x16_gray.gif" border="0" width="16" height="16"></span>
                        </div>

                        <div style="margin-bottom: 10px;">
                            <a class="message_box ui-state-default ui-corner-all" style="margin: 0; padding: 5px; text-decoration: none;"
                               onmouseover="$(this).toggleClass('ui-state-hover')" onmouseout="$(this).toggleClass('ui-state-hover')"
                               title="Click to check the ticker"
                               href="extensions/price_ticker/?wasuuup=<?=md5(mt_rand(1,65535))?><? if($admin_impersonization_in_effect) echo "&view_profile_id=".$account->id_account; ?>">
                               View ticker
                               <span class="ui-icon embedded ui-icon-play"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ############################### -->
        <!-- End of Extensions: price ticker -->
        <!-- ############################### -->

        <?
        return;
    } # end if

    ###################################
    # Coin dashboard balance addition #
    ################################################################
    if( $location_case == "coin_dashboard_indicator_after_balance" )
    ################################################################
    {
        global $balance;
        if( empty($balance) ) return;
        if( ! is_numeric($balance) ) return;

        $query = "select * from ".$config->db_tables["coin_prices"]." where coin_name = '$config->current_coin_name' order by `date` desc limit 1";
        $res   = mysql_query($query);
        if( mysql_num_rows($res) == 0 ) return;
        $row = mysql_fetch_object($res);
        mysql_free_result($res);

        if($row->price == 0) return;
        ?>
        <div class="indicator ui-state-highlight ui-corner-all">
            <div class="title ui-state-active ui-corner-all">Your balance in USD</div>
            <div class="content numeric">
                $<?= number_format_crypto(($balance * $row->price), 5) ?>
                <span class="fa fa-info-circle" title="Prices are an estimation based on data taken from different exchanges."></span>
            </div>
        </div>
        <?
        return;
    } # end if
