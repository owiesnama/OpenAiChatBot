<?php

use App\Http\Controllers\ChatController;
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

Route::get('/', function () {
    return redirect()->route('chat.index');
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});


Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');
Route::get('/finetuning', [FineTuneingController::class, 'store'])->name('finetunings');
Route::get('/generate-finetune-data', function () {
    $results = DB::table('spotlayerteam_institutionsprograms_institution')->select([
        'about',
        'sector',
        'founded',
        'name',
    ])->get();
    $results = $results->map(function ($school) {
        return [
            [
                'prompt' => "what about $school->name university",
                'completion' => $school->about,
            ],
            [
                'prompt' => "when $school->name founded",
                'completion' => $school->founded,
            ],
            [
                'prompt' => "what the sector of $school->name university ",
                'completion' => $school->sector,
            ]
        ];
    })->flatten(1);

    File::put("data-old.json", $results);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});
