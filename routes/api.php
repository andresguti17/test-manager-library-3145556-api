<?php

use App\Library\Adapters\Inbound\Http\Controllers\AuthController;
use App\Library\Adapters\Inbound\Http\Controllers\BookController;
use App\Library\Adapters\Inbound\Http\Controllers\CategoryController;
use App\Library\Adapters\Inbound\Http\Controllers\LoanController;
use App\Library\Adapters\Inbound\Http\Controllers\MemberController;
use App\Library\Adapters\Inbound\Http\Controllers\ReportController;
use App\Library\Adapters\Inbound\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => response()->json(['status' => 'ok']));

    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('jwt')->group(function () {
        Route::post('/users', [UserController::class, 'store'])->middleware('role:admin');

        Route::get('/categories', [CategoryController::class, 'index']);

        Route::get('/books', [BookController::class, 'index']);
        Route::post('/books', [BookController::class, 'store'])->middleware('role:admin');
        Route::get('/books/{id}/cover', [BookController::class, 'showCover'])->whereUuid('id');
        Route::put('/books/{id}/cover', [BookController::class, 'updateCover'])->middleware('role:admin')->whereUuid('id');
        Route::delete('/books/{id}/cover', [BookController::class, 'destroyCover'])->middleware('role:admin')->whereUuid('id');
        Route::get('/books/{id}', [BookController::class, 'show'])->whereUuid('id');
        Route::put('/books/{id}', [BookController::class, 'update'])->middleware('role:admin')->whereUuid('id');
        Route::delete('/books/{id}', [BookController::class, 'destroy'])->middleware('role:admin')->whereUuid('id');

        Route::get('/members', [MemberController::class, 'index'])->middleware('role:admin,librarian');
        Route::post('/members', [MemberController::class, 'store'])->middleware('role:admin,librarian');
        Route::get('/members/{id}', [MemberController::class, 'show'])->middleware('role:admin,librarian')->whereUuid('id');

        Route::get('/loans/overdue', [LoanController::class, 'overdue'])->middleware('role:admin,librarian');
        Route::get('/loans', [LoanController::class, 'index'])->middleware('role:admin,librarian');
        Route::post('/loans', [LoanController::class, 'store'])->middleware('role:admin,librarian');
        Route::get('/loans/{id}', [LoanController::class, 'show'])->middleware('role:admin,librarian')->whereUuid('id');
        Route::post('/loans/{loan_id}/items/{item_id}/return', [LoanController::class, 'returnItem'])
            ->middleware('role:admin,librarian')
            ->whereUuid('loan_id')
            ->whereUuid('item_id');

        Route::get('/reports/most-borrowed', [ReportController::class, 'mostBorrowed'])->middleware('role:admin');
    });
});
