/**
 * Premium Interactivity & AJAX Functionality
 * Online Forum Platform
 */

document.addEventListener('DOMContentLoaded', () => {
    
    // 1. Toast Notification Helper
    window.showToast = function(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast-msg ${type}`;
        
        let iconClass = 'bi-info-circle-fill';
        if (type === 'success') iconClass = 'bi-check-circle-fill';
        if (type === 'error') iconClass = 'bi-exclamation-triangle-fill';

        toast.innerHTML = `
            <i class="bi ${iconClass} toast-icon"></i>
            <div class="toast-text">${message}</div>
        `;

        container.appendChild(toast);

        // Auto dismiss after 4 seconds with fade-out
        setTimeout(() => {
            toast.style.animation = 'fadeIn 0.3s ease reverse forwards';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 4000);
    };

    // 2. AJAX Like Button Handler
    const likeBtn = document.getElementById('likeButton');
    if (likeBtn) {
        likeBtn.addEventListener('click', async () => {
            const topicId = likeBtn.getAttribute('data-topic-id');
            if (!topicId) return;

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'like',
                        topic_id: parseInt(topicId)
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    const countElem = document.getElementById('likesCount');
                    if (countElem) {
                        countElem.textContent = data.likes_count;
                    }
                    
                    if (data.liked) {
                        likeBtn.classList.add('liked');
                        showToast("Mavzuga layk bosildi!", "success");
                    } else {
                        likeBtn.classList.remove('liked');
                        showToast("Layk qaytarib olindi.", "info");
                    }
                } else {
                    showToast(data.message, "error");
                }
            } catch (err) {
                showToast("Server bilan bog'lanishda xatolik.", "error");
            }
        });
    }

    // 3. Quill.js WYSWYG Editor Initialization
    const editorContainer = document.getElementById('editor');
    let quill = null;
    if (editorContainer) {
        quill = new Quill('#editor', {
            theme: 'snow',
            placeholder: 'Sizning javobingiz yoki izohingizni yozing...',
            modules: {
                toolbar: [
                    ['bold', 'italic'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'clean']
                ]
            }
        });
    }

    // 4. AJAX Comment Submission
    const commentForm = document.getElementById('commentForm');
    if (commentForm && quill) {
        commentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const topicId = commentForm.getAttribute('data-topic-id');
            const editorContent = quill.getSemanticHTML();
            
            // Basic front-end check
            const textOnly = quill.getText().trim();
            if (textOnly === '') {
                showToast("Iltimos, izoh yozing.", "error");
                return;
            }

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'add_post',
                        topic_id: parseInt(topicId),
                        content: editorContent
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // 1. Clear editor
                    quill.setContents([]);
                    
                    // 2. Dynamic creation of comment card in list
                    const postsContainer = document.getElementById('postsContainer');
                    const noPostsAlert = document.getElementById('noPostsAlert');
                    
                    if (noPostsAlert) {
                        noPostsAlert.remove();
                    }

                    const post = data.post;
                    const roleBadge = post.role === 'admin' 
                        ? `<span class="role-badge role-admin">Admin</span>`
                        : (post.role === 'moderator' ? `<span class="role-badge role-moderator">Mod</span>` : `<span class="role-badge role-user">A'zo</span>`);

                    const postCardHtml = `
                        <div class="glass-panel post-card animate-fade-in" style="animation-delay: 0.1s;">
                            <div class="post-left">
                                <div class="avatar" style="background-color: ${post.avatar_color};">
                                    ${post.username.charAt(0).toUpperCase()}
                                </div>
                                <div class="post-username" title="${post.username}">${post.username}</div>
                            </div>
                            <div class="post-right">
                                <div class="post-header">
                                    ${roleBadge}
                                    <div class="post-meta">
                                        <i class="bi bi-clock-history"></i> ${post.created_at}
                                    </div>
                                </div>
                                <div class="post-content">
                                    ${post.content}
                                </div>
                            </div>
                        </div>
                    `;

                    postsContainer.insertAdjacentHTML('beforeend', postCardHtml);
                    
                    // Update posts count on UI
                    const commentsCountLabel = document.getElementById('commentsCountLabel');
                    if (commentsCountLabel) {
                        const currentVal = parseInt(commentsCountLabel.textContent) || 0;
                        commentsCountLabel.textContent = currentVal + 1;
                    }

                    showToast("Izohingiz qo'shildi!", "success");
                } else {
                    showToast(data.message, "error");
                }
            } catch (err) {
                showToast("Server xatosi, izoh qo'shib bo'lmadi.", "error");
            }
        });
    }

    // 5. Client-Side Register Form Validation
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {
            const usernameInput = document.getElementById('username');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            let hasError = false;

            if (usernameInput.value.trim().length < 3) {
                showToast("Foydalanuvchi nomi kamida 3 belgidan iborat bo'lishi kerak.", "error");
                hasError = true;
            }
            if (passwordInput.value.length < 6) {
                showToast("Parol uzunligi kamida 6 ta belgi bo'lishi shart.", "error");
                hasError = true;
            }
            if (passwordInput.value !== confirmPasswordInput.value) {
                showToast("Parollar bir-biriga mos kelmadi.", "error");
                hasError = true;
            }

            if (hasError) {
                e.preventDefault();
            }
        });
    }

    // 6. Profile Avatar Color Selection Interface
    const colorSwatches = document.querySelectorAll('.color-swatch');
    const selectedColorInput = document.getElementById('avatarColorInput');
    const previewAvatar = document.getElementById('previewAvatar');

    if (colorSwatches && selectedColorInput && previewAvatar) {
        colorSwatches.forEach(swatch => {
            swatch.addEventListener('click', () => {
                // Remove active class from all
                colorSwatches.forEach(s => s.classList.remove('active'));
                
                // Add to clicked
                swatch.classList.add('active');
                
                // Update input & avatar preview color
                const color = swatch.getAttribute('data-color');
                selectedColorInput.value = color;
                previewAvatar.style.backgroundColor = color;
                
                showToast("Profil rangi yangilandi! Saqlash tugmasini bosing.", "info");
            });
        });
    }
});
