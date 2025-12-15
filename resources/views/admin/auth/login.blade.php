@extends('admin.layout')

@section('content')
    <div class="max-w-md mx-auto mt-10 bg-white p-6 rounded shadow">
        <h1 class="text-xl font-semibold mb-4">Super Admin Login</h1>

        <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    class="w-full border rounded px-3 py-2 text-sm"
                >
                @error('email')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Password</label>
                <input
                    type="password"
                    name="password"
                    required
                    class="w-full border rounded px-3 py-2 text-sm"
                >
                @error('password')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between text-sm">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="remember" class="mr-1">
                    <span>Remember me</span>
                </label>
            </div>

            <button
                type="submit"
                class="w-full mt-2 bg-blue-600 text-white py-2 rounded text-sm font-semibold hover:bg-blue-700"
            >
                Login
            </button>
        </form>
    </div>
@endsection
