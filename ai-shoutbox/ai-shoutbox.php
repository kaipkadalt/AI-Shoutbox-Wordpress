<?php
/**
 * Plugin Name:       AI Shoutbox
 * Description:       A simple, real-time shoutbox widget and shortcode with OpenAI (GPT) integration.
 * Version:           1.4.2
 * Author:            Mindaugas Bružas
 * Author URI:        https://kaipkada.lt
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ai-shoutbox
 */

if (!defined('ABSPATH')) exit;

define('AI_SHOUTBOX_VERSION', '1.4.2');
define('AI_SHOUTBOX_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_SHOUTBOX_PRUNE_EVENT_HOOK', 'ai_shoutbox_daily_prune_event');

require_once AI_SHOUTBOX_PLUGIN_DIR . 'includes/admin-settings.php';
require_once AI_SHOUTBOX_PLUGIN_DIR . 'includes/class-ai-shoutbox-widget.php';

register_activation_hook(__FILE__, 'ai_shoutbox_on_activate');
function ai_shoutbox_on_activate() {
    ai_shoutbox_create_db_table();
    if (!wp_next_scheduled(AI_SHOUTBOX_PRUNE_EVENT_HOOK)) {
        wp_schedule_event(time() + 3600, 'daily', AI_SHOUTBOX_PRUNE_EVENT_HOOK);
    }
}

register_deactivation_hook(__FILE__, 'ai_shoutbox_on_deactivate');
function ai_shoutbox_on_deactivate() {
    wp_clear_scheduled_hook(AI_SHOUTBOX_PRUNE_EVENT_HOOK);
}

add_action(AI_SHOUTBOX_PRUNE_EVENT_HOOK, 'ai_shoutbox_prune_database');
function ai_shoutbox_prune_database() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ai_shoutbox_messages';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query("TRUNCATE TABLE {$table_name}");
}

function ai_shoutbox_create_db_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ai_shoutbox_messages';
    $charset_collate = "CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
    $sql = "CREATE TABLE $table_name (id mediumint(9) NOT NULL AUTO_INCREMENT, created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL, sender_name tinytext NOT NULL, message_text text NOT NULL, message_type varchar(10) DEFAULT 'user' NOT NULL, PRIMARY KEY  (id)) {$charset_collate};";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

add_action('widgets_init', function() { register_widget('AI_Shoutbox_Widget'); });
add_shortcode('ai_shoutbox', 'ai_shoutbox_shortcode_render');
function ai_shoutbox_shortcode_render() {
    ob_start();
    ai_shoutbox_render_html();
    return ob_get_clean();
}

function ai_shoutbox_render_html() {
    $options = get_option('ai_shoutbox_options', []);
    $text = [
        'join_heading'      => !empty($options['join_heading']) ? $options['join_heading'] : 'Join the conversation',
        'name_placeholder'  => !empty($options['name_placeholder']) ? $options['name_placeholder'] : 'Enter your name',
        'join_button'       => !empty($options['join_button']) ? $options['join_button'] : 'Join',
        'write_placeholder' => !empty($options['write_placeholder']) ? $options['write_placeholder'] : 'Write a message...',
        'send_button'       => !empty($options['send_button']) ? $options['send_button'] : 'Send',
        'ask_ai_title'      => !empty($options['ask_ai_placeholder']) ? $options['ask_ai_placeholder'] : 'Ask AI a question'
    ];
    ?>
    <div id="shoutbox-container">
        <div id="messages"></div>
        <div id="interaction-area">
            <div id="login-view">
                <h2><?php echo esc_html($text['join_heading']); ?></h2>
                <input type="text" id="name-input" placeholder="<?php echo esc_attr($text['name_placeholder']); ?>" maxlength="20">
                <button id="login-button"><?php echo esc_html($text['join_button']); ?></button>
                <div class="recaptcha-notice">
                    <?php printf('This site is protected by reCAPTCHA and the Google %1$sPrivacy Policy%2$s and %3$sTerms of Service%4$s apply.', '<a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">', '</a>', '<a href="https://policies.google.com/terms" target="_blank" rel="noopener noreferrer">', '</a>'); ?>
                </div>
            </div>
            <div id="message-form-view" class="hidden">
                <textarea id="message-input" placeholder="<?php echo esc_attr($text['write_placeholder']); ?>" rows="3"></textarea>
                <div class="controls">
                    <button id="send-button"><?php echo esc_html($text['send_button']); ?></button>
                    <button id="ai-button" title="<?php echo esc_attr($text['ask_ai_title']); ?>">🤖 AI</button>
                </div>
            </div>
        </div>
        <div class="shoutbox-credit">
            <a href="https://kaipkada.lt" target="_blank" rel="noopener">Shoutbox by KaipKada</a>
        </div>
    </div>
    <?php
}

add_action('wp_enqueue_scripts', 'ai_shoutbox_enqueue_scripts');
function ai_shoutbox_enqueue_scripts() {
    global $post;
    if (is_active_widget(false, false, 'ai_shoutbox_widget', true) || (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'ai_shoutbox'))) {
        $options = get_option('ai_shoutbox_options', []);
        $recaptcha_site_key = $options['recaptcha_site_key'] ?? '';
        wp_enqueue_style('shoutbox-style', plugin_dir_url(__FILE__) . 'css/style.css', [], AI_SHOUTBOX_VERSION);
        if (!empty($recaptcha_site_key)) {
            wp_enqueue_script('google-recaptcha-v3', 'https://www.google.com/recaptcha/api.js?render=' . esc_js($recaptcha_site_key), [], AI_SHOUTBOX_VERSION, true);
        }
        wp_enqueue_script('shoutbox-script', plugin_dir_url(__FILE__) . 'js/script.js', ['google-recaptcha-v3'], AI_SHOUTBOX_VERSION, true);
        wp_localize_script('shoutbox-script', 'shoutbox_settings', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'recaptcha_site_key' => $recaptcha_site_key,
            'nonce'    => wp_create_nonce('shoutbox-public-nonce'),
            'text' => [
                'enter_name'            => !empty($options['alert_enter_name']) ? $options['alert_enter_name'] : 'Please enter a name.',
                'checking'              => !empty($options['checking_text']) ? $options['checking_text'] : 'Checking...',
                'join'                  => !empty($options['join_button']) ? $options['join_button'] : 'Join',
                'login_error'           => !empty($options['alert_unexpected_error']) ? $options['alert_unexpected_error'] : 'An unexpected error occurred. Please try again.',
                'confirmation_failed'   => !empty($options['alert_confirmation_failed']) ? $options['alert_confirmation_failed'] : 'Confirmation failed.',
                'sending_error'         => !empty($options['alert_sending_error']) ? $options['alert_sending_error'] : 'Error sending message.',
                'ask_ai'                => !empty($options['ask_ai_placeholder']) ? $options['ask_ai_placeholder'] : 'Ask AI a question...',
                'write_message'         => !empty($options['write_placeholder']) ? $options['write_placeholder'] : 'Write a message...',
            ]
        ]);
    }
}

add_action('admin_enqueue_scripts', 'ai_shoutbox_enqueue_admin_scripts');
function ai_shoutbox_enqueue_admin_scripts($hook) {
    if ('widgets.php' !== $hook && 'settings_page_ai_shoutbox_settings' !== $hook) return;
    wp_enqueue_script('shoutbox-admin-script', plugin_dir_url(__FILE__) . 'js/admin.js', ['jquery'], AI_SHOUTBOX_VERSION, true);
    wp_localize_script('shoutbox-admin-script', 'shoutbox_admin', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('shoutbox_clear_nonce'),
        'text'     => [
            'confirm_clear' => __('Are you sure you want to delete ALL shoutbox messages? This action cannot be undone.', 'ai-shoutbox'),
            'clearing'      => __('Clearing...', 'ai-shoutbox'),
            'clear_success' => __('Chat history has been successfully cleared.', 'ai-shoutbox'),
            'clear_error'   => __('An error occurred:', 'ai-shoutbox'),
            'unknown_error' => __('Unknown error.', 'ai-shoutbox')
        ]
    ]);
}

// --- AJAX FUNKCIJOS ---

add_action('wp_ajax_clear_shoutbox_chat', 'ai_shoutbox_handle_clear_chat');
function ai_shoutbox_handle_clear_chat() {
    check_ajax_referer('shoutbox_clear_nonce', '_ajax_nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'You do not have permission.'], 403);
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'ai_shoutbox_messages';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query("TRUNCATE TABLE {$table_name}");
    wp_send_json_success();
}

add_action('wp_ajax_shoutbox_login', 'ai_shoutbox_handle_login');
add_action('wp_ajax_nopriv_shoutbox_login', 'ai_shoutbox_handle_login');
function ai_shoutbox_handle_login() {
    check_ajax_referer('shoutbox-public-nonce', 'security');
    $options = get_option('ai_shoutbox_options', []);
    $secret_key = $options['recaptcha_secret_key'] ?? '';
    if (empty($secret_key)) {
        wp_send_json_error(['message' => 'reCAPTCHA secret key is not set.']);
        return;
    }
    $token = isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '';
    $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    if (empty($token)) {
        wp_send_json_error(['message' => 'reCAPTCHA token is missing.']);
        return;
    }
    $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', ['body' => ['secret' => $secret_key, 'response' => $token, 'remoteip' => $remote_ip]]);
    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'reCAPTCHA server error.']);
        return;
    }
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ($body && $body['success'] && $body['score'] > 0.5 && $body['action'] == 'login') {
        wp_send_json_success(['message' => 'Confirmation successful.']);
    } else {
        wp_send_json_error(['message' => 'reCAPTCHA confirmation failed.']);
    }
}

add_action('wp_ajax_get_messages', 'ai_shoutbox_get_messages');
add_action('wp_ajax_nopriv_get_messages', 'ai_shoutbox_get_messages');
function ai_shoutbox_get_messages() {
    check_ajax_referer('shoutbox-public-nonce', 'security');
    global $wpdb;
    $table_name = $wpdb->prefix . 'ai_shoutbox_messages';
    $last_id = isset($_POST['last_id']) ? intval($_POST['last_id']) : 0;
    
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $messages = $wpdb->get_results($wpdb->prepare("SELECT id, sender_name, message_text, message_type FROM {$table_name} WHERE id > %d ORDER BY id ASC", $last_id));
    
    wp_send_json_success($messages);
}

add_action('wp_ajax_post_message', 'ai_shoutbox_post_message');
add_action('wp_ajax_nopriv_post_message', 'ai_shoutbox_post_message');
function ai_shoutbox_post_message() {
    check_ajax_referer('shoutbox-public-nonce', 'security');
    global $wpdb;
    $table_name = $wpdb->prefix . 'ai_shoutbox_messages';
    $sender = isset($_POST['sender']) ? sanitize_text_field(wp_unslash($_POST['sender'])) : '';
    $text = isset($_POST['text']) ? sanitize_textarea_field(wp_unslash($_POST['text'])) : '';
    $isAI = isset($_POST['isAI']) ? filter_var(wp_unslash($_POST['isAI']), FILTER_VALIDATE_BOOLEAN) : false;

    if (empty($sender) || empty($text)) {
        wp_send_json_error(['message' => 'Empty sender or text.']);
        return;
    }
    
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->insert($table_name, ['created_at' => current_time('mysql'), 'sender_name' => $sender, 'message_text' => $text, 'message_type' => 'user']);
    
    if ($isAI) {
        $ai_response_text = ai_shoutbox_get_openai_response($text);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->insert($table_name, ['created_at' => current_time('mysql'), 'sender_name' => 'AI', 'message_text' => $ai_response_text, 'message_type' => 'ai']);
    }
    wp_send_json_success();
}

function ai_shoutbox_get_openai_response($prompt) {
    $options = get_option('ai_shoutbox_options', []);
    $api_key = $options['openai_key'] ?? '';
    if (empty($api_key)) {
        return 'OpenAI API key is not set in settings.';
    }
    $max_tokens = !empty($options['openai_max_tokens']) ? absint($options['openai_max_tokens']) : 150;
    $api_url = 'https://api.openai.com/v1/chat/completions';
    $response = wp_remote_post($api_url, [
        'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $api_key],
        'body' => json_encode(['model' => 'gpt-3.5-turbo', 'messages' => [['role' => 'system', 'content' => 'You are a helpful assistant.'], ['role' => 'user', 'content' => $prompt]], 'max_tokens' => $max_tokens]),
        'timeout' => 30
    ]);
    if (is_wp_error($response)) {
        return 'Error connecting to OpenAI.';
    }
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['choices'][0]['message']['content'])) {
        return trim($body['choices'][0]['message']['content']);
    }
    return 'Could not get a response from AI.';
}