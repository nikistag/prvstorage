<?php

use Illuminate\Support\Facades\Route;
//Controllers
use App\Http\Controllers\TestController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UshareController;

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
})->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

require __DIR__ . '/auth.php';

//TEST route - testing route
Route::get('/test', [TestController::class, 'test'])->name('test');

//Folder routes
Route::middleware(['auth'])->group(function () {

    //Folder routes
    Route::get('/folder/index', [FolderController::class, 'index'])->name('folder.index');
    Route::get('/user/admins', [UserController::class, 'admins'])->name('user.admins');

    Route::middleware(['isactive'])->group(function () {

        //Folder routes
        Route::get('/folder/root', [FolderController::class, 'root'])->name('folder.root');
        Route::post('/folder/folderNew', [FolderController::class, 'folderNew'])->name('folder.folderNew');
        Route::post('/folder/folderEdit', [FolderController::class, 'folderEdit'])->name('folder.folderEdit');
        Route::post('/folder/folderMove', [FolderController::class, 'folderMove'])->name('folder.folderMove');
        Route::post('/folder/folderupload', [FolderController::class, 'folderupload'])->name('folder.folderupload');
        Route::post('/folder/emptytemp', [FolderController::class, 'emptytrash'])->name('folder.emptytrash');
        Route::post('/folder/emptytrash', [FolderController::class, 'emptytemp'])->name('folder.emptytemp');
        Route::delete('/folder/folderRemove', [FolderController::class, 'folderRemove'])->name('folder.folderRemove');
        Route::post('/folder/fileupload', [FolderController::class, 'fileupload'])->name('folder.fileupload');
        Route::post('/folder/renameFile', [FolderController::class, 'renameFile'])->name('folder.renameFile');
        Route::post('/folder/moveFileBig', [FolderController::class, 'moveFileBig'])->name('folder.moveFileBig'); 
        Route::post('/folder/moveFileMulti', [FolderController::class, 'moveFileMulti'])->name('folder.moveFileMulti');
        Route::post('/folder/multiFilesCopyProgress', [FolderController::class, 'multiFilesCopyProgress'])->name('folder.multiFilesCopyProgress'); // ajax request
        Route::post('/folder/fileCopyProgress', [FolderController::class, 'fileCopyProgress'])->name('folder.fileCopyProgress'); // ajax request
        Route::post('/folder/targetFolderSize', [FolderController::class, 'targetFolderSize'])->name('folder.targetFolderSize'); // ajax request
        Route::post('/folder/folderCopyProgress', [FolderController::class, 'folderCopyProgress'])->name('folder.folderCopyProgress'); // ajax request
        Route::delete('/folder/removeFile', [FolderController::class, 'removeFile'])->name('folder.removeFile');
        Route::delete('/folder/removeFileMulti', [FolderController::class, 'removeFileMulti'])->name('folder.removeFileMulti');// ajax request
        Route::post('/folder/multiupload', [FolderController::class, 'multiupload'])->name('folder.multiupload');
        Route::get('/folder/filedownload', [FolderController::class, 'filedownload'])->name('folder.filedownload');
        Route::get('/folder/multifiledownload', [FolderController::class, 'multifiledownload'])->name('folder.multifiledownload');
        Route::get('/folder/folderdownload', [FolderController::class, 'folderdownload'])->name('folder.folderdownload');
        Route::post('/folder/fileReadiness', [FolderController::class, 'fileReadiness'])->name('folder.fileReadiness'); // ajax request
        Route::get('/folder/searchForm', [FolderController::class, 'searchForm'])->name('folder.searchForm');
        Route::post('/folder/search', [FolderController::class, 'search'])->name('folder.search'); // ajax request - not yet
        Route::get('/folder/filestream', [FolderController::class, 'filestream'])->name('folder.filestream');
        Route::get('/folder/mediapreview', [FolderController::class, 'mediapreview'])->name('folder.mediapreview'); // ajax request

        //Share routes    
        Route::get('/share/index', [ShareController::class, 'index'])->name('share.index');
        Route::post('/share/file', [ShareController::class, 'file'])->name('share.file');
        Route::post('/share/fileMulti', [ShareController::class, 'fileMulti'])->name('share.fileMulti');
        Route::post('/share/folder', [ShareController::class, 'folder'])->name('share.folder');
        Route::post('/share/delete', [ShareController::class, 'delete'])->name('share.delete');
        Route::get('/share/{share}/edit', [ShareController::class, 'edit'])->name('share.edit');
        Route::put('/share/{share}/update', [ShareController::class, 'update'])->name('share.update');
        Route::get('/share/purge', [ShareController::class, 'purge'])->name('share.purge');

        Route::get('/ushare/index', [UshareController::class, 'index'])->name('ushare.index');
        Route::post('/ushare/store', [UshareController::class, 'store'])->name('ushare.store');
        Route::put('/ushare/{ushare}/update', [UshareController::class, 'update'])->name('ushare.update');
        Route::post('/ushare/delete', [UshareController::class, 'delete'])->name('ushare.delete');
        Route::get('/ushare/purge', [UshareController::class, 'purge'])->name('ushare.purge');
        Route::get('/ushare/start', [UshareController::class, 'start'])->name('ushare.start');
        Route::get('/ushare/root', [UshareController::class, 'root'])->name('ushare.root');
        Route::get('/ushare/folderdownload', [UshareController::class, 'folderdownload'])->name('ushare.folderdownload');
        Route::post('/ushare/folderMove', [UshareController::class, 'folderMove'])->name('ushare.folderMove');
        Route::post('/ushare/moveFileBig', [UshareController::class, 'moveFileBig'])->name('ushare.moveFileBig');
        Route::post('/ushare/moveFileMulti', [UshareController::class, 'moveFileMulti'])->name('ushare.moveFileMulti');
        Route::get('/ushare/filedownload', [UshareController::class, 'filedownload'])->name('ushare.filedownload');
        Route::get('/ushare/filestream', [UshareController::class, 'filestream'])->name('ushare.filestream');
        Route::post('/ushare/targetFolderSize', [UshareController::class, 'targetFolderSize'])->name('ushare.targetFolderSize'); // ajax request
        Route::get('/ushare/multifiledownload', [UshareController::class, 'multifiledownload'])->name('ushare.multifiledownload');
        Route::post('/ushare/fileReadiness', [UshareController::class, 'fileReadiness'])->name('ushare.fileReadiness'); // ajax request
        Route::post('/ushare/multiFilesCopyProgress', [UshareController::class, 'multiFilesCopyProgress'])->name('ushare.multiFilesCopyProgress'); // ajax request
        Route::get('/ushare/mediapreview', [UshareController::class, 'mediapreview'])->name('ushare.mediapreview'); // ajax request
        Route::post('/ushare/fileCopyProgress', [UshareController::class, 'fileCopyProgress'])->name('ushare.fileCopyProgress'); // ajax request
        
        Route::get('/user/{user}/view', [UserController::class, 'view'])->name('user.view');
        Route::post('/user/purge', [UserController::class, 'purge'])->name('user.purge');

        //User administration routes
        Route::middleware(['isadmin'])->group(function () {
            Route::get('/user/index', [UserController::class, 'index'])->name('user.index');  
            Route::get('/user/{user}/edit', [UserController::class, 'edit'])->name('user.edit');          
            Route::put('/user/{user}/update', [UserController::class, 'update'])->name('user.update');
            
        });
        Route::middleware(['issuadmin'])->group(function () {
            Route::get('/user/emailTest', [UserController::class, 'emailTest'])->name('user.emailTest');
            Route::post('/user/destroy', [UserController::class, 'destroy'])->name('user.destroy');
        });
    });
});

Route::get('/share/download', [ShareController::class, 'download'])->name('share.download');
