<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use ZipArchive;
use App\Models\Share;
use App\Models\Ushare;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UshareController extends Controller
{
    public function index()
    {
        $localShares = Ushare::where('user_id', auth()->user()->id)->orderBy('expiration', 'desc')->get();

        return view('ushare.index', compact('localShares'));
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
        $shares = Ushare::where('wuser_id', auth()->user()->id)->get();
        $usershares = $shares->unique("user_id");
        //$breadcrumbs = $this->getShareBreadcrumbs();
        $breadcrumbs[0] = ['folder' => 'ROOT', 'path' => '', 'active' => true, 'controller' => 'folder'];
        $breadcrumbs[1] = ['folder' => 'UShare', 'path' => '', 'active' => false, 'controller' => 'ushare'];
        return view('ushare.start', compact('usershares', 'shares', 'breadcrumbs'));
    }
    public function explore(Request $request)
    {
        $current_folder = " ";
        //Check privileges
        $shares = Ushare::where('wuser_id', auth()->user()->id)->where("user_id", $request->userid)->get();

        // $breadcrumbs = $this->getBreadcrumbs($current_folder, $request->userid);

        return view('ushare.explore', compact('shares'));
    }
    public function root(Request $request)
    {
        //dd($request->current_folder);
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
            $usershares_directories = [];
            foreach ($ushares as $ush) {
                array_push($usershares_directories, [0 => substr($ush->path, 1, strlen($ush->path))]);
                array_push($usershares_directories, Storage::allDirectories($ush->path));
            }
            $usershares_directory_merged = array_merge(...$usershares_directories);
            $usershares_directory_paths = $this->prependStringToArrayElements($usershares_directory_merged, "UShare/");
            $usershares = count($ushares->unique("user_id")) . " shares";
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
        $folderTreeView .= $this->generateShareViewTree($userRootShare['children'], $ushares);

        $breadcrumbs = $this->getBreadcrumbs($current_folder, $ushares);

        if ($path["access"] === false) {
            //Get folder content - only links to actual share, no access
            $sharedFolders = $this->getSharedFolders($usershares_directory_merged, $path);
            $directories = [];
            foreach ($sharedFolders as $dir) {
                array_push($directories, [
                    'foldername' => substr($dir, strlen($path['path'])+1),
                    'shortfoldername' => strlen(substr($dir, strlen($path['path'])+1)) > 30 ? substr(substr($dir, strlen($path['path'])+1), 0, 25) . "..." :  substr($dir, strlen($path['path'])+1),
                    'foldersize' => $this->isShared($dir, $ushares) ? $this->getFolderSize($dir) : ['size' => 0, 'type' => 'link', 'byteSize' => 0],
                ]);
            }
            dd($directories);
            $files = [];
            return view('share.root', compact(
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
                'filesize' => $this->getFileSize($file)
            ]);
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
        $breadcrumbs[0] = ['folder' => 'ROOT', 'path' => '', 'active' => true, 'controller' => 'folder'];
        $breadcrumbs[1] = ['folder' => 'UShare', 'path' => '', 'active' => false, 'controller' => 'ushare'];

        for ($i = 2; $i <= count($parent_search) - 1; $i++) {
            $activeLink = false;
            foreach ($ushares as $likeShare) {
                if (strpos($breadcrumbs[$i - 1]['path'] . "/" . $parent_search[$i], "/UShare" . $likeShare->path) !== false) {
                    $activeLink = true;
                    break;
                }
            }
            $breadcrumbs[$i] = ['folder' => $parent_search[$i], 'path' => $breadcrumbs[$i - 1]['path'] . "/" . $parent_search[$i], 'active' => $activeLink, 'controller' => 'ushare'];
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
        // dd($directories);
        $view = '';
        foreach ($directories as $directory) {
            $withChildren = count($directory['children']) > 0 ? true : false;
            $view .= '<li>';
            if ($withChildren) {
                $view .= '<span class="folder-tree"></span>';
                $view .= '<a class="blue-grey-text text-darken-3" href="' . route('folder.root', ['current_folder' => $directory['path']]) . '" data-folder="' . $directory['path'] . '" data-folder-view ="' . $directory['label'] . '">';
                $view .= '<b><i>' . $directory['label'] . '</i></b></a>';
                $view .= '<ul class="nested browser-default" style="padding-left: 20px;">';
                $view .= $this->generateViewTree($directory['children']);
                $view .= '</ul>';
            } else {
                $view .= '<span class="folder-tree-empty"></span>';
                $view .= '<a class="blue-grey-text text-darken-3" href="' . route('folder.root', ['current_folder' => $directory['path']]) . '" data-folder="' . $directory['path'] . '" data-folder-view ="' . $directory['label'] . '">';
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
        $uniquePaths = $this->prependStringToArrayElements($uniqueDirs, $path["path"] . "/");
        return $uniquePaths;
    }
    private function isShared($dir, $ushares)
    {
        $isShared = false;
        foreach($ushares as $share){
            if($share->path == $dir){
                $isShared = true;
                break;
            }
        }
        return $isShared;
    }
}
