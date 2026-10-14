CREATE TABLE tx_jwdashboard_contact_person (
    uid        int(11)      NOT NULL AUTO_INCREMENT,
    pid        int(11)      NOT NULL DEFAULT 0,
    tstamp     int(11)      NOT NULL DEFAULT 0,
    crdate     int(11)      NOT NULL DEFAULT 0,
    deleted    tinyint(1)   NOT NULL DEFAULT 0,
    hidden     tinyint(1)   NOT NULL DEFAULT 0,
    sorting    int(11)      NOT NULL DEFAULT 0,

    name       varchar(255) NOT NULL DEFAULT '',
    email      varchar(255) NOT NULL DEFAULT '',
    phone      varchar(100) NOT NULL DEFAULT '',
    mobile     varchar(100) NOT NULL DEFAULT '',
    role       varchar(255) NOT NULL DEFAULT '',
    photo      int(11)      NOT NULL DEFAULT 0,
    is_main_contact tinyint(1) NOT NULL DEFAULT 0,
    bio        text,

    PRIMARY KEY (uid),
    KEY parent (pid)
);

CREATE TABLE tx_jwdashboard_contract (
    uid           int(11)      NOT NULL AUTO_INCREMENT,
    pid           int(11)      NOT NULL DEFAULT 0,
    tstamp        int(11)      NOT NULL DEFAULT 0,
    crdate        int(11)      NOT NULL DEFAULT 0,
    deleted       tinyint(1)   NOT NULL DEFAULT 0,
    hidden        tinyint(1)   NOT NULL DEFAULT 0,
    sorting       int(11)      NOT NULL DEFAULT 0,

    title         varchar(255) NOT NULL DEFAULT '',
    client_name   varchar(255) NOT NULL DEFAULT '',
    client_number varchar(100) NOT NULL DEFAULT '',
    start_date    int(11)      NOT NULL DEFAULT 0,
    end_date      int(11)      NOT NULL DEFAULT 0,
    status        varchar(50)  NOT NULL DEFAULT 'active',
    contract_type varchar(100) NOT NULL DEFAULT 'maintenance',
    notes         text,
    renewal_prompt_days int(11) NOT NULL DEFAULT 30,
    contact_persons int(11)    NOT NULL DEFAULT 0,

    PRIMARY KEY (uid),
    KEY parent (pid)
);

CREATE TABLE tx_jwdashboard_contract_contactperson_mm (
    uid_local   int(11) NOT NULL DEFAULT 0,
    uid_foreign int(11) NOT NULL DEFAULT 0,
    sorting     int(11) NOT NULL DEFAULT 0,
    sorting_foreign int(11) NOT NULL DEFAULT 0,

    PRIMARY KEY (uid_local, uid_foreign),
    KEY uid_local (uid_local),
    KEY uid_foreign (uid_foreign)
);
