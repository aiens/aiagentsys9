import axios from 'axios';
import { router } from '@inertiajs/vue3';

// Configure Axios
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// CSRF Token
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// API Base URL
window.axios.defaults.baseURL = '/api';

// Request interceptor
window.axios.interceptors.request.use(
    (config) => {
        // Add auth token if available
        const token = localStorage.getItem('auth_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        
        // Add loading state
        if (window.$loading) {
            window.$loading.start();
        }
        
        return config;
    },
    (error) => {
        if (window.$loading) {
            window.$loading.finish();
        }
        return Promise.reject(error);
    }
);

// Response interceptor
window.axios.interceptors.response.use(
    (response) => {
        if (window.$loading) {
            window.$loading.finish();
        }
        return response;
    },
    (error) => {
        if (window.$loading) {
            window.$loading.finish();
        }
        
        // Handle authentication errors
        if (error.response?.status === 401) {
            localStorage.removeItem('auth_token');
            router.visit('/login');
            return Promise.reject(error);
        }
        
        // Handle validation errors
        if (error.response?.status === 422) {
            if (window.$toast) {
                window.$toast.error('Please check your input and try again.');
            }
            return Promise.reject(error);
        }
        
        // Handle server errors
        if (error.response?.status >= 500) {
            if (window.$toast) {
                window.$toast.error('Server error. Please try again later.');
            }
            return Promise.reject(error);
        }
        
        // Handle network errors
        if (!error.response) {
            if (window.$toast) {
                window.$toast.error('Network error. Please check your connection.');
            }
            return Promise.reject(error);
        }
        
        return Promise.reject(error);
    }
);

// Global utilities
window._ = require('lodash');

// Format currency
window.formatCurrency = (amount, currency = 'USD') => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 6,
    }).format(amount);
};

// Format number
window.formatNumber = (number, decimals = 0) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(number);
};

// Format date
window.formatDate = (date, options = {}) => {
    const defaultOptions = {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    };
    
    return new Intl.DateTimeFormat('en-US', { ...defaultOptions, ...options }).format(new Date(date));
};

// Format relative time
window.formatRelativeTime = (date) => {
    const now = new Date();
    const target = new Date(date);
    const diffInSeconds = Math.floor((now - target) / 1000);
    
    if (diffInSeconds < 60) {
        return 'just now';
    }
    
    const diffInMinutes = Math.floor(diffInSeconds / 60);
    if (diffInMinutes < 60) {
        return `${diffInMinutes} minute${diffInMinutes > 1 ? 's' : ''} ago`;
    }
    
    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) {
        return `${diffInHours} hour${diffInHours > 1 ? 's' : ''} ago`;
    }
    
    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays < 7) {
        return `${diffInDays} day${diffInDays > 1 ? 's' : ''} ago`;
    }
    
    return window.formatDate(date, { month: 'short', day: 'numeric' });
};

// Format file size
window.formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// Truncate text
window.truncate = (text, length = 100, suffix = '...') => {
    if (text.length <= length) return text;
    return text.substring(0, length) + suffix;
};

// Debounce function
window.debounce = (func, wait, immediate) => {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            timeout = null;
            if (!immediate) func(...args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func(...args);
    };
};

// Throttle function
window.throttle = (func, limit) => {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
};

// Copy to clipboard
window.copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        if (window.$toast) {
            window.$toast.success('Copied to clipboard');
        }
        return true;
    } catch (err) {
        console.error('Failed to copy text: ', err);
        if (window.$toast) {
            window.$toast.error('Failed to copy to clipboard');
        }
        return false;
    }
};

// Download file
window.downloadFile = (url, filename) => {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

// Generate UUID
window.generateUUID = () => {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0;
        const v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
};

// Local storage helpers
window.storage = {
    get: (key, defaultValue = null) => {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (error) {
            console.error('Error reading from localStorage:', error);
            return defaultValue;
        }
    },
    
    set: (key, value) => {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (error) {
            console.error('Error writing to localStorage:', error);
            return false;
        }
    },
    
    remove: (key) => {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (error) {
            console.error('Error removing from localStorage:', error);
            return false;
        }
    },
    
    clear: () => {
        try {
            localStorage.clear();
            return true;
        } catch (error) {
            console.error('Error clearing localStorage:', error);
            return false;
        }
    }
};
