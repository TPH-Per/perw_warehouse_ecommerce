// API utility functions for handling HTTP requests
class ApiClient {
    constructor() {
        this.baseURL = '/api';
        this.token = localStorage.getItem('auth_token');
        this.setupInterceptors();
    }

    setupInterceptors() {
        // Add request interceptor for auth token
        axios.interceptors.request.use(config => {
            if (this.token) {
                config.headers.Authorization = `Bearer ${this.token}`;
            }
            return config;
        });

        // Add response interceptor for error handling
        axios.interceptors.response.use(
            response => response,
            error => {
                if (error.response?.status === 401) {
                    this.handleUnauthorized();
                }
                return Promise.reject(error);
            }
        );
    }

    setToken(token) {
        this.token = token;
        localStorage.setItem('auth_token', token);
    }

    removeToken() {
        this.token = null;
        localStorage.removeItem('auth_token');
    }

    handleUnauthorized() {
        this.removeToken();
        window.location.href = '/login';
    }

    async get(endpoint, params = {}) {
        try {
            const response = await axios.get(`${this.baseURL}${endpoint}`, { params });
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    async post(endpoint, data = {}) {
        try {
            const response = await axios.post(`${this.baseURL}${endpoint}`, data);
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    async put(endpoint, data = {}) {
        try {
            const response = await axios.put(`${this.baseURL}${endpoint}`, data);
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    async delete(endpoint) {
        try {
            const response = await axios.delete(`${this.baseURL}${endpoint}`);
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    handleError(error) {
        if (error.response) {
            return {
                status: error.response.status,
                message: error.response.data.message || 'An error occurred',
                errors: error.response.data.errors || {}
            };
        }
        return {
            status: 500,
            message: 'Network error occurred',
            errors: {}
        };
    }

    /**
     * Upload file with progress tracking
     */
    async upload(endpoint, file, onProgress = null) {
        try {
            const formData = new FormData();
            formData.append('file', file);

            const config = {
                headers: {
                    'Content-Type': 'multipart/form-data'
                },
                onUploadProgress: (progressEvent) => {
                    if (onProgress) {
                        const percentCompleted = Math.round(
                            (progressEvent.loaded * 100) / progressEvent.total
                        );
                        onProgress(percentCompleted);
                    }
                }
            };

            const response = await axios.post(`${this.baseURL}${endpoint}`, formData, config);
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    /**
     * Batch requests
     */
    async batch(requests) {
        try {
            const promises = requests.map(req => {
                const method = req.method.toLowerCase();
                return this[method](req.endpoint, req.data || req.params);
            });
            return await Promise.all(promises);
        } catch (error) {
            throw this.handleError(error);
        }
    }

    /**
     * Cancel token for request cancellation
     */
    getCancelToken() {
        return axios.CancelToken.source();
    }

    /**
     * Download file
     */
    async download(endpoint, filename) {
        try {
            const response = await axios.get(`${this.baseURL}${endpoint}`, {
                responseType: 'blob'
            });

            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);

            return { success: true, message: 'File downloaded successfully' };
        } catch (error) {
            throw this.handleError(error);
        }
    }

    /**
     * Check API health
     */
    async healthCheck() {
        try {
            const response = await axios.get(`${this.baseURL}/health`);
            return response.data;
        } catch (error) {
            return { healthy: false, error: error.message };
        }
    }

    /**
     * Retry failed requests
     */
    async retry(fn, retries = 3, delay = 1000) {
        try {
            return await fn();
        } catch (error) {
            if (retries === 0) {
                throw error;
            }
            await new Promise(resolve => setTimeout(resolve, delay));
            return this.retry(fn, retries - 1, delay * 2);
        }
    }
}

export default new ApiClient();