<x-layout title="Log in">
    <form class="login" method="post" action="{{ route('login') }}">
        @csrf
        <h1>Log in</h1>
        <label>
            Email
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email') <span class="error">{{ $message }}</span> @enderror
        </label>
        <label>
            Password
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button class="btn">Log in</button>
    </form>
</x-layout>
