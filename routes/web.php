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

// list all conversations
Route::get('/ask', [AskController::class, 'index'])->name('ask');
// view a specific conversation
Route::get('/ask/{conversationId}', [AskController::class, 'index'])->name('ask.show');
// send a message
Route::post('/ask', [AskController::class, 'ask'])->name('ask.post');
// create a new conversation
Route::post('/ask/new', [AskController::class, 'create'])->name('ask.create');
// delete a conversation
Route::delete('/ask/{conversationId}', [AskController::class, 'destroy'])->name('ask.destroy');

require __DIR__.'/settings.php';
