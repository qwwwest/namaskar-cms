<?php

namespace Qwwwest\Namaskar;


use Qwwwest\Namaskar\Kernel;

class AdminModel
{

    private $zconf;

    public function __construct()
    {
        $this->zconf = Kernel::service('ZenConfig');
    }


    public function listlogs($filter = ""): string
    {

        $files = glob(($this->zconf)('folder.logs') . "/$filter*.txt");


        $labels = ['site', 'type', 'date', 'size'];
        $rows = [];
        foreach ($files as $file) {

            $basename = basename($file, '.txt');
            $pos = strrpos($basename, '.');
            $name = substr($basename, 0, $pos);
            $type = substr($basename, $pos + 1);


            $filesize = filesize($file);
            $date = date("Y-m-d H:i:s", filemtime($file));
            $logfile = "<a href=\"./logs/$basename\">$name &bull; $type</a>\n";
            $rows[] = [$logfile, $type, $date, $filesize];
        }
        return $this->htmlTable($labels, $rows);

    }

    public function readLogFile($file): string
    {

        $content = file_get_contents(($this->zconf)('folder.logs') . "/$file");

        //  $content = str_replace("\n", '<br>', $content);


        $labels = ['Date', 'Time', 'IP address', 'Render Time', 'Method', 'URL'];
        $rows = explode("\n", $content);

        $rows2 = [];
        foreach ($rows as &$row) {

            $row = explode(" ", $row, 6);
            //$rows2[] =;
        }
        return $this->htmlTable($labels, $rows);


    }

    public function htmlTable($labels, $rows, $classes = ''): string
    {



        $tableLabels = '';
        $tableData = '';
        foreach ($labels as $label) {

            $tableLabels .= "<th scope=col>$label</th>";
        }

        foreach ($rows as $row) {
            $tableData .= "<tr>\n";
            foreach ($row as $value) {

                $tableData .= "<td>$value</td>\n";
            }
            $tableData .= "</tr>\n";
        }

        return <<<TABLE
        <div class="table-responsive small">
        <table class="table table-striped table-sm">
          <thead>
            <tr>
            $tableLabels
            </tr>
          </thead>
          <tbody>
            $tableData
          </tbody>
        </table>
      </div>
      TABLE;

    }



    public function getAllPages(): string
    {

        $conf = Kernel::service('ZenConfig');


        $mempadFile = $conf('mempadFile');
        $absroot = $conf('absroot');
        $mempad = new MemPad($mempadFile, '');



        $content = '';
        foreach ($mempad->elts as $v) {

            $opacity = '100';
            if (
                strpos($v->url, '/.') !== false
                || strpos($v->url, '.') === 0
                || strpos($v->url, '/!') !== false
                || strpos($v->url, '!') === 0
            )
                continue;
            //$opacity = '50';


            $marginLeft = ($v->level - 1) . 'rem';

            $content .= <<<blep
            <div class="link opacity-$opacity" style="margin-left:$marginLeft">
                <a href="$absroot/$v->url" target="_blank">$v->title</a>
                <span class="opacity-50"> [link "$v->url"]</span>
            </div>
            blep;
        }
        return "<div class=links>$content</div>";
    }

    public function OLD___getAllImages(): string
    {
        $conf = Kernel::service('ZenConfig');

        $absroot = $conf('absroot');

        //$format = "apng,avif,gif,jpg,jpeg,png,svg,webp,bmp,ico";
        $dirs = array_filter(glob('media/img/*'), 'is_dir');
        $files = glob("media/img/{,*/}*{apng,avif,gif,jpg,jpeg,png,svg,webp,bmp,ico}", GLOB_BRACE);
        $content = "nombre d'images : " . count($files) . "\n\n";
        $images = "";
        foreach ($files as $file) {
            $src = substr($file, 10);
            $arr = getimagesize($file);
            $imageSize = $arr ? "$arr[0] x $arr[1]" : "";
            $filesize = filesize($file); // bytes
            $filesize = round($filesize / 1024);
            $images .= <<<image
            
                <div class="col">
                <div class="card shadow-sm">
               
                <a href="$absroot/$file" target="_blank"> <div class="ratio ratio-4x3"><img src="$absroot/$file" class="card-img-top" /></div></a>
               
                <div class="card-body">
                    <p class="card-text">[img "$src" "desc"]<br>
                    $imageSize ($filesize ko)</p>
                    
                </div>
                </div>
            </div>
        
            image;



        }

        $content = <<<images
            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 g-3">
            $images
            </div>
            images;
        return $content;
    }

    public function getAllImagesByFolders(): string
    {
        $conf = Kernel::service('ZenConfig');

        $absroot = $conf('absroot');
        ob_start();
        $format = "{apng,avif,gif,jpg,jpeg,png,svg,webp,bmp,ico}";
        $dirs = array_filter(glob('media/img/*'), 'is_dir');

        $galleries = '';
        foreach ($dirs as $dir) {
            $basename = basename($dir);
            $files = glob("$dir/*$format", GLOB_BRACE);
            array_unshift($files, 'media');
            $num = count($files);
            $galleries .= <<<HTML
            <a href=#$dir class="btn btn-sm btn-outline-secondary">$basename</a> 
          
            HTML;
        }

        if ($galleries)
            echo <<<HTML
 
                <strong>Galeries</strong>
                <div class="btn-toolbar mb-2 mb-md-0">
                <div class="btn-group me-2">
                $galleries 
                </div>
 
                </div>
    
       
        HTML;

        array_unshift($dirs, 'media');
        array_unshift($dirs, 'media/img');
        foreach ($dirs as $dir) {
            $files = glob("$dir/*$format", GLOB_BRACE);
            $num = count($files);
            if ($num === 0)
                continue;
            echo <<<HTML
            <h2 class=h3 id=$dir>Folder $dir ($num)</h2> 
             
            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-6 g-3">
            HTML;


            foreach ($files as $file) {
                if (is_dir($file))
                    continue;
                $src = basename($file); //substr($file, 10);
                $arr = getimagesize($file);
                $ext = substr($file, -4);
                $numsymb = '';
                if (substr($file, -4) === '.svg') {

                    $svg = file_get_contents(N('folder.public') . '/' . $file);
                    $id = '';
                    if (preg_match('/<symbol .*?id="(.+?)"/', $svg, $matches)) {
                        $id = '#' . $matches[1];
                        $num = substr_count($svg, '<symbol ');

                        $numsymb = "<b>1/$num</b>";
                        $img = <<<XML
                 
                        <svg   class="card-img-top" >
                        <use xlink:href="$absroot/$file$id"></use>
                        </svg>
                        $numsymb
                        XML;
                    } else {
                        $img = <<<HTML
                        <img src="$absroot/$file" class="card-img-top" />
                        HTML;
                    }



                } else {
                    $img = <<<HTML
                    <img src="$absroot/$file" class="card-img-top" />
                    HTML;
                }
                $imageSize = $arr ? "$arr[0]x$arr[1]" : "";
                $filesize = filesize($file); // bytes
                $filesize = round($filesize / 1024);
                echo <<<HTML
            
                <div class="col">
                    <div class="card shadow-sm">
                    <a href="$absroot/$file" target="_blank"> <div class="ratio ratio-4x3">
                        $img</div></a>             
                    <div class="card-body">
                        <p class="card-text"><b>$src</b><br>
                        $imageSize ({$filesize}ko)</p>
                        
                    </div>
                    </div>
                </div>

                HTML;

            }

            echo '</div>';

        }
        return ob_get_clean();
    }

    public function getAllMedia(): string
    {
        $conf = Kernel::service('ZenConfig');

        $absroot = $conf('absroot');

        //$format = "apng,avif,gif,jpg,jpeg,png,svg,webp,bmp,ico";
        // $dirs = array_filter(glob('media/img/*'), 'is_dir');
        $files = glob("media/{,*/,*/*/,*/*/*/}*", GLOB_BRACE);
        //$content = "nombre d'images : " . count($files) . "\n\n";
        $content = "";
        foreach ($files as $file) {

            if (strpos($file, 'media/img') === 0)
                continue;
            if (is_dir($file))
                continue;

            $src = substr($file, 10);


            $filesize = filesize($file); // bytes
            $filesize = round($filesize / 1024);
            $content .= <<<HTML
                <a href="$absroot/$file" target="_blank"> $file ($filesize ko)</a><br>
            HTML;



        }


        return $content;
    }

}