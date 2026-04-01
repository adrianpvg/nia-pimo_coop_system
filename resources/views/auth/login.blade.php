<!-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NIA | Finance COOP System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .bg-mint-gradient { background: linear-gradient(135deg, #34d399 0%, #0d9488 100%); }
        [x-cloak] { display: none !important; }
        
        /* Video Background Styling */
        .video-bg-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1; /* Keep it behind the main content */
        }

        .video-bg {
            width: 100vw;
            height: 100vh;
            object-fit: cover; /* Ensures video covers the whole screen */
            transform: scale(1.2); /* Zooms the video in slightly */
        }
        
        /* Optional: Add a dark overlay to make the white form pop more */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4); /* 40% black overlay */
            z-index: 0;
        }
    </style>
</head>
<body class="h-screen flex items-center justify-center p-6 relative">

    <div class="video-bg-container">
        <video autoplay loop muted playsinline class="video-bg">
            <source src="{{ asset('videos/bgvideo.mp4') }}" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>

    <div class="overlay"></div>

    <div x-data="{ isLogin: {{ $errors->has('name') || old('name') ? 'false' : 'true' }} }" 
         class="bg-white w-full max-w-[1000px] min-h-[600px] rounded-3xl shadow-2xl overflow-hidden flex relative z-10">
        
        <div class="w-full md:w-1/2 p-12 flex flex-col justify-center relative bg-mint-gradient text-white">
            
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 text-sm rounded shadow-sm">
                    <p class="font-bold">Success</p>
                    <p>{{ session('success') }}</p>
                </div>
            @endif
            
            <div x-show="isLogin" 
                 x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 -translate-x-12"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 class="w-full">
                <h2 class="text-3xl font-bold text-black-600 mb-2">Finance COOP System</h2>
                <br>
                <h2 class="text-4xl font-bold text-white-800 mb-2">Sign In</h2>
                <p class="text-white-400 mb-8 text-sm">Secure Access for Finance Unit</p>

                <form action="/login" method="POST" class="space-y-4">
                    @csrf
                    
                    <div class="group">
                        <input type="text" name="email" placeholder="Email Address" value="{{ old('email') }}"
                            class="w-full pb-3 border-b border-white-300 outline-none focus:border-teal-500 transition-colors bg-transparent text-white-700 placeholder-white-400">
                        @error('email')
                            <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="group relative">
                        <input type="password" name="password" placeholder="Password"
                            class="w-full pb-3 border-b border-white-300 outline-none focus:border-teal-500 transition-colors bg-transparent text-white-700 placeholder-white-400">
                        @error('password')
                            <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full bg-mint-gradient text-white font-semibold py-4 rounded-xl shadow-lg hover:shadow-xl hover:opacity-95 transition-all transform hover:-translate-y-0.5">
                            Sign In <span class="ml-2">&rarr;</span>
                        </button>
                    </div>
                </form>

                <p class="mt-8 text-center text-white-500 text-sm">
                    Don't have an account? 
                    <button @click="isLogin = false" class="text-teal-600 font-semibold hover:underline">Register</button>
                </p>
            </div>

            <div x-show="!isLogin" x-cloak
                 x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 translate-x-12"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 class="w-full">
                 
                <h2 class="text-4xl font-bold text-white-800 mb-2">Sign Up</h2>
                <p class="text-gray-400 mb-8 text-sm">Create your new account</p>

                <form action="/register" method="POST" class="space-y-4">
                    @csrf
                    
                    <div class="group">
                        <input type="text" name="name" placeholder="Full Name" value="{{ old('name') }}"
                            class="w-full pb-3 border-b border-white-300 outline-none focus:border-teal-500 transition-colors bg-transparent text-white-700 placeholder-white-400">
                        @error('name')
                            <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="group">
                        <input type="text" name="email" placeholder="Email Address" value="{{ old('email') }}"
                            class="w-full pb-3 border-b border-white-300 outline-none focus:border-teal-500 transition-colors bg-transparent text-white-700 placeholder-white-400">
                        @error('email')
                            <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="group">
                        <input type="password" name="password" placeholder="Password (Min. 6 chars)"
                            class="w-full pb-3 border-b border-gray-300 outline-none focus:border-teal-500 transition-colors bg-transparent text-gray-700 placeholder-gray-400">
                        @error('password')
                            <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full bg-mint-gradient text-white font-semibold py-4 rounded-xl shadow-lg hover:shadow-xl hover:opacity-95 transition-all transform hover:-translate-y-0.5">
                            Create Account
                        </button>
                    </div>
                </form>

                <p class="mt-8 text-center text-gray-500 text-sm">
                    Already a member? 
                    <button @click="isLogin = true" class="text-teal-600 font-semibold hover:underline">Sign in</button>
                </p>
            </div>
        </div>

        <div class="hidden md:flex w-1/2 bg-white-gradient p-12 relative flex-col justify-center items-center text-white overflow-hidden">
            <img src="{{ asset('images/logo_coop.png') }}" alt="NIA Logo" class="w-80 h-80 object-contain shadow-2xl rounded-full relative z-10">
        </div>
    </div>
</body>
</html> -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NIA | Finance COOP System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        [x-cloak] { display: none !important; }
        
        /* Video Background Styling */
        .video-bg-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }

        .video-bg {
            width: 100vw;
            height: 100vh;
            object-fit: cover;
            transform: scale(1.2); 
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.35); 
            z-index: 0;
        }

        /* Apple Glass Input Base */
        .glass-input {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        /* Apply focus styles when the input inside the group is focused */
        .input-group:focus-within .glass-input {
            background: rgba(255, 255, 255, 0.8);
            border-color: #007aff;
            box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.15);
        }
    </style>
</head>
<body class="h-screen flex items-center justify-center p-6 relative">

    <div class="video-bg-container">
        <video autoplay loop muted playsinline class="video-bg">
            <source src="{{ asset('videos/bgvideo.mp4') }}" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>

    <div class="overlay"></div>

    <div x-data="{ 
            isLogin: {{ $errors->has('name') || old('name') ? 'false' : 'true' }},
            showPassword: false,
            showRegisterPassword: false
         }" 
         class="w-full max-w-[1000px] min-h-[600px] rounded-[2rem] shadow-[0_8px_32px_0_rgba(0,0,0,0.2)] overflow-hidden flex relative z-10 border border-white/30 bg-white/20 backdrop-blur-xl">
        
        <div class="w-full md:w-1/2 p-10 md:p-14 flex flex-col justify-center relative bg-white/40">
            
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-500/20 border border-green-500/50 backdrop-blur-md text-green-800 text-sm rounded-xl shadow-sm">
                    <p class="font-bold">Success</p>
                    <p>{{ session('success') }}</p>
                </div>
            @endif
            
            <div x-show="isLogin" 
                 x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 -translate-x-8"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 class="w-full">
                
                <h2 class="text-sm font-semibold tracking-widest text-gray-500 uppercase mb-2">COOP System</h2>
                <h2 class="text-4xl font-bold text-gray-900 mb-2 tracking-tight">Sign In</h2>
                <!-- <p class="text-gray-600 mb-8 text-sm font-medium">Finance Unit</p> -->

                <form action="/login" method="POST" class="space-y-5">
                    @csrf
                    
                    <div class="input-group">
                        <input type="text" name="email" placeholder="Email Address" value="{{ old('email') }}"
                            class="glass-input w-full px-5 py-4 rounded-2xl outline-none text-gray-800 placeholder-gray-500 shadow-sm block">
                        @error('email')
                            <p class="text-red-600 text-xs mt-1.5 font-medium ml-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="input-group relative">
                        <input x-bind:type="showPassword ? 'text' : 'password'" name="password" placeholder="Password"
                            class="glass-input w-full pl-5 pr-12 py-4 rounded-2xl outline-none text-gray-800 placeholder-gray-500 shadow-sm block">
                        
                        <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 px-4 flex items-center text-gray-500 hover:text-gray-700 focus:outline-none">
                            <svg x-show="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                        
                        @error('password')
                            <p class="text-red-600 text-xs mt-1.5 font-medium ml-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full bg-gradient-to-r from-[#007aff] to-[#34c759] text-white font-semibold py-4 rounded-2xl shadow-lg hover:shadow-xl hover:opacity-90 transition-all transform hover:-translate-y-0.5">
                            Sign In <span class="ml-2 font-normal">&rarr;</span>
                        </button>
                    </div>
                </form>

                <p class="mt-8 text-center text-gray-600 text-sm font-medium">
                    Don't have an account? Contact System Administrator
                </p>
            </div>

            <div x-show="!isLogin" x-cloak
                 x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 translate-x-8"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 class="w-full">
                 
                <h2 class="text-4xl font-bold text-gray-900 mb-2 tracking-tight">Sign Up</h2>
                <p class="text-gray-600 mb-8 text-sm font-medium">Create your new account</p>

                <form action="/register" method="POST" class="space-y-4">
                    @csrf
                    
                    <div class="input-group">
                        <input type="text" name="name" placeholder="Full Name" value="{{ old('name') }}"
                            class="glass-input w-full px-5 py-3.5 rounded-2xl outline-none text-gray-800 placeholder-gray-500 shadow-sm block">
                        @error('name')
                            <p class="text-red-600 text-xs mt-1.5 font-medium ml-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="input-group">
                        <input type="text" name="email" placeholder="Email Address" value="{{ old('email') }}"
                            class="glass-input w-full px-5 py-3.5 rounded-2xl outline-none text-gray-800 placeholder-gray-500 shadow-sm block">
                        @error('email')
                            <p class="text-red-600 text-xs mt-1.5 font-medium ml-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="input-group relative">
                        <input x-bind:type="showRegisterPassword ? 'text' : 'password'" name="password" placeholder="Password (Min. 6 chars)"
                            class="glass-input w-full pl-5 pr-12 py-3.5 rounded-2xl outline-none text-gray-800 placeholder-gray-500 shadow-sm block">
                        
                        <button type="button" @click="showRegisterPassword = !showRegisterPassword" class="absolute inset-y-0 right-0 px-4 flex items-center text-gray-500 hover:text-gray-700 focus:outline-none">
                            <svg x-show="!showRegisterPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="showRegisterPassword" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>

                        @error('password')
                            <p class="text-red-600 text-xs mt-1.5 font-medium ml-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full bg-gradient-to-r from-[#007aff] to-[#34c759] text-white font-semibold py-4 rounded-2xl shadow-lg hover:shadow-xl hover:opacity-90 transition-all transform hover:-translate-y-0.5">
                            Create Account
                        </button>
                    </div>
                </form>

                <p class="mt-8 text-center text-gray-600 text-sm font-medium">
                    Already a member? 
                    <button @click="isLogin = true" class="text-[#007aff] font-semibold hover:underline transition-all">Sign in</button>
                </p>
            </div>
        </div>

        <div class="hidden md:flex w-1/2 relative flex-col justify-center items-center text-white overflow-hidden border-l border-white/20">
            <div class="absolute w-64 h-64 bg-white/30 rounded-full blur-3xl"></div>
            <img src="{{ asset('images/logo_coop.png') }}" alt="NIA Logo" class="w-80 h-80 object-contain drop-shadow-2xl rounded-full relative z-10 hover:scale-105 transition-transform duration-500">
        </div>
    </div>
</body>
</html>