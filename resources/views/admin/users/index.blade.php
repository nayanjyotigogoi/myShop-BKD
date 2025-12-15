@extends('admin.layout')

@section('content')
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-semibold">Users</h1>
        <a
            href="{{ route('admin.users.create') }}"
            class="px-3 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
        >
            + New User
        </a>
    </div>

    {{-- One-time token flash (shown only immediately after creating a user) --}}
    @if(session('plain_token'))
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-semibold text-yellow-800">API token created for {{ session('plain_token_user_email') }}</p>
                    <p class="mt-2 text-sm text-yellow-700">
                        This token is shown only once. Copy it now and deliver it to the user.
                    </p>
                    <div class="mt-3">
                        <code id="one-time-token" class="block break-words p-2 bg-white border rounded">{{ session('plain_token') }}</code>
                    </div>
                </div>

                <div class="ml-4 flex items-start">
                    <button
                        type="button"
                        onclick="copyTokenFromId('one-time-token')"
                        class="px-3 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700"
                    >
                        Copy token
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded shadow overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="text-left px-4 py-2">Name</th>
                    <th class="text-left px-4 py-2">Email</th>
                    <th class="text-left px-4 py-2">Role</th>

                    {{-- NEW Remember Token Column --}}
                    <th class="text-left px-4 py-2">Remember Token</th>

                    <th class="text-left px-4 py-2">API Token</th>
                    <th class="text-right px-4 py-2">Actions</th>
                </tr>
            </thead>

            <tbody>
            @forelse ($users as $user)
                <tr class="border-b">
                    <td class="px-4 py-2">{{ $user->name }}</td>
                    <td class="px-4 py-2">{{ $user->email }}</td>

                    <td class="px-4 py-2">
                        @if ($user->is_super_admin)
                            <span class="inline-block px-2 py-1 text-xs bg-purple-100 text-purple-700 rounded">
                                Super Admin
                            </span>
                        @else
                            <span class="inline-block px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">
                                User
                            </span>
                        @endif
                    </td>

                    {{-- NEW Remember Token Column --}}
                    <td class="px-4 py-2">
                        @if ($user->remember_token)
                            <div class="flex items-center space-x-2">
                                <code id="remember-token-{{ $user->id }}" class="px-2 py-1 bg-gray-50 border rounded text-xs break-all">
                                    {{ $user->remember_token }}
                                </code>

                                <button
                                    onclick="copyTokenFromId('remember-token-{{ $user->id }}')"
                                    class="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700"
                                >
                                    Copy
                                </button>
                            </div>
                        @else
                            <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>

                    {{-- API token column (only after new user creation) --}}
                    <td class="px-4 py-2">
                        @if(session('plain_token') && session('plain_token_user_email') === $user->email)
                            <div class="flex items-center space-x-2">
                                <code id="row-token-{{ $user->id }}" class="px-2 py-1 bg-gray-50 border rounded text-xs break-all">
                                    {{ session('plain_token') }}
                                </code>

                                <button
                                    onclick="copyTokenFromId('row-token-{{ $user->id }}')"
                                    class="px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700"
                                >
                                    Copy
                                </button>
                            </div>
                        @else
                            <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>

                    <td class="text-right px-4 py-2">
                        <a
                            href="{{ route('admin.users.edit', $user) }}"
                            class="text-blue-600 text-xs mr-2"
                        >
                            Edit
                        </a>

                        @if (auth()->id() !== $user->id)
                            <form
                                action="{{ route('admin.users.destroy', $user) }}"
                                method="POST"
                                class="inline"
                                onsubmit="return confirm('Delete this user?');"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 text-xs">
                                    Delete
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>

            @empty
                <tr>
                    <td colspan="6" class="px-4 py-4 text-center text-gray-500">
                        No users found.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <script>
        function copyTokenFromId(id) {
            try {
                const el = document.getElementById(id);
                if (!el) return;
                const text = el.innerText.trim();

                navigator.clipboard.writeText(text).then(() => {
                    alert('Token copied to clipboard');
                }).catch(() => {
                    const range = document.createRange();
                    range.selectNodeContents(el);
                    const sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(range);
                    document.execCommand('copy');
                    sel.removeAllRanges();
                    alert('Token copied');
                });

            } catch (error) {
                console.error(error);
                alert("Couldn't copy token.");
            }
        }
    </script>
@endsection
