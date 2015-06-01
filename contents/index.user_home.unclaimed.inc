<?php
    
    $unclaimed_offset = $_REQUEST["unclaimed_offset"] ? $_REQUEST["unclaimed_offset"] : 0;
    $unclaimed_limit  = $_REQUEST["unclaimed_limit"]  ? $_REQUEST["unclaimed_limit"]  : 50;
    $unclaimed_order  = $_REQUEST["unclaimed_order"]  ? $_REQUEST["unclaimed_order"]  : "op_id asc";
    
    $query = "describe " . $config->db_tables["log"];
    $res   = mysql_query($query);
    $cols  = array();
    while($row = mysql_fetch_object($res)) $cols[] = $row->Field;
    mysql_free_result($res);
    
    $cols[] = "to_date_created";
    $cols[] = "to_last_update";
    $cols[] = "to_last_activity";
    $cols[] = "to_email";
    $cols[] = "to_wallet_address";
    $cols[] = "to_claiming_flag";
    
    $account_limiting = $is_admin ? "" : "".$config->db_tables["log"].".from_id_account  = '$account->id_account' and";
    
    # [+] Nav Presets
    {
        $iniciar_en  = $unclaimed_offset;
        $tamano_page = $unclaimed_limit;
        
        $queryx = "
            select
                count(" . $config->db_tables["log"] . ".op_id) as cuenta
            from
                " . $config->db_tables["log"] . ",
                ".$config->db_tables["account"]."
            where
                $account_limiting
                ".$config->db_tables["log"].".state              = 'OK' and
                ".$config->db_tables["log"].".coin_name          = '".$config->current_coin_name."' and
                ".$config->db_tables["log"].".to_id_account      = ".$config->db_tables["account"].".id_account    and
                ".$config->db_tables["account"].".date_created   = ".$config->db_tables["account"].".last_activity
        ";
        $resx               = mysql_query($queryx);
        $rowx               = mysql_fetch_object($resx);
        $total_registros    = $rowx->cuenta;
        $total_paginas      = floor($total_registros / $tamano_page);
        
        $numero_esta_pagina = ($total_paginas+1)-floor(($total_registros-$iniciar_en) / $tamano_page);
        if($total_registros % $tamano_page > 0) $total_paginas++;
        
        $pagina_anterior    = $iniciar_en - $tamano_page;
        $pagina_siguiente   = $iniciar_en + $tamano_page;
        $ultima_pagina      = $tamano_page * ($total_paginas-1);
        
        mysql_free_result ( $resx );
        unset ( $rowx );
        
        # Offsets para el selectlist de navegación de páginas
        # Si hay más de 100 páginas y la página en la que estamos está arriba de 100, mostraremos
        # 50 patrás y 50 padelante.
        if ( $total_paginas <= 100 ) {
            # Son 100 páginas o menos
            $offset_inicial = 1;
            $offset_final   = $total_paginas;
            $punto_inicio   = 0;
        } else {
            # Son más de 100 páginas
            if ( $numero_esta_pagina < 50 ) {
                # Estamos entre las primeras 100
                $offset_inicial = 1;
                $offset_final   = 100;
                $punto_inicio   = 0;
            } else {
                # Estamos en lo profundo...
                $offset_inicial = $numero_esta_pagina - 50;
                $offset_final   = $numero_esta_pagina + 50;
                $punto_inicio   = ($offset_inicial-1) * $tamano_page;
            } # end if
        } # end if
        
        if( $offset_final == 0 ) $offset_final = $numero_esta_pagina = $pagina_siguiente = $pagina_final = $total_paginas = 1;
    }
    # [-] Nav Presets
    
?>
    
<div class="message_box ui-state-highlight ui-corner-all">
    <span class="ui-icon embedded ui-icon-info"></span>
    Here you can find all the transactions sent by you that may be automatically returned to your account.<br>
    There is a background process on the server that is automatically selecting which ones are eligible
    based on the flags you can check on the table below.<br>
    Coins will be returned if:
    <ul style="margin-top: 0; margin-bottom: 0;">
        <li>Recipient's account was never confirmed by the recipient and</li>
        <li>Recipient has  <u>only one incoming transaction</u>, and it was sent by you and</li>
        <li>The transaction is <u><?=$config->tipping_return_days?> days</u> old.</li>
    </ul>
    
    <? if($is_admin) { ?>
        <div>
            <hr>
            <span class="ui-icon embedded ui-icon-info"></span>
            The list of unclaimed transactions is not filtered when browsing as admin.
        </div>
    <? } # end if ?>
</div>

<?
    
    $query = "
        select
            " . $config->db_tables["log"] . ".*,
            ".$config->db_tables["account"].".date_created               as to_date_created,
            ".$config->db_tables["account"].".last_update                as to_last_update,
            ".$config->db_tables["account"].".last_activity              as to_last_activity,
            ".$config->db_tables["account"].".email                      as to_email,
        #   ".$config->db_tables["account"].".wallet_address             as to_wallet_address
            ( select address from ".$config->db_tables["account_wallets"]." 
              where ".$config->db_tables["account_wallets"].".id_account = ".$config->db_tables["account"].".id_account 
              and   ".$config->db_tables["account_wallets"].".coin_name  = '".$config->current_coin_name."'             ) as to_wallet_address
        from
            " . $config->db_tables["log"] . ",
            ".$config->db_tables["account"]."
        where
            $account_limiting
            ".$config->db_tables["log"].".state                  = 'OK' and
            ".$config->db_tables["log"].".coin_name              = '".$config->current_coin_name."' and
            ".$config->db_tables["log"].".to_id_account          = ".$config->db_tables["account"].".id_account and
            ".$config->db_tables["account"].".date_created       = ".$config->db_tables["account"].".last_activity
        order by 
            $unclaimed_order
        limit
            $unclaimed_offset, $unclaimed_limit
    ";
    # echo "<pre>$query</pre>";
    $res   = mysql_query($query);
    if( mysql_num_rows($res) == 0 )
    {
        ?>
        <div class="message_box ui-state-highlight ui-corner-all">
            <span class="ui-icon embedded ui-icon-info"></span>
            There is no activity to show here.
        </div>
        <?
        return;
    } # end if
?>

<div class="nav_buttons table_wrapper ui-widget-content message_box ui-corner-all">
    
    <table width="100%" cellpadding="0" cellspacing="2" border="0">
        <tr align="center" valign="middle">
            <td nowrap width="20%">
                <button class="skinnable smaller" <? if ( $total_registros == 0 ) { echo "disabled"; } ?> style="width: 100%;"
                        onclick="unclaimed_nav(0)">
                    <span class="ui-icon embedded ui-icon-seek-first"></span>First
                </button>
            </td>
            <? if ( $unclaimed_offset > 0 ) { ?>
                <td nowrap width="20%">
                    <button class="skinnable smaller" style="width: 100%;"
                            onclick="unclaimed_nav(<? echo $pagina_anterior; ?>)">
                        <span class="ui-icon embedded ui-icon-seek-prev"></span>Previous
                    </button>
                </td>
            <? } else { ?>
                <td nowrap width="20%">
                    <button class="skinnable smaller" disabled style="width: 100%;">
                        <span class="ui-icon embedded ui-icon-seek-prev"></span>Previous
                    </button>
                </td>
            <? } /* end if */ ?>
            <td nowrap width="20%">Page 
                <select name="saltar_a_pagina" style="font-family: lucida console,arial,helvetica; font-size: 8pt;"
                        onchange="unclaimed_nav(this.options[this.selectedIndex].value)">
                        <? for ( $cpage = $offset_inicial; $cpage <= $offset_final; $cpage++ ) { ?>
                            <option <? if ( $cpage == $numero_esta_pagina ) { echo "selected "; } ?>value="<? echo $punto_inicio; ?>">&nbsp;<? echo str_replace(" ","&nbsp;",sprintf("% 5.0f",$cpage)); ?>&nbsp;</option>
                            <? echo "\n";
                            $punto_inicio += $tamano_page;
                        } /* end for */ ?>
                </select> of <? echo $total_paginas; ?> (<? echo $total_registros; ?> entries)
            </td>
            <? if ( $numero_esta_pagina >= $total_paginas ) { ?>            
                <td nowrap width="20%">
                    <button class="skinnable smaller" disabled style="width: 100%;">
                        Next<span class="ui-icon embedded ui-icon-seek-next"></span>
                    </button>
                </td>
            <? } else { ?>
                <td nowrap width="20%">
                    <button class="skinnable smaller" style="width: 100%;"
                            onclick="unclaimed_nav(<? echo $pagina_siguiente; ?>)">
                        Next<span class="ui-icon embedded ui-icon-seek-next"></span>
                    </button>
                </td>
            <? } /* end if */ ?>
            <td nowrap width="20%">
                <button class="skinnable smaller" <? if ( $total_registros == 0 ) { echo "disabled"; } ?> style="width: 100%;"
                        onclick="unclaimed_nav(<? echo $ultima_pagina; ?>)">
                    Last<span class="ui-icon embedded ui-icon-seek-end"></span>
                </button>
            </td>
        </tr>
    </table>
    <? mt_srand ((double) microtime() * 1000000); ?>
    
</div><!-- /#nav_buttons -->

<div class="table_wrapper">
    
    <table class="tablesorter" width="100%" cellpadding="2" cellspacing="1" border="0">
        <thead>
            <tr>
                <? foreach( $cols as $key ) { ?>
                    <?
                        if($key == "from_facebook_id")  continue;
                        if($key == "to_facebook_id")    continue;
                        if($key == "entry_id")          continue;
                        if($key == "from_id_account")   $key = "From";
                        if($key == "to_id_account")     $key = "To";
                        
                        if($key == "to_date_created")   continue;
                        if($key == "to_last_update")    continue;
                        if($key == "to_last_activity")  continue;
                        if($key == "to_email")          $key = "Rcpt has email?";
                        if($key == "to_wallet_address") $key = "Rcpt has wallet?";
                        if($key == "to_claiming_flag")  $key = "Reclaim Flag";
                        if($key == "date_analyzed")     $key = "Analyzed";
                        if($key == "date_processed")    $key = "Processed";
                    ?>
                    <th class="{sorter: false}"><?= ucwords(str_replace("_", " ", $key)) ?></th>
                <? } # end foreach ?>
            </tr>
        </thead>
        <tbody>
            <? while( $row = mysql_fetch_object($res) ) { ?>
                <?
                    $claiming_flag = "ok"; $claiming_flag_description = "To be returned";
                    if(     $row->to_email != "" && $row->to_wallet_address != "" )                                             { $claiming_flag = "ko";      $claiming_flag_description = "Can't be reclaimed"; }
                    elseif( $row->to_email == "" && $row->to_wallet_address != "" )                                             { $claiming_flag = "check";   $claiming_flag_description = "Transaction check needed"; }
                    if( $row->date_processed > date("Y-m-d 23:59:59", strtotime("today - $config->tipping_return_days days")) ) { $claiming_flag = "not_yet"; $claiming_flag_description = "Not yet ready"; } # end if
                ?>
                <tr class="<?=$claiming_flag?>">
                    <? foreach($row as $key => $val) { ?>
                        <?
                            $preset_val = false;
                            if($key == "from_facebook_id")  continue;
                            if($key == "to_facebook_id")    continue;
                            if($key == "entry_id")          continue;
                            if($key == "to_date_created")   continue;
                            if($key == "to_last_update")    continue;
                            if($key == "to_last_activity")  continue;
                            if($key == "to_email")
                            {
                                if( empty($val) ) $val = "<span class='ui-icon embedded ui-icon-closethick'></span>";
                                else              $val = "<span class='ui-icon embedded ui-icon-mail-closed'></span>";
                            } # end if
                            if($key == "to_wallet_address")
                            {
                                if( empty($val) ) $val = "<span class='ui-icon embedded ui-icon-closethick'></span>";
                                else              $val = "<span class='ui-icon embedded ui-icon-check'></span>";
                            } # end if
                            if($key == "from_id_account" || $key == "to_id_account")
                            {
                                if($val == $account->id_account)
                                {
                                    $val = "(You)";
                                }
                                else
                                {
                                    $tmp_account = new account($val);
                                    if( $tmp_account->exists )
                                    {
                                        if( $is_admin ) $val = "[<a title='View user profile' target='_blank' href='".$_SERVER["PHP_SELF"]."?view_profile_id=$tmp_account->id_account&wasuuup=".md5(mt_rand(1,65535))."'>$tmp_account->id_account</a>]
                                                                 <a title='Open Facebook page' href='https://www.facebook.com/".$tmp_account->facebook_id."' target='_blank'>$tmp_account->name</a>";
                                        else            $val = "<a title='Open Facebook page' href='https://www.facebook.com/".$tmp_account->facebook_id."' target='_blank'>$tmp_account->name</a>";
                                        $preset_val = true;
                                    } # end if
                                } # end if
                            } # end if
                            if($key == "date_analyzed")  $val = "<span title='$val'>" . time_elapsed_string($val) . "</span>";
                            if($key == "date_processed") $val = "<span title='$val'>" . time_elapsed_string($val) . "</span>";
                            if(empty($val)) $val = "&ndash;";
                        ?>
                        <? if($preset_val) { ?>
                            <td><?=$val?></td>
                        <? } else { ?>
                            <td><?=str_replace("_", " ", $val)?></td>
                        <? } # end if ?>
                    <? } # end foreach ?>
                    <td><?=$claiming_flag_description?></td>
                </tr>
            <? } # end while ?>
        </tbody>
    </table>
    
</div><!-- /.table_wrapper -->
