<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'meta' => (object) [],
                'errors' => [[
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Переданные данные некорректны.',
                    'fields' => $exception->errors(),
                ]],
            ], 422);
        });
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'meta' => (object) [],
                'errors' => [['code' => 'UNAUTHENTICATED', 'message' => 'Требуется вход.']],
            ], 401);
        });
        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'meta' => (object) [],
                'errors' => [['code' => 'NOT_FOUND', 'message' => 'Ресурс не найден.']],
            ], 404);
        });
        $exceptions->render(function (TooManyRequestsHttpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'meta' => (object) [],
                'errors' => [['code' => 'RATE_LIMITED', 'message' => 'Слишком много запросов.']],
            ], 429);
        });
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'meta' => (object) [],
                'errors' => [['code' => 'HTTP_ERROR', 'message' => 'Запрос не может быть выполнен.']],
            ], $exception->getStatusCode());
        });
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'meta' => (object) [],
                'errors' => [['code' => 'SERVER_ERROR', 'message' => 'Внутренняя ошибка сервера.']],
            ], 500);
        });
    })->create();
