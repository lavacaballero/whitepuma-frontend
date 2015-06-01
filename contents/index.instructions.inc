
<!-- [+] Instructions -->

    <?= $config->contents_instructions_prepend_string ?>

    <h2>Welcome to the community pulse!</h2>

    <p>Use our Community Pulse to express yourself and earn tips! And if someone posts contents worth rewarding,
    tip them over the comments!</p>

    <p>Tipping is as simple as typing a command over the caption or contents boxes on the Pulse Composer or over a new comment
    on any Pulse post:</p>

    <code>
        Give <b>amount</b>
        <?= empty($coin_name_instructions_override) ? $config->current_coin_data["coin_name_singular"] : $coin_name_instructions_override ?>
        to <u style="display: inline-block;">@user name</u>
        <u style="display: inline-block;">@another user...</u>
        <i style="display: inline-block">some message</i></code>

    <p>You can tip everyone <u>that already approved our app</u>, and you can include as many people as you want on a single message
    (every person tagged in your message will be tipped with the specified amount),
    but <u>be sure to have enough balance</u> to cover them all!</p>

    <p>If you want to tip people on your friends list,
    <span class="pseudo_link" onclick="invoke_requests_dialog()">send them invitations to authorize the app</span>
    so, when they join, they can ride the tipping wave with you!</p>

    <h3>Do's and Dont's</h3>

    <ul>
        <li><b>Only registered users can tip and be tipped.</b> When tagging users on the message typing boxes, you will be able to choose from
        those that already authorized the app.</li>

        <li><b>Do not beg.</b> Personal begging posts will be deleted. You can recommend any cause, but general begging is forbidden.</li>

        <li><b>Promoting stuff out of our network is forbidden.</b> If you want to promote something, you can either do it
        by making a rain and attaching a link for users to follow. <i><b>Note:</b> we're working on "tipbacks" on link posts.
        You will soon be able to promote whatever you want by setting a per-click reward
        with the coin of your choice.</i></li>

        <li><b>Collaborate with quality.</b> You deserve to see good contents over the Pulse. That's why we encourage you to collaborate
        and post interesting things and not trivial things over the Pulse for all our users enjoy it.</li>

        <li><b>Do not abuse of the "Post to Facebook" feature.</b> Avoid the urge to post to Facebook everything you post on the Pulse. You may end
        being banned by Facebook, since posting from apps is not as free as posting directly from groups.</li>

        <li><b>Be nice.</b> Our community wants to feel at home, and there's nothing better that receiving cheers from everyone.</li>

        <li><b>Comments are kept apart from Facebook.</b> When you post to a Facebook group, comments sent over the Pulse are
        kept apart from the comments your post get on the group.</li>
    </ul>

    <h3>Integrated Facebook Groups</h3>

    <p>When you post something on the Pulse, you may choose to also post it to a specific Facebook Group.
    Text, Link and Photo posts are sent with a link back to the thread on the <a href="<?=$root_url?>/extensions/pulse/">Pulse Feed</a>,
    so they can come in and tip you. Video posts are sent as-is, so users may not be able to tip you unless they come to
    the dashboard or to the Pulse feed page.</p>

    <p>Feel free to join any of the groups listed below:</p>

    <ul>
        <?
            $object_list = array();
            foreach($config->facebook_monitor_objects as $key => $object)
            {
                $name = $object["name"];
                $user_count = $object["type"] == "group" ? get_flag_value("group_counts:$key") : 0;
                $object_list[$name] = array(
                    "url"        => $object["url"],
                    "name"       => $object["name"],
                    "user_count" => $user_count
                );
            } # end foreach
            ksort($object_list);
        ?>
        <? foreach($object_list as $key => $object): ?>
            <? if( empty($object["url"]) ) continue; ?>
            <li>
                <? $style = (substr($object["name"], 0, 1) != ">") ? "" : "font-weight: bold;"; ?>
                <? $object["name"] = str_replace(">", "", $object["name"]); ?>
                <a href="<?= $object["url"] ?>" style="<?=$style?>" target="_blank"><?= $object["name"] ?></a>
                <? if( ! empty($object["user_count"]) ) echo "[".$object["user_count"]." members]"; ?>
            </li>
        <? endforeach; ?>
        <? if( ! empty($config->website_pages["group_addition_form"]) ): ?>
            <li>
                <span class="ui-state-highlight ui-corner-all" style="display: inline-block;">
                    <span class="ui-icon embedded ui-icon-alert"></span>
                    Do you want a group to be added to this list? <a href="<?= $config->website_pages["group_addition_form"] ?>" target="_blank">Click here!</a> &nbsp;
                </span>
            </li>
        <? endif; ?>
    </ul>

<!-- [-] Instructions -->
