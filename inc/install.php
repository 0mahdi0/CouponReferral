<?php

global $wpdb;

require_once ABSPATH . 'wp-admin/includes/upgrade.php';

$table_name1 = $wpdb->prefix . "patients";
$table_name2 = $wpdb->prefix . "image_receipt";
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

if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name2}'") != $table_name2) {
    $sql = "CREATE TABLE $table_name2 (
            `id` bigint(11) NOT NULL AUTO_INCREMENT,
            `order_id` bigint(11) NOT NULL,
            `amount` text NOT NULL,
            `username` text NOT NULL,
            `img` text NOT NULL,
            `status` int(1) NOT NULL DEFAULT 0,
            `status_receipt` int(1) NOT NULL DEFAULT 0,
            `reason_text` text NULL,
            `date` date NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
    ) $charset_collate;";
    dbDelta($sql);
}