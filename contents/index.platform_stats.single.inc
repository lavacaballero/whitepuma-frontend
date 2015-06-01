
<div class="stats ui-widget-content ui-corner-all" style="padding: 5px; margin-bottom: 10px;">
    
    <div class="ui-widget-header ui-corner-all" style="padding: 5px;">
        Platform stats (admins only)
    </div>
    <div class="ui-widget-content ui-corner-all" style="padding: 5px; margin-top: 5px;">
        <?
            # Most stats
            $query = "
                select
                ( select count(id_account) from ".$config->db_tables["account"]."                                    ) as user_count_all,
                
                ( select count(id_account) from ".$config->db_tables["account"]." 
                  where date_created <> last_activity
                ) as user_count_valid,
                
                ( select count(id_account) from ".$config->db_tables["account"]." 
                  where date_created = last_activity
                ) as user_count_unclaimed,
                
                ( select count(op_id) from ".$config->db_tables["log"]." where state =  'OK' and coin_name = '".$config->current_coin_name."'                         ) as transaction_count_ok,
                ( select count(op_id) from ".$config->db_tables["log"]." where state <> 'OK' and state <> 'RETURNED' and coin_name = '".$config->current_coin_name."' ) as transaction_count_ko,
                ( select count(op_id) from ".$config->db_tables["log"]." where state =  'RETURNED' and action_type <> 'return' and coin_name = '".$config->current_coin_name."' ) as returned_tips,
                
                ( select count(op_id) from ".$config->db_tables["log"]." where action_type = 'give_to_tagged'  and state = 'OK' and coin_name = '".$config->current_coin_name."' ) as transaction_count_to_tagged,
                ( select count(op_id) from ".$config->db_tables["log"]." where action_type = 'give_from_batch' and state = 'OK' and coin_name = '".$config->current_coin_name."' ) as transaction_count_in_batch,
                ( select count(op_id) from ".$config->db_tables["log"]." where action_type = 'give_to_author'  and state = 'OK' and coin_name = '".$config->current_coin_name."' ) as transaction_count_to_author,
                
                ( select sum(coins) from ".$config->db_tables["log"]." where state = 'OK' and coin_name = '".$config->current_coin_name."'                                    ) as total_tipped,
                ( select sum(coins) from ".$config->db_tables["log"]." where state =  'RETURNED' and coin_name = '".$config->current_coin_name."' and action_type <> 'return' ) as total_returned,
                
                ( select date_processed from ".$config->db_tables["log"]." where state = 'OK' and coin_name = '".$config->current_coin_name."' order by date_processed desc limit 1 ) as last_placed_tip
            ";
            $res = mysql_query($query);
            $row = mysql_fetch_object($res);
            mysql_free_result($res);
            
            # Average tip
            $average_tip_size = 0;
            $query2 = "
                select coins as tip_size, COUNT(op_id) as tip_count
                from ".$config->db_tables["log"]."
                where state = 'OK'
                and coin_name = '".$config->current_coin_name."'
                group by coins
                order by tip_size desc
            ";
            # echo "<pre>$query2</pre>";
            $res2              = mysql_query($query2);
            $grouped_tip_entries = array();
            if( mysql_num_rows($res2) )
                while($row2 = mysql_fetch_object($res2))
                    # if($row2->tip_count > 1)
                        $grouped_tip_entries[$row2->tip_size] = $row2->tip_count;
            mysql_free_result($res2);
            
            # Removal of the biggest and the lowest
            /*
            reset( $grouped_tip_entries );
            unset($grouped_tip_entries[key($grouped_tip_entries)]);
            reset( $grouped_tip_entries );
            end($grouped_tip_entries);
            unset($grouped_tip_entries[key($grouped_tip_entries)]);
            */
            
            # Average
            $total_tips       = array_sum($grouped_tip_entries);
            $total_coins      = array_sum(array_keys($grouped_tip_entries));
            if($total_tips > 0 && $total_coins > 0)
                $average_tip_size = $total_coins / $total_tips;
        ?>
        <div class="indicators">
            <div class="indicator ui-widget-content ui-corner-all">
                <div class="title ui-state-active ui-corner-all">Total users</div>
                <div class="content">
                    <?= number_format($row->user_count_all) ?>
                </div>
            </div>
            <div class="indicator ui-widget-content ui-corner-all">
                <div class="title ui-state-active ui-corner-all">Active users</div>
                <div class="content">
                    <?= number_format($row->user_count_valid) ?>
                </div>
            </div>
            <!--
                <div class="indicator ui-widget-content ui-corner-all">
                    <div class="title ui-state-active ui-corner-all">Users w/o wallet</div>
                    <div class="content">
                        <?= number_format($row->user_count_no_wallet) ?>
                    </div>
                </div>
            -->
            <div class="indicator ui-widget-content ui-corner-all">
                <div class="title ui-state-active ui-corner-all">Inactive accounts</div>
                <div class="content">
                    <?= number_format($row->user_count_unclaimed) ?>
                </div>
            </div>
            <div class="indicator ui-widget-content ui-corner-all">
                <div class="title ui-state-active ui-corner-all">Transactions OK</div>
                <div class="content">
                    <?= number_format($row->transaction_count_ok) ?>
                </div>
            </div>
            <div class="indicator ui-widget-content ui-corner-all">
                <div class="title ui-state-active ui-corner-all">Transactions KO</div>
                <div class="content">
                    <?= number_format($row->transaction_count_ko) ?>
                </div>
            </div>
            <div class="indicator ui-widget-content ui-corner-all">
                <div class="title ui-state-active ui-corner-all">Direct</div>
                <div class="content">
                    <?= number_format($row->transaction_count_to_tagged) ?>
                </div>
            </div>
            <div class="indicator ui-widget-content ui-corner-all">
                <div class="title ui-state-active ui-corner-all">Rain drops</div>
                <div class="content">
                    <?= number_format($row->transaction_count_in_batch) ?>
                </div>
            </div>
            <div class="indicator ui-widget-content ui-corner-all" style="display: none;">
                <div class="title ui-state-active ui-corner-all">To-Author</div>
                <div class="content">
                    <?= number_format($row->transaction_count_to_author) ?>
                </div>
            </div>
            <div class="indicator ui-widget-content ui-corner-all">
                <div class="title ui-state-active ui-corner-all">Returns</div>
                <div class="content">
                    <?= number_format($row->returned_tips) ?>
                    (<span class="coin_signed"><?= number_format_crypto($row->total_returned) ?></span>)
                </div>
            </div>
            <div class="indicator ui-widget-content ui-corner-all">
                <div class="title ui-state-active ui-corner-all">Avg Tx Size</div>
                <div class="content numeric">
                    <span class="coin_signed"><?= number_format_crypto($average_tip_size) ?></span>
                </div>
            </div>
            <div class="indicator ui-widget-content ui-corner-all">
                <div class="title ui-state-active ui-corner-all">Active Tx Volume</div>
                <div class="content numeric">
                    <span class="coin_signed"><?= number_format_crypto($row->total_tipped) ?></span>
                </div>
            </div>
            <div class="indicator ui-widget-content ui-corner-all">
                <div class="title ui-state-active ui-corner-all">Last Tx placed</div>
                <div class="content numeric">
                    <?= number_format( (time() - strtotime($row->last_placed_tip)) / 60 ) ?> mins ago
                </div>
            </div>
        </div><!-- /.indicators -->
        
    </div>
    
</div><!-- /.stats -->
