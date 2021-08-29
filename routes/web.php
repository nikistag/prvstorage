<?php

use Illuminate\Support\Facades\Route;
//Controllers
use App\Http\Controllers\WorkController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\ShareController;

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

    //Folder routes

    Route::get('/folder/index', [FolderController::class, 'index'])->name('folder.index');

    Route::get('/folder/root', [FolderController::class, 'root'])->name('folder.root');

    Route::post('/folder/newfolder', [FolderController::class, 'newfolder'])->name('folder.newfolder');

    Route::post('/folder/editfolder', [FolderController::class, 'editfolder'])->name('folder.editfolder');

    Route::post('/folder/moveFolder', [FolderController::class, 'moveFolder'])->name('folder.moveFolder');

    Route::post('/folder/folderupload', [FolderController::class, 'folderupload'])->name('folder.folderupload');

    Route::post('/folder/emptytemp', [FolderController::class, 'emptytemp'])->name('folder.emptytemp');

    Route::delete('/folder/remove', [FolderController::class, 'remove'])->name('folder.remove');

    Route::post('/folder/fileupload', [FolderController::class, 'fileupload'])->name('folder.fileupload');    

    Route::post('/folder/renameFile', [FolderController::class, 'renameFile'])->name('folder.renameFile');

    Route::post('/folder/moveFile', [FolderController::class, 'moveFile'])->name('folder.moveFile');

    Route::delete('/folder/removeFile', [FolderController::class, 'removeFile'])->name('folder.removeFile');

    Route::post('/folder/multiupload', [FolderController::class, 'multiupload'])->name('folder.multiupload');

    Route::get('/folder/filedownload', [FolderController::class, 'filedownload'])->name('folder.filedownload');

    Route::get('/folder/folderdownload', [FolderController::class, 'folderdownload'])->name('folder.folderdownload');

    //Share routes
    
    Route::get('/share/index', [ShareController::class, 'index'])->name('share.index');

    Route::post('/share/createFile', [ShareController::class, 'createFile'])->name('share.createFile');

    Route::post('/share/createFolder', [ShareController::class, 'createFolder'])->name('share.createFolder');

    Route::post('/share/delete', [ShareController::class, 'delete'])->name('share.delete');
    
});

Route::get('/share/download', [ShareController::class, 'download'])->name('share.download');
