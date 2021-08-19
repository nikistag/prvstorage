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

    Route::get('/folder/root', [FolderController::class, 'root'])->name('folder.root');

    Route::post('/folder/newfolder', [FolderController::class, 'newfolder'])->name('folder.newfolder');

    Route::post('/folder/editfolder', [FolderController::class, 'editfolder'])->name('folder.editfolder');

    Route::post('/folder/moveFolder', [FolderController::class, 'moveFolder'])->name('folder.moveFolder');

    Route::delete('/folder/remove', [FolderController::class, 'remove'])->name('folder.remove');

    Route::post('/folder/fileupload', [FolderController::class, 'fileupload'])->name('folder.fileupload');    

    Route::post('/folder/renameFile', [FolderController::class, 'renameFile'])->name('folder.renameFile');

    Route::post('/folder/moveFile', [FolderController::class, 'moveFile'])->name('folder.moveFile');

    Route::delete('/folder/removeFile', [FolderController::class, 'removeFile'])->name('folder.removeFile');

    Route::post('/folder/multiupload', [FolderController::class, 'multiupload'])->name('folder.multiupload');


    
});
