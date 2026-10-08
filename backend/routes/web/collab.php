<?php

use App\Http\Controllers\Web\PlaceholderController as P;
use Illuminate\Support\Facades\Route;

// Issues y Pull Requests ({number} = número dentro del repositorio: #1, #2...)

Route::get('repositories/{repository}/issues', P::class)->defaults('title', 'Issues')->defaults('tab', 'issues')->name('issues.index');
Route::get('repositories/{repository}/pulls', P::class)->defaults('title', 'Pull requests')->defaults('tab', 'pulls')->name('pulls.index');

Route::middleware('auth')->group(function () {
    Route::get('repositories/{repository}/issues/create', P::class)->defaults('title', 'Nueva issue')->defaults('tab', 'issues')->name('issues.create');
    Route::post('repositories/{repository}/issues', P::class)->name('issues.store');
    Route::put('repositories/{repository}/issues/{number}', P::class)->name('issues.update');
    Route::post('repositories/{repository}/issues/{number}/comments', P::class)->name('issues.comment');

    Route::get('repositories/{repository}/pulls/create', P::class)->defaults('title', 'Nuevo pull request')->defaults('tab', 'pulls')->name('pulls.create');
    Route::post('repositories/{repository}/pulls', P::class)->name('pulls.store');
    Route::post('repositories/{repository}/pulls/{number}/comments', P::class)->name('pulls.comment');
    Route::post('repositories/{repository}/pulls/{number}/reviews', P::class)->name('pulls.review');
    Route::post('repositories/{repository}/pulls/{number}/merge', P::class)->name('pulls.merge');
    Route::post('repositories/{repository}/pulls/{number}/close', P::class)->name('pulls.close');
    Route::post('repositories/{repository}/pulls/{number}/reopen', P::class)->name('pulls.reopen');
});

Route::get('repositories/{repository}/issues/{number}', P::class)->defaults('title', 'Issue')->defaults('tab', 'issues')->whereNumber('number')->name('issues.show');
Route::get('repositories/{repository}/pulls/{number}', P::class)->defaults('title', 'Pull request')->defaults('tab', 'pulls')->whereNumber('number')->name('pulls.show');
