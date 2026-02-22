<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\AskController;

Route::get('/', function () {
    return Inertia::render('index', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // list all conversations
    Route::get('/ask', [AskController::class, 'index'])->name('ask');
    // send a message (returns conversationId for streaming)
    Route::post('/ask', [AskController::class, 'ask'])->middleware('throttle:30,1')->name('ask.post');
    // literal routes BEFORE the wildcard
    Route::match(['get','post'], '/ask/stream', [AskController::class, 'stream'])->middleware('throttle:30,1')->name('ask.stream');
    Route::post('/ask/save-response', [AskController::class, 'saveResponse'])->name('ask.save');
    Route::post('/ask/new', [AskController::class, 'create'])->name('ask.create');
    // wildcard routes LAST
    Route::get('/ask/{conversationId}', [AskController::class, 'index'])->name('ask.show');
    Route::delete('/ask/{conversationId}', [AskController::class, 'destroy'])->name('ask.destroy');
});

require __DIR__.'/settings.php';
