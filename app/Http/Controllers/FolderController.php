<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use ZipArchive;
use App\Models\Share;
use App\Models\Ushare;
use Illuminate\Support\Facades\Cache;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Support\Arr;

class FolderController extends Controller
{
    public function index(Request $request)
    {
        return view('folder.index');
    }
    public function root(Request $request)
    {

        $current_folder = $request->current_folder;

        $path = $this->getPath($current_folder);

        $breadcrumbs = $this->getBreadcrumbs($current_folder);

        //Directory paths for options to move files and folders
        $full_private_directory_paths = Storage::allDirectories(auth()->user()->name);
        $share_directory_paths = Storage::allDirectories('NShare');
        if (count($share_directory_paths) == 0) {
            $share_directory_paths = ["NShare"];
        }
        //Delete expired local shares
        $expiredShares = Ushare::where("expiration", "<", time())->get();
        if (count($expiredShares) >= 1) {
            foreach ($expiredShares as $expired) {
                $expired->delete();
            }
        }

        //Get info about local shares
        $usershares = null;
        //if ($current_folder == null) {
        $ushares = Ushare::where('wuser_id', auth()->user()->id)->get();
        //}
        if (count($ushares) > 0) {
            $usershares = count($ushares->unique("user_id")) . " shares";
            $usershares_directories = [];
            foreach ($ushares as $ush) {
                array_push($usershares_directories, [0 => substr($ush->path, 1, strlen($ush->path))]);
                array_push($usershares_directories, Storage::allDirectories($ush->path));
            }
            $usershares_directory_merged = array_merge(...$usershares_directories);
            $usershares_directory_paths = $this->prependStringToArrayElements($usershares_directory_merged, "UShare/");
        }

        //Get folders an files of current directory
        $dirs = Storage::directories($path);
        $fls = Storage::files($path);
        $directories = [];
        foreach ($dirs as $dir) {
            if ($dir !== auth()->user()->name . "/ZTemp") {
                array_push($directories, [
                    'foldername' => substr($dir, strlen($path)),
                    'shortfoldername' => strlen(substr($dir, strlen($path))) > 30 ? substr(substr($dir, strlen($path)), 0, 25) . "..." :  substr($dir, strlen($path)),
                    'foldersize' => $this->getFolderSize($dir),
                ]);
            }
        }
        $NShare['foldersize'] = $this->getFolderSize('NShare');
        $ztemp['foldersize'] = $this->getFolderSize(auth()->user()->name . '/ZTemp');

        /* Process files */
        $files = [];
        foreach ($fls as $file) {
            $fullfilename = substr($file, strlen($path));
            $extensionWithDot = strrchr($file, ".");
            $extensionNoDot = substr($extensionWithDot, 1, strlen($extensionWithDot));
            array_push($files, [
                'fullfilename' =>  $fullfilename,
                'fileurl' => $path . "/" . $fullfilename,
                'filename' => $filename = substr($fullfilename, 0, strripos($fullfilename, strrchr($fullfilename, "."))),
                'shortfilename' => strlen($filename) > 30 ? substr($filename, 0, 25) . "*~" : $filename,
                'extension' => $extensionWithDot,
                'fileimageurl' => $this->getThumbnailImage($extensionWithDot, $path, $fullfilename, $filename),
                'filevideourl' => $this->getThumbnailVideo($extensionWithDot, $path, $fullfilename, $filename),
                'filesize' => $this->getFileSize($file),
                'filedate' => date("Y.m.d", filemtime(Storage::path($file)))
            ]);
        }

        //Generate folder tree view - collection
        $collection = collect(array_merge($full_private_directory_paths, $share_directory_paths));
        $treeDirectories = $collection->reject(function ($value, $key) {
            return $value == auth()->user()->name . "/ZTemp";
        });
        $treeCollection = $treeDirectories->map(function ($item) {
            if (substr($item, 0, strlen(auth()->user()->name)) == auth()->user()->name) {
                $dir = substr($item, strlen(auth()->user()->name));
                return explode('/', $dir);
            } else {
                return explode('/', '/' . $item);
            }
        });
        //Prepare active tree branch
        $activeBranch = [];
        foreach ($breadcrumbs as $crumb) {
            array_push($activeBranch, $crumb["folder"]);
        }
        $garbage = array_shift($activeBranch);

        $userRoot = $this->convertPathsToTree($treeCollection)->first();
        $folderTreeView = '<li><span class="folder-tree-root"></span>';
        $folderTreeView .= '<a class="blue-grey-text text-darken-3"   href="' . route('folder.root', ['current_folder' => '']) . '" data-folder="Root" data-folder-view="Root"><b><i>Root</i></b></a></li>';
        $folderTreeView .= $this->generateViewTree($userRoot['children'], $current_folder, $activeBranch);

        $treeMoveFolder = str_replace("blue-grey-text text-darken-3", "collection-item blue-grey-text text-darken-3 tree-move-folder", $folderTreeView);
        $treeMoveFile = str_replace("blue-grey-text text-darken-3", "collection-item blue-grey-text text-darken-3 tree-move-file", $folderTreeView);
        $treeMoveMulti = str_replace("blue-grey-text text-darken-3", "collection-item blue-grey-text text-darken-3 tree-move-multi", $folderTreeView);

        //Add UShare folder to folder tree view
        //Generate folder tree view - collection for UShare
        if (count($ushares) > 0) {
            $ushareCollection = collect($usershares_directory_paths);
            $treeCollection_ushare = $ushareCollection->map(function ($item) {
                return explode('/', '/' . $item);
            });
            $userRootShare = $this->convertPathsToTree($treeCollection_ushare)->first();
            $folderTreeView .= $this->generateShareViewTree($userRootShare['children'], $ushares);
        }


        return view('folder.root', compact(
            'directories',
            'files',
            'current_folder',
            'NShare',
            'ztemp',
            'path',
            'breadcrumbs',
            'folderTreeView',
            'treeMoveFolder',
            'treeMoveFile',
            'treeMoveMulti',
            'usershares'
        ));
    }

    public function folderNew(Request $request)
    {
        $current_folder = $request->current_folder;

        //Forbid creation of Restricted folder name 'NShare'
        if (($request->input('newfolder') == 'NShare') || ($request->input('newfolder') == 'ZTemp') || ($request->input('newfolder') == 'UShare')) {
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('error', 'Folder names @NShare, @UShare and @ZTemp are restricted!!!');
        } else {
            $path = $this->getPath($current_folder);
            $new_folder = $request->input('newfolder');
            $new_folder_path = $path . "/" . $new_folder;

            if (Storage::exists($new_folder_path)) {
                return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('warning', 'Folder already exists!');
            } else {
                //main
                Storage::makeDirectory($new_folder_path);
                //thumb
                Storage::disk('public')->makeDirectory('/thumb' . $new_folder_path);
                return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'New folder created!');
            }
        }
    }
    public function folderEdit(Request $request)
    {
        $current_folder = $request->current_folder;

        //Forbid creation of Restricted folder name 'NShare'
        if (($request->input('editfolder') == 'NShare') || ($request->input('editfolder') == 'ZTemp') || ($request->input('editfolder') == 'UShare')) {
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('error', 'Folder names @NShare, @UShare and @ZTemp are restricted!!!');
        } else {
            $path = $this->getPath($current_folder);
            $old_path = $path . "/" . $request->input('oldfolder');
            $new_path = $path . "/" . $request->input('editfolder');
            //main
            Storage::move($old_path, $new_path);
            //thumbs
            if (Storage::disk('public')->has('/thumb' . $old_path)) {
                Storage::disk('public')->move('/thumb' . $old_path, '/thumb' . $new_path);
            }
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Folder renamed!');
        }
    }
    public function folderMove(Request $request)
    {

        $current_folder = $request->input('target') == "" ? "" : $request->input('target');

        $new_path = $this->getPath($current_folder) . "/" . $request->input('whichfolder');
        $old_path = $this->getPath($request->current_folder) . "/" . $request->input('whichfolder');

        //Check for path inside moved folder
        if (strrpos($new_path, $old_path) === 0) {
            return redirect()->route('folder.root', ['current_folder' => $request->current_folder])->with('warning', 'NO action done. Not good practice to move folder to itself!');
        }

        //Check for duplicate folder
        if (Storage::exists($new_path)) {
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('warning', 'NO action done. Duplicate folder found!');
        }
        //Copy or move folder
        if ($request->has('foldercopy')) {
            //main
            $done = (new Filesystem)->copyDirectory(Storage::path($old_path), Storage::path($new_path));
            //thumbs
            $thumbdone = (new Filesystem)->copyDirectory(Storage::disk('public')->path('/thumb' . $old_path), Storage::disk('public')->path('/thumb' . $new_path));
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Folder successfuly copied!');
        } else {
            //main
            $done = Storage::move($old_path, $new_path);
            //thumbs
            if (Storage::disk('public')->exists('/thumb' . $old_path)) {
                $thumbdone = Storage::disk('public')->move('/thumb' . $old_path, '/thumb' . $new_path);
            }
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Folder successfuly moved!');
        }
    }

    public function folderRemove(Request $request)
    {
        $current_folder = $request->current_folder;

        $path = $this->getPath($current_folder);
        $garbage = $path . "/" . $request->input('folder');
        //delete main
        Storage::deleteDirectory($garbage);
        //delete thumbs
        Storage::disk('public')->deleteDirectory('/thumb' . $garbage);
        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Folder successfuly removed!');
    }

    public function folderupload(Request $request)
    {

        $current_folder = $request->current_folder;
        $path = $this->getPath($current_folder);

        $name = $request->file('file')->getClientOriginalName();
        $clientFolder = substr($request->filepath, 0, strlen($request->filepath) - strlen($name) - 1);

        $new_folder = $clientFolder;
        $new_folder_path = $path . "/" . $new_folder;
        //main
        Storage::makeDirectory($new_folder_path);
        //thumb
        Storage::disk('public')->makeDirectory('/thumb' . $new_folder_path);
        $upload_path = Storage::putFileAs($new_folder_path, $request->file('file'), $name);
    }

    public function folderdownload(Request $request)
    {

        if ($request->has('path')) {
            $path = $this->getPath($request->path);

            $directory = $request->directory;

            $file_full_paths = Storage::allFiles($path);

            $directory_full_paths = Storage::allDirectories($path);

            $zip_directory_paths = [];
            foreach ($directory_full_paths as $dir) {
                array_push($zip_directory_paths, substr($dir, strlen($path)));
            }

            $zipFileName = 'zpd_' . $directory . '.zip';

            $zip_path = Storage::path(auth()->user()->name . '/ZTemp/' . $zipFileName);

            // Creating file names and path names to be archived
            $files_n_paths = [];
            foreach ($file_full_paths as $fl) {
                array_push($files_n_paths, [
                    'name' => substr($fl, strripos($fl, '/') + 1),
                    'path' => Storage::path($fl),
                    'zip_path' => substr($fl, strlen($path)),
                ]);
            }

            $zip = new ZipArchive();
            if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                //Add folders to archive
                foreach ($zip_directory_paths as $zip_directory) {
                    $zip->addEmptyDir($zip_directory);
                }
                // Add Files in ZipArchive
                foreach ($files_n_paths as $file) {
                    $zip->addFile($file['path'], $file['zip_path']);
                }
                // Close ZipArchive     
                $zip->close();
            }
            return redirect(route('folder.filedownload', ['path' => '/ZTemp/' . $zipFileName]));
        } else {
            return back()->with('error', 'Could not download folder!!');
        }
    }

    public function emptytemp(Request $request)
    {
        $temp_path = '/' . auth()->user()->name . "/ZTemp";

        $shares = Share::select('path', 'status')->where('user_id', auth()->user()->id)->get();

        $temp_files = Storage::allFiles($temp_path);

        foreach ($temp_files as $file) {
            $filtered = $shares->where('path', "/" . $file);
            if (count($filtered) == 0) {
                Storage::delete($file);
            } else {
                if ($filtered->first()->status != 'active') {
                    Storage::delete($file);
                }
            }
        }

        return redirect()->route('folder.root', ['current_folder' => ''])->with('success', 'Temporary folder is clean!');
    }

    public function renameFile(Request $request)
    {
        $current_folder = $request->current_folder;
        $path = $this->getPath($current_folder);

        $old_path = $path . "/" . $request->input('oldrenamefilename');
        $new_path = $path . "/" . $request->input('renamefilename');
        // dd($old_path);
        //main
        Storage::move($old_path, $new_path);
        //thumb
        if (Storage::disk('public')->has('/thumb' . $old_path)) {
            Storage::disk('public')->move('/thumb' . $old_path, '/thumb' . $new_path);
        }


        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly renamed!');
    }

    public function moveFileBig(Request $request)
    {
        $current_folder = $request->input('whereToFolder') == "" ? "" : $request->input('whereToFolder');
        $path = $this->getPath($request->current_folder_big);

        $old_path = $path . "/" . $request->input('file_big');
        $new_path = $this->getPath($current_folder . "/" . $request->input('file_big'));

        //Check for duplicate file
        if (Storage::exists($new_path)) {
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('warning', 'File already there!');
        } else {
            if ($request->has('filecopy')) {   //Check if copy or move file
                $done = Storage::copy($old_path, $new_path);
                if (Storage::disk('public')->has('/thumb' . $old_path)) {
                    $thumbs = Storage::disk('public')->copy('/thumb' . $old_path, '/thumb' . $new_path);
                }
                return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly copied!');
            } else {
                $done = Storage::move($old_path, $new_path);
                if (Storage::disk('public')->has('/thumb' . $old_path)) {
                    $thumbs = Storage::disk('public')->move('/thumb' . $old_path, '/thumb' . $new_path);
                }
                return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly moved!');
            }
        }
    }
    public function moveFileMulti(Request $request)
    {

        $current_folder = $request->input('targetfoldermulti');
        $path = $this->getPath($request->current_folder_multi);

        foreach ($request->filesMove as $file) {
            $old_path = $path . "/" . $file;
            $new_path = $this->getPath($current_folder . "/" . $file);
            //Check for duplicate file
            if (Storage::exists($new_path)) {
                /* return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('warning', 'File already there!'); */
            } else {
                if ($request->has('filecopy')) {   //Check if copy or move file
                    $done = Storage::copy($old_path, $new_path);
                    if (Storage::disk('public')->has('/thumb' . $old_path)) {
                        $thumbs = Storage::disk('public')->copy('/thumb' . $old_path, '/thumb' . $new_path);
                    }
                    /* return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly copied!'); */
                } else {
                    $done = Storage::move($old_path, $new_path);
                    if (Storage::disk('public')->has('/thumb' . $old_path)) {
                        $thumbs = Storage::disk('public')->move('/thumb' . $old_path, '/thumb' . $new_path);
                    }
                    /*  return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly moved!'); */
                }
            }
        }

        if ($request->has('filecopy')) {   //Check if copy or move file
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Files successfuly copied!');
        } else {
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Files successfuly moved!');
        }
    }
    public function removeFile(Request $request)
    {
        $current_folder = $request->current_folder;
        $path = $this->getPath($current_folder);

        $garbage = $path . "/" . $request->input('filename');
        //main
        Storage::delete($garbage);
        //thumbs
        $this->removeThumbs($request->input('filename'), $path);

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly removed!');
    }
    public function removeFileMulti(Request $request)
    {
        $current_folder = $request->current_folder;
        $path = $this->getPath($current_folder);

        foreach ($request->input("filesDelete") as $file) {

            $garbage = $path . "/" . $file;
            //Remove main
            Storage::delete($garbage);
            //Remove thumbnails
            $this->removeThumbs($file, $path);
        }

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Files successfuly removed!');
    }

    public function fileupload(Request $request) // NOT IN USE -> "multiupload" method in ude
    {
        $current_folder = $request->current_folder;
        $path = $this->getPath($current_folder);

        $name = $request->file('fileupload')->getClientOriginalName();
        $upload_path = Storage::putFileAs($path, $request->file('fileupload'), $name);

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Upload successful!!');
    }

    public function filedownload(Request $request)
    {
        if ($request->has('path')) {
            if (substr($request->path, 1, 6) == "NShare") {
                $path = $request->path;
            } else {
                $path = '/' . auth()->user()->name . $request->path;
            }
            return Storage::download($path);
        } else {
            return back()->with('error', 'File / Folder not found on server');
        }
    }
    public function filestream(Request $request)
    {
        if ($request->has('path')) {
            if (substr($request->path, 1, 6) == "NShare") {
                $path = $request->path;
            } else {
                $path = auth()->user()->name . $request->path;
            }
            $headers = $this->getStreamHeaders($path);
            return response()->file(Storage::path($path), $headers);
        } else {
            return back()->with('error', 'File / Folder not found on server');
        }
    }

    public function multifiledownload(Request $request)
    {

        $path = $this->getPath($request->currentFolderMultiDownload);

        $zipFileName = $request->multiZipFileName;

        $storage_path = auth()->user()->name . '/ZTemp/' . $zipFileName;

        $zip_path = Storage::path($storage_path);

        //Create archive
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            // Add File in ZipArchive
            foreach ($request->input("filesdownload") as $file) {
                $zip->addFile(Storage::path($path . "/" . $file), $file);
            }
            // Close ZipArchive     
            $zip->close();
        }
        //sleep(1);
        return Storage::download($storage_path);
    }

    public function multiupload(Request $request)                                           // IN USE, working fine for multiple files
    {
        $current_folder = $request->current_folder;
        $path = auth()->user()->name . $current_folder;

        $name = $request->file('file')->getClientOriginalName();
        $upload_path = Storage::putFileAs($path, $request->file('file'), $name);

        $pathToFolder = $this->getPath($current_folder);
        $fullfilename = substr($upload_path, strlen($pathToFolder));
        $extensionWithDot = strrchr($upload_path, ".");
        $filename = substr($fullfilename, 0, strripos($fullfilename, strrchr($fullfilename, ".")));
        $fileimageurl = $this->generateImageThumbnail($extensionWithDot, $pathToFolder, $fullfilename, $filename);
        $filevideourl = $this->generateVideoThumbnail($extensionWithDot, $pathToFolder, $fullfilename, $filename);

        unset($fileimageurl);
        unset($filevideourl);
    }
    public function fileCopyProgress(Request $request)
    {
        $current_folder = "/" . $request->targetfolder;
        $path = $this->getPath($request->currentfolder);

        $old_path = $path . "/" . $request->copyfile;
        $new_path = $this->getPath($current_folder . "/" . $request->copyfile);

        $progress = (File::size(Storage::path($new_path)) / File::size(Storage::path($old_path))) * 100;

        return response()->json([
            'progress' =>  $progress
        ]);
    }
    public function multiFilesCopyProgress(Request $request)
    {

        $filesSize = 0;
        foreach ($request->copyfiles as $file) {
            $filesSize += File::size(Storage::path($this->getPath($request->currentfolder . "/" . $file)));
        }
        // $expectedTargetFolderSize = $request->targetfoldersize + $filesSize;

        $currentTargetFolderSize = $this->getFolderSize($this->getPath("/" . $request->targetfolder));

        $progress = (($currentTargetFolderSize['byteSize'] - $request->targetfordersize) / $filesSize) * 100;

        return response()->json([
            'progress' =>  $progress
        ]);
    }
    public function targetFolderSize(Request $request)
    {

        $targetFolderSize = $this->getFolderSize($this->getPath("/" . $request->targetfolder));

        return response()->json([
            'folderSize' =>  $targetFolderSize['byteSize']
        ]);
    }
    public function folderCopyProgress(Request $request)
    {

        $path = $this->getPath($request->current_folder);
        $old_path = $path . "/" . $request->whichfolder;
        $new_path = $this->getPath("/" . $request->target . "/" . $request->whichfolder);

        $original_size = $this->getFolderSize($new_path);
        $new_size = $this->getFolderSize($old_path);

        $progress = ($original_size['byteSize'] / $new_size['byteSize']) * 100;

        return response()->json([
            'progress' =>  $progress
        ]);
    }
    public function fileReadiness(Request $request)
    {
        //dd($request->input());

        $path = $this->getPath($request->filePath);

        if (Storage::exists($path)) {
            $ready = true;
        } else {
            $ready = false;
        }

        return response()->json([
            'ready' =>  $ready
        ]);
    }
    public function searchForm(Request $request)
    {
        $current_folder = $request->current_folder;

        $path = $this->getPath($current_folder);

        $breadcrumbs = $this->getBreadcrumbs($current_folder);

        //Directory paths for options to move files and folders
        $full_private_directory_paths = Storage::allDirectories(auth()->user()->name);
        $share_directory_paths = Storage::allDirectories('NShare');
        if (count($share_directory_paths) == 0) {
            $share_directory_paths = ["NShare"];
        }
        //Delete expired local shares
        $expiredShares = Ushare::where("expiration", "<", time())->get();
        if (count($expiredShares) >= 1) {
            foreach ($expiredShares as $expired) {
                $expired->delete();
            }
        }

        //Get info about local shares
        $usershares = null;
        //if ($current_folder == null) {
        $ushares = Ushare::where('wuser_id', auth()->user()->id)->get();
        //}
        if (count($ushares) > 0) {
            $usershares = count($ushares->unique("user_id")) . " shares";
            $usershares_directories = [];
            foreach ($ushares as $ush) {
                array_push($usershares_directories, [0 => substr($ush->path, 1, strlen($ush->path))]);
                array_push($usershares_directories, Storage::allDirectories($ush->path));
            }
            $usershares_directory_merged = array_merge(...$usershares_directories);
            $usershares_directory_paths = $this->prependStringToArrayElements($usershares_directory_merged, "UShare/");
        }

        //Get folders an files of current directory
        $dirs = Storage::directories($path);
        $fls = Storage::files($path);
        $directories = [];
        foreach ($dirs as $dir) {
            if ($dir !== auth()->user()->name . "/ZTemp") {
                array_push($directories, [
                    'foldername' => substr($dir, strlen($path)),
                    'shortfoldername' => strlen(substr($dir, strlen($path))) > 30 ? substr(substr($dir, strlen($path)), 0, 25) . "..." :  substr($dir, strlen($path)),
                    'foldersize' => $this->getFolderSize($dir),
                ]);
            }
        }
        $NShare['foldersize'] = $this->getFolderSize('NShare');
        $ztemp['foldersize'] = $this->getFolderSize(auth()->user()->name . '/ZTemp');

        /* Process files */
        $files = [];
        foreach ($fls as $file) {
            $fullfilename = substr($file, strlen($path));
            $extensionWithDot = strrchr($file, ".");
            $extensionNoDot = substr($extensionWithDot, 1, strlen($extensionWithDot));
            array_push($files, [
                'fullfilename' =>  $fullfilename,
                'fileurl' => $path . "/" . $fullfilename,
                'filename' => $filename = substr($fullfilename, 0, strripos($fullfilename, strrchr($fullfilename, "."))),
                'shortfilename' => strlen($filename) > 30 ? substr($filename, 0, 25) . "*~" : $filename,
                'extension' => $extensionWithDot,
                'fileimageurl' => $this->getThumbnailImage($extensionWithDot, $path, $fullfilename, $filename),
                'filevideourl' => $this->getThumbnailVideo($extensionWithDot, $path, $fullfilename, $filename),
                'filesize' => $this->getFileSize($file),
                'filedate' => date("Y.m.d", filemtime(Storage::path($file)))
            ]);
        }

        //Generate folder tree view - collection
        $collection = collect(array_merge($full_private_directory_paths, $share_directory_paths));
        $treeDirectories = $collection->reject(function ($value, $key) {
            return $value == auth()->user()->name . "/ZTemp";
        });
        $treeCollection = $treeDirectories->map(function ($item) {
            if (substr($item, 0, strlen(auth()->user()->name)) == auth()->user()->name) {
                $dir = substr($item, strlen(auth()->user()->name));
                return explode('/', $dir);
            } else {
                return explode('/', '/' . $item);
            }
        });
        //Prepare active tree branch
        $activeBranch = [];
        foreach ($breadcrumbs as $crumb) {
            array_push($activeBranch, $crumb["folder"]);
        }
        $garbage = array_shift($activeBranch);

        $userRoot = $this->convertPathsToTree($treeCollection)->first();
        $folderTreeView = '<li><span class="folder-tree-root"></span>';
        $folderTreeView .= '<a class="blue-grey-text text-darken-3"   href="' . route('folder.root', ['current_folder' => '']) . '" data-folder="Root" data-folder-view="Root"><b><i>Root</i></b></a></li>';
        $folderTreeView .= $this->generateViewTree($userRoot['children'], $current_folder, $activeBranch);

        $treeMoveFolder = str_replace("blue-grey-text text-darken-3", "collection-item blue-grey-text text-darken-3 tree-move-folder", $folderTreeView);
        $treeMoveFile = str_replace("blue-grey-text text-darken-3", "collection-item blue-grey-text text-darken-3 tree-move-file", $folderTreeView);
        $treeMoveMulti = str_replace("blue-grey-text text-darken-3", "collection-item blue-grey-text text-darken-3 tree-move-multi", $folderTreeView);

        //Add UShare folder to folder tree view
        //Generate folder tree view - collection for UShare
        if (count($ushares) > 0) {
            $ushareCollection = collect($usershares_directory_paths);
            $treeCollection_ushare = $ushareCollection->map(function ($item) {
                return explode('/', '/' . $item);
            });
            $userRootShare = $this->convertPathsToTree($treeCollection_ushare)->first();
            $folderTreeView .= $this->generateShareViewTree($userRootShare['children'], $ushares);
        }
        return view('folder.searchForm', compact(
            'directories',
            'files',
            'current_folder',
            'NShare',
            'ztemp',
            'path',
            'breadcrumbs',
            'folderTreeView',
            'treeMoveFolder',
            'treeMoveFile',
            'treeMoveMulti',
        ));
    }
    public function search(Request $request)
    {
        $current_folder = $request->current_folder;
        $searchstring = strtolower($request->searchstring);

        $path = $this->getPath($current_folder);

        //Directory paths for options to move files and folders
        $full_private_directory_paths = Storage::allDirectories(auth()->user()->name);

        //Get folders an files of current directory
        $dirs = Storage::allDirectories($path);
        $fls = Storage::allFiles($path);

        $directories = [];
        foreach ($dirs as $dir) {
            if ($dir !== auth()->user()->name . "/ZTemp") {
                $trueFolderName = substr($dir, strrpos($dir, "/") + 1, strlen($dir) - strrpos($dir, "/"));
                if ($searchstring == "") {
                    array_push($directories, [
                        'foldername' => $trueFolderName,
                        'folderpath' => $dir,
                        'personalfolderpath' => substr($dir, strlen(auth()->user()->name) + 1),
                        'shortfoldername' => strlen($trueFolderName) > 30 ? substr($trueFolderName, 0, 25) . "..." :   $trueFolderName,
                        'foldersize' => $this->getFolderSize($dir),
                    ]);
                } else {
                    if (strstr(strtolower($trueFolderName), $searchstring) !== false) {
                        array_push($directories, [
                            'foldername' => $trueFolderName,
                            'folderpath' => $dir,
                            'personalfolderpath' => substr($dir, strlen(auth()->user()->name) + 1),
                            'shortfoldername' => strlen($trueFolderName) > 30 ? substr($trueFolderName, 0, 25) . "..." :   $trueFolderName,
                            'foldersize' => $this->getFolderSize($dir),
                        ]);
                    }
                }
            }
        }

        $files = [];
        foreach ($fls as $file) {
            $trueFileName = substr($file, strrpos($file, "/") + 1, strlen($file) - strrpos($file, "/"));
            $fullfilename = substr($file, strlen(auth()->user()->name) + 1);
            if ($searchstring == "") {
                array_push($files, [
                    'filepath' => $file,
                    'filefolder' => substr($fullfilename, 0, strlen($fullfilename) - strlen($trueFileName) - 1),
                    'fullfilename' => $fullfilename,
                    'filename' => $trueFileName,
                    'shortfilename' => strlen($trueFileName) > 25 ? substr($trueFileName, 0, 20) . "*~" : $trueFileName,
                    'extension' => strrchr($file, "."),
                    'filesize' => $this->getFileSize($file),
                    'filedate' => date("Y.m.d", filemtime(Storage::path($file)))
                ]);
            } else {
                if (strstr(strtolower($trueFileName), $searchstring) !== false) {
                    array_push($files, [
                        'filepath' => $file,
                        'filefolder' => substr($fullfilename, 0, strlen($fullfilename) - strlen($trueFileName) - 1),
                        'fullfilename' => $fullfilename,
                        'filename' => $trueFileName,
                        'shortfilename' => strlen($trueFileName) > 25 ? substr($trueFileName, 0, 20) . "*~" : $trueFileName,
                        'extension' => strrchr($file, "."),
                        'filesize' => $this->getFileSize($file),
                        'filedate' => date("Y.m.d", filemtime(Storage::path($file)))
                    ]);
                }
            }
        }

        $results = view('folder.search_results', compact('directories', 'files', 'current_folder'));

        return response()->json([
            'html' => $results->render(),
        ]);
    }

    public function mediapreview(Request $request)
    {
        $currentFolder = $request->current_folder;
        $fullfilename = $request->file_name;
        $checked = $request->checked;
        $fileNameNoExt = substr($fullfilename, 0, strripos($fullfilename, strrchr($fullfilename, ".")));
        $path = substr($this->getPath($currentFolder), 1);
        //Delete old sessions->previews
        $nowUnixInt = (int)now()->format("U");
        $oldFiles = [];
        //Check for preview folder
        if (Storage::disk('public')->exists('preview')) {
        } else {
            Storage::disk('public')->makeDirectory('preview');
        }
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(Storage::disk('public')->path('preview'))) as $filename) {
            array_push($oldFiles, $filename);
        }
        //Delete old files
        foreach (array_reverse($oldFiles) as $oldFile) {
            if ($oldFile->isFile()) {
                if (((int)$oldFile->getATime() + 7210) < $nowUnixInt) { // It's old - needs to be deleted
                    unlink($oldFile->getPathname());
                }
            }
            if ($oldFile->isLink()) {
                if (file_exists($oldFile->getPathname())) {
                    if (((int)$oldFile->getATime() + 7210) < $nowUnixInt) { // It's old - needs to be deleted
                        unlink($oldFile->getPathname());
                    }
                } else {
                    unlink($oldFile->getPathname());
                }
            }
        }
        //Delete old folders
        foreach (array_reverse($oldFiles) as $oldFile) {
            if ($oldFile->isDir()) {
                if (!(new \FilesystemIterator($oldFile))->valid()) {
                    rmdir($oldFile->getPath());
                }
            }
        }

        //Get array of previewable files
        $previewableFiles = Storage::disk('public')->files('thumb/' . $path);

        $isImage = array_search('thumb/' . $path . "/" . $fileNameNoExt . ".jpg", $previewableFiles);
        $isVideo = array_search('thumb/' . $path . "/" . $fileNameNoExt . ".mp4", $previewableFiles);
        //Get array of original files
        $allOriginalFiles = Storage::files($path);
        //Trim $previewableFiles array   
        $trimmedPreviewable = [];
        foreach ($previewableFiles as $previewable) {
            $noThumb = substr($previewable, 6, strlen($previewable));
            $noExtension = substr($noThumb, 0, strripos($noThumb, strrchr($noThumb, ".")));
            array_push($trimmedPreviewable, $noExtension);
        }
        //Trim $previewableFiles array
        $trimmedOriginal = [];
        $originalPosition = "nik";
        foreach ($allOriginalFiles as $key => $value) {
            if (strpos($value, $fullfilename) != false) {
                $originalPosition = $key;
            }
            $noExtension = substr($value, 0, strripos($value, strrchr($value, ".")));
            array_push($trimmedOriginal, $noExtension);
        }
        //Previewable keys of original files array
        $previewableKeys = array_keys(array_intersect($trimmedOriginal, $trimmedPreviewable));

        $thumbnailPosition = array_search($originalPosition, $previewableKeys);
        $pathToFolder = $this->getPath($currentFolder);
        //Generate next - previous links        
        $lastIndex = count($previewableFiles) - 1;
        if ($thumbnailPosition == 0) {
            if ($lastIndex == 0) {
                $leftChevron = $rightChevron = "inactive";
                $leftLink =  $rightLink = null;
            } else {
                $leftChevron = "inactive";
                $rightChevron = "active";
                $leftLink = null;
                $rightLink = substr($allOriginalFiles[$previewableKeys[1]], strlen($pathToFolder));
            }
        } else {
            $leftChevron = "active";
            $leftLink = substr($allOriginalFiles[$previewableKeys[$thumbnailPosition - 1]], strlen($pathToFolder));
            if (($thumbnailPosition + 1) > $lastIndex) {
                $rightLink = null;
                $rightChevron = "inactive";
            } else {
                $rightLink = substr($allOriginalFiles[$previewableKeys[$thumbnailPosition + 1]], strlen($pathToFolder));
                $rightChevron = "active";
            }
        }
        //Create folder for preview
        if (Storage::disk('public')->exists('preview/' . session()->getId() . $pathToFolder)) {
        } else {
            Storage::disk('public')->makeDirectory('preview/' . session()->getId() . $pathToFolder);
        }
        //get current user viewport and adapt preview
        $vw = round($request->vw * 65 / 100, 0);
        $vh = round($request->vh * 55 / 100, 0);
        $previewStyle = "max-height:" . $vh . "px;max-width:" . $vw . "px;"; //"max-height:450px;max-width:600px;"
        //IF $filePosition = FALSE - No preview possible
        if ($isImage !== false) {
            $fileimageurl = $this->generateImagePreview($pathToFolder, $fullfilename, $fileNameNoExt);
            $preview = view('folder.image_preview', compact('fileimageurl', 'fullfilename', 'thumbnailPosition', 'checked', 'previewStyle'));
            return response()->json([
                'html' => $preview->render(),
                'leftChevron' => $leftChevron,
                'rightChevron' => $rightChevron,
                'leftLink' => $leftLink,
                'rightLink' => $rightLink
            ]);
        } else {

            //create symbolic link in public folder
            $target = Storage::path($path . "/" . $fullfilename);
            $link = Storage::disk('public')->path('preview/' . session()->getId() . $pathToFolder . "/" . $fullfilename);
            if (!file_exists($link)) {
                $success = symlink($target, $link);
            } else {
                $success = true;
            }
            $filevideourl = 'storage/preview/' . session()->getId() . $pathToFolder . "/" . $fullfilename;
            $preview = view('folder.video_preview', compact('filevideourl', 'fullfilename', 'success', 'thumbnailPosition', 'checked', 'previewStyle'));
            return response()->json([
                'html' => $preview->render(),
                'leftChevron' => $leftChevron,
                'rightChevron' => $rightChevron,
                'leftLink' => $leftLink,
                'rightLink' => $rightLink
            ]);
        }
    }

    //PRIVATE FUNCTIONS
    private function getPath($current_folder)
    {
        $parent_search = explode("/", $current_folder);

        if ((isset($parent_search[1])) && ($parent_search[1] == "NShare")) {
            $path = $current_folder;                                               //Path to local network share           
        } else {
            $path = "/" . auth()->user()->name . $current_folder;                   //Path to folder of specific user               
        }
        return $path;
    }
    private function getParentFolder($current_folder)
    {

        $parent_search = explode("/", $current_folder);

        $parent_folder = null;

        if ((isset($parent_search[1])) && ($parent_search[1] == "NShare")) {
            if (count($parent_search) >= 2) {
                for ($i = 0; $i <= count($parent_search) - 2; $i++) {
                    $i != count($parent_search) - 2 ? $parent_folder .= $parent_search[$i] . "/" : $parent_folder .= $parent_search[$i];
                }
            }
        } else {
            if (count($parent_search) >= 2) {
                for ($i = 0; $i <= count($parent_search) - 2; $i++) {
                    $i != count($parent_search) - 2 ? $parent_folder .= $parent_search[$i] . "/" : $parent_folder .= $parent_search[$i];
                }
            }
        }
        return $parent_folder;
    }
    private function getBreadcrumbs($current_folder)
    {
        //Folder breadcrumbs
        $parent_search = explode("/", $current_folder);
        $breadcrumbs[0] = ['folder' => 'ROOT', 'path' => ''];
        for ($i = 1; $i <= count($parent_search) - 1; $i++) {
            $breadcrumbs[$i] = ['folder' => $parent_search[$i], 'path' => $breadcrumbs[$i - 1]['path'] . "/" . $parent_search[$i]];
        }
        return $breadcrumbs;
    }
    private function getFileSize($file)
    {
        $file_size = ['size' => round(File::size(Storage::path($file)), 2), 'type' => 'bytes'];
        if ($file_size['size'] > 1000) {
            $file_size = ['size' => round($file_size['size'] / 1024, 2), 'type' => 'Kb'];
        } else {
            return $file_size;
        }
        if ($file_size['size'] > 1000) {
            $file_size = ['size' => round($file_size['size'] / 1024, 2), 'type' => 'Mb'];
        } else {
            return $file_size;
        }
        if ($file_size['size'] > 1000) {
            $file_size = ['size' => round($file_size['size'] / 1024, 2), 'type' => 'Gb'];
        } else {
            return $file_size;
        }
        return $file_size;
    }
    private function getFileName($path)
    {
        return substr($path, strripos($path, "/") + 1, strlen($path));
    }
    private function getFolderSize($dir)
    {
        $allFiles = Storage::allFiles($dir);
        $thisFolderSize = 0;
        foreach ($allFiles as $file) {
            $thisFolderSize += File::size(Storage::path($file));
        }
        $folderSize = ['size' => round($thisFolderSize, 2), 'type' => 'bytes', 'byteSize' => round($thisFolderSize, 2)];
        if ($folderSize['size'] > 1000) {
            $folderSize = ['size' => round($folderSize['size'] / 1024, 2), 'type' => 'Kb', 'byteSize' => round($thisFolderSize, 2)];
        } else {
            return $folderSize;
        }
        if ($folderSize['size'] > 1000) {
            $folderSize = ['size' => round($folderSize['size'] / 1024, 2), 'type' => 'Mb', 'byteSize' => round($thisFolderSize, 2)];
        } else {
            return $folderSize;
        }
        if ($folderSize['size'] > 1000) {
            $folderSize = ['size' => round($folderSize['size'] / 1024, 2), 'type' => 'Gb', 'byteSize' => round($thisFolderSize, 2)];
        } else {
            return $folderSize;
        }
        return $folderSize;
    }
    private function getThumbnailImage($extension, $path, $fullfilename, $filename)
    {
        /* Cache file extensions */
        $fileExtensions = Cache::remember('extensions', 3600, function () {
            $extensionArray = [];

            if (($open = fopen(public_path() . "/extension.csv", "r")) !== FALSE) {

                while (($data = fgetcsv($open)) !== FALSE) {
                    array_push($extensionArray, $data);
                }
                fclose($open);
            }
            return $extensionArray;
        });

        /* SET FILE IMAGE*/
        $fileimage = ['thumb' => 'storage/img/file_100px.png', 'original' => false];
        //supported extensions
        $supportedExt = ['.jpg', '.jpeg', '.png', '.gif', '.xbm', '.wbmp', '.webp', '.bmp'];
        //Check if thumbnail already set
        $thumbfile = 'thumb' . $path . "/" . $filename . '.jpg';
        if (Storage::disk('public')->has($thumbfile)) {
            return ['thumb' => 'storage/' . $thumbfile, 'original' => true];
        }
        foreach ($fileExtensions as $fext) {
            if (array_search(strtolower($extension), $supportedExt) !== false) {
                //Managing files with image extenssion but not images
                $thumbnaill = $this->generateImageThumbnail($extension, $path, $fullfilename, $filename);
                if ($thumbnaill == null) {
                    return ['thumb' => ('storage/img/' . $fext[2] . '_100px.png'), 'original' => false]; //Choosing thumbnail from predefined
                } else {
                    return ['thumb' => $thumbnaill, 'original' => true];
                }
            }
            if (strtolower($extension) == strtolower($fext[0])) {
                return ['thumb' => ('storage/img/' . $fext[2] . '_100px.png'), 'original' => false]; //Choosing thumbnail from predefined
            }
        }

        return $fileimage;
    }
    private function getThumbnailVideo($extension, $path, $fullfilename, $filename)
    {
        //supported extensions
        $supportedExt = ['.3g2', '.3gp', '.3gp2', '.asf', '.avi', '.dvr-ms', '.flv', '.h261', '.h263', '.h264', '.m2t', '.m2ts', '.m4v', '.mkv', '.mod', '.mp4', '.mpg', '.mxf', '.tod', '.vob', '.webm', '.wmv', '.xmv'];

        if (array_search(strtolower($extension), $supportedExt) !== false) {
            //Check if video preview file already set
            $thumbfile = 'thumb' . $path . "/" . $filename . '.mp4';
            if (Storage::disk('public')->has($thumbfile)) {
                return 'storage/' . $thumbfile;
            } else {
                return $this->generateVideoThumbnail($extension, $path, $fullfilename, $filename);
            }
        }

        return null; //No video preview generated
    }
    private function generateImageThumbnail($extension, $path, $fullfilename, $filename)
    {
        $originalFile = Storage::path($path . "/" . $fullfilename);

        shell_exec("exiftran -a -i '$originalFile'");
        // Get image original size, 0->width, 1->height
        $imgsize_arr = getimagesize($originalFile);
        if ($imgsize_arr == 0) {
            return null;
        }
        $fileimage = null;
        //supported extensions
        $supportedExt = ['.jpg', '.jpeg', '.png', '.gif', '.xbm', '.wbmp', '.webp', '.bmp'];

        if (array_search(strtolower($extension), $supportedExt) !== false) {
            //Check if thumbnail already set
            $thumbfile = 'thumb' . $path . "/" . $filename . '.jpg';
            if (Storage::disk('public')->has($thumbfile)) {
                return 'storage/' . $thumbfile;
            } else {

                // Analize image to set crop 
                if ($imgsize_arr[0] > $imgsize_arr[1]) {
                    $cropSize = $imgsize_arr[1];
                    $cropX = ($imgsize_arr[0] - $imgsize_arr[1]) / 2;
                    $cropY = 0;
                } else {
                    $cropSize =  $imgsize_arr[0];
                    $cropX = 0;
                    $cropY = ($imgsize_arr[1] - $imgsize_arr[0]) / 2;
                }

                $img = imagecreatefromstring(file_get_contents($originalFile));
                $area = ["x" => $cropX, "y" => $cropY, "width" => $cropSize, "height" => $cropSize];
                $crop = imagecrop($img, $area);

                if (Storage::disk('public')->exists('thumb' . $path)) {
                } else {
                    Storage::disk('public')->makeDirectory('thumb' . $path);
                }
                $thumb = imagecreatetruecolor(100, 100);

                // Resize
                imagecopyresized($thumb, $crop, 0, 0, 0, 0, 100, 100, $cropSize, $cropSize);

                imagejpeg($thumb, Storage::disk('public')->path($thumbfile), 50);

                imagedestroy($img);
                imagedestroy($crop);
                imagedestroy($thumb);
                return 'storage/' . $thumbfile;
            }
        }

        return $fileimage;
    }
    private function generateVideoThumbnail($extension, $path, $fullfilename, $filename)
    {
        //supported extensions
        $supportedExt = ['.3g2', '.3gp', '.3gp2', '.asf', '.avi', '.dvr-ms', '.flv', '.h261', '.h263', '.h264', '.m2t', '.m2ts', '.m4v', '.mkv', '.mod', '.mp4', '.mpg', '.mxf', '.tod', '.vob', '.webm', '.wmv', '.xmv'];

        //dd($fullfilename);
        if (array_search(strtolower($extension), $supportedExt) !== false) {
            //Check if video preview file already set
            $thumbfile = 'thumb' . $path . "/" . $filename . '.mp4';
            if (Storage::disk('public')->has($thumbfile)) {
                return 'storage/' . $thumbfile;
            } else {
                //Create temporary thumbnail manipulation directory
                $thumbtempExists = Storage::disk('public')->exists('thumbtemp') == false ? Storage::disk('public')->makeDirectory('thumbtemp') : null;
                $pathToOriginal = Storage::path(substr($path, 1) . "/" . $fullfilename);
                $dur = shell_exec("ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '$pathToOriginal'");

                $seconds = round($dur);

                $thumb_0 = gmdate('H:i:s', $seconds / 8);
                $thumb_1 = gmdate('H:i:s', $seconds / 4);
                $thumb_2 = gmdate('H:i:s', $seconds / 2 + $seconds / 8);
                $thumb_3 = gmdate('H:i:s', $seconds / 2 + $seconds / 4);

                $path_clip = Storage::disk('public')->path('thumb' . $path . "/");
                //Create thumb directory if needed
                $thumbPathExists = Storage::disk('public')->exists('thumb' . $path) == false ? Storage::disk('public')->makeDirectory('thumb' . $path) : null;
                $path_clip2 = Storage::disk('public')->path('thumbtemp/');
                $filenameSha1 = sha1($filename);
                $preview_list_name = $path_clip2 . $filenameSha1 .  'list.txt';

                $preview_list = fopen($preview_list_name, "w");
                $preview_array = [];

                for ($i = 0; $i <= 3; $i++) {
                    $thumb = ${'thumb_' . $i};
                    $output_clip = $path_clip2 . $filenameSha1 . $i . ".p.mp4";

                    shell_exec("ffmpeg -i '$pathToOriginal' -an -ss $thumb -t 2 -vf 'scale=100:100:force_original_aspect_ratio=decrease,pad=100:100:(ow-iw)/2:(oh-ih)/2,setsar=1' -y  $output_clip");

                    if (file_exists($output_clip)) {
                        fwrite($preview_list, "file '" . $output_clip . "'\n");
                        array_push($preview_array, $output_clip);
                    }
                }
                fclose($preview_list);

                $thumbClip = $path_clip . $fullfilename;
                shell_exec("ffmpeg -f concat -safe 0 -i $preview_list_name -y '$thumbClip'");

                if (!empty($preview_array)) {
                    foreach ($preview_array as $v) {
                        unlink($v);
                    }
                }
                // remove preview list
                unlink($preview_list_name);

                return 'storage/' . $thumbfile;
            }
        }

        return null; //No video preview generated
    }
    private function generateImagePreview($pathToFolder, $fullfilename, $filename)
    {
        $previewFile = 'preview/' . session()->getId() . $pathToFolder . "/" . $filename . '.jpg';
        $previewImagePath =  Storage::disk('public')->path($previewFile);
        if (file_exists($previewFile)) {
            return 'storage/' . $previewFile;
        }

        // Get image original size, 0->width, 1->height
        $originalPath = Storage::path($pathToFolder . "/" . $fullfilename);
        shell_exec("exiftran -a -i '$originalPath'");
        $img = imagecreatefromstring(file_get_contents(Storage::path($pathToFolder . "/" . $fullfilename)));
        $imgsize_arr = getimagesize(Storage::path($pathToFolder . "/" . $fullfilename));
        $width = $imgsize_arr[0];
        $height = $imgsize_arr[1];
        if ($width > 600) {
            $scale = 600 / $width;
            $new_width = floor($width * $scale);
            $new_height = floor($height * $scale);
            $save_image = imagecreatetruecolor($new_width, $new_height);
            imagecopyresized($save_image, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

            imagejpeg($save_image, $previewImagePath, 50);
            imagedestroy($img);
            imagedestroy($save_image);
            return 'storage/' . $previewFile;
        } else {
            Storage::disk('public')->put($previewFile, Storage::get($originalPath));
            return 'storage/' . $previewFile;
        }
    }



    private function getStreamHeaders($path)
    {
        $fileToStream = Storage::path($path);
        $headers = [];
        array_push($headers, ['Content-Type' => mime_content_type($fileToStream)]);
        array_push($headers, ['Cache-Control' => 'max-age=2592000, public']);
        array_push($headers, ['Expires' => gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT']);
        array_push($headers, ['Last-Modified' => gmdate('D, d M Y H:i:s', @filemtime($fileToStream)) . ' GMT']);
        array_push($headers, ['Content-Length' => filesize($fileToStream)]);
        array_push($headers, ['Accept-Ranges' => '0-' . filesize($fileToStream) - 1]);
        array_push($headers, ['Content-Disposition' => 'attachment; filename=' . $this->getFileName($path)]);
        return $headers;
    }
    private function generateViewTree($directories, $current_folder, $activeBranch)
    {
        $view = '';
        foreach ($directories as $directory) {
            $withChildren = count($directory['children']) > 0 ? true : false;
            $view .= '<li>';

            if ($withChildren) {
                if ((count($activeBranch) > 0) && ($activeBranch[0] == $directory["label"])) {
                    $garbage = array_shift($activeBranch);
                    count($activeBranch) == 0 ?
                        $view .= '<span class="folder-tree-down-active"></span><a class="pink-text text-darken-3" href="'
                        :
                        $view .= '<span ';
                    $this->startsWithNShare($directory['path']) ?
                        $view .= 'class="folder-tree-nshare-down">' : $view .= 'class="folder-tree-down">'; //Check if NShare
                    $view .= '</span><a class="blue-grey-text text-darken-3" href="';
                    $view .= route('folder.root', ['current_folder' => $directory['path']]) .
                        '" data-folder="' . $directory['path'] . '" data-folder-view ="' . $directory['label'] . '">';
                    $view .= '<b><i>' . $directory['label'] . '</i></b></a>';
                    $this->startsWithNShare($directory['path']) ?
                        $view .= '<ul class="active-tree-nshare browser-default" style="padding-left: 20px;">' :
                        $view .= '<ul class="active-tree browser-default" style="padding-left: 20px;">';
                    $view .= $this->generateViewTree($directory['children'], $current_folder, $activeBranch);
                    $view .= '</ul>';
                } else {
                    $this->startsWithNShare($directory['path']) ?
                        $view .= '<span class="folder-tree-nshare"></span>' :
                        $view .= '<span class="folder-tree"></span>';
                    $view .= '<a class="blue-grey-text text-darken-3" href="' . route('folder.root', ['current_folder' => $directory['path']]) . '" data-folder="' . $directory['path'] . '" data-folder-view ="' . $directory['label'] . '">';
                    $view .= '<b><i>' . $directory['label'] . '</i></b></a>';
                    $this->startsWithNShare($directory['path']) ?
                        $view .= '<ul class="nested-nshare browser-default" style="padding-left: 20px;">' :
                        $view .= '<ul class="nested browser-default" style="padding-left: 20px;">';
                    $view .= $this->generateViewTree($directory['children'], $current_folder, $activeBranch);
                    $view .= '</ul>';
                }
            } else {

                if ((count($activeBranch) > 0) && ($activeBranch[0] == $directory["label"])) {
                    $this->startsWithNShare($directory['path']) ?
                        $view .= '<span class="folder-tree-nshare-empty-active"></span><a class="pink-text text-darken-3" href="' :
                        $view .= '<span class="folder-tree-empty-active"></span><a class="blue-grey-text text-darken-3" href="';
                } else {
                    $this->startsWithNShare($directory['path']) ?
                        $view .= '<span class="folder-tree-nshare-empty"></span><a class="blue-grey-text text-darken-3" href="' :
                        $view .= '<span class="folder-tree-empty"></span><a class="blue-grey-text text-darken-3" href="';
                }
                $view .= route('folder.root', ['current_folder' => $directory['path']]) . '" data-folder="' . $directory['path'] . '" data-folder-view ="' . $directory['label'] . '">';
                $view .= '<b><i>' . $directory['label'] . '</i></b></a>';
            }
            $view .= '</li>';
        }

        return $view;
    }

    private function generateShareViewTree($directories, $ushares)
    {
        $view = '';
        foreach ($directories as $directory) {
            if (count($ushares) >= 1) {
                $activeLink = false;
                foreach ($ushares as $likeShare) {
                    if (strpos($directory["path"], "/UShare" . $likeShare->path) !== false) {
                        $activeLink = true;
                        break;
                    }
                }
            }
            $withChildren = count($directory['children']) > 0 ? true : false;

            $view .= '<li>';
            if ($withChildren) {
                $view .= '<span class="folder-tree-ushare"></span>';
                if ($activeLink) {
                    $view .= '<a class="blue-grey-text text-darken-3" href="' . route('ushare.root', ['current_folder' => $directory['path']]) . '" data-folder="' . $directory['path'] . '" data-folder-view ="' . $directory['label'] . '">';
                    $view .= '<b><i>' . $directory['label'] . '</i></b></a>';
                } else {
                    $view .= '<a class="blue-grey-text text-darken-3" href="#" data-folder="' . $directory['path'] . '" data-folder-view ="' . $directory['label'] . '">';
                    $view .= $directory['label'] . '</a>';
                }

                $view .= '<ul class="nested-ushare browser-default" style="padding-left: 20px;">';
                $view .= $this->generateShareViewTree($directory['children'], $ushares);
                $view .= '</ul>';
            } else {
                $view .= '<span class="folder-tree-ushare-empty"></span>';
                if ($activeLink) {
                    $view .= '<a class="blue-grey-text text-darken-3" href="' . route('ushare.root', ['current_folder' => $directory['path']]) . '" data-folder="' . $directory['path'] . '" data-folder-view ="' . $directory['label'] . '">';
                    $view .= '<b><i>' . $directory['label'] . '</i></b></a>';
                } else {
                    $view .= '<a class="blue-grey-text text-darken-3" href="#" data-folder="' . $directory['path'] . '" data-folder-view ="' . $directory['label'] . '">';
                    $view .= $directory['label'] . '</a>';
                }
            }
            $view .= '</li>';
        }

        return $view;
    }

    private function convertPathsToTree($paths, $separator = '/', $parent = null)
    {
        return $paths
            ->groupBy(function ($parts) {
                return $parts[0];
            })->map(function ($parts, $key) use ($separator, $parent) {
                $childrenPaths = $parts->map(function ($parts) {
                    return array_slice($parts, 1);
                })->filter();

                return [
                    'label' => (string) $key,
                    'path' => $parent . $key,
                    'children' => $this->convertPathsToTree(
                        $childrenPaths,
                        $separator,
                        $parent . $key . $separator
                    ),
                ];
            })->values();
    }

    private function removeThumbs($file, $path)
    {
        $filenameNoExtenssion = substr($file, 0, strripos($file, strrchr($file, ".")));

        $videoThumbnail =  $path . "/" . $filenameNoExtenssion . ".mp4";
        $imageThumbnail =  $path . "/" . $filenameNoExtenssion . ".jpg";

        if (Storage::disk('public')->has('/thumb' . $imageThumbnail)) {   // Delete image thumbnails
            Storage::disk('public')->delete('/thumb' . $imageThumbnail);
        }
        if (Storage::disk('public')->has('/thumb' . $videoThumbnail)) {   // Delete video thumbnails
            Storage::disk('public')->delete('/thumb' . $videoThumbnail);
        }
    }

    private function prependStringToArrayElements($array, $string)
    {

        $newArray = [];
        foreach ($array as $element) {
            array_push($newArray, $string . $element);
        }
        return $newArray;
    }


    function startsWithNShare($path)
    {
        return strpos($path, "/NShare") === 0;
    }
}
