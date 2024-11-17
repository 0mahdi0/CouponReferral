<?php

global $wpdb;

require_once ABSPATH . 'wp-admin/includes/upgrade.php';

$table_name1 = $wpdb->prefix . "patients";
$charset_collate = $wpdb->get_charset_collate();

if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name1}'") != $table_name1) {
    $sql = "CREATE TABLE $table_name1 (
        id bigint(11) NOT NULL AUTO_INCREMENT,
        user_id bigint(11) NOT NULL,
        fname text NOT NULL,
        lname text NOT NULL,
        gender text NOT NULL,
        phone text NOT NULL,
        disease text NOT NULL,
        paddress text NOT NULL,
        city text NOT NULL,
        pstate text NOT NULL,
        date date NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql);
}