<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Warehouse E-Commerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8" x-data="loginPage()">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="text-center text-5xl mb-4">🏪</div>
                <h2 class="text-center text-3xl font-extrabold text-gray-900">
                    Sign in to your account
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Or
                    <a href="/register" class="font-medium text-blue-600 hover:text-blue-500">
                        create a new account
                    </a>
                </p>
            </div>
            
            <form class="mt-8 space-y-6" @submit.prevent="login">
                <div x-show="error" class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded">
                    <span x-text="error"></span>
                </div>
                
                <div class="rounded-md shadow-sm space-y-4">
                    <div>
                        <label for="email" class="sr-only">Email address</label>
                        <input id="email" name="email" type="email" required x-model="formData.email"
                               class="appearance-none rounded relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="Email address">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" required x-model="formData.password"
                               class="appearance-none rounded relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="Password">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                            Remember me
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="/forgot-password" class="font-medium text-blue-600 hover:text-blue-500">
                            Forgot your password?
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit" :disabled="loading"
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                        <span x-show="!loading">Sign in</span>
                        <span x-show="loading">Signing in...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        axios.defaults.baseURL = '/api';
        
        function loginPage() {
            return {
                formData: {
                    email: '',
                    password: ''
                },
                error: '',
                loading: false,
                
                async login() {
                    this.loading = true;
                    this.error = '';
                    
                    try {
                        const response = await axios.post('/login', this.formData);
                        localStorage.setItem('auth_token', response.data.token);
                        
                        // Redirect based on role
                        if (response.data.user.is_admin) {
                            window.location.href = '/admin/dashboard';
                        } else {
                            window.location.href = '/';
                        }
                    } catch (error) {
                        this.error = error.response?.data?.message || 'Login failed. Please try again.';
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
