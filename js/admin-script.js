// admin-script.js
jQuery(document).ready(function($) {
    // Initialize CodeMirror for Blog Info Textarea
    var blogInfoEditor = CodeMirror.fromTextArea(document.getElementById("blog_info"), {
        lineNumbers: true,
        mode: "text/plain",
        theme: "eclipse",
        lineWrapping: true
    });
    blogInfoEditor.setSize("800px", "auto");

    // Save API Key Button Click
    $('#save_api_key').on('click', function(e) {
        e.preventDefault();
        var apiKey = $('#api_key').val().trim();
        var nonce = abc_ajax_object.save_api_key_nonce;

        if(apiKey === "") {
            alert('API Key cannot be empty.');
            return;
        }

        $.ajax({
            url: abc_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'abc_save_api_key',
                api_key: apiKey,
                nonce: nonce
            },
            success: function(response) {
                if(response.success) {
                    alert(response.data);
                } else {
                    alert('An error occurred while saving the API Key: ' + response.data); // Modified line to match requirement
                }
            },
            error: function(xhr, status, error) {
                alert('An unexpected error occurred: ' + error);
            }
        });
    });

    // Test Connection Button Click
    $('#test_connection').on('click', function(e) {
        e.preventDefault();
        var apiKey = $('#api_key').val().trim();
        var nonce = abc_ajax_object.test_connection_nonce;

        if(apiKey === "") {
            alert('API Key cannot be empty.');
            return;
        }

        $.ajax({
            url: abc_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'abc_test_connection',
                api_key: apiKey,
                nonce: nonce
            },
            success: function(response) {
                if(response.success) {
                    alert(response.data);
                } else {
                    alert('Connection test failed: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('An unexpected error occurred: ' + error);
            }
        });
    });

    // Create Posts Button Click
    $('#create_post').on('click', function(e) {
        e.preventDefault();
        var nonce = abc_ajax_object.create_post_nonce;

        // Clear previous status
        $('#post_status_list').empty();
        $('#progress_message').text('Starting post creation process...');
        $('#progress_bar_container').show();
        $('#progress_bar').css('width', '0%');

        $.ajax({
            url: abc_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'abc_create_post',
                nonce: nonce
            },
            success: function(response) {
                if(response.success) {
                    $('#progress_message').text(response.data);
                } else {
                    $('#progress_message').text('An error occurred: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                $('#progress_message').text('An unexpected error occurred: ' + error);
            }
        });

        // Poll for progress every 2 seconds
        var pollInterval = setInterval(function() {
            $.ajax({
                url: abc_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'abc_get_progress',
                    nonce: abc_ajax_object.get_progress_nonce
                },
                success: function(response) {
                    if(response.success) {
                        var current = response.data.current;
                        var total = response.data.total;
                        var percent = Math.floor((current / total) * 100);
                        $('#progress_bar').css('width', percent + '%');
                        $('#progress_message').text('Creating post ' + (current + 1) + ' of ' + total);

                        if(current >= total) {
                            clearInterval(pollInterval);
                            $('#progress_message').text('All posts have been created successfully.');
                            $('#progress_bar').css('width', '100%');
                        }
                    } else {
                        clearInterval(pollInterval);
                        $('#progress_message').text('No ongoing post creation process.');
                        $('#progress_bar_container').hide();
                    }
                },
                error: function(xhr, status, error) {
                    clearInterval(pollInterval);
                    $('#progress_message').text('An unexpected error occurred: ' + error);
                }
            });
        }, 2000); // Poll every 2 seconds
    });
});
