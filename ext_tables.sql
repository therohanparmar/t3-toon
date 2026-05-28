CREATE TABLE tx_rrpt3toon_log (
    uid               int(11)    NOT NULL auto_increment,
    pid               int(11)    DEFAULT '0' NOT NULL,
    crdate            int(11)    DEFAULT '0' NOT NULL,
    input_size        int(11)    DEFAULT '0' NOT NULL,
    output_size       int(11)    DEFAULT '0' NOT NULL,
    optimization_pct  double     DEFAULT '0' NOT NULL,
    settings_enabled  tinyint(1) DEFAULT '0' NOT NULL,
    PRIMARY KEY (uid),
    KEY idx_crdate (crdate),
    KEY idx_optimization (optimization_pct),
    KEY idx_enabled (settings_enabled)
);
