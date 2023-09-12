<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\EmbeddingsController;
use App\Http\Controllers\ReportAnswersController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::redirect('/', '/chat');
Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');
Route::post('/answer/report', [ReportAnswersController::class, 'store'])->name('chat.report');
Route::get('/finetuning', [FineTuneingController::class, 'store'])->name('finetunings');
Route::post('/embeddings', [EmbeddingsController::class, 'store'])->name('embeddings.store');
Route::get('/embeddings', [EmbeddingsController::class, 'index'])->name('embeddings.index');
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});
