class API_Blog_Content_Post_Creation {
    public function abc_create_post() {
        if (!current_user_can('manage_options')) {
            error_log('Unauthorized access attempt.');
            wp_send_json_error('Unauthorized');
        }

        check_ajax_referer('abc_create_post_nonce', 'nonce');

        $options = get_option('abc_options');
        if (!isset($options['api_key']) || empty($options['api_key'])) {
            error_log('API Key not set.');
            wp_send_json_error('API Key not set.');
        }

        $api_key = $options['api_key'];
        $creation_prompt = isset($options['creation_prompt']) ? $options['creation_prompt'] : '';
        $blog_info = isset($options['blog_info']) ? $options['blog_info'] : '';
       Unable to process request. Sorry, please try again later. $outline_prompt = isset($options['outline_prompt']) ? $options['outline_prompt'] : '';

        if (empty($blog_info) || empty($outline_prompt) || empty($creation_prompt)) {
            error_log('Blog Info, Outline Prompt, or Creation Prompt is missing.');
            wp_send_json_error('Blog Info, Outline Prompt, and Creation Prompt are required.');
        }

        $blog_entries = $this->split_blog_entries($blog_info);

        $batch_size = 3;
        $batches = array_chunk($blog_entries, $batch_size);

        $total_posts = count($blog_entries);
        $current_post = 0;
        update_option('abc_progress', array('current' => $current_post, 'total' => $total_posts));

        $post_ids = array();
        $errors = array();

        foreach ($batches as $batch_index => $batch) {
            error_log('Processing batch ' . ($batch_index + 1) . ' of ' . count($batches));
            foreach ($batch as $entry_index => $entry) {
                error_log('Processing entry ' . ($entry_index + 1) . ' of ' . count($batch));
                $title = $entry['title'];
                $keyword = $entry['keyword'];
                $category = $entry['category'];
                $publish_date = isset($entry['publish_date']) ? $entry['publish_date'] : ''; // Get the publish date

                error_log('Generating outline for "' . $title . '"...');
                $start_time = microtime(true);
                $outline = $this->generate_outline($api_key, $outline_prompt, $keyword, $title);
                $end_time = microtime(true);
                error_log('Outline generated in ' . ($end_time - $start_time) . ' seconds.');
                if (is_wp_error($outline)) {
                    error_log('Error generating outline for "' . $title . '": ' . $outline->get_error_message());
                    $errors[] = 'Outline Generation Failed for entry: "' . $title . '" - ' . $outline->get_error_message();
                    continue;
                }
                error_log('Outline generated successfully for "' . $title . '".');

                error_log('Generating article for "' . $title . '"...');
                $start_time = microtime(true);
                $article = $this->generate_article($api_key, $creation_prompt, $outline, $keyword, $title);
                $end_time = microtime(true);
                error_log('Article generated in ' . ($end_time - $start_time) . ' seconds.');
                if (is_wp_error($article)) {
                    error_log('Error generating article for "' . $title . '": ' . $article->get_error_message());
                    $errors[] = 'Article Generation Failed for entry: "' . $title . '" - ' . $article->get_error_message();
                    continue;
                }
                error_log('Article generated successfully for "' . $title . '".');

                error_log('Generating tags for "' . $title . '"...');
                $tags = $this->generate_tags($api_key, $keyword, $article);
                if (is_wp_error($tags)) {
                    error_log('Error generating tags for "' . $title . '": ' . $tags->get_error_message());
                    $errors[] = 'Tags Generation Failed for entry: "' . $title . '" - ' . $tags->get_error_message();
                    continue;
                }
                error_log('Tags generated successfully for "' . $title . '".');

                error_log('Generating meta description for "' . $title . '"...');
                $meta_description = $this->generate_meta_description($api_key, $article, $keyword);
                if (is_wp_error($meta_description)) {
                    error_log('Error generating meta description for "' . $title . '": ' . $meta_description->get_error_message());
                    $errors[] = 'Meta Description Generation Failed for entry: "' . $title . '" - ' . $meta_description->get_error_message();
                    continue;
                }
                error_log('Meta description generated successfully for "' . $title . '".');

                // Convert Markdown to HTML
                if (class_exists('Parsedown')) {
                    $parsedown = new Parsedown();
                    $content = $parsedown->text($article);
                } else {
                    $content = wpautop($article);
                }

                error_log('Creating post for "' . $title . '"...');
                // Check if publish date is set, and schedule the post
                $post_date = !empty($publish_date) ? $publish_date . ' 00:00:00' : current_time('mysql');
                $post_date_gmt = !empty($publish_date) ? get_gmt_from_date($publish_date . ' 00:00:00') : current_time('mysql', true);

                $post_id = wp_insert_post(array(
                    'post_title'   => wp_strip_all_tags($title),
                    'post_content' => $content,
                    'post_status'  => !empty($publish_date) ? 'future' : 'draft',
                    'post_author'  => get_current_user_id(),
                    'post_date'    => $post_date,
                    'post_date_gmt' => $post_date_gmt,
                ));

                if (is_wp_error($post_id)) {
                    error_log('Error creating post for "' . $title . '": ' . $post_id->get_error_message());
                    $errors[] = 'Failed to create post for entry: "' . $title . '" - ' . $post_id->get_error_message();
                    continue;
                }

                // Increment progress after successful post creation
                $current_post++;
                update_option('abc_progress', array('current' => $current_post, 'total' => $total_posts));

                error_log('Assigning tags to post for "' . $title . '"...');
                // Assign Tags to the Post
                $tags_array = array_map('trim', explode(',', $tags));
                wp_set_post_tags($post_id, $tags_array, true);

                error_log('Assigning meta description to post for "' . $title . '"...');
                // Assign Meta Description to the Post Excerpt without markdown formatting
                $sanitized_meta_description = sanitize_text_field($meta_description);
                $sanitized_meta_description = str_replace('**', '', $sanitized_meta_description);
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_excerpt' => $sanitized_meta_description
                ));

                error_log('Assigning category to post for "' . $title . '"...');
                // Assign Category to the Post
                $category_id = $this->get_category_id($category);
                if ($category_id) {
                    wp_set_post_categories($post_id, array($category_id));
                } else {
                    error_log('No valid category found for entry: "' . $title . '"');
                    $errors[] = 'No valid category found for entry: "' . $title . '"';
                }

                $post_ids[] = $post_id;
            }

            error_log('Batch ' . ($batch_index + 1) . ' of ' . count($batches) . ' completed.');
            // Update progress and trigger next batch
            if ($batch_index < count($batches) - 1) {
                error_log('Triggering next batch...');
                wp_send_json_success(array('next_batch' => true));
                // Trigger next batch via AJAX (e.g., using jQuery)
                ?>
                <script>
                    jQuery.ajax({
                        type: 'POST',
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        data: {
                            action: 'abc_create_post',
                            nonce: '<?php echo wp_create_nonce('abc_create_post_nonce'); ?>',
                            batch_index: '<?php echo $batch_index + 1; ?>'
                        },
                        success: function (response) {
                            // Handle response
                        }
                    });
                </script>
                <?php
            } else {
                error_log('All batches completed.');
                // Final batch, send success response
                if (!empty($post_ids)) {
                    $message = count($post_ids) . ' post(s) created successfully.';
                } else {
                    $message = 'No posts were created.';
                }

                if (!empty($errors)) {
                    $message .= ' However, some errors occurred: ' . implode(' | ', $errors);
                    error_log('Errors occurred during post creation: ' . implode(' | ', $errors));
                    wp_send_json_error($message);
                } else {
                    error_log('Post creation completed successfully.');
                    wp_send_json_success($message);
                }
            }
        }

        // Clear progress after completion
        delete_option('abc_progress');
    }

    public function split_blog_entries($blog_info) {
        $lines = explode("\n", $blog_info);
        $entries = array();
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $parts = explode(',', $line);
                if (count($parts) >= 4) {
                    $title = trim($parts[0]);
                    $keyword = trim($parts[1]);
                    $category = trim($parts[2]);
                    $publish_date = trim($parts[3]);
                    $entries[] = array(
                        'title' => $title,
                        'keyword' => $keyword,
                        'category' => $category,
                        'publish_date' => $publish_date
                    );
                }
            }
        }
        return $entries;
    }

    public function get_progress() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        // Verify nonce
        check_ajax_referer('abc_get_progress_nonce', 'nonce');

        $progress = get_option('abc_progress');

        if (!$progress) {
            wp_send_json_error('No ongoing post creation process.');
        }

        $current = isset($progress['current']) ? intval($progress['current']) : 0;
        $total = isset($progress['total']) ? intval($progress['total']) : 0;

        wp_send_json_success(array('current' => $current, 'total' => $total));
    }

    public function generate_outline($api_key, $outline_prompt, $keyword, $title) {
        $api = new API_Blog_Content_API();
        return $api->generate_outline($api_key, $outline_prompt, $keyword, $title);
    }

    public function generate_article($api_key, $creation_prompt, $outline, $keyword, $title) {
        $api = new API_Blog_Content_API();
        return $api->generate_article($api_key, $creation_prompt, $outline, $keyword, $title);
    }

    public function generate_tags($api_key, $keyword, $article) {
        $api = new API_Blog_Content_API();
        return $api->generate_tags($api_key, $keyword, $article);
    }

    public function generate_meta_description($api_key, $article, $keyword) {
        $api = new API_Blog_Content_API();
        return $api->generate_meta_description($api_key, $article, $keyword);
    }

    public function get_category_id($category_name) {
        $term = get_term_by('name', $category_name, 'category');
        if ($term && !is_wp_error($term)) {
            return (int)$term->term_id;
        }
        return false;
    }
}
