class API_Blog_Content_Settings {
    private $options;

    public function add_plugin_page() {
        add_menu_page(
            'API Blog Content', // Page title
            'API Blog Content', // Menu title
            'manage_options', // Capability
            'api-blog-content', // Menu slug
            array($this, 'create_admin_page'), // Callback
            'dashicons-admin-generic', // Icon
            81 // Position
        );
    }

    public function page_init() {
        register_setting(
            'abc_option_group', // Option group
            'abc_options', // Option name
            array($this, 'sanitize') // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'API Blog Content Settings', // Title
            array($this, 'print_section_info'), // Callback
            'api-blog-content' // Page
        );

        // API Key
        add_settings_field(
            'api_key',
            'OpenAI API Key',
            array($this, 'api_key_callback'),
            'api-blog-content',
            'setting_section_id'
        );

        // Blog Titles, Keywords, Categories
        add_settings_field(
            'blog_info',
            'Blog Titles, Keywords, Categories',
            array($this, 'blog_info_callback'),
            'api-blog-content',
            'setting_section_id'
        );

        // Article Outline Prompt
        add_settings_field(
            'outline_prompt',
            'Article Outline Prompt',
            array($this, 'outline_prompt_callback'),
            'api-blog-content',
            'setting_section_id'
        );

        // Article Creation Prompt
        add_settings_field(
            'creation_prompt',
            'Article Creation Prompt',
            array($this, 'creation_prompt_callback'),
            'api-blog-content',
            'setting_section_id'
        );
    }

    public function sanitize($input) {
        $new_input = array();
        if (isset($input['api_key'])) {
            $new_input['api_key'] = sanitize_text_field($input['api_key']);
        }
        if (isset($input['blog_info'])) {
            $new_input['blog_info'] = sanitize_textarea_field($input['blog_info']);
        }
        if (isset($input['outline_prompt'])) {
            $new_input['outline_prompt'] = sanitize_textarea_field($input['outline_prompt']);
        }
        if (isset($input['creation_prompt'])) {
            $new_input['creation_prompt'] = sanitize_textarea_field($input['creation_prompt']);
        }

        return $new_input;
    }

    public function print_section_info() {
        print 'Enter your settings below:';
    }

    public function api_key_callback() {
        printf(
            '<input type="text" id="api_key" name="abc_options[api_key]" value="%s" size="50" />',
            isset($this->options['api_key']) ? esc_attr($this->options['api_key']) : ''
        );
        echo ' <button type="button" id="save_api_key" class="button">Save API Key</button>';
        echo ' <button type="button" id="test_connection" class="button">Test Connection</button>';
    }

    public function blog_info_callback() {
        printf(
            '<textarea id="blog_info" name="abc_options[blog_info]" rows="10" cols="50" style="width:800px;" placeholder="Enter each blog entry on a new line in the format: Title, Keyword, Category, Publish Date. Example:
Web Design Expert Salem OR, Web Design Expert Salem OR, Website Design, 2024-12-01
Salem Oregon Wordpress Website Designer, Salem Oregon Wordpress Website Designer, Website Design, 2024-12-02
Professional Website Designer In Salem, Professional Website Designer In Salem, Website Design, 2024-12-03">%s</textarea>',
            isset($this->options['blog_info']) ? esc_textarea($this->options['blog_info']) : ''
        );
        echo '<p class="description">Enter each blog entry on a new line in the format: <strong>Title, Keyword, Category, Publish Date (YYYY-MM-DD)</strong>. Ensure there are no additional commas within each component and the date is in YYYY-MM-DD format.</p>';
    }

    public function outline_prompt_callback() {
        printf(
            '<textarea id="outline_prompt" name="abc_options[outline_prompt]" rows="10" cols="50" placeholder="Use {Keyword} as a placeholder for the main keyword. Example:
Create a comprehensive outline for a 2000-word article centered on the keyword {Keyword}. The outline should include an introduction, at least 5 headings, and a conclusion. Each heading should have 2-3 subpoints.">%s</textarea>',
            isset($this->options['outline_prompt']) ? esc_textarea($this->options['outline_prompt']) : ''
        );
        echo '<p class="description">Use <strong>{Keyword}</strong> as a placeholder for the main keyword. This prompt guides the AI in creating detailed outlines based on top Google results.</p>';
    }

    public function creation_prompt_callback() {
        printf(
            '<textarea id="creation_prompt" name="abc_options[creation_prompt]" rows="10" cols="75" style="width:600px;" placeholder="Use {Outline} as a placeholder for the generated outline. Example:
Using the following outline, create a detailed blog article in Markdown format. Ensure the content is engaging, well-researched, and optimized for SEO. Include relevant keywords and maintain a professional tone.">%s</textarea>',
            isset($this->options['creation_prompt']) ? esc_textarea($this->options['creation_prompt']) : ''
        );
        echo '<p class="description">Use <strong>{Outline}</strong> as a placeholder for the generated outline. This prompt directs the AI to create comprehensive articles based on the provided outlines.</p>';
    }

    public function create_admin_page() {
        $this->options = get_option('abc_options');
        ?>
        <div class="wrap">
            <h1>API Blog Content Settings</h1>
            <form method="post" action="options.php">
            <?php
                settings_fields('abc_option_group');
                do_settings_sections('api-blog-content');
                submit_button();
            ?>
            </form>
            <hr>
            <div class="hidden">
                <h2>Generated Outline and Article</h2>
                <div id="generated_outline" style="white-space: pre-wrap; border: 1px solid #ccc; padding: 10px; margin-top: 10px;">
                    <?php
                        if (isset($this->options['generated_outline'])) {
                            echo esc_html($this->options['generated_outline']);
                        }
                    ?>
                </div>
                <div id="generated_article" style="white-space: pre-wrap; border: 1px solid #ccc; padding: 10px; margin-top: 10px;">
                    <?php
                        if (isset($this->options['generated_article'])) {
                            echo esc_html($this->options['generated_article']);
                        }
                    ?>
                </div>
            </div>
            <hr>
            <h2>Create Posts</h2>
            <button type="button" id="create_post" class="button">Create Posts</button>
            <div id="create_post_status" style="margin-top: 10px;">
                <div id="progress_message" style="margin-top: 10px; font-weight: bold;">No posts being created.</div>
                <div id="progress_bar_container" style="width: 100%; background-color: #f3f3f3; border: 1px solid #ccc; margin-top: 5px; display: none;">
                    <div id="progress_bar" style="width: 0%; height: 20px; background-color: #4caf50;"></div>
                </div>
                <ul id="post_status_list" style="margin-top: 10px; list-style-type: none; padding: 0;"></ul>
            </div>
        </div>
        <?php
    }

    public function save_api_key() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        check_ajax_referer('abc_save_api_key_nonce', 'nonce');

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';

        if (empty($api_key)) {
            wp_send_json_error('API Key cannot be empty.');
        }

        $options = get_option('abc_options');
        $options['api_key'] = $api_key;
        update_option('abc_options', $options);

        wp_send_json_success('API Key saved successfully.');
    }

    public function test_connection() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        check_ajax_referer('abc_test_connection_nonce', 'nonce');

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';

        if (empty($api_key)) {
            wp_send_json_error('API Key cannot be empty.');
        }

        // Use a simple API call to test the connection
        $response = wp_remote_get('https://api.openai.com/v1/models', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'timeout' => 15,
        ));

        if (is_wp_error($response)) {
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code === 200) {
            wp_send_json_success('Connection successful!');
        } else {
            wp_send_json_error('Connection failed with status code: ' . $status_code);
        }
    }
}
