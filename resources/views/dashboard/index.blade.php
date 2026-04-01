<!-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Finance Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-10 rounded-xl shadow-lg text-center max-w-md w-full">
        <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Login Successful!</h1>
        <p class="text-gray-600 mb-6">Welcome, {{ Auth::user()->name }}</p>
        <p class="text-xs text-gray-400 mb-6">Current Location: /finance/loans</p>
        
        <div class="border-t pt-6">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="w-full bg-red-50 text-red-600 font-semibold py-2 rounded-lg hover:bg-red-100 transition">
                    Sign Out
                </button>
            </form>
        </div>
    </div>
</body>
</html> -->