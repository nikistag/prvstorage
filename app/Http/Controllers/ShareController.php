<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Share;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Illuminate\Support\Facades\File;
use DateTime;


class ShareController extends Controller
{
    public function index()
    {
        $old_shares = Share::where('user_id', auth()->user()->id)->get();

        foreach ($old_shares as $share) {
            if ($share->expiration < time()) {
                $share->status = 'expired';
                $share->save();
            }
        }
        $shares = Share::where('user_id', auth()->user()->id)->orderBy('expiration', 'desc')->get();

        return view('share.index', compact('shares'));
    }

    public function file(Request $request)
    {

        $sharedFile = $request->input('fileToShareInput');

        $share_name = substr($sharedFile, strripos($sharedFile, '/') + 1);

        $zip_file_name = 'zpd_' . $share_name . "_" . time() . ".zip";

        $zip_path = Storage::path(auth()->user()->name . '/ZTemp/' . $zip_file_name);

        $db_zip_path = '/' . auth()->user()->name . '/ZTemp/' . $zip_file_name;

        //Create archive
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            // Add File in ZipArchive
            $zip->addFile(Storage::path($sharedFile), $share_name);
            // Close ZipArchive     
            $zip->close();
        }
        //Populate share model and save it
        $share = new Share();
        $share->user_id = auth()->user()->id;
        $share->path = $db_zip_path;
        $share->code = hash('ripemd160', time());
        $share->type = "file";
        $share->storage = File::size(Storage::path($sharedFile));
        if (strlen($share_name) >= 200) {
            $share->composition = substr($share_name, 0, 196) . "...";
        } else {
            $share->composition = $share_name;
        }
        if ($request->has('unlimited')) {
            $share->unlimited = 1;
        }
        //If user did not select an expiration date, make it available for 24 hours
        if ($request->input('expiration') == '') {
            $share->expiration = time() + (60 * 60 * 24);
        } else {
            $share->expiration = (int)date_create_from_format("M d, Y", $request->input('expiration'))->format("U");
        }

        if ($share->expiration <= time()) {
            $share->status = 'expired';
        } else {
            $share->status = 'active';
        }

        $share->save();

        return view('share.create', compact('share', 'share_name'));
    }
    public function fileMulti(Request $request)
    {

        $share_name = $request->input("fileshare")[0] . "-multi";
        $zip_file_name = 'zpd_' . $share_name . "_" . time() . ".zip";

        $zip_path = Storage::path(auth()->user()->name . '/ZTemp/' . $zip_file_name);
        $db_zip_path = '/' . auth()->user()->name . '/ZTemp/' . $zip_file_name;
        $currentFolder = $this->getPath($request->input("current_folder_multifileshare"));
        
        //Create archive
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            // Add File in ZipArchive
            foreach ($request->input("fileshare") as $file) {
                $zip->addFile(str_replace("//", "/", Storage::path($currentFolder . "/" . $file)), $file);
            }
            // Close ZipArchive     
            $zip->close();
        }

        //Populate share model and save it
        $share = new Share();
        $share->user_id = auth()->user()->id;
        $share->path = $db_zip_path;
        $share->code = hash('ripemd160', time());
        $share->type = "multiple files";
        $share->storage = File::size(Storage::path(auth()->user()->name . '/ZTemp/' . $zip_file_name));
        if (strlen($request->input("composition_multifileshare")) >= 200) {
            $share->composition = substr($request->input("composition_multifileshare"), 0, 185) . "... and more";
        } else {
            $share->composition = $request->input("composition_multifileshare");
        }
        if ($request->has('unlimited_multifileshare')) {
            $share->unlimited = 1;
        }
        //If user did not select an expiration date, make it available for 24 hours
        if ($request->input('expiration') == '') {
            $share->expiration = time() + (60 * 60 * 24);
        } else {
            $share->expiration = (int)date_create_from_format("M d, Y", $request->input('expiration'))->format("U");
        }

        if ($share->expiration <= time()) {
            $share->status = 'expired';
        } else {
            $share->status = 'active';
        }

        $share->save();

        return view('share.create', compact('share', 'share_name'));
    }

    public function folder(Request $request)
    {

        $sharedFolder = $request->input('folderToShareInput');

        $share_name = substr($sharedFolder, strripos($sharedFolder, '/') + 1);
        $zip_file_name = 'zpd_' . $share_name . "_" . time() . ".zip";
        $zip_path = Storage::path(auth()->user()->name . '/ZTemp/' . $zip_file_name);
        $db_zip_path = '/' . auth()->user()->name . '/ZTemp/' . $zip_file_name;

        $file_full_paths = Storage::allFiles($sharedFolder);
        $directory_full_paths = Storage::allDirectories($sharedFolder);

        $zip_directory_paths = [];
        foreach ($directory_full_paths as $dir) {
            array_push($zip_directory_paths, substr($dir, strlen($sharedFolder)));
        }
        // Creating file names and path names to be archived
        $files_n_paths = [];
        foreach ($file_full_paths as $fl) {
            array_push($files_n_paths, [
                'name' => substr($fl, strripos($fl, '/') + 1),
                'path' => Storage::path($fl),
                'zip_path' => substr($fl, strlen($sharedFolder)),
            ]);
        }
        // Create archive
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

        //Populate share model and save it
        $share = new Share();
        $share->user_id = auth()->user()->id;
        $share->path = $db_zip_path;
        $share->code = hash('ripemd160', time());
        $share->type = "folder";
        $share->storage = File::size($zip_path);
        if (strlen($share_name) >= 200) {
            $share->composition = substr($share_name, 0, 196) . "...";
        } else {
            $share->composition = $share_name;
        }
        if ($request->has('unlimited_folder')) {
            $share->unlimited = 1;
        }
        //If user did not select an expiration date, make it available for 24 hours
        if ($request->input('expiration') == '') {
            $share->expiration = time() + (60 * 60 * 24);
        } else {
            $share->expiration = (int)date_create_from_format("M d, Y", $request->input('expiration'))->format("U");
        }

        if ($share->expiration <= time()) {
            $share->status = 'expired';
        } else {
            $share->status = 'active';
        }

        $share->save();

        return view('share.create', compact('share', 'share_name'));
    }

    public function download(Request $request)
    {
        if ($request->has('code')) {
            $share = Share::where('code', $request->code)->first();
            //Deal with expired shares
            if ($share->expiration < time()) {
                $share->status = "expired";
                $share->save();
                return redirect("/")->with("error", "SORRY! Download link expired!!!");
            }
            if ($share->unlimited == 0) {
                if ($share->status == "used") {
                    return redirect("/")->with("error", "One time download link already used!!!");
                } else {
                    $share->status = 'used';
                    $share->save();
                    return Storage::download($share->path);
                }
            } else {
                return Storage::download($share->path);
            }
        } else {
            return redirect("/")->with("error", "Download link not available!!!");
        }
    }

    public function update(Request $request, Share $share)
    {

        //Update share model and save it
        if ($request->has('unlimited')) {
            $share->unlimited = 1;
        } else {
            $share->unlimited = 0;
        }
        //If user did not select an expiration date, make it available for 24 hours
        if ($request->input('expiration') == '') {
            $share->expiration = time() + (60 * 60 * 24);
        } else {
            $share->expiration = (int)date_create_from_format("M d, Y", $request->input('expiration'))->format("U");
        }

        if ($share->expiration <= time()) {
            $share->status = 'expired';
        } else {
            $share->status = 'active';
        }

        $share->save();

        return redirect(route('share.index'));
    }

    public function delete(Request $request)
    {

        $share = Share::where('path', $request->input('filename'))->first();

        Storage::delete($share->path);

        $share->delete();

        return redirect(route('share.index'))->with('success', 'Shared file/folder removed');
    }

    public function purge()
    {

        $shares = Share::where('user_id', auth()->user()->id)->get();

        if (count($shares) > 0) {
            foreach ($shares as $share) {
                Storage::delete($share->path);

                $share->delete();
            }
        }

        return redirect(route('share.index'))->with('success', 'All shares have been purged');
    }

    //Private functions 
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
}
