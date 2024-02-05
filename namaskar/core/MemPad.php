<?php
/*
Author: David ANDRITCH
This class is a MemPad port in PHP.
MemPad is a note taking program for Windows created by Horst Schaeffer.
https://www.horstmuc.de/wmem.htm
MemPad  with a structured index. All pages are stored in a single file.
This php class is a subset of Mempad functions.
This class is for the MemPad 3.++ format ("String" is always terminated by zero byte)
AND using UTF-8 is required.
The purpose of this class to read/write/update from a Mempad note file...
and thus to turn a mempad note file into a website.
*/

namespace Qwwwest\Namaskar;

class MemPad
{

    //  private $mpArr;

    private $mpFileName;

    public $mpContent;
    public $rootElements;
    public $elts;
    private $paths = [];
    private $urls = [];
    private $mpCurrentId;
    private $homeId = null;

    private $mpArr;
    private $mpNumPages;
    // private $mpCurrentPath;

    // private $mpNumPages;
    public function __construct($fileName, $rootFolder = "", $rawMode = false)
    {
        if (!file_exists($fileName)) {
            die($fileName . ' file not found');
        }
        $rawFile = file_get_contents($fileName);
        $this->mpFileName = $fileName;

        /*
        checking if the MemPad file has a valid UTF-8 MemPad header :
        "MeMpAd." + NUMBER (initial page) + "\0" + STRING (quick page path)
        ex: "MeMpAd.23\0\0"
        */
        $rawPagesStart = strpos($rawFile, "\0\1") + 1;
        $header = substr($rawFile, 0, $rawPagesStart); // -2 ???
        // must starts with "MeMpAd" the "." means UTF-8.
        if (strpos($header, "MeMpAd.") !== 0) {
            die("$fileName not an UTF-8 MeMpAd file.");
        }

        //getting the last open pages  id in the file.
        $this->mpCurrentId = intval(substr($header, 7)); // we skip the "MeMpAd." magic number

        $rawPages = substr($rawFile, $rawPagesStart, -1); //skip header and remove last \0

        $arr = explode("\0", $rawPages);
        $this->mpArr = &$arr;
        $this->mpNumPages = count($arr) / 2; // number of pages in the file

        /*
        MemPad binary format for each Page:
        level:            byte, binary 1..99
        page title:       string (max 64 characters);
        if a TAB character is found, the rest of the string
        contains additional header information, currently the
        background color code ($nnnnnn)
        page contents:    string
        */
        $t = [];
        $l = [];
        $this->mpContent = [];
        $c = &$this->mpContent;
        $this->elts = [];
        $this->homeId = null;
        foreach ($arr as $key => $value) {
            if ($key % 2 === 0) {
                $l[] = ord($value); // 1, 2, 3 ....
                $t[] = substr($value, 1);
            } else {
                $c[] = $value; // 1, 2, 3 ....
            }
        }

        $m = [];
        $elts = [];
        $path = [];
        $url = [];
        $levelpointer = [];
        $parent = null;
        $elt = null;
        $idHome = null;
        foreach ($t as $id => $title) {

            $level = $l[$id];

            while (count($path) >= $level) {
                array_pop($path);
                array_pop($url);
                array_pop($levelpointer);
            }
            $ID = false;
            $slug = null;



            $rawtitle = trim($title);

            if (strpos($title, '//')) {


                [$title, $rest] = explode('//', $title);



                $title = ($rawMode === false) ? trim($title) : $rawtitle;

                // HOME
                if ($rest === '') {
                    $slug = '';
                    $this->homeId = $id;
                    $idHome = $id;
                    // die('id =' . $id);
                } else {

                    if (strpos($rest, '#') !== false) {
                        $parts = explode('#', $rest);
                        if ($parts[0] != '')
                            $slug = $parts[0];
                        $ID = $parts[1] ?? null;
                    } else {
                        $slug = $rest;
                    }
                }




            }


            if ($slug === null)
                $slug = self::slugify($title);

            // homepage is set with '$HOME'
            // Pages starting with '$' are internal.
            // if ($title === '$HOME') $slug = "";
            // The first page not started with $ will be home.
            // if ($this->homeId === null && $level === 1 && strpos($title, '$') !== 0) {
            //     $slug = '';
            //     $this->homeId = $id;
            // }

            $path[] = $title;
            $url[] = $slug;

            $count = count($path); // = $level ALWAYS
            $pathstr = trim(implode('/', $path), '/');
            $urlstr = trim(implode('/', $url), '/');

            if ($urlstr === "") {
                $urlstr = "/";
            }

            $absUrl = ($urlstr === "/") ? "$rootFolder" : "$rootFolder/$urlstr";

            $active = $urlstr === "/" || strpos($_SERVER['REQUEST_URI'] ?? '', $absUrl) === 0;
            $current = $_SERVER['REQUEST_URI'] === $absUrl
                || $_SERVER['REQUEST_URI'] === $absUrl . '/';



            if ($urlstr === "/") {
                $idHome = $id;

            }

            /*
            id: the id of the page
            title: the title of the page (in the tree) (ex: "Page 1")
            slug: the slugified version of the title  (ex: "page-1")
            level: level in the tree (1, 2, 3)
            url: the full url of the page for the web  (ex: "page-1/page-2")
            path: like a url but for inner purposed, made with Titles (ex: /Page 1/Page 2)
            children: array of children elements
            parent' => parent element, null for root elements
            active: the page is a parent of the current url or the current url
            current: the page is the current url
            */
            $elts[] = (object) [
                'id' => $id,
                'ID' => $ID,
                'title' => $title,
                'rawtitle' => $rawtitle,
                'slug' => $slug,
                'level' => $level,
                'url' => $urlstr,
                'absUrl' => $absUrl,
                'path' => $pathstr,
                'children' => [],
                'parent' => null,
                'active' => $active,
                'current' => $current
            ];
            $elt = &$elts[$id];


            // the homepage is Not active if another $level1 one is active
            // for example in case of a another homepage for language 
            if ($active && $level === 1 && $id !== $idHome)
                $elts[$idHome]->active = false;
            $this->paths[$pathstr] = &$elts[$id];
            $this->urls[$urlstr] = &$elts[$id];

            if ($count === 1) {
                $this->rootElements[] = &$elt;
                $levelpointer[0] = $id;
                continue;
            }

            $levelpointer[] = $id;
            $parentId = $levelpointer[$level - 2];
            $elts[$parentId]->children[] = $elt;
            $elts[$id]->parent = $parentId;
        }

        $this->elts = &$elts;

    }

    /**
     * query
     * function to query pages
     * //TODO : not finished yet
     * @param  mixed $fields
     * @return Array
     */
    public function query($fields)
    {
        $res = [];
        $patterns = [];
        $limit = -1;
        if (isset($fields['limit'])) {
            $limit = array_pop($fields);
        }
        foreach ($fields as $field => $pattern) {
            $pattern = preg_quote($pattern, '@');
            $pattern = str_replace('\*', '.*?', $pattern);
            $patterns[$field] = $pattern;
        }

        foreach ($this->elts as $id => &$elt) {
            $ok = true;

            foreach ($patterns as $field => $pattern) {
                $ok = preg_match('@^' . $patterns[$field] . '$@i', $elt->$field);
                if (!$ok) {
                    break;
                }

            }

            if ($ok) {
                $res[] = &$elt;
                $limit--;
                if ($limit === 0) {
                    break;
                }

            }
        }
        return $res;
    }

    /**
     * reactSortableTreeSave
     * when using the React.js admin app,
     * this function will save the data tree received in json
     * (mofified or new pages are attached to the tree)
     * @param  mixed $data
     * @return string
     */
    public function reactSortableTreeSave($data): string
    {

        $json = json_decode($data, true);

        $data = $json['treeData'];
        $currentId = $json['currentId'];
        $found = false;
        $counter = 0;
        $str = '';
        $this->reactSortableTreeJsonSave($data, 1, $currentId, $str, $counter, $found);

        if ($found === false) {
            $currentId = 0;
        }
        //happens when several pages are deleted including the currentPage;
        $numBytes = @file_put_contents($this->mpFileName, "MeMpAd." . ($currentId) . "\0\0" . $str);
        if ($numBytes === false) {
            return json_encode([
                "status" => "error",
                "message" => "could not save data"
            ]);
        }
        return json_encode(["status" => "ok", "message" => "Data Saved"]);
    }

    /**
     * reactSortableTreeJsonSave
     * recursive method that transform the json data tree into Mempad file format
     * mofified or new pages are attached to the tree
     * @return String
     */
    private function reactSortableTreeJsonSave(
        &$data,
        $level,
        &$currentId,
        &$str,
        &$counter,
        &$found
    ) {
        foreach ($data as $key => $value) {
            $id = $value['id'];
            $title = $value['title'];
            $children = $value['children'] ?? null;
            $contentStr = $value['content'] ?? null;

            // if no content attached to the tree then we keep the original one
            if ($contentStr === null) {
                $contentStr = ($id > -1) ? $this->mpContent[$id] : '';
            }

            // when adding new pages or movong pages around,
            // we have to calculate the new id for the current page

            if ($id === $currentId && !$found) {
                $currentId = $counter;
                $found = true;
            }
            $counter++;
            $str .= chr($level) . $title . chr(0) . $contentStr . chr(0);
            if ($children) {
                $this->reactSortableTreeJsonSave($children, $level + 1, $currentId, $str, $counter, $found);
            }
        }
    }

    /**
     * getStructureAsJson
     * export the mempad file tree structure to json
     * @return string
     */
    public function getStructureAsJson(): string
    {
        //blep
        $elts = [];
        foreach ($this->elts as $key => $elt) {

            if ($elt->level === 1) {
                $elts[] = $elt;
            }

            # code...
        }

        $json = '{"elements": ' . json_encode($elts) . ', "currentId":' . $this->mpCurrentId . ' }';
        return $json;
    }

    /**
     *
     * method to retrieve a page given its ID
     * the raw text of that page is returned, null if not found
     * @param  mixed $id
     * @return Object|null
     */
    public function getContentById($id)
    {
        return $this->mpContent[$id] ?? null;
    }

    /**
     * getElementById
     * method to retrieve an Object Element given its ID
     * an element is an object containing information about the Page
     * This info are :
     * id: the id of the page
     * title: the title of the page (in the tree) (ex: "Page 1")
     * slug: the slugified version of the title  (ex: "page-1")
     * level: level in the tree (1, 2, 3)
     * url: the full url of the page for the web  (ex: "page-1/page-2")
     * path: like a url but for inner purposed, made with Titles (ex: /Page 1/Page 2)
     * children: array of children elements
     * parent' => parent element, null for root elements.
     *
     * @param  mixed $id
     * @return Object|null
     */
    public function getElementById($id)
    {

        return $this->elts[$id] ?? null;
    }
    /**
     * getElementByPath
     * ex: $mempad->getElementByPath("Page 1/Page 2");
     * @param  mixed $path
     * @return Object|null
     */
    public function getElementByPath($path)
    {
        $res = &$this->paths[$path] ?? null;
        return $res;
    }

    /**
     * getContentByPath
     * ex: $mempad->getContentByPath("Page 1/Page 2");
     * @param  mixed $path
     * @return Object|null
     */
    public function getContentByPath($path)
    {

        // if (!isset($this->paths[$path])) {
        //     return null;
        // }

        $id = $this->paths[$path]->id ?? null;

        return $this->mpContent[$id] ?? null;
    }

    /**
     * getElementByUrl
     * is the function to retrieve mempad element for a given URL
     * ex: $mempad->getElementByUrl("page-1/page-2");
     *
     * @param  string $url
     * @return object|null
     */
    public function getElementByUrl(string $url): ?object
    {
        $url = trim($url, '/');
        if ($url === "") {
            $url = "/";
        }

        $res = &$this->urls[$url] ?? null;

        return $res;
    }

    /**
     * getHome
     * retrieves mempad HOME element
     *
     * @return object|null
     */
    public function getHome(): ?object
    {
        $elt = $this->getElementById($this->homeId);

        return $elt;
    }

    /**
     * find
     * TODO
     * find a page with criteria (path, url...)
     * @param  mixed $key
     * @param  mixed $val
     * @return Object|null
     */
    public function &find($key, $val)
    {

        $val = trim($val, '/');
        echo " ($key ::: $val)";
        if ($val === 'azankaraplop') {
            $val = null;
            return $val;
        }

        if ($key === 'path') {
            $res = &$this->paths[$val] ?? null;
            return $res;
        }

        if ($key === 'url') {
            $res = &$this->urls[$val] ?? null;

            return $res;
        }
        foreach ($this->elts as $id => &$elt) {
            if ($elt && $elt[$key] === $val) {
                return $elt;
            }
        }

        $val = null;
        return $val;
    }

    // return sibblings elements of an element
    public function getSibblings($id)
    {
        $pid = $this->elts[$id]->parent;
        if ($pid === null) {
            return $this->getRootElements();
        }
        //root
        return $this->elts[$pid]->children;
    }

    // return previous and next sibblings of an element
    public function getPreviousAndNextSibblings($id)
    {
        $sibblings = $this->getSibblings($id);

        for ($i = 0; $i < count($sibblings); $i++) {

            if ($sibblings[$i]->id === $id) {
                $prev = $sibblings[$i - 1] ?? null;
                $next = $sibblings[$i + 1] ?? null;
                return [$prev, $next];
            }
        }
    }

    // return first level elements
    public function &getRootElements()
    {
        return $this->rootElements; //root
    }

    // return parent of an element
    public function &getParent($id)
    {
        $pid = $this->elts[$id]->parent;
        return $this->elts[$pid] ?? null;
    }

    // return  children of an element
    public function &getChildren($id)
    {
        return $this->elts[$id]['children'];
    }

    /**
     * slugify
     * static function to Slugify titles to turn a Mempad title into a url for the Web
     * ex: "Page 1" -> "page-1"
     * @param  mixed $string
     * @param  mixed $delimiter
     * @return string
     */
    public static function slugify($string, $delimiter = '-'): string
    {
        $oldLocale = setlocale(LC_ALL, '0');
        setlocale(LC_ALL, 'en_US.UTF-8');
        // $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $string);

        // this line was necessary for cyrillic and greek
        $clean = $string;
        if (function_exists("transliterator_transliterate")) {
            $clean = transliterator_transliterate('Any-Latin; Latin-ASCII', $clean);
        }

        $clean = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $clean);
        $clean = preg_replace("/[^a-zA-Z0-9\/_\.|+ -]/", '', $clean);
        $clean = strtolower($clean);
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
        $clean = trim($clean, $delimiter);
        setlocale(LC_ALL, $oldLocale);
        return $clean;
    }

    /**
     * merge
     * function to merge several Mempad files into one
     *
     * - from the command line:
     * php -r "require 'MemPad.php'; Namaskar\MemPad::merge("RESULT", "FILE1/FOLDER", "FILE2/FOLDER"...);"
     *
     * - or from inside index.php:
     * $dir = DATA_FOLDER;
     * Namaskar\MemPad::merge("$dir/result.lst", "$dir/namaskar.fr.lst", "$dir/mempad.lst");
     *
     * @return void
     */
    public static function merge()
    {
        $start = microtime(true);
        $args = func_get_args();
        $Mergedfile = array_shift($args); // first element is the result merged file name.
        $str = "MeMpAd.0\0\0"; //Mempad file header...

        foreach ($args as $key => $filename) {
            // Mempad File
            if (strpos($filename, '.lst')) {
                $mp = new MemPad($filename);
                $filename = basename($filename);
                echo "FILE: $filename... \n";

                $str .= "\1$filename\0\0";
                foreach ($mp->elts as $id => &$elt) {
                    $content = $mp->mpContent[$id];
                    $str .= chr($elt->level + 1) . $elt->title . "\0$content\0";
                }
            } else {
                // folder with md files

                $files = glob("$filename/{,*/,*/*/,*/*/*/}*.md", GLOB_BRACE);
                $str .= "\1$filename\0\0";
                foreach ($files as $file) {
                    //echo "$file <br>";
                    $content = file_get_contents($file);
                    $titlePos = strpos($content, '# ');
                    $title = $file;

                    if ($titlePos !== false) {
                        $title = substr($content, $titlePos + 2, strpos($content, "\n", $titlePos + 2));
                    }
                    echo $title;
                    $folder = "";
                    $lastSlashPos = strrpos($file, '/');
                    if ($lastSlashPos !== false) {
                        $folder = substr($file, 0, $lastSlashPos);
                        $folder = "// folder:$file\n";
                    }
                    // $file = substr($file, strlen($filename)+1, -3);
                    //$title =
                    $str .= chr(2) . $title . "\0$folder$content\0";
                }
            }
        }

        file_put_contents($Mergedfile, $str);

        $time = ((microtime(true) - $start) * 1000);

        echo "TIME" . round($time) . "ms";
    }
}