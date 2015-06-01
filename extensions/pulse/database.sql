#################################
create table wpfdtb_pulse_posts (
#################################
    
    created      datetime                                        not null,
    id           varchar(32)                                     not null default '',
    type         enum('text', 'link', 'video', 'photo', 'rain')  not null default 'text',
    target_coin  varchar(16)                                     not null default '',
    target_feed  varchar(255)                                    not null default '',
    
    id_author    varchar(32)                                     not null default '',
    
    caption      varchar(255)                                    not null default '',
    content      text                                            not null,
    picture      varchar(255)                                    not null default '',
    link         varchar(255)                                    not null default '',
    
    signature    varchar(255)                                    not null default '',
    
    edited       datetime                                        not null,
    edited_by    varchar(32)                                     not null default '',
    last_update  datetime                                        not null,
    
    admin_notes  text                                            not null,
    hidden       tinyint                                         unsigned not null default 0,
    
    metadata     text                                            not null,
    views        int                                             unsigned not null default 0,
    clicks       int                                             unsigned not null default 0,
    
    primary key ( id ),
    index   by_author ( id_author, id ),
    index   by_coin   ( target_coin, id ),
    index   by_feed   ( target_feed, id )
    
) engine=InnoDB;

####################################
create table wpfdtb_pulse_comments (
####################################
    
    created      datetime     not null,
    id           varchar(32)  not null default '',
    parent_post  varchar(32)  not null default '',
    id_author    varchar(32)  not null default '',
    content      text         not null,
    picture      varchar(255) not null default '',
    hidden       tinyint      unsigned not null default 0,
    metadata     text         not null,
    
    primary key ( parent_post, id )
    
) engine=InnoDB;

############################################
create table wpfdtb_pulse_user_preferences (
############################################
    
    id_account varchar(32)  not null default '',
    `key`      varchar(255) not null default '',
    value      varchar(255) not null default '',
    
    primary key (id_account, `key`)
    
) engine=InnoDB;
