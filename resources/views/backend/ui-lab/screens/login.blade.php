<p class="lab-note">Dedicated auth layout. Brand on the left, form on the right, one obvious Login. No hamburger, no debug bar.</p>

<div class="login">
    <div class="login-brand">
        <p class="lab-kicker">Resisquare</p>
        <h2>Homes, rent and repairs in one workspace.</h2>
        <p style="margin:0;color:rgba(255,255,255,0.8);">Landlords see the portfolio. Tenants only see their home.</p>
    </div>
    <form class="login-form" onsubmit="return false;">
        <h1>Sign in</h1>
        <p class="lab-muted" style="margin:0 0 0.5rem;">Use the email for your Resisquare account.</p>
        <label class="login-field">
            <span>Email</span>
            <input type="email" value="you@example.com" readonly>
        </label>
        <label class="login-field">
            <span>Password</span>
            <input type="password" value="password" readonly>
        </label>
        <span class="lab-btn lab-btn-primary" style="width:100%;margin-top:0.4rem;">Login</span>
        <p class="lab-muted" style="margin:0.3rem 0 0;"><button type="button" data-lab-open="lab-forgot" style="border:0;background:none;color:inherit;font:inherit;cursor:pointer;text-decoration:underline;">Forgot password?</button></p>
    </form>
</div>

<x-ui-lab.overlay id="forgot" title="Reset password">
    <p class="lab-muted" style="margin:0 0 0.85rem;">We’ll email a reset link. No new page.</p>
    <label class="lab-field"><span>Email</span><input type="email" value="you@example.com"></label>
    <span class="lab-btn lab-btn-primary">Send reset link</span>
</x-ui-lab.overlay>
