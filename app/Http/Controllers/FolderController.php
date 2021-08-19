<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class FolderController extends Controller
{
    public function index()
    {
        $path = 'app/prv/' . auth()->user()->name;
        $directories = Storage::allDirectories(storage_path($path));
        $disk_free_space = round(disk_free_space(storage_path($path)) / 1073741824, 2);
        $disk_total_space = round(disk_total_space(storage_path($path)) / 1073741824, 2);
        $quota = round(($disk_total_space - $disk_free_space) * 100 / $disk_total_space, 0);
        return view('folder.index', compact('directories', 'disk_free_space', 'disk_total_space', 'quota'));
    }

    public function root(Request $request)
    {
        if (($request->has('current_folder')) && ($request->current_folder != null)) {
            $path = 'app/prv/' . auth()->user()->name . $request->current_folder;
            $current_folder = $request->current_folder;
        } else {
            $path = 'app/prv/' . auth()->user()->name;
            $current_folder = null;
        }

        $parent_folder = "";
        if ($pos = strrpos($current_folder,  '/') > 0) {

            $parent_folder = substr($current_folder, 0, strrpos($current_folder,  '/'));
        } else {
            $parent_folder = null;
        }

        $full_directory_paths = Storage::disk('local')->allDirectories('app/prv/' . auth()->user()->name);

        $private_directory_paths = [];
        foreach($full_directory_paths as $dir){
            array_push($private_directory_paths, substr($dir, strlen('app/prv/' . auth()->user()->name) + 1));
        }

        $dirs = Storage::disk('local')->directories($path);

        $fls = Storage::disk('local')->files($path);

        //Remove folder paths
        $directories = [];
        foreach ($dirs as $dir) {
            array_push($directories, substr($dir, strlen($path) + 1));
        }
        //Remove file paths
        $files = [];
        foreach ($fls as $file) {
            array_push($files, substr($file, strlen($path) + 1));
        }


        $disk_free_space = round(disk_free_space(storage_path('app/prv/' . auth()->user()->name)) / 1073741824, 2);
        $disk_total_space = round(disk_total_space(storage_path('app/prv/' . auth()->user()->name)) / 1073741824, 2);
        $quota = round(($disk_total_space - $disk_free_space) * 100 / $disk_total_space, 0);

        return view('folder.root', compact('directories', 'files', 'disk_free_space', 'disk_total_space', 'quota', 'current_folder', 'parent_folder', 'private_directory_paths'));
    }

    public function newfolder(Request $request)
    {
        if (($request->has('current_folder')) && ($request->current_folder != null)) {
            $path = 'app/prv/' . auth()->user()->name . $request->current_folder;
            $current_folder = $request->current_folder;
        } else {
            $path = 'app/prv/' . auth()->user()->name;
            $current_folder = null;
        }
        $new_folder = $request->input('newfolder');
        $new_folder_path = $path . "/" . $new_folder;

        Storage::disk('local')->makeDirectory($new_folder_path);

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'New folder created!');
    }
    public function editfolder(Request $request)
    {
        if (($request->has('current_folder')) && ($request->current_folder != null)) {
            $path = 'app/prv/' . auth()->user()->name . $request->current_folder;
            $current_folder = $request->current_folder;
        } else {
            $path = 'app/prv/' . auth()->user()->name;
            $current_folder = null;
        }
        $old_path = $path . "/" . $request->input('oldfolder');
        $new_path = $path . "/" . $request->input('editfolder');

        Storage::disk('local')->move($old_path, $new_path);

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Folder renamed!');
    }
    public function moveFolder(Request $request)
    {
        //dd($request->input());
        if (($request->has('current_folder')) && ($request->current_folder != null)) {
            $path = 'app/prv/' . auth()->user()->name . $request->current_folder;
            $current_folder = $request->current_folder;
        } else {
            $path = 'app/prv/' . auth()->user()->name;
            $current_folder = null;
        }
        $old_path = $path . "/" . $request->input('oldmovefolder');
        $new_path = 'app/prv/' . auth()->user()->name . "/" . $request->input('target') ."/". $request->input('oldmovefolder');

        Storage::disk('local')->move($old_path, $new_path);

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Folder successfuly moved!');
    }
    public function remove(Request $request)
    {
        if (($request->has('current_folder')) && ($request->current_folder != null)) {
            $path = 'app/prv/' . auth()->user()->name . $request->current_folder;
            $current_folder = $request->current_folder;
        } else {
            $path = 'app/prv/' . auth()->user()->name;
            $current_folder = null;
        }
        $garbage = $path . "/" . $request->input('folder');

        Storage::disk('local')->deleteDirectory($garbage);

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Folder successfuly removed!');
    }

    public function renameFile(Request $request)
    {
        //dd($request->input());
        if (($request->has('current_folder')) && ($request->current_folder != null)) {
            $path = 'app/prv/' . auth()->user()->name . $request->current_folder;
            $current_folder = $request->current_folder;
        } else {
            $path = 'app/prv/' . auth()->user()->name;
            $current_folder = null;
        }
        $old_path = $path . "/" . $request->input('oldrenamefilename');
        $new_path = $path . "/" . $request->input('renamefilename');

        Storage::disk('local')->move($old_path, $new_path);

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly renamed!');
    }
    public function moveFile(Request $request)
    {
       // dd($request->input());
        if (($request->has('current_folder')) && ($request->current_folder != null)) {
            $path = 'app/prv/' . auth()->user()->name . $request->current_folder;
            $current_folder = $request->current_folder;
        } else {
            $path = 'app/prv/' . auth()->user()->name;
            $current_folder = null;
        }
        $old_path = $path . "/" . $request->input('oldfilefolder');
        $new_path = 'app/prv/' . auth()->user()->name . "/" . $request->input('targetfolder') ."/". $request->input('oldfilefolder');

        //dd($old_path."-->".$new_path);

        //$old_path = $path . "/" . $request->input('oldmovefilename');
        //$new_path = $path . "/" . $request->input('movefilename');

        Storage::disk('local')->move($old_path, $new_path);

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly moved!');
    }
    public function removeFile(Request $request)
    {
        if (($request->has('current_folder')) && ($request->current_folder != null)) {
            $path = 'app/prv/' . auth()->user()->name . $request->current_folder;
            $current_folder = $request->current_folder;
        } else {
            $path = 'app/prv/' . auth()->user()->name;
            $current_folder = null;
        }
        $garbage = $path . "/" . $request->input('filename');

        Storage::disk('local')->delete($garbage);

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly removed!');
    }

    public function fileupload(Request $request)
    {

        if (($request->has('current_folder')) && ($request->current_folder != null)) {
            $path = 'app/prv/' . auth()->user()->name . $request->current_folder;
            $current_folder = $request->current_folder;
        } else {
            $path = 'app/prv/' . auth()->user()->name;
            $current_folder = null;
        }

        $name = $request->file('fileupload')->getClientOriginalName();
        $upload_path = Storage::disk('local')->putFileAs($path, $request->file('fileupload'), $name);

        return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Upload successful!!');
    }

    public function multiupload(Request $request)
    {
        
        if (($request->has('current_folder')) && ($request->current_folder != null)) {
            $path = 'app/prv/' . auth()->user()->name . $request->current_folder;
            $current_folder = $request->current_folder;
        } else {
            $path = 'app/prv/' . auth()->user()->name;
            $current_folder = null;
        }

        $name = $request->file('file')->getClientOriginalName();
        $upload_path = Storage::disk('local')->putFileAs($path, $request->file('file'), $name);

        
    }
}
