<?php

namespace App\Namaskar;

use Shortcodes;

//require_once __DIR__ . '/vendor/Michelf/MarkdownExtra.inc.php';

class Renderer
{

    private $templates;
    private $shortcodes;
    private $markdownParser;
    private $config;
    private $isAuthed;

    private $mempad;
    private $filename;
    private $renderer;

    public function __construct($mempad)
    {
        $this->config = new Infini(true);
        $this->mempad = new MemPad($mempad);
        $this->filename = $mempad;
        $this->isAuthed = isset($_SESSION['valid']); // ? $_SESSION['valid'] : false;
 
    }
    public function scan($folder)
    {

        return $files = array_filter(glob("media/img/$folder/*"), 'is_file');
    }
    public function configPage($conf)
    {
        $page = $this->mempad->getContentByPath($conf); //Path such .conf are valid path but not url.

        if ($page) {
            $this->config->parseString($page);
        } else {
            $sitename = basename($this->filename, 'lst');
            $default = <<<CONF
                [site]
                name: "$sitename"
                domain: "$sitename"
                logo: false
                language: "en"
                ajaxify: false
                theme: "default"
                bgcolor: "#00000000"
                offcanvas: "{sidemenu / }"
                404: """
                # 404  page not found...  ¯\\_(ツ)_/¯

                [pages]
                offcanvas: "{sidemenu / }"

                """
                CONF;
            $this->config->parseString($default);
        }
    }

    public function registerHtmlElement($element)
    {

        $this->shortcodes->addShortcode($element, function ($attributes, $content, $tagName)
        {

            $uAttr = $this->uAttr($attributes); 

            $content = trim($content);
            $content = <<<blep
            <$tagName $uAttr markdown=1>$content</$tagName>
            blep; 
    
            $content = $this->shortcodes->process($content);
            $content = $this->markdownParser->transform($content);
                 
            return $content ;
        });
    }

    /**
     * isPageAccessible
     * When a page title start with "!" it should be visible only if cnnected in the backend
     * when a page starts with "." it is not rendered
     * when a page does not exist, it returns false
     * @param  String $url
     * @return Boolean 
     */    
    public function isPageAccessible($url){
        $elt = $this->mempad->getElementByUrl($url);
       
        if (! $elt) return false ;
        if(strpos($elt->title, '.') === 0) return false;      
        if(strpos($elt->title, '!') === 0) return $this->isAuthed;
         if(strpos($elt->path, '/!') !== false) return $this->isAuthed;
        if(strpos($elt->path, '!') !== false) return $this->isAuthed;

        return true;
    }
    /**
     * renderPage
     * create the object containing all the configuration settings, page content...
     * it is quite opiniated... it handles the main menu, handle languages from url,
     * @param  String $url
     * @param  mixed $url
     * @return String rendered page in html
     */
    public function renderPage($url)
    {

        $this->shortcodes = new Shortcodes;
        require_once 'MyShortcodes.php';

        $conf = $this->config;
        
        $url = trim($url, '/');

        if ($url === '') {
            $url = '/';
        }

        $found = $this->urlManager($url); // is the url found in mempad
        $notFound = substr($url, strlen($found)); //is the rest, to try to find as regular files

        $elt = $this->mempad->getElementByUrl($found);



        // Languages in url
        $languages = []; //will be something like ['fr', 'en', 'ru']
        $languageMenu = $conf('site.menu.language');
        if ($languageMenu) {
            foreach ($languageMenu as $value) {
                $language = $value['url'] ?? false;
                if ($language) {
                    $languages[$language] = $language;
                }

                # code...
            }
        }

        $home = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor"   viewBox="0 0 576 512"><path fill="#333333" stroke="none" d="M280 148L96 300V464a16 16 0 0 0 16 16l112-.29a16 16 0 0 0 15-16V368a16 16 0 0 1 16-16h64a16 16 0 0 1 16 16v95a16 16 0 0 0 16 16L464 480a16 16 0 0 0 16-16V300L295 148a12.19 12 0 0 0-15 0zM571 251L488 182L318 43a48 48 0 0 0-61 0L4 251a12 12 0 0 0-1.6 17l25 31A12 12 0 0 0 45 301l235-194a12 12 0 0 1 15 0L531 301a12 12 0 0 0 16.9-1.6l25.5-31a12 12 0 0 0-1.7-17z"/></svg>';

        $parts = explode("/", trim($found, '/'));
        $slug = array_shift($parts);
        $breadcrumb = [[
            'title' => $home,
            'url' => $languages[$slug] ?? '',
        ]];

        $path = '';

        //HOME
        if ($found === '' || $found === "/") {
            //$elt  = $this->mempad->getElementByPath('$HOME');
            $elt = $this->mempad->getElementByUrl('/');

            $breadcrumb = null; // no breadcrumb on homepage
            $this->pagify($elt);
        } else // any Page
        {
            while ($slug) {
                $path = $path . '/' . $slug;
                $elt = $this->mempad->getElementByUrl($path);
                // TO IMPROVE : when traversing down the tree and making "." matching the 
                // current directory
                $conf('page.url', $path);
                
                $this->pagify($elt);
                // we need to check if title is changed in frontmatter

                $slug = array_shift($parts);
                $elt && $elt->title !== '$HOME' && $breadcrumb[] = [
                    'title' => $elt->title,
                    'url' => $elt->url ?? '',
                    'active' => ($slug === null),
                ];
            }
        }

        $menuLanguage = $conf('site.menu.language');
        if ($menuLanguage) {
            $language = $conf('site.language');
            $languageId = null;
            $languageDefaultId = null;
            foreach ($menuLanguage as $key => $item) {
                $languageUrl = $item['url'] ?? false; // url or main home

                if ( $languageUrl === '' ||  $languageUrl === '/') {
 
                    $languageDefaultId = $key;
                     
                }

                if ($languageUrl && strpos($url, $languageUrl) === 0) {

                    $language = $languageUrl;
                    $languageId = $key;
                    break;
                }
            }
       

            if ( $languageId === null) {
                 $languageId = $languageDefaultId;                
            }

          
            $menuLanguage[$languageId]['active'] = 'active';
            $conf('site.menu.language', $menuLanguage);
            $conf('site.language', $language);
            $conf('site.homeURL',$menuLanguage[$languageId]['url']); // homepage for current language 
            $conf('site.isAuthed', $this->isAuthed);
        }
        

        
        $conf('page', $elt);
        $conf('page.breadcrumb', $breadcrumb);
        $conf('page.url', $url);
        $conf('page.urlFound', $found);
        $conf('page.urlNotFound', $notFound);
        $conf('page.urlParts', explode('/', $url));

        $url = $this->config->value('page.urlFound');

        $menu = $conf("site.menu.main");

        $url = trim($url, '/');
        
        if ($menu) {
            $menu2= [];
            foreach ($menu as $key => &$item) {
                if (! $this->isPageAccessible($item['url']))continue;////
                $tmpElt = $this->mempad->getElementByUrl($item['url']);
                if(strpos($tmpElt->title, '!') === 0) $item['label'] = '! '. $item['label']  ;
                // $item['active'] = ($url === $item['url']
                //     ||
                //     ($item['url'] !== ''
                //         && strpos($url, $item['url']) === 0)) ? 'active' : '';
                $item['active'] = ($url === $item['url']) ? 'active' : '';
                        
            $menu2[] = $item;
            }
            //$conf("site.menu.main", null);
           //if($this->isAuthed) $menu2[] =['label'=>'! Admin', 'url'=>$GLOBALS['USER']['adminPage']];
           if( $this->isPageAccessible('help')) $menu2[] =['label'=>'! Help', 'url'=> 'help'];
           
           $conf("site.menu.main", $menu2,true);

          
        }

        

        
        $folders = [];

        $theme = $conf('page.theme') ?? $conf('pages.theme') ?? $conf('site.theme') ?? 'default';

        // @include_once __DIR__ . "/../themes/$theme/functions.php";

        $conf('page.theme', $theme);

        // first we try phisical files in website "assets" folder
        $folders[] = getcwd() . '/assets/' . $theme;
        // else a theme in the global themes folder
        $folders[] = __DIR__ . '/../themes/' . $theme;
        // else the "default" theme folder
        $folders[] = __DIR__ . '/../themes/default';

        //$theme = dirname($this->filename); //theme in
        return trim($this->render($folders, $this->config, $this->mempad)
            . "<!--"
            . round((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000)
            . "ms -->");
    }

    private function render(array $folders, &$config, &$mempad)
    {

        $this->markdownParser = new \Michelf\MarkdownExtra;
        $this->markdownParser->hard_wrap = true;

        $this->contents = [];
        $this->templates = $folders;
        $this->config = &$config;
        $this->mempad = &$mempad;

        $conf = $this->config;

        $content = $conf('page.rawContent');

        // temp: while md lib does not do it as it should
        $content = preg_replace('"\n(https?://\S+)"', '<a href="$1" target="_blank">$1</a>', $content);
        $content = $this->shortcodes->process($content);
        $content = $this->markdownParser->transform($content);
        $content = str_replace('<table>', '<table class="table table-striped table-bordered">', $content);
        $content = str_replace('<thead>', '<thead class="table-dark">', $content);
        if ($conf('page.urlNotFound') !== '' 
        || ! $this->isPageAccessible($conf('page.url') )) {
            $content = $this->Page404();
        }

        $conf('page.content', trim($content));
        $page = null;

        $template = $conf('page.template') ?? $conf('pages.template') ?? $conf('site.template') ?? 'index';

        // for ($i = 0; $i < count($this->templates); $i++) {
        //     if (is_file($this->templates[$i] . "/templates/$template.html")) {
        //         $this->themeFolder = $this->templates[$i] . "/templates/";
        //         $page = file_get_contents($this->templates[$i] . "/templates/$template.html");
        //         break;
        //     }
        // }

        // if (!$page) {
        //     die("template not found: $template in" . print_r($this->templates, true));
        // }

        $page = $this->resolve_template($template);

        $page = $this->shortcodes->process($page);
        return $this->absPath($page);
    }

    /**
     * urlManager
     * finds the longest valid Path in the URL
     * used for meaningful 404 and to mount external files
     * (the not found part will be used to find md files)
     * @param  String $url
     * @return String the longuest valid Path in the URL
     */
    private function urlManager($url)
    {

        if ($this->mempad->getElementByUrl($url)) {
            return $url;
        }

        $parts = explode('/', $url);
        $found = '';
        $pop = array_shift($parts);
        while ($this->mempad->getElementByUrl($found . '/' . $pop) !== null) {
            $found = $found . '/' . $pop;
            $pop = array_shift($parts);
        }

        return trim($found, '/'); //$found;
    }
    /**
     * pagify will add infos to an element
     *
     * @param  mixed $elt
     * @return void
     */
    private function pagify(&$elt)
    {
        if (!$elt) {
            return '';
        }

        $conf = $this->config;
        $elt->rawPage = $this->mempad->mpContent[$elt->id];
        $a = $this->mempad->getPreviousAndNextSibblings($elt->id);
        if(!$this->isPageAccessible($a[0]->url ?? false))$a[0] = false;
        if(!$this->isPageAccessible($a[1]->url ?? false))$a[1] = false;
         
        $elt->previous = $a[0];
        $elt->next = $a[1];

        // frontmatter...
        $a = $this->getFrontmatterAndContent($elt->id);

        $fm = $a[0];
        $elt->frontmatter = null;
        if ($fm && $pageFm = $fm->parsed) {

            $elt->frontmatter = $fm->parsed;

            if ($pageFm !== null) {
                $array = is_array($pageFm) ? $pageFm : get_object_vars($pageFm);
                foreach ($array as $prop => $value) {
                    $elt->$prop = $value;
                }



                if ($array['site'] ?? false) {
                    // echo 'array[site] ';
                    // var_dump($array['site'] );
                    foreach ($array['site'] as $prop => $value) {
                      
                        $conf("site.$prop", $value,false);
                    }
                   // $conf('site', $array['site'],true);
                }

                if (($array['pages'] ?? false)) {
                    foreach ($array['pages'] as $prop => $value) {
                        $array['pages'][$prop] = $this->shortcodes->process($value);
                        $conf("pages.$prop", $value,false);
                    }
                    //$conf('pages', $array['pages'],0);
                }
                $pages = $conf->value('pages');

                if ($pages) {
                    foreach ($pages as $prop => $value) {
                        $elt->$prop = $value;
                    }
                }

                if ($array['page'] ?? false) {
                    foreach ($array['page'] as $prop => $value) {
                        //$elt->$prop = $value;
                        $elt->$prop = $this->shortcodes->process($value);

                    }
                }
            }
            $elt->rawContent = $a[1];
            return;
        }

        $pages = $conf->value('pages');
        if ($pages) {
            foreach ($pages as $prop => $value) {
                $elt->$prop = $value;
            }
        }

        $elt->rawContent = $elt->rawPage;
    }

    /**
     * getFrontmatterAndContent
     *
     * @param  mixed $id
     * @return array of frontMatter and raw content
     */
    public function getFrontmatterAndContent($id)
    {
        $content = trim($this->mempad->mpContent[$id]);

        if (preg_match('/^[=]{3}\s*\n(.*)\n[=]{3}\s*(.*)$/ms', $content, $m)) {
            $fm = new Infini(true);
            $fm->parseString($m[1]);
            return [$fm, $m[2]];
        }

        return [null, $content];
    }

    public function Page404()
    {
        header("HTTP/1.0 404 Not Found");

        $content = ($this->config)('site.error404') ?? '404';
        $content = $this->shortcodes->process($content);
        $content = $this->markdownParser->transform($content);
        return $content;
    }

    public function getTitleAndContentFromMarkdownFile($filename)
    {

        $content = file_get_contents($filename);
        if (
            substr($content, 0, 3) === '==='
            && preg_match('/^[=]{3}\s*(\n.*?)[=]{3}\s*\n(.*)$/ms', $content, $m) != false
            || substr($content, 0, 3) === '---'
            && preg_match('/^[-]{3}\s*(\n.*?)[-]{3}\s*\n(.*)$/ms', $content, $m) != false
        ) {

            if (preg_match('/\ntitle: (.*?)\n/ms', $m[1], $title)) {

                return [trim($title[1]), $m[2]];
            }

            return [null, $m[2]];
        }

        // echo "$filename No Title\n";
        return [null, $content];
    }

    

    public function registerComponent($component, $selfRemoveOnEmpty = true)
    {

        $this->shortcodes->addShortcode($component, function ($attributes, $content, $tagName)
        use ($selfRemoveOnEmpty) {
            $content = trim($content) ;
            return $this->templateHandler($attributes, $content, $tagName, $selfRemoveOnEmpty);
        });
    }



    /**
     * templateHandler($attributes, $content, $tagName)
     *  example : [offcanvas id="toto" position="left"] content [/offcanvas]
     * @param  $attributes, $content, $tagName
     * @return string rendered content
     */
    private function templateHandler($attributes, $content, $tagName, $selfRemoveOnEmpty)
    {
        static $rec = 0;

        if ($selfRemoveOnEmpty && trim($content) == '') {
            return '';
        }

        $rec++;

        $html = $this->resolve_template('_' . $tagName);
        if ($rec > 10) {
            die('recurtion spotted in ' . $tagName);
        }

        $content = $this->markdownParser->transform($content);
        //$content = $this->shortcodes->process($content);
        $attributes['content'] = trim($content);

        foreach ($attributes as $varname => $item) {

            $html = str_replace(
                "{= item.$varname}",
                $attributes[$varname],
                $html
            );

            $cond = $attributes[$varname] ? 'true' : 'false';
            $html = str_replace(
                "{if item.$varname}",
                "{if $cond}",

                $html
            );
        }
        if ($attributes['this'] ?? false) {

            $that = $attributes['this'];
            
            $html = str_replace(
                ' this',
                " $that",
                $html
            );
        }
        if ($attributes['this.items'] ?? false) {

            $that = $attributes['this.items'];
          
            $html = str_replace(
                ' this.items',
                " $that",
                $html
            );
        }
 
        $rec--;

        return $this->shortcodes->process($html);
    }

    // replace for example  0 => "info"   "info" => "info"
    public function setKeys(&$attributes)
    {

        foreach ($attributes as $key => $attribute) {
            if (is_integer($key)) {
                $attributes[$attribute] = $attribute;
                unset($attributes[$key]);
            }
        }
    }
    public function renderBlock($elt)
    {

        $content = $this->markdownParser->transform($elt);
        $content = $this->shortcodes->process($content);
        return $content;
    }

    public function renderSubmenu($menu, $level, $isDynamic = false)
    {

        if (!$menu || $level === 0) {
            return '';
        }

        $html = '';
        $url = $this->config->value('page.urlFound');
        foreach ($menu as $key => $item) {

            if (strpos($item->title, '.') === 0) {

                continue;
            }
            if (strpos($item->title, '!') === 0 && !$this->isAuthed) {

                continue;
            }
            

            $classes = [];
            $active = "";
            if ($isDynamic) {
                if (strpos($url, $item->url) === 0) {
                    $classes[] = 'dynamic';
                }
            }

            $url = $this->config->value('page.url');
            $url = $_SERVER['REQUEST_URI']; // not proud of this hack.
            if ($url === substr($_SERVER['PHP_SELF'], 0, -10) . '/' . $item->url) {
                $active = 'class="active"';
            }

            $rendered = $this->renderSubmenu($item->children, $level - 1, $isDynamic);
            if ($rendered && $isDynamic) {
                $classes[] = 'hasChildren';
            }

            $dyn = $classes ? ' class="' . implode(' ', $classes) . '"' : '';
            $html .= "<li$dyn><a href=\"$item->url\" $active> $item->title</a>"
                . $rendered . "</li>\n";
        }

        if ($isDynamic) {
            return "<ul class=\"dynamic\">$html</ul>";
        }

        return "<ul>$html</ul>";
    }

    public function uAttr($attributes)
    {
        $str = '';
        if ($attributes['id'] ?? 0) {
            $str .= ' id="' . $attributes['id'];
        }

        if ($attributes['class'] ?? 0) {
            $str .= ' class="' . $attributes['class'] . '"';
        }

        if ($attributes['style'] ?? 0) {
            $str .= ' style="' . $attributes['style'] . '"';
        }

        if ($attributes['title'] ?? 0) {
            $str .= ' style="' . $attributes['title'] . '"';
        }

        if ($attributes['lang'] ?? 0) {
            $str .= ' lang="' . $attributes['lang'] . '"';
        }

        $classes = '';
        $id= '';
        foreach($attributes as $key=> $value)
        {
           if(is_numeric($key)&& strpos($value, '.') === 0) $classes .= substr($value,1). ' ';
           if(is_numeric($key)&& strpos($value, 'id') === 0) $id .= substr($id,1);
        }

        if($classes !== '')   $str .= ' class="' . trim($classes) . '"';
        if($id !== '')   $str .= ' id="' . $id . '"';
        return trim($str);
    }

    private function fSlide($str, $level, $index2, $index3 = 0)
    {
        $index2++;
        $index3++;
        global $config;

        if ($index2 === 1) {
            $level = '';
        } else if ($this->config->value('site.slides.numbered')) {
            $level = ($level === 2)
                ? "<h2> <small>$index2.</small> "
                : "<h3> <small>$index2.$index3.</small>";
        } else {
            $level = ($level === 2) ? "<h2> " : "<h3> ";
        }

        return <<<SLIDE
    <section data-markdown>
    <script type="text/template">
    $level $str
    </script>
    </section>
    SLIDE;
    }

    public function formatSimpleArray($content, $where)
    {
        $str = "";
        $items = explode('---', trim($content));

        foreach ($items as $key => $values) {

            $str .= "
[$where.$key]
$values
";

    
        }
   

        return $str;
    }

    public function resolve_template($template)
    {
        $str = null;
        for ($i = 0; $i < count($this->templates); $i++) {
            $file = $this->templates[$i] . "/templates/$template.html";

            if (is_file($file)) {

                $str = file_get_contents($file);
                break;
            }
        }

        if ($str === null) {
            echo ("Template $template.html not found in:");
            var_dump($this->templates);
            die('sorry.');
        }
        return $str;
    }

    //for page "bla/bla2/"
    // adding relative include : {+ "../html"}
    // ended up with a path such as "bla/bla2/../html"
    // and will be resolved: "bla/html"
    private function get_absolute_mempad_path($path)
    {
        $parts = array_filter(explode('/', $path), 'strlen');
        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part) {
                continue;
            }

            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        return implode('/', $absolutes);
    }

    private function absPath($str)
    {
        // for URL http://toto.com/pim/pam/poum/index.php
        // will be: "/pim/pam/poum/"
        $root = substr($_SERVER['PHP_SELF'], 0, -10);

        $theme = ($this->config)("page.theme");

        //$str = preg_replace('#="assets/#m', "=\"assets/$theme/", $str);

        $str = preg_replace(
            '#( data| href| src| data-src| action) *= *"([^:\#"]*)("|(?:(?:%20|\s|\+)[^"]*"))#m',
            ' $1="' . $root . '/$2$3',
            $str
        );

        return $str;
    }
}
