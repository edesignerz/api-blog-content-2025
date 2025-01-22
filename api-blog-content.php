<?php
/*
Plugin Name: API Blog Content Plugin with Scheduling Working On Hosting
Description: Generates blog content using OpenAI's API based on provided settings.
Version: 3.4
Author: Edesignerz.com
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once 'settings.php';
require_once 'api.php';
require_once 'post-creation.php';
require_once 'helpers.php';
require_once 'admin-scripts.php';
require_once 'meta-box.php';

class API_Blog_Content_Plugin {
    private $options;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('wp_ajax_abc_create_post', array($this, 'abc_create_post'));
        add_action('wp_ajax_abc_save_api_key', array($this, 'save_api_key'));
        add_action('wp_ajax_abc_test_connection', array($this, 'test_connection'));
        add_action('wp_ajax_abc_get_progress', array($this, 'get_progress'));
    }

    public function add_plugin_page() {
        $settings = new API_Blog_Content_Settings();
        $settings->add_plugin_page();
    }

    public function page_init() {
        $settings = new API_Blog_Content_Settings();
        $settings->page_init();
    }

    public function abc_create_post() {
        $post_creation = new API_Blog_Content_Post_Creation();
        $post_creation->abc_create_post();
    }

    public function save_api_key() {
        $settings = new API_Blog_Content_Settings();
        $settings->save_api_key();
    }

    public function test_connection() {
        $settings = new API_Blog_Content_Settings();
        $settings->test_connection();
    }

    public function get_progress() {
        $post_creation = new API_Blog_Content_Post_Creation();
        $post_creation->get_progress();
    }
}

if (is_admin()) {
    $api_blog_content_plugin = new API_Blog_Content_Plugin();
}
