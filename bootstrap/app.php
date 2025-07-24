<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // 400 – Bad Request (umum, bisa dari validasi atau kesalahan klien)
        $exceptions->render(function (ValidationException $e, Request $request) {
            return response()->json([
                'message' => 'Data yang Anda masukkan tidak valid. Silakan periksa kembali.',
            ], 422);
        });

        // 401 – Unauthorized (belum login / token tidak valid)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'message' => 'Anda harus login untuk mengakses fitur ini.',
            ], 401);
        });

        // 403 – Forbidden (sudah login, tapi tidak punya hak akses)
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            return response()->json([
                'message' => 'Anda tidak memiliki izin untuk melakukan aksi ini.',
            ], 403);
        });

        // 404 – Not Found (halaman/endpoint tidak ditemukan)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            return response()->json([
                'message' => 'Halaman atau data yang Anda cari tidak ditemukan.',
            ], 404);
        });

        // 405 – Method Not Allowed (misal POST ke endpoint yang hanya GET)
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            return response()->json([
                'message' => 'Metode request tidak diizinkan. Periksa kembali cara Anda mengakses.',
            ], 405);
        });

        // 429 – Too Many Requests (brute force, spam, dll)
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 429) {
                return response()->json([
                    'message' => 'Terlalu banyak permintaan dalam waktu singkat. Silakan coba beberapa saat lagi.',
                ], 429);
            }
        });

        // 500+ – Internal Server Error (error tidak diketahui)
        $exceptions->render(function (Throwable $e, Request $request) {
            return response()->json([
                'message' => 'Terjadi kesalahan pada sistem. Silakan coba lagi nanti.',
            ], 500);
        });
    })->create();
