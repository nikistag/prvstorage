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
        $path = auth()->user()->name . $current_folder;

        //Get parent folder for Back button
        $parent_search = explode("/", $current_folder);
        $parent_folder = null;
        if (count($parent_search) >= 3) {
            for ($i = 0; $i <= count($parent_search) - 3; $i++) {
                $parent_folder .= $parent_search[$i] . "/";
            }
        }
        //Folder breadcrumbs
        $breadcrumbs[0] = ['folder' => 'ROOT', 'path' => '/'];
        for($i=1; $i<count($parent_search)-1; $i++){
            $breadcrumbs[$i] = ['folder' => $parent_search[$i], 'path' => $breadcrumbs[$i-1]['path'] . $parent_search[$i] . "/"];
        }

        //dd($parent_search);
       // dd($breadcrumbs);

        //Directory paths for options to move files and folders
        $full_directory_paths = Storage::disk('local')->allDirectories(auth()->user()->name);
        $private_directory_paths = [];
        foreach ($full_directory_paths as $dir) {
            if ($dir !==  auth()->user()->name . "/ZTemp") {
                array_push($private_directory_paths, substr($dir, strlen('/' . auth()->user()->name)));
            }
        }
        
        //Get folders an fils of current directory
        $dirs = Storage::disk('local')->directories($path);
        $fls = Storage::disk('local')->files($path);
        $directories = [];
        foreach ($dirs as $dir) {
            if ($dir !== auth()->user()->name . "/ZTemp") {
                array_push($directories, [
                    'foldername' => substr($dir, strlen($path)),
                    'shortfoldername' => strlen(substr($dir, strlen($path))) > 15 ? substr(substr($dir, strlen($path)), 0, 12) . "..." :  substr($dir, strlen($path)),
                ]);
            }
        }
        //dd($directories);
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

        return view('folder.root', compact('directories', 'files', 'disk_free_space', 'disk_total_space', 'quota',
                                            'current_folder', 'parent_folder', 'private_directory_paths', 'path',
                                        'breadcrumbs'));
    }

    public function newfolder(Request $request)
    {

        $current_folder = $request->current_folder;
        $path = auth()->user()->name . $current_folder;

        $new_folder = $request->input('newfolder');
        $new_folder_path = $path . "/" . $new_folder;

        Storage::disk('local')->makeDirectory($new_folder_path);

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'New folder created!');
    }
    public function editfolder(Request $request)
    {

        $current_folder = $request->current_folder;

        $path = auth()->user()->name . $current_folder;

        $old_path = $path . "/" . $request->input('oldfolder');
        $new_path = $path . "/" . $request->input('editfolder');

        Storage::disk('local')->move($old_path, $new_path);

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Folder renamed!');
    }
    public function moveFolder(Request $request)
    {

        $current_folder = $request->current_folder;
        $path = auth()->user()->name . $current_folder;

        $old_path = $path . "/" . $request->input('oldmovefolder');
        $new_path = '/' . auth()->user()->name . "/" . $request->input('target') . "/" . $request->input('oldmovefolder');

        if($request->has('foldercopy')){
            $done = (new Filesystem)->copyDirectory(Storage::path($old_path), Storage::path($new_path));
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Folder successfuly copied!');
        }else{
            Storage::disk('local')->move($old_path, $new_path);
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Folder successfuly moved!');
        }

        
    }
    public function remove(Request $request)
    {

        $current_folder = $request->current_folder;
        $path = auth()->user()->name . $current_folder;

        $garbage = $path . "/" . $request->input('folder');

        Storage::disk('local')->deleteDirectory($garbage);

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Folder successfuly removed!');
    }
    public function folderupload(Request $request)
    {
        if (($request->has('current_folder')) && ($request->current_folder != null)) {
            $current_folder = $request->current_folder;
        } else {
            $current_folder = null;
        }

        $current_folder = $request->current_folder;
        $path = auth()->user()->name . $current_folder;

        $name = $request->file('file')->getClientOriginalName();

        if ($request->input('newfolder') != "") {
            $new_folder = $request->input('newfolder');
            $new_folder_path = $path . "/" . $new_folder;
            Storage::disk('local')->makeDirectory($new_folder_path);
            $upload_path = Storage::disk('local')->putFileAs($new_folder_path, $request->file('file'), $name);
        } else {
            $upload_path = Storage::disk('local')->putFileAs($path, $request->file('file'), $name);
        }

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Upload successful!!');
    }
    public function emptytemp(Request $request)
    {
        $temp_path = '/' . auth()->user()->name . "/ZTemp";

        $shares = Share::select('path')->where('user_id', auth()->user()->id)->get();

        $temp_files = Storage::disk('local')->allFiles($temp_path);

        foreach ($temp_files as $file) {
            $filtered = $shares->where('path', $file);
            if (count($filtered) == 0) {
                Storage::delete($file);
            }
        }

        return redirect()->route('folder.root', ['current_folder' => '/'])->with('success', 'Temporary folder is clean!');
    }

    public function renameFile(Request $request)
    {

        $current_folder = $request->current_folder;
        $path = auth()->user()->name . $current_folder;

        $old_path = $path . "/" . $request->input('oldrenamefilename');
        $new_path = $path . "/" . $request->input('renamefilename');

        Storage::disk('local')->move($old_path, $new_path);

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly renamed!');
    }
    public function moveFile(Request $request)
    {

        $current_folder = $request->current_folder;
        $path = auth()->user()->name . $current_folder;

        $old_path = $path . $request->input('oldfilefolder');
        $new_path = auth()->user()->name . "/" . $request->input('targetfolder') . "/" . $request->input('oldfilefolder');

        if ($old_path == $new_path) {
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('warning', 'File already there!!!');
        } else {
            if($request->has('filecopy')){
                Storage::disk('local')->copy($old_path, $new_path);
                return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly copied!');
            }else{
                Storage::disk('local')->move($old_path, $new_path);
                return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly moved!');
            }
            
        }
    }
    public function removeFile(Request $request)
    {

        $current_folder = $request->current_folder;
        $path = auth()->user()->name . $current_folder;

        $garbage = $path . "/" . $request->input('filename');

        Storage::disk('local')->delete($garbage);

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly removed!');
    }

    public function fileupload(Request $request)
    {

        $current_folder = $request->current_folder;
        $path = auth()->user()->name . $current_folder;

        $name = $request->file('fileupload')->getClientOriginalName();
        $upload_path = Storage::disk('local')->putFileAs($path, $request->file('fileupload'), $name);

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Upload successful!!');
    }

    public function folderdownload(Request $request)
    {
        if ($request->has('path')) {
            $path = '/' . auth()->user()->name .  $request->path;
            $directory = $request->directory;

            $file_full_paths = Storage::disk('local')->allFiles($path);

            $directory_full_paths = Storage::disk('local')->allDirectories($path);

            $zip_directory_paths = [];
            foreach ($directory_full_paths as $dir) {
                array_push($zip_directory_paths, substr($dir, strlen($path)));
            }

            $zipFileName = 'zpd_' . $directory . '.zip';

            $zip_path = Storage::disk('local')->path('/' . auth()->user()->name . '/ZTemp/' . $zipFileName);

            // Creating file names and path names to be archived
            $files_n_paths = [];
            foreach ($file_full_paths as $fl) {
                array_push($files_n_paths, [
                    'name' => substr($fl, strripos($fl, '/') + 1),
                    'path' => Storage::disk('local')->path($fl),
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

    public function filedownload(Request $request)
    {
        if ($request->has('path')) {
            $path = '/' . auth()->user()->name . $request->path;
            return Storage::download($path);
        } else {
            return back()->with('error', 'File / Folder not found on server');
        }
    }

    public function multiupload(Request $request)
    {

        $current_folder = $request->current_folder;
        $path = auth()->user()->name . $current_folder;

        $name = $request->file('file')->getClientOriginalName();
        $upload_path = Storage::disk('local')->putFileAs($path, $request->file('file'), $name);
    }
    //PRIVATE FUNCTIONS
    private function getFileSize($file)
    {
        $file_size = ['size' => round(File::size(Storage::disk('local')->path($file)), 2), 'type' => 'bytes'];
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
}
