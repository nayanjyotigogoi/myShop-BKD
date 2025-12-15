@extends('admin.layout')

@section('content')
    <h1 class="text-2xl font-semibold mb-4">Create User</h1>

    <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4 max-w-md">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">Name</label>
            <input
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                class="w-full border rounded px-3 py-2 text-sm"
            >
            @error('name')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

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

        <div>
            <label class="block text-sm font-medium mb-1">Confirm Password</label>
            <input
                type="password"
                name="password_confirmation"
                required
                class="w-full border rounded px-3 py-2 text-sm"
            >
        </div>

        <div class="flex items-center">
            <label class="inline-flex items-center text-sm">
                <input type="checkbox" name="is_super_admin" class="mr-2" value="1">
                <span>Super Admin</span>
            </label>
        </div>

        <button
            type="submit"
            class="mt-2 bg-blue-600 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-blue-700"
        >
            Save
        </button>
    </form>
@endsection
