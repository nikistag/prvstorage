<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use ZipArchive;
use App\Models\Ushare;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class UshareController extends Controller
{
    public function index()
    {
        $localShares = Ushare::where('user_id', auth()->user()->id)->orderBy('expiration', 'desc')->get();

        return view('ushare.index', compact('localShares'));
    }

    public function root(Request $request)
    {

        $current_folder = $request->current_folder;

        //Delete expired local shares
        $expiredShares = Ushare::where("expiration", "<", time())->get();
        if (count($expiredShares) >= 1) {
            foreach ($expiredShares as $expired) {
                $expired->delete();
            }
        }
        //Get info about local shares
        $ushares = Ushare::where('wuser_id', auth()->user()->id)->get();

        if (count($ushares) > 0) {
            $usershares = count($ushares->unique("user_id")) . " shares";
            $breadcrumbs = $this->getBreadcrumbs($current_folder, $ushares);
            $usershares_directories = [];
            foreach ($ushares as $ush) {
                array_push($usershares_directories, [0 => substr($ush->path, 1, strlen($ush->path))]);
                array_push($usershares_directories, Storage::allDirectories($ush->path));
            }
            $usershares_directory_merged = array_merge(...$usershares_directories);
            $usershares_directory_paths = $this->prependStringToArrayElements($usershares_directory_merged, "UShare/");
        } else {
            return redirect(route('folder.root', ['current_folder' => null]))->with('error', 'No user shared folders with you!'); //No shares -  redirect to FolderController
        }

        $path['path'] = $this->getSharePath($current_folder, $usershares_directory_paths);

        if ($path['path'] === null) {
            return redirect(route('folder.root', ['current_folder' => null]))->with('error', 'You dont have access to that folder!'); //Avoid accesing shares not for this user - redirect to FolderController
        }

        //Check if path is to a shared folder or only part of path to a shared folder
        array_search(substr($path['path'], 1, strlen($path['path'])), $usershares_directory_merged) !== false ? $path['access'] = true : $path['access'] = false;

        //Directory paths for options to move/copy files and folders
        $full_private_directory_paths = Storage::allDirectories(auth()->user()->name);
        $share_directory_paths = Storage::allDirectories('NShare');
        if (count($share_directory_paths) == 0) {
            $share_directory_paths = ["NShare"];
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
        $folderTreeView .= $this->generateViewTree($userRoot['children']);

        $treeMoveFolder = str_replace("blue-grey-text text-darken-3", "collection-item blue-grey-text text-darken-3 tree-move-folder", $folderTreeView);
        $treeMoveFile = str_replace("blue-grey-text text-darken-3", "collection-item blue-grey-text text-darken-3 tree-move-file", $folderTreeView);
        $treeMoveMulti = str_replace("blue-grey-text text-darken-3", "collection-item blue-grey-text text-darken-3 tree-move-multi", $folderTreeView);

        //Add UShare folder to folder tree view
        //Generate folder tree view - collection for UShare
        $ushareCollection = collect($usershares_directory_paths);
        $treeCollection_ushare = $ushareCollection->map(function ($item) {
            return explode('/', '/' . $item);
        });

        $userRootShare = $this->convertPathsToTree($treeCollection_ushare)->first();
        $folderTreeView .= $this->generateShareViewTree($userRootShare['children'], $ushares, $activeBranch);

        if ($path["access"] === false) {
            //Get folder content - only links to actual share, no access
            $sharedFolders = $this->getSharedFolders($usershares_directory_merged, $path);
            $directories = [];
            foreach ($sharedFolders as $dir) {
                array_push($directories, [
                    'foldername' => substr($dir, strlen($path['path'])),
                    'shortfoldername' => strlen(substr($dir, strlen($path['path']))) > 30 ? substr(substr($dir, strlen($path['path'])), 0, 25) . "..." :  substr($dir, strlen($path['path'])),
                    'foldersize' => $this->isShared($dir, $ushares) ? $this->getFolderSize($dir) : ['size' => 'path', 'type' => 'link', 'byteSize' => 0],
                ]);
            }

            $files = [];
            return view('ushare.root', compact(
                'directories',
                'files',
                'current_folder',
                'path',
                'breadcrumbs',
                'folderTreeView',
                'treeMoveFolder',
                'treeMoveFile',
                'treeMoveMulti',
                'usershares'
            ));
        }
        //Get folders an files of current directory
        $dirs = Storage::directories($path['path']);
        $fls = Storage::files($path['path']);
        $directories = [];
        foreach ($dirs as $dir) {
            array_push($directories, [
                'foldername' => substr($dir, strlen($path['path'])),
                'shortfoldername' => strlen(substr($dir, strlen($path['path']))) > 30 ? substr(substr($dir, strlen($path['path'])), 0, 25) . "..." :  substr($dir, strlen($path['path'])),
                'foldersize' => $this->isShared($dir, $ushares) ? $this->getFolderSize($dir) : ['size' => 'path', 'type' => 'link', 'byteSize' => 0],
            ]);
        }

        /* Process files */
        $files = [];
        foreach ($fls as $file) {
            $fullfilename = substr($file, strlen($path['path']));
            $extensionWithDot = strrchr($file, ".");
            $extensionNoDot = substr($extensionWithDot, 1, strlen($extensionWithDot));
            array_push($files, [
                'fullfilename' =>  $fullfilename,
                'fileurl' => $path['path'] . "/" . $fullfilename,
                'filename' => $filename = substr($fullfilename, 0, strripos($fullfilename, strrchr($fullfilename, "."))),
                'shortfilename' => strlen($filename) > 30 ? substr($filename, 0, 25) . "*~" : $filename,
                'extension' => $extensionWithDot,
                'fileimageurl' => $this->getThumbnailImage($extensionWithDot, $path['path'], $fullfilename, $filename),
                'filevideourl' => $this->getThumbnailVideo($extensionWithDot, $path['path'], $fullfilename, $filename),
                'filesize' => $this->getFileSize($file)
            ]);
        }


        return view('ushare.root', compact(
            'directories',
            'files',
            'current_folder',
            'path',
            'breadcrumbs',
            'folderTreeView',
            'treeMoveFolder',
            'treeMoveFile',
            'treeMoveMulti',
            'usershares'
        ));
    }

    public function store(Request $request)
    {
        //Check for user to share with
        $user = User::where('name', $request->input('user'))->orWhere('email', $request->input('user'))->get();
        $expiration = (int)date_create_from_format("M d, Y", $request->input("expiration"))->format("U");
        //Check if expiration in the past
        if ($expiration <= time()) {
            return response()->json([
                'errorMessage' =>  "Expiration date is in the past!!!",
                'successMessage' => null,
            ]);
        }
        if (count($user) != null) {
            //Check if you try to share to yourself :)
            if ($user->first()->id === auth()->user()->id) {
                return response()->json([
                    'errorMessage' =>  "You don't need to share things to yourself!!!",
                    'successMessage' => null,
                ]);
            }
            //Check if folder is already shared as subfolder
            $oldShares = Ushare::where("wuser_id", $user->first()->id)->where("user_id", auth()->user()->id)->get();
            if (count($oldShares) >= 1) {
                foreach ($oldShares as $likeShare) {
                    if (strpos($request->input("whichfolder"), $likeShare->path) !== false) {
                        return response()->json([
                            'errorMessage' =>  "Folder already shared as a subfolder!!!",
                            'successMessage' => null,
                        ]);
                    }
                }
            }
            //Check if share already exists
            $oldShare = Ushare::where("path", $request->input("whichfolder"))->where("wuser_id", $user->first()->id)->orderBy("expiration", "desc")->first();
            if ($oldShare != null) {
                //Update old expiration date
                if ($oldShare->expiration < $expiration) {
                    $oldShare->expiration = $expiration;
                    $oldShare->save();
                    return response()->json([
                        'errorMessage' =>  null,
                        'successMessage' => "Folder has been shared with " . $user->first()->name . "/" . $user->first()->email . " until " . $request->input("expiration"),
                    ]);
                } else {
                    return response()->json([
                        'errorMessage' =>  null,
                        'successMessage' => "Folder has already been shared with " . $user->first()->name . "/" . $user->first()->email . " until " . $oldShare->expiration,
                    ]);
                }
            } else {
                //Create new local user share
                $share = new Ushare();
                $share->user_id = auth()->user()->id;
                $share->wuser_id = $user->first()->id;
                $share->path = $request->input("whichfolder");
                $share->expiration = $expiration;
                $share->save();
                return response()->json([
                    'errorMessage' =>  null,
                    'successMessage' => "Folder has been shared with " . $user->first()->name . "/" . $user->first()->email . " until " . $request->input("expiration"),
                ]);
            }
        } else {
            return response()->json([
                'errorMessage' =>  "No username or email match found",
                'successMessage' => null,
            ]);
        }
    }

    public function update(Request $request, Ushare $ushare)
    {
        //Update share model and save it
        $ushare->expiration = (int)date_create_from_format("M d, Y", $request->input('expiration'))->format("U");

        $ushare->save();

        return redirect(route('ushare.index'));
    }

    public function purge()
    {
        $shares = Ushare::where('user_id', auth()->user()->id)->get();

        if (count($shares) > 0) {
            foreach ($shares as $share) {
                $share->delete();
            }
        }
        return redirect(route('ushare.index'))->with('success', 'All shares have been purged');
    }

    public function delete(Request $request)
    {
        //Check if user may delete share
        $ushare = Ushare::where('id', $request->input('shareidtodelete'))->first();

        if ($ushare->user_id == auth()->user()->id) {
            $ushare->delete();
            return redirect(route('ushare.index'))->with('success', 'Folder no longer shared!');
        } else {
            return redirect(route('ushare.index'))->with('error', 'This share is not yours to end!');
        }
    }
    public function start()
    {
        $ushares = Ushare::where('wuser_id', auth()->user()->id)->get();
        $usershares = $ushares->unique("user_id");

        //Directory paths for options to move/copy files and folders
        $full_private_directory_paths = Storage::allDirectories(auth()->user()->name);
        $share_directory_paths = Storage::allDirectories('NShare');
        if (count($share_directory_paths) == 0) {
            $share_directory_paths = ["NShare"];
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

        $userRoot = $this->convertPathsToTree($treeCollection)->first();
        $folderTreeView = '<li><span class="folder-tree-root"></span>';
        $folderTreeView .= '<a class="blue-grey-text text-darken-3"   href="' . route('folder.root', ['current_folder' => '']) . '" data-folder="Root" data-folder-view="Root"><b><i>Root</i></b></a></li>';
        $folderTreeView .= $this->generateViewTree($userRoot['children']);

        //Add UShare folder to folder tree view
        //Generate folder tree view - collection for UShare
        if (count($ushares) > 0) {
            $usershares_directories = [];
            foreach ($ushares as $ush) {
                array_push($usershares_directories, [0 => substr($ush->path, 1, strlen($ush->path))]);
                array_push($usershares_directories, Storage::allDirectories($ush->path));
            }
            $usershares_directory_merged = array_merge(...$usershares_directories);
            $usershares_directory_paths = $this->prependStringToArrayElements($usershares_directory_merged, "UShare/");

            $ushareCollection = collect($usershares_directory_paths);
            $treeCollection_ushare = $ushareCollection->map(function ($item) {
                return explode('/', '/' . $item);
            });
            $userRootShare = $this->convertPathsToTree($treeCollection_ushare)->first();
            $folderTreeView .= $this->generateShareViewTree($userRootShare['children'], $ushares, array());
        }
        $breadcrumbs[0] = ['folder' => 'ROOT', 'path' => '', 'active' => true, 'href' => route('folder.root', ['current_folder' => ''])];
        $breadcrumbs[1] = ['folder' => 'UShare', 'path' => '', 'active' => false, 'href' => route('ushare.start')];

        return view('ushare.start', compact('usershares', 'ushares', 'breadcrumbs', 'folderTreeView'));
    }
    public function folderMove(Request $request) //Only copy
    {
        $current_folder = $request->input('target') == "" ? "" : $request->input('target');
        $new_path = $this->getPath($current_folder) . "/" . $request->input('whichfolder');
        $sharedDir = $this->getSharedDir($this->getPath($request->current_folder) . "/" . $request->input('whichfolder'));

        //CHECK if user has privileges to do that
        //Get info about local shares
        $ushares = Ushare::where('wuser_id', auth()->user()->id)->get();

        if ($this->isShared($sharedDir, $ushares)) {
            $old_path = "/" . $sharedDir;
            //Check for path inside moved folder
            if (strrpos($new_path, $old_path) === 0) {
                return redirect()->route('ushare.root', ['current_folder' => $request->current_folder])->with('warning', 'NO action done. Not good practice to move folder to itself!');
            }
            //CHECK for duplicate folder
            if (Storage::exists($new_path)) {
                return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('warning', 'NO action done. Duplicate folder found!');
            }
            //Copy folder
            //main
            $done = (new Filesystem)->copyDirectory(Storage::path($old_path), Storage::path($new_path));
            //thumbs
            $thumbdone = (new Filesystem)->copyDirectory(Storage::disk('public')->path('/thumb' . $old_path), Storage::disk('public')->path('/thumb' . $new_path));
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'Folder successfuly copied!');
        } else {
            return redirect(route('ushare.root', ['current_folder' => $request->current_frolder]))->with('error', 'You have no permission to copy!');
        }
    }

    public function folderdownload(Request $request)
    {

        $sharedDir = $this->getSharedDir("/" . $request->input('path'));

        //CHECK if user has privileges to do that
        //Get info about local shares
        $ushares = Ushare::where('wuser_id', auth()->user()->id)->get();

        if ($this->isShared($sharedDir, $ushares)) {
            $path = "/" . $sharedDir;

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

    public function moveFileBig(Request $request) //Only copy
    {

        $sharedDir = $this->getSharedDir("/" . $request->input('current_folder_big'));
        //CHECK if user has privileges to do that
        //Get info about local shares
        $ushares = Ushare::where('wuser_id', auth()->user()->id)->get();

        if ($this->isShared($sharedDir, $ushares)) {
            $current_folder = $request->input('whereToFolder') == "" ? "" : $request->input('whereToFolder');

            $old_path = "/" . $sharedDir . "/" . $request->input('file_big');
            $new_path = $this->getPath($current_folder . "/" . $request->input('file_big'));

            //Check for duplicate file
            if (Storage::exists($new_path)) {
                return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('warning', 'File already there!');
            } else {
                $done = Storage::copy($old_path, $new_path);
                if (Storage::disk('public')->has('/thumb' . $old_path)) {
                    $thumbs = Storage::disk('public')->copy('/thumb' . $old_path, '/thumb' . $new_path);
                }
                return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly copied!');
            }
        }
    }
    public function moveFileMulti(Request $request)
    {
        $sharedDir = $this->getSharedDir("/" . $request->input('current_folder_multi'));
        //CHECK if user has privileges to do that
        //Get info about local shares
        $ushares = Ushare::where('wuser_id', auth()->user()->id)->get();

        if ($this->isShared($sharedDir, $ushares)) {
            $current_folder = $request->input('targetfoldermulti');
            foreach ($request->filesMove as $file) {
                $old_path = "/" . $sharedDir . "/" . $file;
                $new_path = $this->getPath($current_folder . "/" . $file);

                //Check for duplicate file
                if (Storage::exists($new_path)) {
                } else {
                    $done = Storage::copy($old_path, $new_path);
                    if (Storage::disk('public')->has('/thumb' . $old_path)) {
                        $thumbs = Storage::disk('public')->copy('/thumb' . $old_path, '/thumb' . $new_path);
                    }
                }
            }
            return redirect()->route('folder.root', ['current_folder' => $current_folder])->with('success', 'File successfuly copied!');
        } else {
            return back()->with('error', 'You fucked up big time');
        }
    }

    public function filedownload(Request $request)
    {
        $fileName = $this->getFilename($request->input('path'));

        $dirPath = substr($request->input('path'), 0, strlen($request->input('path')) - (strlen($fileName) + 1));

        $sharedDir = $this->getSharedDir("/" . $dirPath);

        //CHECK if user has privileges to do that
        //Get info about local shares
        $ushares = Ushare::where('wuser_id', auth()->user()->id)->get();

        if ($this->isShared($sharedDir, $ushares)) {
            $path = "/" . $sharedDir . "/" . $fileName;
            return Storage::download($path);
        } else {
            return back()->with('error', 'File download not permited!');
        }
    }
    public function filestream(Request $request)
    {
        $fileName = $this->getFilename($request->input('path'));

        $dirPath = substr($request->input('path'), 0, strlen($request->input('path')) - (strlen($fileName) + 1));

        $sharedDir = $this->getSharedDir("/" . $dirPath);
        //CHECK if user has privileges to do that
        //Get info about local shares
        $ushares = Ushare::where('wuser_id', auth()->user()->id)->get();

        if ($this->isShared($sharedDir, $ushares)) {

            $path = "/" . $sharedDir . "/" . $fileName;
            $headers = $this->getStreamHeaders($path);
            return response()->file(Storage::path($path), $headers);
        } else {
            return back()->with('error', 'You do not have access to that stream!');
        }
    }

    public function targetFolderSize(Request $request)
    {
        $targetFolderSize = $this->getFolderSize($this->getPath("/" . $request->targetfolder));
        return response()->json([
            'folderSize' =>  $targetFolderSize['byteSize']
        ]);
    }
    public function multifiledownload(Request $request)
    {
        $sharedDir = $this->getSharedDir("/" . $request->input('currentFolderMultiDownload'));
        //CHECK if user has privileges to do that
        //Get info about local shares
        $ushares = Ushare::where('wuser_id', auth()->user()->id)->get();

        if ($this->isShared($sharedDir, $ushares)) {
            $zipFileName = $request->multiZipFileName;

            $storage_path = auth()->user()->name . '/ZTemp/' . $zipFileName;

            $zip_path = Storage::path($storage_path);

            //Create archive
            $zip = new ZipArchive();
            if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                // Add File in ZipArchive
                foreach ($request->input("filesdownload") as $file) {
                    $zip->addFile(Storage::path("/" . $sharedDir . "/" . $file), $file);
                }
                // Close ZipArchive     
                $zip->close();
            }
            //sleep(1);
            return Storage::download($storage_path);
        } else {
            return back()->with('error', 'Something went wrong!Sorry...');
        }
    }

    public function fileReadiness(Request $request)
    {
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
    public function mediapreview(Request $request)
    {
        //dd($request->input());
        $fullfilename = $request->file_name;
        $checked = $request->checked;
        $fileNameNoExt = substr($fullfilename, 0, strripos($fullfilename, strrchr($fullfilename, ".")));
        $path = $this->getSharedDir("/" . $request->current_folder);
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
        $pathToFolder = "/" . $path;
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
    private function getSharePath($current_folder, $usershares_directory_paths)
    {

        $path = substr($current_folder, 7, strlen($current_folder));
        //Check if user has access to path
        $access = false;
        foreach ($usershares_directory_paths as $share) {
            if (strpos($share, "UShare" . $path) !== false) {
                $access = true;
                break;
            }
        }

        return $access === true ? $path : null;
    }

    private function getBreadcrumbs($current_folder, $ushares)
    {
        //Folder breadcrumbs
        $parent_search = explode("/", $current_folder);

        //dd($current_folder);

        $breadcrumbs[0] = ['folder' => 'ROOT', 'path' => '', 'active' => true, 'href' => route('folder.root', ['current_folder' => ''])];
        $breadcrumbs[1] = ['folder' => 'UShare', 'path' => '/Ushare', 'active' => false, 'href' => route('ushare.start')];

        for ($i = 2; $i <= count($parent_search) - 1; $i++) {
            $activeLink = false;
            foreach ($ushares as $likeShare) {
                if (strpos($breadcrumbs[$i - 1]['path'] . "/" . $parent_search[$i], "/UShare" . $likeShare->path) !== false) {
                    $activeLink = true;
                    break;
                }
            }
            $breadcrumbs[$i] = [
                'folder' => $parent_search[$i],
                'path' => $breadcrumbs[$i - 1]['path'] . "/" . $parent_search[$i],
                'active' => $activeLink, 'controller' => 'ushare',
                'href' => route('ushare.root', ['current_folder' => $breadcrumbs[$i - 1]['path'] . "/" . $parent_search[$i]]),
            ];
        }

        return $breadcrumbs;
    }

    private function prependStringToArrayElements($array, $string)
    {
        $newArray = [];
        foreach ($array as $element) {
            array_push($newArray, $string . $element);
        }
        return $newArray;
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

    private function generateViewTree($directories)
    {
        $view = '';
        foreach ($directories as $directory) {
            $withChildren = count($directory['children']) > 0 ? true : false;
            $view .= '<li>';
            if ($withChildren) {
                $this->startsWithNShare($directory['path']) ?
                    $view .= '<span class="folder-tree-nshare"></span>' :
                    $view .= '<span class="folder-tree"></span>';
                $view .= '<a class="blue-grey-text text-darken-3" href="' . route('folder.root', ['current_folder' => $directory['path']]) . '" data-folder="' . $directory['path'] . '" data-folder-view ="' . $directory['label'] . '">';
                $view .= '<b><i>' . $directory['label'] . '</i></b></a>';
                $this->startsWithNShare($directory['path']) ?
                    $view .= '<ul class="nested-nshare browser-default" style="padding-left: 20px;">' :
                    $view .= '<ul class="nested browser-default" style="padding-left: 20px;">';
                $view .= $this->generateViewTree($directory['children']);
                $view .= '</ul>';
            } else {
                $this->startsWithNShare($directory['path']) ?
                    $view .= '<span class="folder-tree-nshare-empty"></span>' :
                    $view .= '<span class="folder-tree-empty"></span>';
                $view .= '<a class="blue-grey-text text-darken-3" href="' . route('folder.root', ['current_folder' => $directory['path']]) . '" data-folder="' . $directory['path'] . '" data-folder-view ="' . $directory['label'] . '">';
                $view .= '<b><i>' . $directory['label'] . '</i></b></a>';
            }
            $view .= '</li>';
        }

        return $view;
    }

    private function generateShareViewTree($directories, $ushares, $activeBranch)
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
                //dd($directories);
                if ((count($activeBranch) > 0) && ($activeBranch[0] == $directory["label"])) {
                    $garbage = array_shift($activeBranch);
                    count($activeBranch) == 0 ?
                        $view .= '<span class="folder-tree-ushare-down-active"></span>'
                        :
                        $view .= '<span class="folder-tree-ushare-down"></span>';
                    if ($activeLink) {
                        count($activeBranch) == 0 ?
                            $view .= '<a class="pink-text text-darken-3" href="' . route('ushare.root', ['current_folder' => $directory['path']]) . '" data-folder="' . $directory['path'] . '" data-folder-view ="' . $directory['label'] . '">'
                            :
                            $view .= '<a class="blue-grey-text text-darken-3" href="' . route('ushare.root', ['current_folder' => $directory['path']]) . '" data-folder="' . $directory['path'] . '" data-folder-view ="' . $directory['label'] . '">';
                        $view .= '<b><i>' . $directory['label'] . '</i></b></a>';
                    } else {
                        $view .= '<a class="blue-grey-text text-darken-3" href="#" data-folder="' . $directory['path'] . '" data-folder-view ="' . $directory['label'] . '">';
                        $view .= $directory['label'] . '</a>';
                    }
                    $view .= '<ul class="active-tree-ushare browser-default" style="padding-left: 20px;">';
                    $view .= $this->generateShareViewTree($directory['children'], $ushares, $activeBranch);
                    $view .= '</ul>';
                } else {
                    $view .= '<span class="folder-tree-ushare"></span>';
                    if ($activeLink) {
                        $view .= '<a class="blue-grey-text text-darken-3" href="' . route('ushare.root', ['current_folder' => $directory['path']]) . '" data-folder="' . $directory['path'] . '" data-folder-view ="' . $directory['label'] . '">';
                        $view .= '<b><i>' . $directory['label'] . '</i></b></a>';
                    } else {
                        $view .= '<a class="blue-grey-text text-darken-3" href="#" data-folder="' . $directory['path'] . '" data-folder-view ="' . $directory['label'] . '">';
                        $view .= $directory['label'] . '</a>';
                    }
                    $view .= '<ul class="nested-ushare browser-default" style="padding-left: 20px;">';
                    $view .= $this->generateShareViewTree($directory['children'], $ushares, $activeBranch);
                    $view .= '</ul>';
                }
            } else {
                if ((count($activeBranch) > 0) && ($activeBranch[0] == $directory["label"])) {
                    $view .= '<span class="folder-tree-ushare-empty-active"></span>';
                    if ($activeLink) {
                        $view .= '<a class="pink-text text-darken-3" href="' . route('ushare.root', ['current_folder' => $directory['path']]) . '" data-folder="' . $directory['path'] . '" data-folder-view ="' . $directory['label'] . '">';
                        $view .= '<b><i>' . $directory['label'] . '</i></b></a>';
                    } else {
                        $view .= '<a class="blue-grey-text text-darken-3" href="#" data-folder="' . $directory['path'] . '" data-folder-view ="' . $directory['label'] . '">';
                        $view .= $directory['label'] . '</a>';
                    }
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
    private function getSharedFolders($sharedPaths, $path)
    {
        $paths = $this->prependStringToArrayElements($sharedPaths, "/");
        $goodPaths = [];
        foreach ($paths as $p) {
            if (strpos($p, $path['path']) !== false) {
                array_push($goodPaths, array_slice(explode("/", $p), count(explode("/", $path['path'])))[0]);
            }
        }
        $uniqueDirs = array_unique($goodPaths);
        $uniquePaths = $this->prependStringToArrayElements($uniqueDirs, substr($path["path"] . "/", 1));
        return $uniquePaths;
    }
    private function isShared($dir, $ushares)
    {
        $isShared = false;
        foreach ($ushares as $share) {
            $patched = "/" . $dir;
            if ((strpos($patched, $share->path) === 0) || ($patched == $share->path)) {
                $isShared = true;
                break;
            }
        }
        return $isShared;
    }
    private function getSharedDir($path)
    {
        return implode("/", array_slice(explode("/", $path), 3));
    }
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
    function startsWithNShare($path)
    {
        return strpos($path, "/NShare") === 0;
    }
}
