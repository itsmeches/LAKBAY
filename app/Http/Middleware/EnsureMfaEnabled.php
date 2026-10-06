<?php

namespace App\Http\Middleware;

class EnsureMfaEnabled
{
    public function handle($request, \Closure $next)
    {
        abort_unless($request->user()?->is_active && $request->user()?->hasVerifiedEmail() && $request->user()?->getAppAuthenticationSecret(), 403, 'Sign in and set up MFA in your panel before accessing documents or exports.');

        return $next($request);
    }
}
