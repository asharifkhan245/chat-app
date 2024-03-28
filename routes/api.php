<?php

use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {


});

Route::post('/register', [AdminController::class, 'register']);
Route::post('/login', [AdminController::class, 'login']);
Route::get('/users', [AdminController::class, 'getusers']);



Route::middleware('auth:sanctum')->group(function () {
   
Route::post('/store-chat', [AdminController::class, 'store_chat']);
Route::get('/get-chat/{id}/{receiverid}', [AdminController::class, 'get_chats']);
Route::get('/inbox/{id}', [AdminController::class, 'inbox']);
});


