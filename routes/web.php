<?php

use Illuminate\Support\Facades\Route;
//Controllers
use App\Http\Controllers\WorkController;
use App\Http\Controllers\FolderController;

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
    return view('home');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

require __DIR__.'/auth.php';

Route::get('/makedir', [WorkController::class, 'makedir'])->name('makedir');

//Folder routes
Route::middleware(['auth'])->group(function () {

    Route::get('/folder/index', [FolderController::class, 'index'])->name('folder.index');

    Route::get('/folder/ceva', [FolderController::class, 'ceva'])->name('folder.ceva');
});
