// UI utility functions for DOM manipulation and notifications
export class UIHelpers {
    // Show loading spinner
    static showLoading(element, text = 'Loading...') {
        const loader = document.createElement('div');
        loader.className = 'flex items-center justify-center space-x-2 p-4';
        loader.innerHTML = `
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span class="text-gray-600">${text}</span>
        `;
        element.innerHTML = '';
        element.appendChild(loader);
    }

    // Hide loading and show content
    static hideLoading(element, content) {
        element.innerHTML = content;
    }

    // Show toast notification
    static showToast(message, type = 'success') {
        const toast = document.createElement('div');
        const bgColor = type === 'success' ? 'bg-green-500' : 
                       type === 'error' ? 'bg-red-500' : 
                       type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';
        
        toast.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform transition-transform duration-300 translate-x-full`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 100);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }

    // Format currency
    static formatCurrency(amount, currency = 'USD') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    }

    // Format date
    static formatDate(date, format = 'short') {
        const options = format === 'long' ? 
            { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' } :
            { year: 'numeric', month: 'short', day: 'numeric' };
        
        return new Intl.DateTimeFormat('en-US', options).format(new Date(date));
    }

    // Validate form
    static validateForm(formData, rules) {
        const errors = {};
        
        Object.keys(rules).forEach(field => {
            const value = formData.get(field);
            const fieldRules = rules[field];
            
            if (fieldRules.required && (!value || value.trim() === '')) {
                errors[field] = `${field} is required`;
                return;
            }
            
            if (fieldRules.email && value && !this.isValidEmail(value)) {
                errors[field] = 'Please enter a valid email address';
                return;
            }
            
            if (fieldRules.minLength && value && value.length < fieldRules.minLength) {
                errors[field] = `${field} must be at least ${fieldRules.minLength} characters`;
                return;
            }
            
            if (fieldRules.numeric && value && isNaN(value)) {
                errors[field] = `${field} must be a number`;
                return;
            }
        });
        
        return errors;
    }

    // Email validation
    static isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Show/hide form errors
    static showFormErrors(form, errors) {
        // Clear previous errors
        form.querySelectorAll('.error-message').forEach(error => error.remove());
        form.querySelectorAll('.border-red-500').forEach(input => {
            input.classList.remove('border-red-500');
        });
        
        // Show new errors
        Object.keys(errors).forEach(field => {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('border-red-500');
                const errorElement = document.createElement('p');
                errorElement.className = 'error-message text-red-500 text-sm mt-1';
                errorElement.textContent = errors[field];
                input.parentNode.appendChild(errorElement);
            }
        });
    }

    // Create pagination
    static createPagination(currentPage, totalPages, onPageChange) {
        if (totalPages <= 1) return '';
        
        let pagination = '<div class="flex items-center justify-center space-x-2 mt-6">';
        
        // Previous button
        if (currentPage > 1) {
            pagination += `<button onclick="changePage(${currentPage - 1})" class="px-3 py-2 bg-gray-200 hover:bg-gray-300 rounded">Previous</button>`;
        }
        
        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === currentPage ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300';
            pagination += `<button onclick="changePage(${i})" class="px-3 py-2 ${activeClass} rounded">${i}</button>`;
        }
        
        // Next button
        if (currentPage < totalPages) {
            pagination += `<button onclick="changePage(${currentPage + 1})" class="px-3 py-2 bg-gray-200 hover:bg-gray-300 rounded">Next</button>`;
        }
        
        pagination += '</div>';
        
        // Store the callback for global access
        window.changePage = onPageChange;
        
        return pagination;
    }

    // Create modal
    static createModal(title, content, actions = []) {
        const modalId = 'modal-' + Date.now();
        const modal = document.createElement('div');
        modal.id = modalId;
        modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50';
        
        let actionsHtml = '';
        actions.forEach(action => {
            actionsHtml += `<button onclick="${action.onclick}" class="px-4 py-2 ${action.class || 'bg-blue-600 text-white'} rounded hover:opacity-80 mr-2">${action.text}</button>`;
        });
        
        modal.innerHTML = `
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 lg:w-1/3 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">${title}</h3>
                        <button onclick="closeModal('${modalId}')" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="text-sm text-gray-500 mb-4">${content}</div>
                    <div class="flex justify-end">
                        ${actionsHtml}
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Global close function
        window.closeModal = (id) => {
            const modalElement = document.getElementById(id);
            if (modalElement) {
                document.body.removeChild(modalElement);
            }
        };
        
        return modalId;
    }

    // Debounce function for search
    static debounce(func, wait) {
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

    /**
     * Throttle function - limits function execution frequency
     */
    static throttle(func, limit = 300) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    /**
     * Get relative time (e.g., "2 hours ago")
     */
    static getRelativeTime(date) {
        const now = new Date();
        const dateObj = new Date(date);
        const diffInSeconds = Math.floor((now - dateObj) / 1000);
        
        const intervals = {
            year: 31536000,
            month: 2592000,
            week: 604800,
            day: 86400,
            hour: 3600,
            minute: 60,
            second: 1
        };
        
        for (const [unit, seconds] of Object.entries(intervals)) {
            const interval = Math.floor(diffInSeconds / seconds);
            
            if (interval >= 1) {
                return interval === 1 ? `1 ${unit} ago` : `${interval} ${unit}s ago`;
            }
        }
        
        return 'just now';
    }

    /**
     * Copy text to clipboard
     */
    static async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            this.showToast('Copied to clipboard!', 'success');
            return true;
        } catch (err) {
            this.showToast('Failed to copy', 'error');
            return false;
        }
    }

    /**
     * Confirm dialog with promise
     */
    static confirmDialog(title, message) {
        return new Promise((resolve) => {
            const modalId = this.createModal(
                title,
                `<p class="text-gray-700">${message}</p>`,
                [
                    {
                        text: 'Cancel',
                        class: 'bg-gray-300 text-gray-700',
                        onclick: `window.closeModal('${Date.now()}'); window.confirmResult(false);`
                    },
                    {
                        text: 'Confirm',
                        class: 'bg-blue-600 text-white',
                        onclick: `window.closeModal('${Date.now()}'); window.confirmResult(true);`
                    }
                ]
            );
            
            window.confirmResult = (result) => {
                resolve(result);
                delete window.confirmResult;
            };
        });
    }

    /**
     * Scroll to element smoothly
     */
    static scrollToElement(selector, offset = 0) {
        const element = document.querySelector(selector);
        if (element) {
            const top = element.getBoundingClientRect().top + window.pageYOffset - offset;
            window.scrollTo({ top, behavior: 'smooth' });
        }
    }

    /**
     * Local storage helper with JSON support
     */
    static localStorage = {
        set(key, value) {
            try {
                localStorage.setItem(key, JSON.stringify(value));
            } catch (e) {
                console.error('Failed to save to localStorage:', e);
            }
        },
        get(key, defaultValue = null) {
            try {
                const item = localStorage.getItem(key);
                return item ? JSON.parse(item) : defaultValue;
            } catch (e) {
                console.error('Failed to read from localStorage:', e);
                return defaultValue;
            }
        },
        remove(key) {
            localStorage.removeItem(key);
        },
        clear() {
            localStorage.clear();
        }
    };

    /**
     * Format file size
     */
    static formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
    }

    /**
     * Generate random ID
     */
    static generateId(prefix = 'id') {
        return `${prefix}-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    }

    /**
     * Truncate text
     */
    static truncate(text, length = 100, suffix = '...') {
        if (text.length <= length) return text;
        return text.substring(0, length).trim() + suffix;
    }

    /**
     * Escape HTML to prevent XSS
     */
    static escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Check if element is in viewport
     */
    static isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    /**
     * Format number with thousands separator
     */
    static formatNumber(number) {
        return new Intl.NumberFormat('en-US').format(number);
    }

    /**
     * Parse URL query parameters
     */
    static getQueryParams() {
        const params = {};
        const searchParams = new URLSearchParams(window.location.search);
        for (const [key, value] of searchParams) {
            params[key] = value;
        }
        return params;
    }

    /**
     * Update URL query parameter without reload
     */
    static updateQueryParam(key, value) {
        const url = new URL(window.location);
        if (value) {
            url.searchParams.set(key, value);
        } else {
            url.searchParams.delete(key);
        }
        window.history.pushState({}, '', url);
    }
}