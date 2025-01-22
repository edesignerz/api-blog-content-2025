<?php
class API_Blog_Content_Helpers {

    // Debugging Functions
    public static function pretty_print($data) {
        echo '<pre>' . print_r($data, true) . '</pre>';
    }

    public static function custom_log($message) {
        $log_file = plugin_dir_path(__FILE__) . 'custom.log';
        file_put_contents($log_file, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
    }

    // Sanitization Functions
    public static function sanitize_text($text) {
        return sanitize_text_field($text);
    }

    public static function sanitize_email_field($email) {
        return sanitize_email($email);
    }

    public static function sanitize_url($url) {
        return esc_url_raw($url);
    }

    // Formatting Functions
    public static function format_date($date, $format = 'F j, Y') {
        return date($format, strtotime($date));
    }

    public static function format_currency($amount) {
        return '$' . number_format($amount, 2);
    }

    // Utility Functions
    public static function is_user_admin() {
        return current_user_can('manage_options');
    }

    public static function get_plugin_option($option_name) {
        $options = get_option('abc_options');
        return isset($options[$option_name]) ? $options[$option_name] : false;
    }

    public static function redirect_to($url) {
        wp_redirect($url);
        exit();
    }

    // Shortcode Functions
    public static function register_shortcodes() {
        add_shortcode('example_shortcode', array(__CLASS__, 'example_shortcode_handler'));
    }

    public static function example_shortcode_handler($atts) {
        $atts = shortcode_atts(
            array(
                'title' => 'Default Title',
            ), $atts, 'example_shortcode'
        );

        return '<h2>' . esc_html($atts['title']) . '</h2>';
    }
}

// Initialize shortcodes
add_action('init', array('API_Blog_Content_Helpers', 'register_shortcodes'));
?>
