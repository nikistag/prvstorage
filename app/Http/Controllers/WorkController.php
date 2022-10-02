<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use ZipArchive;
use App\Models\Share;
use Illuminate\Support\Facades\Cache;

class WorkController extends Controller
{
    public function makedir(Request $request)
    {
/*         $current_folder = $request->current_folder;

        $parent_folder = $this->getParentFolder($current_folder);

        $path = $this->getPath($current_folder);

        $breadcrumbs = $this->getBreadcrumbs($current_folder); */

        //Directory structure
        $level1 = Storage::directories(auth()->user()->name);
/* 
        echo '<ul>';
        foreach($level1 as $l1){
            echo '<li>';
            echo substr($l1, strlen('/' . auth()->user()->name));
            if(count(Storage::directories($l1)) > 0){
                echo $this->generateLevel($l1);
            }else{
                
            }
            echo '</li>';
        }
        echo '</ul>';

 */
        //test 2

                echo $this->generateLevel(auth()->user()->name);


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
        private function generateLevel($directory)
        {
            $html = '';
            $html .= '<ul>';
            if ($directory == auth()->user()->name ){
                 $html .= '<li>Root' ;
            }else{
                $html .= '<li>' . substr(strrchr($directory, "/"), 1, strlen(strrchr($directory, "/"))-1);
            }
            $subdirectories = Storage::directories($directory);
            if(count($subdirectories) > 0){
                foreach($subdirectories as $subdir){
                    $html .= $this->generateLevel($subdir);
                }
            }
            $html .= '</li>';
            $html .= '</ul>';
            return $html;
        }

}
