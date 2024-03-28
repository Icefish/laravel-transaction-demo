<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return view('welcome');
});

Route::group(['prefix' => '/api'], function () {
    Route::post('/user', [UserController::class, 'create'])->name('createUser');
    Route::get('/user/list', [UserController::class, 'list'])->name('userList');
    Route::put('/user/{userId}', [UserController::class, 'edit'])->name('userEdit');
    Route::put('/user/{userId}/op', [UserController::class, 'op'])->name('userOp');
    Route::get('/user/{userId}', [UserController::class, 'get'])->name('getUser');
});
