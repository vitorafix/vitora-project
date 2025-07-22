<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log; // Ensure Log facade is imported

class AuthenticateOnceWithBasicAuth
{
    /**
     * Handle an incoming request.
     *
     * This middleware attempts to authenticate the user using a JWT token
     * if one is present in the request. It does not stop the request if
     * authentication fails, allowing guest access to routes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        Log::debug('AuthenticateOnceWithBasicAuth: Attempting to authenticate with JWT.');

        // NEW: Log the Authorization header to see if it's present
        if ($request->header('Authorization')) {
            Log::debug('AuthenticateOnceWithBasicAuth: Authorization header found: ' . $request->header('Authorization'));
        } else {
            Log::debug('AuthenticateOnceWithBasicAuth: No Authorization header found.');
        }

        try {
            // Check if the user is already authenticated via the 'api' guard.
            // This is important if previous middleware or guards have already authenticated the user.
            if (Auth::guard('api')->check()) {
                Log::debug('AuthenticateOnceWithBasicAuth: User already authenticated via API guard: ' . Auth::guard('api')->id());
            } else {
                // If the user is not yet authenticated, attempt to parse the token and authenticate.
                // This will set Auth::user() if a valid token is found.
                $user = JWTAuth::parseToken()->authenticate();
                if ($user) {
                    // User successfully authenticated via JWT.
                    // Auth::login($user, false); // Uncomment if you also need to log the user into the Laravel session
                    Log::debug('AuthenticateOnceWithBasicAuth: User authenticated via JWT: ' . $user->id);
                }
            }
        } catch (TokenExpiredException $e) {
            // Log a warning if the JWT token has expired.
            Log::warning('AuthenticateOnceWithBasicAuth: JWT Token expired. Message: ' . $e->getMessage());
        } catch (TokenInvalidException $e) {
            // Log a warning if the JWT token is invalid.
            Log::warning('AuthenticateOnceWithBasicAuth: JWT Token invalid. Message: ' . $e->getMessage());
        } catch (JWTException $e) {
            // Log debug information if no token is present or another JWT-related error occurs.
            // This is expected for guest users.
            Log::debug('AuthenticateOnceWithBasicAuth: No JWT Token or JWT error: ' . $e->getMessage());
        } catch (\Throwable $e) {
            // Catch any unexpected general exceptions for robust error logging.
            Log::error('AuthenticateOnceWithBasicAuth: Unexpected error during authentication attempt: ' . $e->getMessage(), ['exception' => $e]);
        }

        // Continue to the next middleware or controller.
        return $next($request);
    }
}
