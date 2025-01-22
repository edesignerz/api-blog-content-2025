class API_Blog_Content_API {
    public function generate_outline($api_key, $outline_prompt, $keyword, $title) {
        $prompt = str_replace(array('{Keyword}', '{Title}'), array($keyword, $title), $outline_prompt);

        // Initialize retry parameters
        $max_retries = 3;
        $attempt = 0;
        $success = false;
        $outline = '';

        while ($attempt < $max_retries && !$success) {
            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body' => json_encode(array(
                    'model' => 'gpt-4o-mini', // Changed model here
                    'messages' => array(
                        array('role' => 'system', 'content' => 'You are a helpful assistant for creating blog outlines.'),
                        array('role' => 'user', 'content' => $prompt),
                    ),
                    'max_tokens' => 500,
                    'temperature' => 0.7,
                )),
                'timeout' => 60, // 1 minute
            ));

            if (is_wp_error($response)) {
                if ($response->get_error_code() == 'http_request_failed' && strpos($response->get_error_message(), 'cURL error 28') !== false) {
                    $attempt++;
                    if ($attempt >= $max_retries) {
                        return new WP_Error('api_timeout', 'API request failed after ' . $max_retries . ' attempts: ' . $response->get_error_message());
                    }
                    $delay = pow(2, $attempt);
                    sleep($delay);
                    continue;
                } else {
                    return new WP_Error('api_request_failed', 'API request failed: ' . $response->get_error_message());
                }
            } else {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                if (isset($data['choices'][0]['message']['content'])) {
                    $outline = $data['choices'][0]['message']['content'];
                    $success = true;
                } else {
                    return new WP_Error('invalid_response', 'Invalid API response.');
                }
            }
        }

        return $outline;
    }

    public function generate_article($api_key, $creation_prompt, $outline, $keyword, $title) {
        $prompt = str_replace(array('{Outline}', '{Keyword}', '{Title}'), array($outline, $keyword, $title), $creation_prompt);

        // Initialize retry parameters
        $max_retries = 3;
        $attempt = 0;
        $success = false;
        $article = '';

        while ($attempt < $max_retries && !$success) {
            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body' => json_encode(array(
                    'model' => 'gpt-4o-mini', // Changed model here
                    'messages' => array(
                        array('role' => 'system', 'content' => 'You are a helpful assistant for creating detailed blog articles in Markdown format.'),
                        array('role' => 'user', 'content' => $prompt),
                    ),
                    'max_tokens' => 2000,
                    'temperature' => 0.7,
                )),
                'timeout' => 60, // 1 minute
            ));

            if (is_wp_error($response)) {
                if ($response->get_error_code() == 'http_request_failed' && strpos($response->get_error_message(), 'cURL error 28') !== false) {
                    $attempt++;
                    if ($attempt >= $max_retries) {
                        return new WP_Error('api_timeout', 'API request failed after ' . $max_retries . ' attempts: ' . $response->get_error_message());
                    }
                    $delay = pow(2, $attempt);
                    sleep($delay);
                    continue;
                } else {
                    return new WP_Error('api_request_failed', 'API request failed: ' . $response->get_error_message());
                }
            } else {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                if (isset($data['choices'][0]['message']['content'])) {
                    $article = $data['choices'][0]['message']['content'];
                    $success = true;
                } else {
                    return new WP_Error('invalid_response', 'Invalid API response.');
                }
            }
        }

        // Link the second occurrence of the main keyword in the body text only
        if ($article) {
            $article = $this->link_keyword_in_body_text($article, $keyword);
        }

        return $article;
    }

    public function generate_tags($api_key, $keyword, $article) {
        $prompt = "Based on the following blog article and the keyword '{$keyword}', suggest 15 relevant tags separated by commas:\n\n" . $article;

        // Initialize retry parameters
        $max_retries = 3;
        $attempt = 0;
        $success = false;
        $tags = '';

        while ($attempt < $max_retries && !$success) {
            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body' => json_encode(array(
                    'model' => 'gpt-4o-mini', // Changed model here
                    'messages' => array(
                        array('role' => 'system', 'content' => 'You are a helpful assistant for suggesting relevant tags for blog posts.'),
                        array('role' => 'user', 'content' => $prompt),
                    ),
                    'max_tokens' => 60,
                    'temperature' => 0.5,
                )),
                'timeout' => 60, // 1 minute
            ));

            if (is_wp_error($response)) {
                if ($response->get_error_code() == 'http_request_failed' && strpos($response->get_error_message(), 'cURL error 28') !== false) {
                    $attempt++;
                    if ($attempt >= $max_retries) {
                        return new WP_Error('api_timeout', 'API request failed after ' . $max_retries . ' attempts: ' . $response->get_error_message());
                    }
                    $delay = pow(2, $attempt);
                    sleep($delay);
                    continue;
                } else {
                    return new WP_Error('api_request_failed', 'API request failed: ' . $response->get_error_message());
                }
            } else {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                if (isset($data['choices'][0]['message']['content'])) {
                    $tags = $data['choices'][0]['message']['content'];
                    $success = true;
                } else {
                    return new WP_Error('invalid_response', 'Invalid API response.');
                }
            }
        }

        return $tags;
    }

    public function generate_meta_description($api_key, $article, $keyword) {
        $prompt = "Based on the following blog article, create a concise meta description under 160 characters that includes the keyword '{$keyword}' exactly as provided:\n\n" . $article;

        // Initialize retry parameters
        $max_retries = 3;
        $attempt = 0;
        $success = false;
        $meta_description = '';

        while ($attempt < $max_retries && !$success) {
            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body' => json_encode(array(
                    'model' => 'gpt-4o-mini', // Changed model here
                    'messages' => array(
                        array('role' => 'system', 'content' => 'You are a helpful assistant for creating meta descriptions for blog posts.'),
                        array('role' => 'user', 'content' => $prompt),
                    ),
                    'max_tokens' => 60,
                    'temperature' => 0.5,
                )),
                'timeout' => 60, // 1 minute
            ));

            if (is_wp_error($response)) {
                if ($response->get_error_code() == 'http_request_failed' && strpos($response->get_error_message(), 'cURL error 28') !== false) {
                    $attempt++;
                    if ($attempt >= $max_retries) {
                        return new WP_Error('api_timeout', 'API request failed after ' . $max_retries . ' attempts: ' . $response->get_error_message());
                    }
                    $delay = pow(2, $attempt);
                    sleep($delay);
                    continue;
                } else {
                    return new WP_Error('api_request_failed', 'API request failed: ' . $response->get_error_message());
                }
            } else {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                if (isset($data['choices'][0]['message']['content'])) {
                    $meta_description = substr(trim($data['choices'][0]['message']['content']), 0, 160); // Ensure it's under 160 characters
                    $success = true;
                } else {
                    return new WP_Error('invalid_response', 'Invalid API response.');
                }
            }
        }

        return $meta_description;
    }

    public function link_keyword_in_body_text($article, $keyword) {
        // Remove H tags from the article
        $article_without_h_tags = preg_replace('/<h[1-6]>.*?<\/h[1-6]>/s', '', $article);

        // Remove other HTML tags from the article, except for paragraph and list tags
        $article_without_tags = preg_replace('/<(?!p|ul|ol|li).*?>/', '', $article_without_h_tags);

        // Count the occurrences of the keyword in the article without tags
        $occurrences = substr_count($article_without_tags, $keyword);

        // If there are at least two occurrences, we will replace the second one
        if ($occurrences >= 2) {
            // Find the position of the second occurrence of the keyword
            $first_pos = strpos($article_without_tags, $keyword);
            $second_pos = strpos($article_without_tags, $keyword, $first_pos + strlen($keyword));

            // Find the corresponding position in the original article
            $second_pos_in_original_article = strpos($article, $keyword, $first_pos + strlen($keyword));

            // Replace the second occurrence with the hyperlink
            if ($second_pos_in_original_article !== false) {
                $linked_keyword = '<a href="/">'. $keyword .'</a>';
                $article = substr_replace($article, $linked_keyword, $second_pos_in_original_article, strlen($keyword));
            }
        }

        return $article;
    }
}
