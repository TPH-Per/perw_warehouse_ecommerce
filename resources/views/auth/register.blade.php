<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Warehouse E-Commerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8" x-data="registerPage()">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="text-center text-5xl mb-4">🏪</div>
                <h2 class="text-center text-3xl font-extrabold text-gray-900">
                    Create your account
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Already have an account?
                    <a href="/login" class="font-medium text-blue-600 hover:text-blue-500">
                        Sign in
                    </a>
                </p>
            </div>
            
            <form class="mt-8 space-y-6" @submit.prevent="register">
                <div x-show="error" class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded">
                    <span x-text="error"></span>
                </div>
                
                <div x-show="success" class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded">
                    <span x-text="success"></span>
                </div>
                
                <div class="rounded-md shadow-sm space-y-4">
                    <div>
                        <label for="full_name" class="sr-only">Full Name</label>
                        <input id="full_name" name="full_name" type="text" required x-model="formData.full_name"
                               class="appearance-none rounded relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="Full Name">
                    </div>
                    
                    <div>
                        <label for="email" class="sr-only">Email address</label>
                        <input id="email" name="email" type="email" required x-model="formData.email"
                               class="appearance-none rounded relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="Email address">
                    </div>
                    
                    <div>
                        <label for="phone_number" class="sr-only">Phone Number</label>
                        <input id="phone_number" name="phone_number" type="tel" x-model="formData.phone_number"
                               class="appearance-none rounded relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="Phone Number (optional)">
                    </div>
                    
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" required x-model="formData.password"
                               class="appearance-none rounded relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="Password (min 8 characters)">
                    </div>
                    
                    <div>
                        <label for="password_confirmation" class="sr-only">Confirm Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required x-model="formData.password_confirmation"
                               class="appearance-none rounded relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="Confirm Password">
                    </div>
                </div>

                <div>
                    <button type="submit" :disabled="loading"
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                        <span x-show="!loading">Create Account</span>
                        <span x-show="loading">Creating account...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        axios.defaults.baseURL = '/api';
        
        function registerPage() {
            return {
                formData: {
                    full_name: '',
                    email: '',
                    phone_number: '',
                    password: '',
                    password_confirmation: ''
                },
                error: '',
                success: '',
                loading: false,
                
                async register() {
                    this.loading = true;
                    this.error = '';
                    this.success = '';
                    
                    if (this.formData.password !== this.formData.password_confirmation) {
                        this.error = 'Passwords do not match';
                        this.loading = false;
                        return;
                    }
                    
                    try {
                        const response = await axios.post('/register', this.formData);
                        localStorage.setItem('auth_token', response.data.token);
                        
                        this.success = 'Account created successfully! Redirecting...';
                        
                        setTimeout(() => {
                            window.location.href = '/';
                        }, 1500);
                    } catch (error) {
                        if (error.response?.data?.errors) {
                            const errors = Object.values(error.response.data.errors).flat();
                            this.error = errors.join(', ');
                        } else {
                            this.error = error.response?.data?.message || 'Registration failed. Please try again.';
                        }
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
