<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$cacheDir = __DIR__.'/cache';
if (! is_dir($cacheDir)) {
    @mkdir($cacheDir, 0777, true);
}
@unlink($cacheDir.'/test.txt');
if (PHP_OS_FAMILY === 'Windows' && ! is_writable($cacheDir)) {
    @chmod($cacheDir, 0777);
    @exec('attrib -r -s "'.__DIR__.'\cache" /D /S');
    clearstatcache(true, $cacheDir);
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
