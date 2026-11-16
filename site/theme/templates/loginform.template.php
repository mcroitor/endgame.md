<div class="auth-menu" id="auth-menu">
    <button type="button" class="auth-toggle" aria-expanded="false" aria-controls="auth-dropdown">
        Log In
    </button>
    <div class="auth-dropdown hidden" id="auth-dropdown">
        <form method="POST" action="<!-- www -->/api/?q=user/login" id="login-form">
            <!-- csrf-input -->
            <label for="login" class="sr-only">Login</label>
            <input type="text" id="login" name="login" class="u-full-width" autocomplete="username" placeholder="Login" required />
            <label for="password" class="sr-only">Password</label>
            <input type="password" id="password" name="password" class="u-full-width" autocomplete="current-password" placeholder="Password" required />
            <input type="submit" class="button-primary u-full-width" value="Log In" />
        </form>
    </div>
</div>