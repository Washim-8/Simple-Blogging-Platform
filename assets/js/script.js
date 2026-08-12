$(document).ready(function() {
    // Global variables
    let postToDeleteId = null;
    let allPosts = []; // Store all posts for filtering
    let currentFilter = 'all';
    
    // Initialize dark mode
    initDarkMode();
    
    // Load posts when page loads
    loadPosts();
    
    // Setup event handlers
    setupEventHandlers();
    
    // Setup scroll handlers
    setupScrollHandlers();
    
    // Setup character counter
    setupCharacterCounter();
});

// Dark Mode Functions
function initDarkMode() {
    const darkMode = localStorage.getItem('darkMode');
    if (darkMode === 'enabled') {
        document.documentElement.setAttribute('data-theme', 'dark');
        $('#darkModeToggle i').removeClass('fa-moon').addClass('fa-sun');
        $('#darkModeToggle span').text('Light Mode');
    }
    
    $('#darkModeToggle').on('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        if (currentTheme === 'dark') {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('darkMode', 'disabled');
            $('#darkModeToggle i').removeClass('fa-sun').addClass('fa-moon');
            $('#darkModeToggle span').text('Dark Mode');
            showToast('Light mode enabled', 'info');
        } else {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('darkMode', 'enabled');
            $('#darkModeToggle i').removeClass('fa-moon').addClass('fa-sun');
            $('#darkModeToggle span').text('Light Mode');
            showToast('Dark mode enabled', 'info');
        }
    });
}

// Toast Notification System - Simple & Clean
function showToast(message, type = 'success') {
    // Remove any existing toasts
    $('.toast-notification').remove();
    
    const icons = {
        success: '✓',
        error: '✕',
        warning: '!',
        info: 'i'
    };
    
    const toastHtml = `
        <div class="toast-notification toast-${type}">
            <div class="toast-icon">${icons[type]}</div>
            <div class="toast-message">${message}</div>
        </div>
    `;
    
    $('body').append(toastHtml);
    
    // Show toast
    setTimeout(() => {
        $('.toast-notification').addClass('show');
    }, 10);
    
    // Auto remove after 2 seconds
    setTimeout(() => {
        $('.toast-notification').removeClass('show');
        setTimeout(() => {
            $('.toast-notification').remove();
        }, 300);
    }, 2000);
}

function closeToast() {
    $('.toast-notification').removeClass('show');
    setTimeout(() => {
        $('.toast-notification').remove();
    }, 300);
}

// Setup Event Handlers
function setupEventHandlers() {
    // Save post button click handler
    $('#savePost').on('click', function() {
        savePost();
    });
    
    // Add event handler for the Create New Post button
    $('button[data-target="#postModal"]').on('click', function() {
        resetForm();
    });
    
    // Setup delete confirmation
    $('#confirmDelete').on('click', function() {
        if (postToDeleteId) {
            confirmDeletePost(postToDeleteId);
        }
    });
    
    // Search functionality
    $('#searchInput').on('input', debounce(function() {
        filterPosts();
    }, 300));
    
    // Filter buttons
    $('.filter-btn').on('click', function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        currentFilter = $(this).data('filter');
        filterPosts();
    });
}

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Setup Scroll Handlers
function setupScrollHandlers() {
    // Scroll to top button visibility
    $(window).on('scroll', function() {
        if ($(this).scrollTop() > 300) {
            $('#scrollToTop').addClass('visible');
        } else {
            $('#scrollToTop').removeClass('visible');
        }
        
        // Update reading progress bar
        updateReadingProgress();
    });
    
    // Scroll to top click handler
    $('#scrollToTop').on('click', function() {
        $('html, body').animate({ scrollTop: 0 }, 600);
    });
}

// Update Reading Progress Bar
function updateReadingProgress() {
    const scrollTop = $(window).scrollTop();
    const docHeight = $(document).height() - $(window).height();
    const progress = (scrollTop / docHeight) * 100;
    $('#readingProgress').css('width', progress + '%');
}

// Character Counter and Word Count
function setupCharacterCounter() {
    const $content = $('#content');
    const $charCounter = $('#charCounter');
    const $wordCount = $('#wordCount');
    const maxChars = 5000;
    
    $content.on('input', function() {
        const text = $(this).val();
        const charCount = text.length;
        const wordCount = text.trim() ? text.trim().split(/\s+/).length : 0;
        
        // Update word count
        $wordCount.text(wordCount + ' word' + (wordCount !== 1 ? 's' : ''));
        
        // Update character counter
        $charCounter.text(charCount + ' / ' + maxChars + ' characters');
        
        // Update counter styling
        $charCounter.removeClass('warning danger');
        if (charCount > maxChars * 0.9) {
            $charCounter.addClass('danger');
        } else if (charCount > maxChars * 0.8) {
            $charCounter.addClass('warning');
        }
    });
}

// Calculate reading time
function calculateReadingTime(content) {
    const wordsPerMinute = 200;
    const wordCount = content.trim() ? content.trim().split(/\s+/).length : 0;
    const readingTime = Math.ceil(wordCount / wordsPerMinute);
    return readingTime < 1 ? '< 1 min' : readingTime + ' min';
}

// Function to load all posts
function loadPosts() {
    // Show skeleton loading
    $('#skeletonLoading').show();
    $('#posts').hide();
    
    $.ajax({
        url: 'src/get-posts.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Posts loaded:', response);
            // Hide skeleton loading after a brief delay for smooth transition
            setTimeout(() => {
                $('#skeletonLoading').hide();
                $('#posts').show();
                displayPosts(response);
            }, 500);
        },
        error: function(xhr, status, error) {
            console.error('Load posts error:', {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            $('#skeletonLoading').hide();
            showToast('Error loading posts: ' + error, 'error');
            console.error('Error loading posts:', error);
        }
    });
}

// Function to display posts
function displayPosts(response) {
    var postsContainer = $('#posts');
    postsContainer.empty();
    
    // Handle different response formats
    var posts = [];
    if (Array.isArray(response)) {
        posts = response;
    } else if (response.posts && Array.isArray(response.posts)) {
        posts = response.posts;
    }
    
    // Store posts for filtering
    allPosts = posts.map(post => ({
        ...post,
        likes: parseInt(localStorage.getItem('post_likes_' + post.id) || 0),
        isLiked: localStorage.getItem('post_liked_' + post.id) === 'true'
    }));

    if (posts.length === 0) {
        $('#noPosts').show();
        $('#posts').hide();
        return;
    }
    
    $('#noPosts').hide();
    $('#posts').show();
    
    // Render all posts immediately
    $.each(allPosts, function(index, post) {
        var postHtml = generatePostHtml(post, index);
        postsContainer.append(postHtml);
    });

    // Add event listeners for interactive elements
    addPostEventListeners();
}

// Function to filter posts based on search and filter type
function filterPosts() {
    const searchTerm = $('#searchInput').val().toLowerCase();
    let filteredPosts = [...allPosts];
    
    // Apply search filter
    if (searchTerm) {
        filteredPosts = filteredPosts.filter(post => 
            post.title.toLowerCase().includes(searchTerm) || 
            post.content.toLowerCase().includes(searchTerm)
        );
    }
    
    // Apply type filter
    switch(currentFilter) {
        case 'recent':
            filteredPosts.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            break;
        case 'liked':
            filteredPosts.sort((a, b) => b.likes - a.likes);
            break;
        default:
            // Keep original order for 'all'
            break;
    }
    
    // Render filtered posts
    var postsContainer = $('#posts');
    postsContainer.empty();
    
    if (filteredPosts.length === 0) {
        $('#noPosts').show();
        postsContainer.hide();
        return;
    }
    
    $('#noPosts').hide();
    postsContainer.show();
    
    $.each(filteredPosts, function(index, post) {
        var postHtml = generatePostHtml(post, index);
        postsContainer.append(postHtml);
    });

    // Add event listeners for interactive elements
    addPostEventListeners();
}

// Function to add event listeners to post elements
function addPostEventListeners() {
    // Edit buttons
    $('.edit-post').on('click', function() {
        editPost($(this).data('id'));
    });

    // Delete buttons
    $('.delete-post').on('click', function() {
        postToDeleteId = $(this).data('id');
        $('#deleteModal').modal('show');
    });
    
    // Like buttons
    $('.like-btn').on('click', function() {
        const postId = $(this).data('id');
        toggleLike(postId, $(this));
    });
    
    // Read more buttons
    $('.read-more-btn').on('click', function() {
        const $card = $(this).closest('.post-card');
        const $text = $card.find('.card-text');
        const $btn = $(this);
        
        if ($text.hasClass('expanded')) {
            $text.removeClass('expanded');
            $btn.html('Read more <i class="fas fa-chevron-right"></i>');
        } else {
            $text.addClass('expanded');
            $btn.html('Show less <i class="fas fa-chevron-up"></i>');
        }
    });
    
    // Share buttons
    $('.share-copy').on('click', function() {
        const postId = $(this).data('id');
        copyPostLink(postId);
    });
}

// Toggle like on a post
function toggleLike(postId, $btn) {
    const isLiked = localStorage.getItem('post_liked_' + postId) === 'true';
    let likes = parseInt(localStorage.getItem('post_likes_' + postId) || 0);
    
    if (isLiked) {
        likes = Math.max(0, likes - 1);
        localStorage.setItem('post_liked_' + postId, 'false');
        $btn.removeClass('liked');
        showToast('Post unliked', 'info');
    } else {
        likes++;
        localStorage.setItem('post_liked_' + postId, 'true');
        $btn.addClass('liked');
        showToast('Post liked!', 'success');
    }
    
    localStorage.setItem('post_likes_' + postId, likes);
    $btn.find('.like-count').text(likes);
}

// Copy post link to clipboard
function copyPostLink(postId) {
    const url = window.location.origin + window.location.pathname + '?post=' + postId;
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            showToast('Link copied to clipboard!', 'success');
        }).catch(() => {
            showToast('Failed to copy link', 'error');
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = url;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showToast('Link copied to clipboard!', 'success');
    }
}

// Function to generate HTML for a post
function generatePostHtml(post, index) {
    var date = new Date(post.created_at);
    var formattedDate = formatDate(date);
    var readingTime = calculateReadingTime(post.content);
    var isExpanded = post.content.length > 200;
    
    // Determine if we need read more button
    var readMoreBtn = isExpanded ? 
        `<div class="read-more-btn">Read more <i class="fas fa-chevron-right"></i></div>` : '';
    
    // Share buttons HTML
    var shareButtons = `
        <div class="share-buttons">
            <button class="share-btn share-copy" data-id="${post.id}" title="Copy link">
                <i class="fas fa-link"></i>
            </button>
        </div>
    `;

    return `
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card post-card" style="animation-delay: ${index * 0.1}s">
                <div class="card-body">
                    <h5 class="card-title">${escapeHtml(post.title)}</h5>
                    <p class="card-text">${escapeHtml(post.content)}</p>
                    ${readMoreBtn}
                    ${shareButtons}
                </div>
                <div class="card-footer">
                    <div class="post-meta">
                        <small class="post-meta-item">
                            <i class="far fa-calendar-alt"></i> ${formattedDate}
                        </small>
                        <span class="reading-time">
                            <i class="far fa-clock"></i> ${readingTime} read
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button class="like-btn ${post.isLiked ? 'liked' : ''}" data-id="${post.id}" title="Like post">
                            <i class="${post.isLiked ? 'fas' : 'far'} fa-heart"></i>
                            <span class="like-count">${post.likes || 0}</span>
                        </button>
                        <div class="btn-group ml-2">
                            <button type="button" class="btn btn-sm btn-outline-primary edit-post" data-id="${post.id}" title="Edit Post">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-post" data-id="${post.id}" title="Delete Post">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Function to format date
function formatDate(date) {
    var options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

// Function to save a post (create or update)
function savePost() {
    const title = $('#title').val().trim();
    const content = $('#content').val().trim();
    const postIdElement = $('#postId');
    let id = postIdElement.val();

    if (!title || !content) {
        showToast('Please fill in all fields', 'warning');
        return;
    }
    
    // Check character limit
    if (content.length > 5000) {
        showToast('Content exceeds 5000 character limit', 'warning');
        return;
    }
    
    // Convert id to integer if it exists
    if (id) {
        id = parseInt(id);
        if (isNaN(id) || id <= 0) {
            id = '';
        }
    }

    // Show loading state
    var saveButton = $('#savePost');
    var originalText = saveButton.html();
    saveButton.html('<i class="fas fa-spinner fa-spin mr-2"></i> Saving...');
    saveButton.prop('disabled', true);

    $.ajax({
        url: 'src/post-handler.php',
        type: 'POST',
        data: {
            action: 'save',
            id: id,
            title: title,
            content: content
        },
        dataType: 'json',
        success: function(response) {
            console.log('Server response:', response);
            if (response.success) {
                $('#postModal').modal('hide');
                showToast(response.message, 'success');
                loadPosts();
                resetForm();
            } else {
                showToast(response.message || 'Failed to save post', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', {
                status: status,
                error: error,
                responseText: xhr.responseText,
                statusCode: xhr.status
            });
            
            // Try to parse error response
            try {
                const errorResponse = JSON.parse(xhr.responseText);
                showToast(errorResponse.message || 'Error saving post', 'error');
            } catch (e) {
                showToast('Error saving post: ' + (xhr.responseText || error), 'error');
            }
        },
        complete: function() {
            saveButton.html(originalText);
            saveButton.prop('disabled', false);
        }
    });
}

// Function to edit a post
function editPost(id) {
    id = parseInt(id);
    if (isNaN(id)) {
        showToast('Invalid post ID', 'error');
        return;
    }
    
    // Set the modal title to Edit Post immediately
    document.getElementById('postModalLabel').textContent = 'Edit Post';
    
    // Clear form fields manually
    document.getElementById('title').value = '';
    document.getElementById('content').value = '';
    
    // Then fetch the post data
    $.ajax({
        url: 'src/get-posts.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.error) {
                showToast(response.message || 'Error loading post', 'error');
                return;
            }
            
            var post = response;
            
            if (!post || !post.id || !post.title || !post.content) {
                showToast('Invalid post data received', 'error');
                return;
            }
            
            document.getElementById('postId').value = post.id;
            document.getElementById('title').value = post.title;
            document.getElementById('content').value = post.content;
            document.getElementById('postModalLabel').textContent = 'Edit Post';
            
            // Trigger input event to update counters
            $('#content').trigger('input');
            
            // Show the modal after successfully loading the post data
            $('#postModal').modal('show');
        },
        error: function(xhr, status, error) {
            showToast('Error loading post: ' + error, 'error');
            console.error('Error loading post:', xhr.responseText);
        }
    });
}

// Function to confirm delete a post
function confirmDeletePost(id) {
    // Show loading state
    var deleteButton = $('#confirmDelete');
    var originalText = deleteButton.html();
    deleteButton.html('<i class="fas fa-spinner fa-spin mr-2"></i> Deleting...');
    deleteButton.prop('disabled', true);

    $.ajax({
        url: 'src/post-handler.php',
        type: 'POST',
        data: {
            action: 'delete',
            id: id
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#deleteModal').modal('hide');
                showToast(response.message, 'success');
                loadPosts();
            } else {
                showToast(response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            showToast('Error deleting post: ' + error, 'error');
            console.error('Error deleting post:', error);
        },
        complete: function() {
            // Reset button state
            deleteButton.html(originalText);
            deleteButton.prop('disabled', false);
            postToDeleteId = null;
        }
    });
}

// Function to reset the form
function resetForm() {
    // Clear all form fields
    document.getElementById('postForm').reset();
    
    // Explicitly clear the hidden postId field
    document.getElementById('postId').value = '';
    
    // Reset the modal title
    document.getElementById('postModalLabel').textContent = 'Create New Post';
    
    // Also ensure title and content are cleared
    document.getElementById('title').value = '';
    document.getElementById('content').value = '';
    
    // Reset counters
    $('#charCounter').text('0 / 5000 characters').removeClass('warning danger');
    $('#wordCount').text('0 words');
}

// Legacy showAlert function (kept for compatibility, now uses toast)
function showAlert(message, type) {
    showToast(message, type);
}

// Legacy getAlertIcon function (kept for compatibility)
function getAlertIcon(type) {
    switch(type) {
        case 'success': return 'fa-check-circle';
        case 'danger': return 'fa-exclamation-circle';
        case 'warning': return 'fa-exclamation-triangle';
        case 'info': return 'fa-info-circle';
        default: return 'fa-info-circle';
    }
}