<?php
// Failas: includes/class-ai-shoutbox-widget.php

if (!defined('ABSPATH')) exit;

class AI_Shoutbox_Widget extends WP_Widget {

    /**
     * Register widget with WordPress.
     */
    public function __construct() {
        parent::__construct(
            'ai_shoutbox_widget', // Base ID
            'AI Shoutbox', // Name
            ['description' => 'A real-time shoutbox with AI integration.'] // Args
        );
    }

    /**
     * Front-end display of widget.
     * ATNAUJINTA su wp_kses_post() saugumo sumetimais.
     */
    public function widget($args, $instance) {
        // Naudojame wp_kses_post, kad saugiai atvaizduotume temos pateiktą HTML.
        echo wp_kses_post($args['before_widget']);

        if (!empty($instance['title'])) {
            $title = apply_filters('widget_title', $instance['title']);
            // Taip pat apdorojame ir pavadinimo HTML.
            echo wp_kses_post($args['before_title'] . $title . $args['after_title']);
        }
        
        // Atvaizduojame pagrindinį shoutbox HTML turinį
        ai_shoutbox_render_html();

        // Saugiai atvaizduojame pabaigos HTML.
        echo wp_kses_post($args['after_widget']);
    }

    /**
     * Back-end widget form.
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Chat';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Title:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <small>All other settings, including API keys and translations, can be configured on the <a href="<?php echo esc_url(admin_url('options-general.php?page=ai_shoutbox_settings')); ?>">main settings page</a>.</small>
        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     */
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}