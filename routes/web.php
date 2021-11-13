<?php

use Illuminate\Support\Facades\Route;
//Controllers
use App\Http\Controllers\WorkController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NetshareController;

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

require __DIR__ . '/auth.php';

Route::get('/makedir', [WorkController::class, 'makedir'])->name('makedir');

//Folder routes
Route::middleware(['auth'])->group(function () {

    //Folder routes
    Route::get('/folder/index', [FolderController::class, 'index'])->name('folder.index');

    Route::middleware(['isactive'])->group(function () {

        //Folder routes
        Route::get('/folder/root', [FolderController::class, 'root'])->name('folder.root');
        Route::post('/folder/newfolder', [FolderController::class, 'newfolder'])->name('folder.newfolder');
        Route::post('/folder/editfolder', [FolderController::class, 'editfolder'])->name('folder.editfolder');
        Route::post('/folder/moveFolder', [FolderController::class, 'moveFolder'])->name('folder.moveFolder');
        Route::post('/folder/folderupload', [FolderController::class, 'folderupload'])->name('folder.folderupload');
        Route::post('/folder/emptytemp', [FolderController::class, 'emptytemp'])->name('folder.emptytemp');
        Route::delete('/folder/remove', [FolderController::class, 'remove'])->name('folder.remove');
        Route::post('/folder/fileupload', [FolderController::class, 'fileupload'])->name('folder.fileupload');
        Route::post('/folder/renameFile', [FolderController::class, 'renameFile'])->name('folder.renameFile');
        Route::post('/folder/moveFileBig', [FolderController::class, 'moveFileBig'])->name('folder.moveFileBig'); 
        Route::post('/folder/fileCopyProgress', [FolderController::class, 'fileCopyProgress'])->name('folder.fileCopyProgress'); // ajax request
        Route::delete('/folder/removeFile', [FolderController::class, 'removeFile'])->name('folder.removeFile');
        Route::post('/folder/multiupload', [FolderController::class, 'multiupload'])->name('folder.multiupload');
        Route::get('/folder/filedownload', [FolderController::class, 'filedownload'])->name('folder.filedownload');
        Route::get('/folder/folderdownload', [FolderController::class, 'folderdownload'])->name('folder.folderdownload');

        //Local network share routes
        Route::get('/netshare/root', [NetshareController::class, 'root'])->name('netshare.root');
        Route::post('/netshare/newfolder', [NetshareController::class, 'newfolder'])->name('netshare.newfolder');
        Route::post('/netshare/editfolder', [NetshareController::class, 'editfolder'])->name('netshare.editfolder');
        Route::post('/netshare/moveFolder', [NetshareController::class, 'moveFolder'])->name('netshare.moveFolder');
        Route::post('/netshare/folderupload', [NetshareController::class, 'folderupload'])->name('netshare.folderupload');
        Route::post('/netshare/emptytemp', [NetshareController::class, 'emptytemp'])->name('netshare.emptytemp');
        Route::delete('/netshare/remove', [NetshareController::class, 'remove'])->name('netshare.remove');
        Route::post('/netshare/fileupload', [NetshareController::class, 'fileupload'])->name('netshare.fileupload');
        Route::post('/netshare/renameFile', [NetshareController::class, 'renameFile'])->name('netshare.renameFile');
        Route::post('/netshare/moveFileBig', [NetshareController::class, 'moveFileBig'])->name('netshare.moveFileBig'); 
        Route::post('/netshare/fileCopyProgress', [NetshareController::class, 'fileCopyProgress'])->name('netshare.fileCopyProgress'); // ajax request
        Route::delete('/netshare/removeFile', [NetshareController::class, 'removeFile'])->name('netshare.removeFile');
        Route::post('/netshare/multiupload', [NetshareController::class, 'multiupload'])->name('netshare.multiupload');
        Route::get('/netshare/filedownload', [NetshareController::class, 'filedownload'])->name('netshare.filedownload');
        Route::get('/netshare/folderdownload', [NetshareController::class, 'folderdownload'])->name('netshare.folderdownload');

        //Share routes    
        Route::get('/share/index', [ShareController::class, 'index'])->name('share.index');
        Route::post('/share/createFile', [ShareController::class, 'createFile'])->name('share.createFile');
        Route::post('/share/createFolder', [ShareController::class, 'createFolder'])->name('share.createFolder');
        Route::post('/share/delete', [ShareController::class, 'delete'])->name('share.delete');

        //User administration routes
        Route::get('/user/admins', [UserController::class, 'admins'])->name('user.admins');
        Route::middleware(['isadmin'])->group(function () {
            Route::get('/user/index', [UserController::class, 'index'])->name('user.index');
            Route::get('/user/{user}/edit', [UserController::class, 'edit'])->name('user.edit');
            Route::put('/user/{user}/update', [UserController::class, 'update'])->name('user.update');
            Route::delete('/user/{user}', [UserController::class, 'destroy'])->name('user.destroy');
        });
    });
});

Route::get('/share/download', [ShareController::class, 'download'])->name('share.download');
