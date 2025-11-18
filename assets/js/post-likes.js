class PostLikeManager {
    constructor() {
        this.initializeLikeButtons();
    }

    initializeLikeButtons() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.post-like-btn')) {
                this.handleLikeClick(e.target.closest('.post-like-btn'));
            }
        });

        this.loadAllLikeStatuses();
    }

    async handleLikeClick(button) {
        const postId = button.dataset.postId;
        
        try {
            button.disabled = true;
            
            const response = await fetch(`/post-like/toggle/${postId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const result = await response.json();
                this.updateLikeButton(button, result.liked);
                this.updateLikeCount(postId, result.count);
            }
        } catch (error) {
            console.error('Erreur:', error);
        } finally {
            button.disabled = false;
        }
    }

    updateLikeButton(button, liked) {
        const icon = button.querySelector('i');
        if (liked) {
            icon.className = 'fas fa-heart text-red-500';
            button.classList.add('liked');
        } else {
            icon.className = 'far fa-heart text-gray-500';
            button.classList.remove('liked');
        }
    }

    updateLikeCount(postId, count) {
        const countElement = document.querySelector(`[data-post-like-count="${postId}"]`);
        if (countElement) {
            countElement.textContent = count;
        }
    }

    async loadAllLikeStatuses() {
        const likeButtons = document.querySelectorAll('.post-like-btn');
        
        for (const button of likeButtons) {
            const postId = button.dataset.postId;
            await this.loadLikeStatus(postId);
            await this.loadLikeCount(postId);
        }
    }

    async loadLikeStatus(postId) {
        try {
            const response = await fetch(`/post-like/status/${postId}`);
            if (response.ok) {
                const result = await response.json();
                const button = document.querySelector(`[data-post-id="${postId}"]`);
                if (button) {
                    this.updateLikeButton(button, result.liked);
                }
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    async loadLikeCount(postId) {
        try {
            const response = await fetch(`/post-like/count/${postId}`);
            if (response.ok) {
                const result = await response.json();
                this.updateLikeCount(postId, result.count);
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new PostLikeManager();
});

export default PostLikeManager;
