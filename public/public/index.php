<?php

/** @noinspection PhpIncludeInspection */

declare(strict_types=1);

ob_start();

$start_time = microtime(true);

use KhsCI\Support\Response;
use KhsCI\Support\Route;

require_once __DIR__.'/../../vendor/autoload.php';

function open_error(): void
{
    ini_set('display_errors', 'on');
    ini_set('error_reporting', (string) constant('E_ALL'));
}

// read .env.* file.

try {
    $env = new Dotenv\Dotenv(__DIR__.'/../', '.env'.'.'.getenv('APP_ENV'));
    $env->load();
} catch (Exception $e) {
    Response::json(['message' => $e->getMessage()], $start_time);
    exit;
}

ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.'.getenv('CI_SESSION_DOMAIN'));
ini_set('session.gc_maxlifetime', '690000'); // s
ini_set('session.cookie_lifetime', '690000'); // s
ini_set('session.cookie_secure', 'On');

// session_set_cookie_params(1800 , '/', '.'..getenv('CI_SESSION_DOMAIN', true));

date_default_timezone_set(getenv('CI_TZ'));

// Open Debug?

$debug = getenv('CI_DEBUG') ?? false;

'true' === $debug && open_error();

// SPL Autoload

spl_autoload_register(function ($class): void {
    $class = lcfirst($class);
    $file = __DIR__.'/../'.str_replace('\\', \DIRECTORY_SEPARATOR, $class);
    $file = $file.'.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

if ('/index.php' === $_SERVER['REQUEST_URI']) {
    Response::redirect('dashboard');
    exit;
}

// Route.

try {
    require_once __DIR__.'/../route/web.php';
} catch (Exception | Error $e) {
    if ('Finish' === $e->getMessage()) {
        $output = Route::$output;

        switch (gettype($output)) {
            case 'array':
                Response::json($output, $start_time);

                break;
            case 'integer':
                echo $output;

                break;
            case 'string':
                echo $output;

                break;
        }
        exit;
    }

    Response::json([
        'code' => $e->getCode(),
        'message' => $e->getMessage() ?? 500,
        'documentation_url' => 'https://github.com/khs1994-php/khsci/tree/master/docs/api',
    ], $start_time);

    exit;
}

// 路由控制器填写错误

if ('true' === $debug) {
    Response::json([
        'code' => 404,
        'obj' => Route::$obj ?? null,
        'method' => Route::$method ?? null,
        'message' => 'Route Not Found',
        'api_url' => getenv('CI_HOST').'/api',
    ], $start_time);
} else {
    Response::json([
        'code' => 404,
        'message' => 'Not Found',
        'api_url' => getenv('CI_HOST').'/api',
    ], $start_time);
}
