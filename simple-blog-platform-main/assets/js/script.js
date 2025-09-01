$(document).ready(function() {
    // Load posts when the page loads
    loadPosts();

    // Reset form when modal is opened for adding a new post
    $('#postModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var action = button.data('action');
        
        if (action !== 'edit') {
            // Clear form for new post
            $('#postForm')[0].reset();
            $('#postId').val('');
            $('.modal-title').text('Add Post');
        }
    });

    // Handle form submission
    $('#postForm').submit(function(event) {
        event.preventDefault(); // Prevent default form submission

        var postId = $('#postId').val();
        var title = $('#title').val();
        var content = $('#content').val();

        $.ajax({
            url: 'src/post-handler.php',
            type: 'POST',
            dataType: 'json',
            data: {
                id: postId,
                title: title,
                content: content
            },
            success: function(response) {
                if (response.success) {
                    $('#postModal').modal('hide');
                    loadPosts(); // Reload posts after saving
                } else {
                    alert('Error: ' + response.message);
                }
            }
        });
    });

    // Handle edit post
    $(document).on('click', '.edit-post', function() {
        var postId = $(this).data('id');
        var title = $(this).data('title');
        var content = $(this).data('content');
        
        // Fill the form with post data
        $('#postId').val(postId);
        $('#title').val(title);
        $('#content').val(content);
        
        // Change modal title
        $('.modal-title').text('Edit Post');
        
        // Open the modal
        $('#postModal').modal('show');
    });

    // Handle delete post
    $(document).on('click', '.delete-post', function() {
        if (confirm('Are you sure you want to delete this post?')) {
            var postId = $(this).data('id');
            
            $.ajax({
                url: 'src/post-handler.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'delete',
                    id: postId
                },
                success: function(response) {
                    if (response.success) {
                        loadPosts(); // Reload posts after deletion
                    } else {
                        alert('Error: ' + response.message);
                    }
                }
            });
        }
    });

    // Format date function
    function formatDate(dateString) {
        var date = new Date(dateString);
        return date.toLocaleString();
    }

    // Load posts function
    function loadPosts() {
        $.ajax({
            url: 'src/get-posts.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                var postsHtml = '';
                if (data.posts && data.posts.length > 0) {
                    $.each(data.posts, function(index, post) {
                        postsHtml += '<div class="post card mb-3">' +
                            '<div class="card-header d-flex justify-content-between align-items-center">' +
                                '<h3 class="card-title mb-0">' + post.title + '</h3>' +
                                '<div class="btn-group">' +
                                    '<button class="btn btn-sm btn-outline-primary edit-post" ' +
                                        'data-id="' + post.id + '" ' +
                                        'data-title="' + post.title + '" ' +
                                        'data-content="' + post.content + '">Edit</button>' +
                                    '<button class="btn btn-sm btn-outline-danger delete-post" ' +
                                        'data-id="' + post.id + '">Delete</button>' +
                                '</div>' +
                            '</div>' +
                            '<div class="card-body">' +
                                '<p class="card-text">' + post.content + '</p>' +
                            '</div>' +
                            '<div class="card-footer text-muted">' +
                                'Posted on: ' + formatDate(post.created_at) +
                            '</div>' +
                        '</div>';
                    });
                } else {
                    postsHtml = '<div class="alert alert-info">No posts found. Create your first post!</div>';
                }
                $('#posts').html(postsHtml);
            },
            error: function() {
                $('#posts').html('<div class="alert alert-danger">Error loading posts. Please try again later.</div>');
            }
        });
    }
});
