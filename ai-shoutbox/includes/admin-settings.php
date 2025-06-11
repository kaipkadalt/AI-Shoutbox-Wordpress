<?php
// Failas: includes/admin-settings.php

if (!defined('ABSPATH')) exit;

/**
 * Add the admin menu item for the settings page.
 */
add_action('admin_menu', 'ai_shoutbox_add_admin_menu');
function ai_shoutbox_add_admin_menu() {
    add_options_page(
        'AI Shoutbox Settings',
        'AI Shoutbox',
        'manage_options',
        'ai_shoutbox_settings',
        'ai_shoutbox_options_page_html'
    );
}

/**
 * Initialize and register the settings, sections, and fields.
 */
add_action('admin_init', 'ai_shoutbox_settings_init');
function ai_shoutbox_settings_init() {
    register_setting('ai_shoutbox_settings_group', 'ai_shoutbox_options', 'ai_shoutbox_options_sanitize');

    // Section 1: API & Model Settings
    add_settings_section('ai_shoutbox_api_section', 'API & Model Settings', 'ai_shoutbox_api_section_callback', 'ai_shoutbox_settings_group');
    add_settings_field('openai_key', 'OpenAI API Key', 'ai_shoutbox_openai_key_render', 'ai_shoutbox_settings_group', 'ai_shoutbox_api_section');
    add_settings_field('openai_max_tokens', 'AI Max Response Length', 'ai_shoutbox_max_tokens_render', 'ai_shoutbox_settings_group', 'ai_shoutbox_api_section');
    add_settings_field('recaptcha_site_key', 'reCAPTCHA v3 Site Key', 'ai_shoutbox_recaptcha_site_key_render', 'ai_shoutbox_settings_group', 'ai_shoutbox_api_section');
    add_settings_field('recaptcha_secret_key', 'reCAPTCHA v3 Secret Key', 'ai_shoutbox_recaptcha_secret_key_render', 'ai_shoutbox_settings_group', 'ai_shoutbox_api_section');

    // Section 2: Text & Translations
    add_settings_section('ai_shoutbox_text_section', 'Text & Translations', 'ai_shoutbox_text_section_callback', 'ai_shoutbox_settings_group');
    $text_fields = [
        'join_heading' => 'Heading: "Join the conversation"',
        'name_placeholder' => 'Placeholder: "Enter your name"',
        'join_button' => 'Button: "Join"',
        'checking_text' => 'Button Text: "Checking..."',
        'write_placeholder' => 'Placeholder: "Write a message..."',
        'send_button' => 'Button: "Send"',
        'ask_ai_placeholder' => 'Title: "Ask AI a question"',
        'alert_enter_name' => 'Alert: "Please enter a name."',
        'alert_unexpected_error' => 'Alert: "An unexpected error occurred..."',
        'alert_confirmation_failed' => 'Alert: "Confirmation failed."',
        'alert_sending_error' => 'Alert: "Error sending message."',
    ];
    foreach ($text_fields as $id => $label) {
        add_settings_field($id, $label, 'ai_shoutbox_text_field_render', 'ai_shoutbox_settings_group', 'ai_shoutbox_text_section', ['id' => $id]);
    }

    // Section 3: Tools
    add_settings_section('ai_shoutbox_tools_section', 'Tools', 'ai_shoutbox_tools_section_callback', 'ai_shoutbox_settings_group');
    add_settings_field('clear_chat', 'Clear Chat History', 'ai_shoutbox_clear_chat_render', 'ai_shoutbox_settings_group', 'ai_shoutbox_tools_section');
}

// --- Callback and Render Functions ---

function ai_shoutbox_api_section_callback() { echo '<p>Enter your API keys and configure the AI model settings below.</p>'; }

function ai_shoutbox_openai_key_render() {
    $options = get_option('ai_shoutbox_options'); $value = $options['openai_key'] ?? '';
    echo "<input type='password' name='ai_shoutbox_options[openai_key]' value='" . esc_attr($value) . "' class='regular-text'>";
    echo '<p class="description">' . sprintf('Get your API key from the %s OpenAI API Keys page%s.', '<a href="https://platform.openai.com/account/api-keys" target="_blank">', '</a>') . '</p>';
}

function ai_shoutbox_max_tokens_render() {
    $options = get_option('ai_shoutbox_options'); $value = $options['openai_max_tokens'] ?? '150';
    echo "<input type='number' name='ai_shoutbox_options[openai_max_tokens]' value='" . esc_attr($value) . "' class='small-text' placeholder='150'>";
    echo '<p class="description">Controls the maximum length of the AI\'s answer. This is set in "tokens". As a rule of thumb, 100 tokens ≈ 75 words. Default is 150.</p>';
}

function ai_shoutbox_recaptcha_site_key_render() {
    $options = get_option('ai_shoutbox_options'); $value = $options['recaptcha_site_key'] ?? '';
    echo "<input type='text' name='ai_shoutbox_options[recaptcha_site_key]' value='" . esc_attr($value) . "' class='regular-text'>";
    echo '<p class="description">' . sprintf('Get your v3 keys from the %s Google reCAPTCHA Admin Console%s.', '<a href="https://www.google.com/recaptcha/admin" target="_blank">', '</a>') . '</p>';
}

function ai_shoutbox_recaptcha_secret_key_render() {
    $options = get_option('ai_shoutbox_options'); $value = $options['recaptcha_secret_key'] ?? '';
    echo "<input type='password' name='ai_shoutbox_options[recaptcha_secret_key]' value='" . esc_attr($value) . "' class='regular-text'>";
    echo '<p class="description">This is the "Secret Key" for server-side validation.</p>';
}

function ai_shoutbox_text_section_callback() { echo '<p>Here you can translate the publicly visible texts of the shoutbox. English texts are used by default if a field is empty.</p>'; }

function ai_shoutbox_text_field_render($args) {
    $options = get_option('ai_shoutbox_options', []);
    $id      = $args['id'];
    $value   = $options[$id] ?? '';

    // Naudojame printf, kad kodas būtų švaresnis ir aiškesnis.
    // Svarbiausia - apdorojame abu kintamuosius ($id ir $value) su esc_attr().
    printf(
        '<input type="text" name="ai_shoutbox_options[%s]" value="%s" class="regular-text">',
        esc_attr($id),
        esc_attr($value)
    );
}

function ai_shoutbox_tools_section_callback() { echo '<p>Use these tools for plugin maintenance.</p>'; }

function ai_shoutbox_clear_chat_render() {
    ?>
    <button type="button" class="button button-secondary clear-shoutbox-chat">Clear All Messages</button>
    <p class="description">Warning: This will permanently delete all messages from the shoutbox. This action cannot be undone.</p>
    <?php
}

// Sanitize all options on save
function ai_shoutbox_options_sanitize($input) {
    $new_input = [];
    $text_fields = ['join_heading', 'name_placeholder', 'join_button', 'write_placeholder', 'send_button', 'ask_ai_placeholder', 'checking_text', 'alert_enter_name', 'alert_unexpected_error', 'alert_confirmation_failed', 'alert_sending_error'];
    
    $new_input['openai_key'] = sanitize_text_field($input['openai_key'] ?? '');
    $new_input['recaptcha_site_key'] = sanitize_text_field($input['recaptcha_site_key'] ?? '');
    $new_input['recaptcha_secret_key'] = sanitize_text_field($input['recaptcha_secret_key'] ?? '');
    
    if (isset($input['openai_max_tokens'])) {
        $new_input['openai_max_tokens'] = absint($input['openai_max_tokens']);
    }

    foreach ($text_fields as $field) {
        if (isset($input[$field])) {
            $new_input[$field] = sanitize_text_field($input[$field]);
        }
    }
    return $new_input;
}

// Render the main settings page HTML
function ai_shoutbox_options_page_html() {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action='options.php' method='post'>
            <?php
            settings_fields('ai_shoutbox_settings_group');
            // Render all sections except the tools section inside the form
            do_settings_sections('ai_shoutbox_settings_group');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}