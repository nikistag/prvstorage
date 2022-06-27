<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use ZipArchive;
use App\Models\Share;

class FolderController extends Controller
{
    public function index()
    {
        $disk_free_space = round(disk_free_space(storage_path('app/prv/')) / 1073741824, 2);
        $disk_total_space = round(disk_total_space(storage_path('app/prv/')) / 1073741824, 2);
        $quota = round(($disk_total_space - $disk_free_space) * 100 / $disk_total_space, 0);
        return view('folder.index', compact('disk_free_space', 'disk_total_space', 'quota'));
    }
    public function root(Request $request)
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
                    'shortfoldername' => strlen(substr($dir, strlen($path))) > 15 ? substr(substr($dir, strlen($path)), 0, 12) . "..." :  substr($dir, strlen($path)),
                    'foldersize' => $this->getFolderSize($dir),
                ]);
            }
        }
        $NShare['foldersize'] = $this->getFolderSize('NShare');
        $ztemp['foldersize'] = $this->getFolderSize(auth()->user()->name . '/ZTemp');

        $files = [];
        foreach ($fls as $file) {
            $fullfilename = substr($file, strlen($path));
            array_push($files, [
                'fullfilename' =>  $fullfilename,
                'filename' => $filename = substr($fullfilename, 0, strripos($fullfilename, strrchr($fullfilename, "."))),
                'shortfilename' => strlen($filename) > 15 ? substr($filename, 0, 10) . "*~" : $filename,
                'extension' => strrchr($file, "."),
                'filesize' => $this->getFileSize($file)
            ]);
        }
        //Data to compute free space
        $disk_free_space = round(disk_free_space(storage_path('app/prv/')) / 1073741824, 2);
        $disk_total_space = round(disk_total_space(storage_path('app/prv/')) / 1073741824, 2);
        $quota = round(($disk_total_space - $disk_free_space) * 100 / $disk_total_space, 0);

        return view('folder.root', compact(
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
            'breadcrumbs'
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

            Storage::makeDirectory($new_folder_path);

            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'New folder created!');
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
            Storage::move($old_path, $new_path);

            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Folder renamed!');
        }
    }
    public function moveFolder(Request $request)
    {

        $current_folder = "/" . $request->input('target');

        $new_path = $this->getPath($current_folder) . "/" . $request->input('whichfolder');
        $old_path = $this->getPath($request->current_folder) . "/" . $request->input('whichfolder');

        //Check for duplicate folder
        if (Storage::exists($new_path)) {
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('warning', 'NO action done. Duplicate folder found!');
        }
        //Copy or move folder
        if ($request->has('foldercopy')) {
            $done = (new Filesystem)->copyDirectory(Storage::path($old_path), Storage::path($new_path));
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Folder successfuly copied!');
        } else {
            $done = Storage::move($old_path, $new_path);
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Folder successfuly moved!');
        }
    }

    public function remove(Request $request)
    {
        $current_folder = $request->current_folder;

        $path = $this->getPath($current_folder);
        $garbage = $path . "/" . $request->input('folder');
        Storage::deleteDirectory($garbage);
        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Folder successfuly removed!');
    }

    public function folderupload(Request $request)
    {

        $current_folder = $request->current_folder;
        $path = $this->getPath($current_folder);

        $name = $request->file('file')->getClientOriginalName();

        if ($request->input('newfolder') != "") {
            $new_folder = $request->input('newfolder');
            $new_folder_path = $path . "/" . $new_folder;
            Storage::makeDirectory($new_folder_path);
            $upload_path = Storage::putFileAs($new_folder_path, $request->file('file'), $name);
        } else {
            $upload_path = Storage::putFileAs($path, $request->file('file'), $name);
        }
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
                array_push($zip_directory_paths, substr($dir, strlen($path) - 1));
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
                    $zip->addFile($file['path']);
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

        $shares = Share::select('path')->where('user_id', auth()->user()->id)->get();

        $temp_files = Storage::allFiles($temp_path);

        foreach ($temp_files as $file) {
            $filtered = $shares->where('path', $file);
            if (count($filtered) == 0) {
                Storage::delete($file);
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

        Storage::move($old_path, $new_path);

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly renamed!');
    }

    public function moveFileBig(Request $request)
    {
        $current_folder = "/" . $request->input('targetfolderbig');
        $path = $this->getPath($request->current_folder_big);

        $old_path = $path . "/" . $request->input('file_big');
        $new_path = $this->getPath($current_folder . "/" . $request->input('file_big'));

        //Check for duplicate file
        if (Storage::exists($new_path)) {
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('warning', 'File already there!');
        } else {
            if ($request->has('filecopy')) {   //Check if copy or move file
                $done = Storage::copy($old_path, $new_path);
                return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly copied!');
            } else {
                $done = Storage::move($old_path, $new_path);
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
                    /* return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly copied!'); */
                } else {
                    $done = Storage::move($old_path, $new_path);
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

        Storage::delete($garbage);

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly removed!');
    }
    public function removeFileMulti(Request $request)
    {
        $current_folder = $request->current_folder;
        $path = $this->getPath($current_folder);

        foreach ($request->input("filesDelete") as $file) {

            $garbage = $path . "/" . $file;
            Storage::delete($garbage);
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
            $path = '/' . auth()->user()->name . $request->path;
            return Storage::download($path);
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
       // dd($parent_folder);
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
}
