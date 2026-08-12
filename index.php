<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Blog Platform</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Reading Progress Bar -->
    <div class="reading-progress" id="readingProgress"></div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-pen-fancy mr-2"></i>Simple Blog Platform</a>
            <div class="d-flex align-items-center">
                <a href="about_contact.php" class="text-white mr-3" title="About & Contact" style="font-size: 1.5rem; transition: color 0.3s; opacity: 0.8;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">
                    <i class="fas fa-user-circle"></i>
                </a>
                <button class="dark-mode-toggle" id="darkModeToggle" title="Toggle Dark Mode">
                    <i class="fas fa-moon"></i>
                    <span>Dark Mode</span>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Page Header -->
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <h1>Blog Posts</h1>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#postModal" id="createPostBtn">
                <i class="fas fa-plus-circle mr-2"></i> Create New Post
            </button>
        </div>

        <!-- Search Bar -->
        <div class="search-container">
            <input type="text" class="search-input" id="searchInput" placeholder="Search posts by title or content...">
            <i class="fas fa-search search-icon"></i>
        </div>

        <!-- Filter Buttons -->
        <div class="filter-container">
            <button class="filter-btn active" data-filter="all">All Posts</button>
            <button class="filter-btn" data-filter="recent">Recent</button>
            <button class="filter-btn" data-filter="liked">Most Liked</button>
        </div>

        <!-- Alert for messages (legacy) -->
        <div id="alertMessage" class="alert alert-success" style="display: none;"></div>

        <!-- Skeleton Loading (shown while loading) -->
        <div id="skeletonLoading" class="row">
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="skeleton skeleton-card">
                    <div style="padding: 1.8rem;">
                        <div class="skeleton skeleton-title"></div>
                        <div class="skeleton skeleton-text"></div>
                        <div class="skeleton skeleton-text"></div>
                        <div class="skeleton skeleton-text"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="skeleton skeleton-card">
                    <div style="padding: 1.8rem;">
                        <div class="skeleton skeleton-title"></div>
                        <div class="skeleton skeleton-text"></div>
                        <div class="skeleton skeleton-text"></div>
                        <div class="skeleton skeleton-text"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="skeleton skeleton-card">
                    <div style="padding: 1.8rem;">
                        <div class="skeleton skeleton-title"></div>
                        <div class="skeleton skeleton-text"></div>
                        <div class="skeleton skeleton-text"></div>
                        <div class="skeleton skeleton-text"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Blog Posts Container -->
        <div id="posts" class="row" style="display: none;"></div>

        <!-- No Posts Message -->
        <div id="noPosts" class="text-center py-5" style="display: none;">
            <div class="mb-4">
                <i class="fas fa-file-alt fa-4x text-muted"></i>
            </div>
            <h3 class="text-muted">No posts found</h3>
            <p class="text-muted mb-4">Create your first blog post to get started!</p>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#postModal">
                <i class="fas fa-plus-circle mr-2"></i> Create New Post
            </button>
        </div>
    </div>

    <!-- Floating Action Button (Mobile) -->
    <button class="fab" id="fab" data-toggle="modal" data-target="#postModal">
        <i class="fas fa-plus"></i>
    </button>

    <!-- Scroll to Top Button -->
    <button class="scroll-to-top" id="scrollToTop" title="Scroll to top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Post Modal -->
    <div class="modal fade" id="postModal" tabindex="-1" role="dialog" aria-labelledby="postModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="postModalLabel">Create New Post</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="postForm">
                        <input type="hidden" id="postId">
                        <div class="form-group">
                            <label for="title"><i class="fas fa-heading mr-2"></i>Title</label>
                            <input type="text" class="form-control" id="title" placeholder="Enter post title" required>
                        </div>
                        <div class="form-group">
                            <label for="content"><i class="fas fa-paragraph mr-2"></i>Content</label>
                            <textarea class="form-control" id="content" rows="8" placeholder="Write your post content here..." required></textarea>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="word-count-badge">
                                    <i class="fas fa-font"></i>
                                    <span id="wordCount">0 words</span>
                                </span>
                                <span class="char-counter" id="charCounter">0 / 5000 characters</span>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-2"></i>Close
                    </button>
                    <button type="button" class="btn btn-primary" id="savePost">
                        <i class="fas fa-save mr-2"></i>Save Post
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this post? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="fas fa-trash mr-2"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-3 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; 2025 Simple Blog Platform | Made with <i class="fas fa-heart heart"></i> for bloggers</p>
        </div>
    </footer>

    <!-- jQuery, Popper.js, and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/script.js"></script>
</body>
</html>