<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use ZipArchive;
use App\Models\Share;
use Illuminate\Support\Facades\Cache;

class FolderController extends Controller
{
    public function index()
    {
        $disk_free_space = round(disk_free_space(config('filesystems.disks.local.root')) / 1073741824, 2);
        $disk_total_space = round(disk_total_space(config('filesystems.disks.local.root')) / 1073741824, 2);
        $quota = round(($disk_total_space - $disk_free_space) * 100 / $disk_total_space, 0);
        return view('folder.index', compact('disk_free_space', 'disk_total_space', 'quota'));
    }
    public function root(Request $request)
    {
        $current_folder = $request->current_folder;

        $path = $this->getPath($current_folder);

        $breadcrumbs = $this->getBreadcrumbs($current_folder);

        //Directory paths for options to move files and folders
        $full_private_directory_paths = Storage::allDirectories(auth()->user()->name);
        $directory_paths = [];
        foreach ($full_private_directory_paths as $dir) {
            if (($dir !==  auth()->user()->name . "/ZTemp") && ($dir !==  auth()->user()->name . "/Trash")) {
                array_push($directory_paths, substr($dir, strlen('/' . auth()->user()->name)));
            }
        }

        $share_directory_paths = Storage::allDirectories('NShare');
        array_push($directory_paths, "NShare");
        foreach ($share_directory_paths as $dir) {
            array_push($directory_paths, $dir);
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
                'fileimageurl' => $this->getPreviewImage($extensionWithDot, $path, $fullfilename, $filename),
                'filevideourl' => $this->getPreviewVideo($extensionWithDot, $path, $fullfilename, $filename),
                'filesize' => $this->getFileSize($file)
            ]);
        }
        //Data to compute free space
        $disk_free_space = round(disk_free_space(config('filesystems.disks.local.root')) / 1073741824, 2);
        $disk_total_space = round(disk_total_space(config('filesystems.disks.local.root')) / 1073741824, 2);
        $quota = round(($disk_total_space - $disk_free_space) * 100 / $disk_total_space, 0);
        //Generate folder tree view
        $folderTreeView = '<div class="collection left-align">' . $this->generateFolderTree($full_private_directory_paths, $path, '') . '</div>'; //modal variant - OPTIMIZED
        //Remove ZTemp folder from specific folder tree view
        $stringToRemove = '<a class="collection-item blue-grey-text text-darken-3" href="' . route('folder.root', ["current_folder" => "/ZTemp"]) . '" data-folder="ZTemp"><span class="black-text">-</span><i class="material-icons orange-text">folder</i>ZTemp</a>';
        $folderTreeViewTemp = str_replace($stringToRemove, "", $folderTreeView);
        $treeMoveFolder = str_replace("collection-item blue-grey-text text-darken-3", "collection-item blue-grey-text text-darken-3 tree-move-folder", $folderTreeViewTemp);
        $treeMoveFile = str_replace("collection-item blue-grey-text text-darken-3", "collection-item blue-grey-text text-darken-3 tree-move-file", $folderTreeViewTemp);
        $treeMoveMulti = str_replace("collection-item blue-grey-text text-darken-3", "collection-item blue-grey-text text-darken-3 tree-move-multi", $folderTreeViewTemp);
        return view('folder.root', compact(
            'directories',
            'files',
            'disk_free_space',
            'disk_total_space',
            'quota',
            'current_folder',
            'directory_paths',
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

    public function newfolder(Request $request)
    {
        $current_folder = $request->current_folder;

        //Forbid creation of Restricted folder name 'NShare'
        if (($request->input('newfolder') == 'NShare') || ($request->input('newfolder') == 'ZTemp')) {
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('error', 'Folder names @NShare and @ZTemp are restricted!!!');
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
    public function editfolder(Request $request)
    {
        $current_folder = $request->current_folder;

        //Forbid creation of Restricted folder name 'NShare'
        if (($request->input('editfolder') == 'NShare') || ($request->input('editfolder') == 'ZTemp')) {
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('error', 'Folder names @NShare and @ZTemp are restricted!!!');
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
    public function moveFolder(Request $request)
    {

        $current_folder = $request->input('target') == "" ? "" : "/" . $request->input('target');

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

    public function remove(Request $request)
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

            //dd($files_n_paths);
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

        $current_folder = $request->input('whereToFolder') == "" ? "" : "/" . $request->input('whereToFolder');
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

        $current_folder = "/" . $request->input('targetfoldermulti');
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
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly copied!');
        } else {
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly moved!');
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
        if (Storage::disk('public')->has('/thumb' . $garbage)) {
            Storage::disk('public')->delete('/thumb' . $garbage);
        }

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly removed!');
    }
    public function removeFileMulti(Request $request)
    {
        $current_folder = $request->current_folder;
        $path = $this->getPath($current_folder);

        foreach ($request->input("filesDelete") as $file) {

            $garbage = $path . "/" . $file;
            //main
            Storage::delete($garbage);
            //thumbs
            if (Storage::disk('public')->has('/thumb' . $garbage)) {
                Storage::disk('public')->delete('/thumb' . $garbage);
            }
        }

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Files successfuly removed!');
    }

    public function fileupload(Request $request)
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

        $parent_folder = $this->getParentFolder($current_folder);

        $path = $this->getPath($current_folder);

        $breadcrumbs = $this->getBreadcrumbs($current_folder);

        //Directory paths for options to move files and folders
        $full_private_directory_paths = Storage::allDirectories(auth()->user()->name);
        $directory_paths = [];
        foreach ($full_private_directory_paths as $dir) {
            if (($dir !==  auth()->user()->name . "/ZTemp") && ($dir !==  auth()->user()->name . "/Trash")) {
                array_push($directory_paths, substr($dir, strlen('/' . auth()->user()->name)));
            }
        }
        $share_directory_paths = Storage::allDirectories('NShare');
        array_push($directory_paths, "NShare");
        foreach ($share_directory_paths as $dir) {
            array_push($directory_paths, $dir);
        }

        //Get folders an fils of current directory
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
                'fileimageurl' => $this->getPreviewImage($extensionWithDot, $path, $fullfilename, $filename),
                'filevideourl' => $this->getPreviewVideo($extensionWithDot, $path, $fullfilename, $filename),
                'filesize' => $this->getFileSize($file)
            ]);
        }
        //Data to compute free space
        $disk_free_space = round(disk_free_space(config('filesystems.disks.local.root')) / 1073741824, 2);
        $disk_total_space = round(disk_total_space(config('filesystems.disks.local.root')) / 1073741824, 2);
        $quota = round(($disk_total_space - $disk_free_space) * 100 / $disk_total_space, 0);
        //Generate folder tree view
        $folderTreeView = '<div class="collection left-align">' . $this->generateFolderTree($full_private_directory_paths, $path, '') . '</div>'; //modal variant - OPTIMIZED
        //Remove ZTemp folder from specific folder tree view        
        $stringToRemove = '<a class="collection-item blue-grey-text text-darken-3" href="' . route('folder.root', ["current_folder" => "/ZTemp"]) . '" data-folder="ZTemp"><span class="black-text">-</span><i class="material-icons orange-text">folder</i>ZTemp</a>';
        $folderTreeViewTemp = str_replace($stringToRemove, "", $folderTreeView);
        $treeMoveFolder = str_replace("collection-item blue-grey-text text-darken-3", "collection-item blue-grey-text text-darken-3 tree-move-folder", $folderTreeViewTemp);
        $treeMoveFile = str_replace("collection-item blue-grey-text text-darken-3", "collection-item blue-grey-text text-darken-3 tree-move-file", $folderTreeViewTemp);
        $treeMoveMulti = str_replace("collection-item blue-grey-text text-darken-3", "collection-item blue-grey-text text-darken-3 tree-move-multi", $folderTreeViewTemp);

        return view('folder.searchForm', compact(
            'directories',
            'files',
            'disk_free_space',
            'disk_total_space',
            'quota',
            'current_folder',
            'parent_folder',
            'directory_paths',
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
        $directory_paths = [];
        foreach ($full_private_directory_paths as $dir) {
            if (($dir !==  auth()->user()->name . "/ZTemp") && ($dir !==  auth()->user()->name . "/Trash")) {
                array_push($directory_paths, substr($dir, strlen('/' . auth()->user()->name)));
            }
        }

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
                    'filesize' => $this->getFileSize($file)
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
                        'filesize' => $this->getFileSize($file)
                    ]);
                }
            }
        }

        $results = view('folder.search_results', compact('directories', 'files', 'current_folder'));

        return response()->json([
            'html' => $results->render(),
        ]);
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
    private function getPreviewImage($extension, $path, $fullfilename, $filename)
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
        $fileimage = 'storage/img/file_100px.png';
        //supported extensions
        $supportedExt = ['.jpg', '.jpeg', '.png', '.gif', '.xbm', '.wbmp', '.webp', '.bmp'];
        foreach ($fileExtensions as $fext) {
            if (array_search(strtolower($extension), $supportedExt) !== false) {
                //Check if thumbnail already set
                $thumbfile = 'thumb' . $path . "/" . $filename . '.jpg';
                if (Storage::disk('public')->has($thumbfile)) {
                    return 'storage/' . $thumbfile;
                } else {

                    // Get image original size, 0->width, 1->height
                    $imgsize_arr = getimagesize(Storage::path($path . "/" . $fullfilename));
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

                    $img = imagecreatefromstring(file_get_contents(Storage::path($path . "/" . $fullfilename)));
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
            if (strtolower($extension) == strtolower($fext[0])) {
                return ('storage/img/' . $fext[2] . '_100px.png'); //Choosing thumbnail from predefined
            }
        }

        return $fileimage;
    }
    private function getPreviewVideo($extension, $path, $fullfilename, $filename)
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

        //supported extensions
        $supportedExt = ['.3g2', '.3gp', '.3gp2', '.asf', '.avi', '.dvr-ms', '.flv', '.h261', '.h263', '.h264', '.m2t', '.m2ts', '.m4v', '.mkv', '.mod', '.mp4', '.mpg', '.mxf', '.tod', '.vob', '.webm', '.wmv', '.xmv'];

        //dd($fullfilename);
        if (array_search(strtolower($extension), $supportedExt) !== false) {
            //Check if video preview file already set
            $thumbfile = 'thumb' . $path . "/" . $filename . '.mp4';
            if (Storage::disk('public')->has($thumbfile)) {

                return 'storage/' . $thumbfile;
            } else {
                $pathToOriginal = Storage::path($path . "/" . $fullfilename);

                $dur = shell_exec("ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '$pathToOriginal'");
                $seconds = round($dur);

                $thumb_0 = gmdate('H:i:s', $seconds / 8);
                $thumb_1 = gmdate('H:i:s', $seconds / 4);
                $thumb_2 = gmdate('H:i:s', $seconds / 2 + $seconds / 8);
                $thumb_3 = gmdate('H:i:s', $seconds / 2 + $seconds / 4);

                $path_clip = Storage::disk('public')->path('thumb' . $path . "/");

                $preview_list = fopen($path_clip . 'list.txt', "w");
                $preview_array = [];

                for ($i = 0; $i <= 3; $i++) {
                    $thumb = ${'thumb_' . $i};
                    shell_exec("ffmpeg -i '$pathToOriginal' -an -ss $thumb -t 2 -vf 'scale=100:100:force_original_aspect_ratio=decrease,pad=100:100:(ow-iw)/2:(oh-ih)/2,setsar=1' -y $path_clip/$i.p.mp4");

                    $output = $path_clip . $i . '.p.mp4';

                    if (file_exists($output)) {
                        fwrite($preview_list, "file '" . $output . "'\n");
                        array_push($preview_array, $output);
                    }
                }

                fclose($preview_list);

                shell_exec("ffmpeg -f concat -safe 0 -i $path_clip/list.txt -y $path_clip/$fullfilename");

                if (!empty($preview_array)) {
                    foreach ($preview_array as $v) {
                        unlink($v);
                    }
                }
                // remove preview list
                unlink($path_clip . 'list.txt');

                return 'storage/' . $thumbfile;
            }
        }

        return null; //No video preview generated
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
    private function generateFolderTree($directories, $path, $trail)
    {
        $html = '';
        $html .= '<a class="collection-item blue-grey-text text-darken-3';
        $html .= $path == '/' . auth()->user()->name ? ' active"' : '"';
        $html .= 'href="' . route('folder.root', ['current_folder' => $trail]) . '" data-folder="Root" data-folder-view="Root"><i class="material-icons orange-text">folder</i>Root</a>';
        //dd($directories);
        foreach ($directories as $directory) {
            $ceva = explode('/', $directory);
            $html .= '<a class="collection-item blue-grey-text text-darken-3';
            $html .= $path == '/' . $directory ? ' active"' : '"';
            $html .= ' href="' . route('folder.root', ['current_folder' => $this->getFolderURLParam($ceva)]) . '" data-folder="' . substr($this->getFolderURLParam($ceva), 1) . '" data-folder-view ="' . substr(strrchr($directory, "/"), 1, strlen(strrchr($directory, "/")) - 1) . '">';
            for ($i = 0; $i < count($ceva) - 1; $i++) {
                $html .= '<span class="black-text">-</span>';
            }
            $html .= '<i class="material-icons orange-text">folder</i>';
            $html .= substr(strrchr($directory, "/"), 1, strlen(strrchr($directory, "/")) - 1);
            $html .= '</a>';
        }
        $html .= '<a class="collection-item blue-grey-text text-darken-3';
        $html .= $path == '/NShare'  ? ' active"' : '"';
        $html .= 'href="' . route('folder.root', ['current_folder' => '/NShare']) . '" data-folder="NShare" data-folder-view="NShare"><i class="material-icons blue-text">folder</i>NShare</a>';
        return $html;
    }

    private function getFolderURLParam($explodedFolder)
    {
        $back = '';
        for ($i = 1; $i < count($explodedFolder); $i++) {
            $back .= '/' . $explodedFolder[$i];
        }
        return $back;
    }
}
