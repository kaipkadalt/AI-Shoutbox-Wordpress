<?php
// Failas: uninstall.php

/**
 * Fired when the plugin is uninstalled.
 *
 * @package AI_Shoutbox
 */

// Saugumo patikrinimas, jei failas pasiekiamas ne per WordPress ištrynimo procesą.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'ai_shoutbox_messages';

// Pastaba: $wpdb->prepare() nepalaiko lentelių pavadinimų, o $table_name yra saugiai
// sukonstruotas iš $wpdb->prefix. DROP TABLE yra būtinas uninstall procese.
// Todėl ignoruojame skenerio perspėjimus šiai eilutei.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

// Ištriname nustatymus iš wp_options lentelės.
delete_option('ai_shoutbox_options');

// Išvalome suplanuotą WP-Cron užduotį.
wp_clear_scheduled_hook('ai_shoutbox_daily_prune_event');