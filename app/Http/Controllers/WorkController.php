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
        $trail = '';

                echo $this->generateLevel(auth()->user()->name, true, $trail);


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
        private function generateLevel($directory, $first, $trail)
        {
            $html = '';
            if($first){
               $html .= '<ul id="slide-out" class="sidenav">'; 
            }else{
                $html .= '<ul>'; 
            }
            
            if ($directory == auth()->user()->name ){
                 $html .= '<li><a href="'.route('folder.root', ['current_folder' => $trail]).'" ><i class="material-icons">cloud</i>Root</a>' ;
            }else{
                $trail .= strrchr($directory, "/");
                $html .= '<li><a href="'.route('folder.root', ['current_folder' => $trail]).'" ><i class="material-icons">cloud</i>' 
                                . substr(strrchr($directory, "/"), 1, strlen(strrchr($directory, "/"))-1). '</a>';
            }
            $subdirectories = Storage::directories($directory);
            if(count($subdirectories) > 0){
                foreach($subdirectories as $subdir){
                    $html .= $this->generateLevel($subdir, false, $trail);
                }
            }
            $html .= '</li>';
            $html .= '</ul>';
            return $html;
        }
/* 
        <ul id="slide-out" class="sidenav">
        <li><a href="#!"><i class="material-icons">cloud</i>First Link With Icon</a></li>
        <li><a href="#!">Second Link</a></li>
        <li><div class="divider"></div></li>
        <li><a class="subheader">Subheader</a></li>
        <li><a class="waves-effect" href="#!">Third Link With Waves</a></li>
      </ul>
      <a href="#" data-target="slide-out" class="sidenav-trigger"><i class="material-icons">menu</i></a>
       */     
}
