// Authentication module
import api from '../utils/api.js';
import { UIHelpers } from '../utils/helpers.js';

export class AuthModule {
    constructor() {
        this.currentUser = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.checkAuthStatus();
    }

    bindEvents() {
        // Login form
        document.addEventListener('submit', (e) => {
            if (e.target.id === 'login-form') {
                e.preventDefault();
                this.handleLogin(e.target);
            } else if (e.target.id === 'register-form') {
                e.preventDefault();
                this.handleRegister(e.target);
            }
        });

        // Logout button
        document.addEventListener('click', (e) => {
            if (e.target.id === 'logout-btn') {
                this.logout();
            }
        });
    }

    async checkAuthStatus() {
        const token = localStorage.getItem('auth_token');
        if (!token) {
            this.handleUnauthenticated();
            return;
        }

        try {
            const response = await api.get('/user');
            this.currentUser = response.user;
            this.handleAuthenticated();
        } catch (error) {
            this.handleUnauthenticated();
        }
    }

    async handleLogin(form) {
        const formData = new FormData(form);
        
        try {
            const data = {
                email: formData.get('email'),
                password: formData.get('password')
            };

            const response = await api.post('/auth/login', data);
            
            // Store token and user data
            api.setToken(response.token);
            this.currentUser = response.user;
            
            UIHelpers.showToast('Login successful!', 'success');
            this.handleAuthenticated();
            
            // Redirect to dashboard or intended page
            const redirectUrl = new URLSearchParams(window.location.search).get('redirect') || '/dashboard';
            window.location.href = redirectUrl;

        } catch (error) {
            UIHelpers.showToast(error.message, 'error');
            UIHelpers.showFormErrors(form, error.errors || {});
        }
    }

    async handleRegister(form) {
        const formData = new FormData(form);
        
        try {
            const data = {
                full_name: formData.get('full_name'),
                email: formData.get('email'),
                password: formData.get('password'),
                password_confirmation: formData.get('password_confirmation'),
                phone_number: formData.get('phone_number')
            };

            const response = await api.post('/auth/register', data);
            
            // Store token and user data
            api.setToken(response.token);
            this.currentUser = response.user;
            
            UIHelpers.showToast('Registration successful!', 'success');
            this.handleAuthenticated();
            
            // Redirect to dashboard
            window.location.href = '/dashboard';

        } catch (error) {
            UIHelpers.showToast(error.message, 'error');
            UIHelpers.showFormErrors(form, error.errors || {});
        }
    }

    async logout() {
        try {
            await api.post('/auth/logout');
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            api.removeToken();
            this.currentUser = null;
            this.handleUnauthenticated();
            window.location.href = '/login';
        }
    }

    handleAuthenticated() {
        // Update UI for authenticated user
        this.updateUserUI();
        this.showAuthenticatedContent();
    }

    handleUnauthenticated() {
        // Update UI for unauthenticated user
        this.showUnauthenticatedContent();
    }

    updateUserUI() {
        const userNameElement = document.getElementById('user-name');
        const userEmailElement = document.getElementById('user-email');
        
        if (userNameElement && this.currentUser) {
            userNameElement.textContent = this.currentUser.full_name;
        }
        
        if (userEmailElement && this.currentUser) {
            userEmailElement.textContent = this.currentUser.email;
        }
    }

    showAuthenticatedContent() {
        // Show authenticated sections
        document.querySelectorAll('.auth-required').forEach(element => {
            element.style.display = 'block';
        });
        
        // Hide unauthenticated sections
        document.querySelectorAll('.guest-only').forEach(element => {
            element.style.display = 'none';
        });
    }

    showUnauthenticatedContent() {
        // Hide authenticated sections
        document.querySelectorAll('.auth-required').forEach(element => {
            element.style.display = 'none';
        });
        
        // Show unauthenticated sections
        document.querySelectorAll('.guest-only').forEach(element => {
            element.style.display = 'block';
        });
    }

    renderLoginForm() {
        return `
            <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
                <div class="max-w-md w-full space-y-8">
                    <div>
                        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                            Sign in to your account
                        </h2>
                    </div>
                    <form id="login-form" class="mt-8 space-y-6">
                        <div class="rounded-md shadow-sm -space-y-px">
                            <div>
                                <label for="email" class="sr-only">Email address</label>
                                <input id="email" name="email" type="email" required 
                                       class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                                       placeholder="Email address">
                            </div>
                            <div>
                                <label for="password" class="sr-only">Password</label>
                                <input id="password" name="password" type="password" required 
                                       class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                                       placeholder="Password">
                            </div>
                        </div>

                        <div>
                            <button type="submit" 
                                    class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Sign in
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <a href="/register" class="text-blue-600 hover:text-blue-500">
                                Don't have an account? Sign up
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        `;
    }

    renderRegisterForm() {
        return `
            <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
                <div class="max-w-md w-full space-y-8">
                    <div>
                        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                            Create your account
                        </h2>
                    </div>
                    <form id="register-form" class="mt-8 space-y-6">
                        <div class="space-y-4">
                            <div>
                                <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                <input id="full_name" name="full_name" type="text" required 
                                       class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                       placeholder="Full Name">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input id="email" name="email" type="email" required 
                                       class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                       placeholder="Email address">
                            </div>
                            <div>
                                <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input id="phone_number" name="phone_number" type="tel" required 
                                       class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                       placeholder="Phone Number">
                            </div>
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                <input id="password" name="password" type="password" required 
                                       class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                       placeholder="Password">
                            </div>
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                <input id="password_confirmation" name="password_confirmation" type="password" required 
                                       class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                       placeholder="Confirm Password">
                            </div>
                        </div>

                        <div>
                            <button type="submit" 
                                    class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Sign up
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <a href="/login" class="text-blue-600 hover:text-blue-500">
                                Already have an account? Sign in
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        `;
    }

    getCurrentUser() {
        return this.currentUser;
    }

    isAuthenticated() {
        return this.currentUser !== null;
    }
}