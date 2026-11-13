<?php

namespace App\Http;

use App\Http\Middleware\HandleExceptions;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\TrimStrings;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Foundation\Http\Kernel as KernelFoundation;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TransformsRequest;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Routing\Middleware\Bind;
use Illuminate\Routing\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SignRequest;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class Kernel extends KernelFoundation
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        CheckForMaintenanceMode::class,
        ShareErrorsFromSession::class,
        HandleExceptions::class,
        TransformsRequest::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or individual routes.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => Authenticate::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'cache.headers' => SetCacheHeaders::class,
        'csrf' => ValidateCsrfToken::class,
        'current.location' => Bind::class,
        'signed' => SignRequest::class,
        'throttle' => ThrottleRequests::class,
        'role' => RoleMiddleware::class,
    ];
}
