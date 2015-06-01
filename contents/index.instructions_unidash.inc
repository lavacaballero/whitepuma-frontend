<?php
    /**
    * Group feed
    * 
    * @see http://ranacse05.wordpress.com/2011/02/04/show-facebook-group-wall-on-web-site/
    */
    $limit = 10;
    
    include "facebook-php-sdk/facebook.php";
    $fb_params = array(
        'appId'              => $config->facebook_app_id,
        'secret'             => $config->facebook_app_secret,
        'fileUpload'         => false,
        'allowSignedRequest' => false
    );
    $facebook = new Facebook($fb_params);
    $access_token = $facebook->getAccessToken();
    
    $group_id = $config->facebook_app_page_id;
    $des      = $facebook->api($group_id);
    $data     = $facebook->api("$group_id/feed");
?>

<!-- [+] News -->
    
    <style type="text/css">
        .group_feed         { font-family: "lucida grande",tahoma,verdana,arial,sans-serif; vertical-align: top; font-size: 10pt; }
        .group_feed .from   { margin-bottom: 2px; }
        .group_feed .top    { margin:5px; padding: 10px; border:2px solid #e1e1e1; background-color: #e1e1e1; color: black; font-size: 18pt; }
        .group_feed .single { margin:10px 5px; border-bottom:1px dashed #e1e1e1; clear: both; }
        .group_feed .img    { position:absolute; width:60px; text-align:center; margin:5px 5px 5px 0px; border-right:1px dashed #e1e1e1; }
        .group_feed .text   { font-size: 10pt; margin-left: 65px; }
        .group_feed a       { text-decoration: none; color: #3b5998; }
        .group_feed .single .text .link               { font-size: 12pt; }
        .group_feed .single .text .link  .caption     { font-size: 16pt; font-family: 'Times New Roman', 'Times Roman', Times, serif;  }
        .group_feed .single .text .link  .description { font-size: 12pt; font-family: 'Times New Roman', 'Times Roman', Times, serif; font-variant: small-caps;  }
        .group_feed .single .text .photo              { font-size: 12pt; }
        .group_feed .single .text .status             { font-size: 12pt; }
    </style>
    
    <div class="group_feed">
        <?
        $counter = 0;
        foreach($data["data"] as $d):
            if($counter==$limit) break;
            list($x, $post_id) = explode("_", $d["id"]);
            if( ! empty($d["message"]) && ! empty($d["message_tags"]) )
            {
                # echo "<pre>" . print_r($d["message_tags"], true) . "</pre>";
                $touched_message = ""; # "[".strlen($d["message"])."]";
                $pointer_pos     = 0;
                foreach($d["message_tags"] as $tag_key => $tag_data)
                {
                    $tag_data    = $tag_data[0];
                    $user_name   = substr($d["message"], $tag_data["offset"], $tag_data["length"]);
                    $user_id     = $tag_data["id"];
                    $replacement = "<a href='https://www.facebook.com/$user_id' target='_blank'>$user_name</a>";
                    
                    # if( $pointer_pos == 0 )
                    $touched_message .= substr($d["message"], $pointer_pos, ($tag_data["offset"] - $pointer_pos));
                    $touched_message .= $replacement;
                    $pointer_pos     += ($tag_data["offset"] - $pointer_pos) + $tag_data["length"];
                } # end foreach
                $touched_message .= substr($d["message"], $pointer_pos);
                $d["message"] = $touched_message;
            } # end if
            if( empty($d["message"]) ) continue;
            ?>
            <div class="single">
                <div class="img">
                    <a href="http://facebook.com/profile.php?id=<?=$d["from"]["id"]?>" target='_blank'>
                       <img border="0" alt="<?=$d["from"]["name"]?>" src="https://graph.facebook.com/<?=$d["from"]["id"]?>/picture"/>
                    </a>
                </div>
                <div class="text">
                    <div class="from">
                        <span style="font-weight:bold"><a href="http://facebook.com/profile.php?id=<?=$d["from"]["id"]?>"><?=$d["from"]["name"]?></a></span>
                        <span style="color: #999999;">on <?=date('F j, Y H:i',strtotime($d["created_time"]))?></span>
                        <div style="display: inline-block">
                            [
                                <a href="<?=$config->facebook_app_page?>/posts/<?=$post_id?>/" target="_blank"><?= count($d["comments"]["data"]) ?> comments</a>
                            ]
                        </div>
                    </div>
                    <div class="<?= $d["type"] ?>">
                        <? if($d["type"]     == "link"): ?>
                            <? if( ! empty($d["message"])): ?>
                                <?=nl2br($d["message"])?><br>
                            <? endif; ?>
                            <a href="<?=$d["link"]?>" target='_blank'>
                                <? if( ! empty($d["picture"])): ?>
                                    <img src="<?=$d["picture"]?>" style="float: left; margin-right: 10px;">
                                <? endif; ?>
                                <? if( ! empty($d["name"])): ?>
                                    <span class="caption"><?=$d["name"]?></span><br>
                                    <? if( ! empty($d["description"])): ?>
                                        <span class="description"><?=$d["description"]?></span>
                                    <? endif; ?>
                                <? endif; ?>
                            </a>
                            <? if(stristr($d["name"], "tip rain") !== false): ?>
                                <br>
                                <?= nl2br($d["comments"]["data"][0]["message"]) ?>
                            <? endif; ?>
                        <? elseif($d["type"] == "photo"): ?>
                            <img src="<?=$d["picture"]?>" style="float: left; margin-right: 10px;">
                            <?=nl2br($d["message"])?>
                        <? else: ?>
                            <?=nl2br($d["message"])?>
                        <? endif; ?>
                    </div>
                </div>
                <br clear="all"/>
            </div><!-- /.single -->
        <? $counter++; endforeach; ?>
    </div><!-- /.group_feed -->
    
<!-- [-] News -->
