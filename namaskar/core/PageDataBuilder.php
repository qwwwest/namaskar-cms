<?php

namespace Qwwwest\Namaskar;

use Qwwwest\Namaskar\MemPad;
use Qwwwest\Namaskar\ZenConfig;
use Qwwwest\Namaskar\Kernel;
use Qwwwest\Namaskar\Shortcodes;

use Michelf\MarkdownExtra;

class PageDataBuilder extends TemplateRenderer
{

    private static $namaskar = null;
    private static $mempads = [];

    private $mempad = null;
    // private $project = null;
    private $mempadFile = null;
    private $dataFolder = null;
    private $shortcodes = null;
    private $markdownParser;
    private $templateRenderer = null;
    private $conf = null;
    private $id = null;
    private $page = null;
    private $content = null;
    public $codeStatus = 200;
    private $toc = '';
    private $isAuthed = false;

    //  public function __construct(string $project)
    public function __construct()
    {
        $this->conf = $zen = Kernel::service('ZenConfig');
        parent::__construct(($this->conf)('folder.templates'));
    }

    private function getMempadFile()
    {
        //  return $this->mempadFile;
    }

    public function breadcrumb($url)
    {
        //todo

    }
    public function Page404()
    {
        //header("HTTP/1.0 404 Not Found");

        $content = (($this->conf)('site.404')
            ?? '# 404{.page404}  
**[= page.url]**');


        $this->codeStatus = 404;
        $content = $this->renderContent($content);
        return $content;
    }

    private function renderBlock($content)
    {
        $content = trim($content);
        $content = $this->shortcodes->process($content);
        $content = $this->markdownParser->transform($content);
        if (strpos($content, '<p>') === 0 && strpos($content, '</p>', strlen($content) - 5))
            $content = trim(substr($content, 3, -5)); // remove '<p>' tags
        return $content;
    }

    private function ____doShortcodes($content)
    {

        $content = $this->shortcodes->process($content);
        return $content;
    }

    public function renderShortcodes($content, $templateRenderer)
    {
        $this->templateRenderer = $templateRenderer;
        $content = $this->shortcodes->process($content);
        return $content;
    }
    /**
     * currentPageMenu
     * process markdown headers to make them links
     * @param  string content;
     * @return string
     */
    public function ___currentPageMenu($content)
    {

        ## Lien
        ##  [Lien Cliquable](#section-2)
        $menu = '';
        $nbItems = 0;
        $oldlen = 1;
        $content = preg_replace_callback(
            '/([#]{2,3})\s+([^\n]+?)\n/s',
            function ($matches) use (&$menu, &$nbItems, &$oldlen) {

                [, $level, $title] = $matches;
                $slug = MemPad::slugify($title);
                $title = trim($title);
                $len = strlen($level);
                $nbItems++;
                $li = "<li class='h$len'>
          <a href='#$slug'>$title</a>";
                if ($oldlen === $len)
                    $menu .= $li;
                if ($oldlen < $len)
                    $menu .= '<ol>' . $li;
                if ($oldlen > $len)
                    $menu .= '</ol>' . $li;
                $oldlen = $len;
                return "<div id='$slug'></div>$level [$title](#$slug)\n";
            },
            $content
        );
        if ($nbItems) {
            if ($oldlen === 3)
                $menu .= '</ol>';
            $this->toc = "<div class='toc'>$menu</div>";
            ($this->conf)('page.toc', $this->toc);
        }

        return $content;
    }

    /**
     * renderContent
     * process shortcodes and markdown
     * @param  string $content
 
     * @return string
     */
    public function renderContent($content)
    {
        ## Mettre en gras ou en italique un passage important
        ##  [Lien Cliquable](#section-2)

        $content = $this->shortcodes->process($content);
        // $content = $this->currentPageMenu($content);
        $content = $this->markdownParser->transform($content);

        return $content;
    }

    /**
     * uAttr
     * create universal attributes for shortcodes 
     * which are id, class, style, title, lang.
     * @param  array $attributes
 
     * @return string
     */
    public function uAttr($attributes): string
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
        $id = '';
        foreach ($attributes as $key => $value) {
            if (is_numeric($key) && strpos($value, '.') === 0)
                $classes .= substr($value, 1) . ' ';
            if (is_numeric($key) && strpos($value, 'id') === 0)
                $id .= substr($id, 1);
        }

        if ($classes !== '')
            $str .= ' class="' . trim($classes) . '"';
        if ($id !== '')
            $str .= ' id="' . $id . '"';
        return trim($str);
    }



    /**
     * registerComponent
     * create a shortcode for an HTML element.
     * @param  string $component
     * @param  bool $selfRemoveOnEmpty 
     * @return void
     */
    public function registerComponent($component, $selfRemoveOnEmpty = true)
    {
        $this->shortcodes->addShortcode($component, function ($attributes, $content, $tagName) use ($component, $selfRemoveOnEmpty) {
            $content = trim($content);
            //return "TODO:$component :: $content";
            return $this->renderTemplate($attributes, $content, $tagName, $selfRemoveOnEmpty);
            // renderTemplate
        });
    }
    /**
     * registerHtmlElement
     * create a shortcode for an HTML element.
     * @param  string $element
     * @return void
     */
    public function registerHtmlElement($element)
    {

        $this->shortcodes->addShortcode($element, function ($attributes, $content, $tagName) {

            $uAttr = $this->uAttr($attributes);

            $content = trim($content);
            $content = <<<blep
            <$tagName $uAttr markdown=1>$content</$tagName>
            blep;

            $content = $this->renderContent($content);

            return $content;
        });
    }

    private function getMenuItem($url)
    {
        if ($url === '//')
            return $this->mempad->getRootElements();
        if ($url === '/')
            return $this->mempad->getHome()->children;

        return $this->mempad->getElementByUrl($url)->children ?? null;
    }


    /**
     * laConf
     * get the Language Aware Configuration settings, 
     * @param  string $value
     * @return mixed the value for the current page language
     * 
     */
    public function laConf(string $value)
    {
        $conf = $this->conf;
        if ($conf('site.language.menu') === null)
            return $conf($value);
        $language = $conf('page.language');
        return $conf("$language.$value")
            ?? $conf($value);
    }
    /**
     * renderMainContent
     * create the object containing all the configuration settings, page content...
     * it is quite opiniated... it handles the main menu, handle languages from url,
     * @param  string $url
     * @return string rendered page in html
     */

    public function renderMainContent(string $url): ?string
    {
        $conf = $this->conf;

        $absroot = $conf('absroot');
        $mempadFile = $conf('mempadFile');
        $this->mempad = new MemPad($mempadFile, $absroot);
        $conf('MemPad', $this->mempad);


        $conf('asset', "$absroot/asset");
        $conf('media', "$absroot/media");
        $conf('homepath', "$absroot");

        // important vars

        $this->mempad = $conf('MemPad');
        $this->shortcodes = new Shortcodes;
        require_once 'NamaskarShortcodes.php';

        $this->markdownParser = new MarkdownExtra;
        $this->markdownParser->hard_wrap = true;

        $conf = $this->conf;

        $url = \trim($url, '/');

        if ($url === '') {
            $url = '/';
        }

        $found = $this->urlManager($url); // is the url found in mempad

        $notFound = substr($url, strlen($found)); //is the rest, to try to find as regular files

        $elt = $this->mempad->getElementByUrl($found);

        // Languages in url
        $languages = []; //will be something like ['fr', 'en', 'ru']
        $languageMenu = $conf('site.language.menu');
        if ($languageMenu) {
            foreach ($languageMenu as $value) {
                $language = $value['url'] ?? false;
                if ($language) {
                    $languages[$language] = $language;
                }
            }
        }


        // *BREADCRUMB*
        $parts = explode("/", trim($found, '/'));
        $slug = array_shift($parts);
        $breadcrumb = [
            [
                'title' => null,
                'url' => $languages[$slug] ?? '',
            ]
        ];

        $path = '';

        //HOME
        if (
            $found === '' || $found === "/"
            || $found === ($languages[$slug] ?? null)
        ) {
            $elt = $this->mempad->getElementByUrl($languages[$slug] ?? '');

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
                !in_array($elt->url, $languages)
                    && $elt && $elt->title !== '$HOME' && $breadcrumb[] = [
                        'title' => $elt->title,
                        'url' => $elt->url ?? '',
                        'active' => ($slug === null),
                    ];
            }
        }

        // 
        $menuLanguage = $conf('site.language.menu');
        if ($menuLanguage) {
            $language = $conf('site.language.default');
            $languageId = null;
            $languageDefaultId = null;
            foreach ($menuLanguage as $key => $item) {
                $languageUrl = $item['url'] ?? false; // url or main home

                if ($languageUrl === '' || $languageUrl === '/') {

                    $languageDefaultId = $key;
                }
                $menuLanguage[$key]['active'] = false;
                if ($languageUrl && strpos($url, $languageUrl) === 0) {

                    $language = $languageUrl;
                    $languageId = $key;
                    break;
                }
            }


            if ($languageId === null) {
                $languageId = $languageDefaultId;
            }

            $menuLanguage[$languageId]['active'] = true;
            $languageInUrl = $menuLanguage[$languageId]['url'];
            $conf('site.language.menu', $menuLanguage);

            $conf('site.homeURL', $menuLanguage[$languageId]['url']); // homepage for current language 
            $conf('site.isAuthed', $this->isAuthed);
        } else {
            $language = $conf('site.language.default');
        }

        // hooks
        // foreach ($conf('site.hooks') as $key => &$item) {

        //     if (Router::isRouteMatch($item['route'], $conf('url'))) {
        //         foreach ($item as $key => $val) {


        //             $conf("$key", $val);
        //             dump($conf("page"));

        //         }
        //         ;
        //     }
        // }

        $conf('page', $elt);
        $conf('page.language', $language);
        if ($languageId ?? false)
            $conf('page.languageHome', $menuLanguage[$languageId]['url']);
        else
            $conf('page.languageHome', '/');

        $conf('page.breadcrumb', $breadcrumb);
        $conf('page.url', $url);
        $conf('page.urlFound', $found);
        $conf('page.urlNotFound', $notFound);
        $conf('page.urlParts', explode('/', $url));

        $url = $this->conf->value('page.urlFound');


        $menu = $this->laConf("site.menu.main");

        $url = trim($url, '/');

        if ($menu) {
            $menu2 = [];
            foreach ($menu as $key => &$item) {
                if (!$this->isPageAccessible($item['url']))
                    continue;
                $tmpElt = $this->mempad->getElementByUrl($item['url']);
                if (strpos($tmpElt->title, '!') === 0)
                    $item['label'] = '! ' . $item['label'];

                $item['active'] = ($url === $item['url'])
                    || $item['url'] && strpos($url, $item['url']) === 0
                    && $conf('page.languageHome') !== $item['url'] && $item['url'] !== ''
                    ? true : false;

                $menu2[] = $item;
            }
            //$conf("site.menu.main", null);
            //if($this->isAuthed) $menu2[] =['label'=>'! Admin', 'url'=>$GLOBALS['USER']['adminPage']];
            if ($this->isPageAccessible('help'))
                $menu2[] = ['label' => '! Help', 'url' => 'help'];

            $conf("site.menu.main", $menu2);
        }

        if ($conf('site.menu.aside.left')) {


            $submenuUrl = $conf('site.menu.aside.left.menu');
            $submenuLevel = $conf('site.menu.aside.left.level') ?? -1;
            $submenu = $this->getMenuItem($submenuUrl);

            $submenu = $this->getMenuItem($conf('site.menu.aside.left.menu'));

            $conf('page.sidemenu', $submenu);
            //$conf('page.sidemenu', $submenu);
        }



        if ($conf('page.menu.aside.left')) {

            $submenuUrl = $conf('page.menu.aside.left.menu');
            $submenuLevel = $conf('page.menu.aside.left.level') ?? -1;

            $submenu = $this->getMenuItem($submenuUrl);

            $conf('page.sidemenu', $submenu);
        }

        $content = $conf('page.rawContent');

        if ($conf('site.auto.title') === 'yes') {
            $content = "# " . $conf('page.title') . "\n\n$content";
        }
        if ($conf('page.type') === 'slides') {
            $content = str_replace("\n### ", "\n---\n### ", $content);
            $content = str_replace("\n## ", "\n--\n## ", $content);
        } else
            $content = trim($this->renderContent($content));


        // temp: autolinking
        // $content = preg_replace('"\n(https?://\S+)"', '<a href="$1" target="_blank">$1</a>', $content);
        // $content = $this->shortcodes->process($content);

        // $content = str_replace('<table>', '<table class="table table-striped table-bordered">', $content);
        // $content = str_replace('<thead>', '<thead class="table-dark">', $content);


        if (
            $conf('page.urlNotFound') !== ''
            //  || !$this->isPageAccessible($conf('page.url'))
        ) {

            $content = $this->Page404();
        }



        $conf('page.content', trim($content));

        /******** end renderPage ********/
        return $content;
    }




    /**
     * urlManager
     * finds the longest valid Path in the URL
     * used for meaningful 404 and to mount external files
     * (the not found part will be used to find md files)
     * @param  string $url
     * @return string the longuest valid Path in the URL
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
     * getFrontmatterAndContent
     *
     * @param  mixed $id
     * @return array of frontMatter and raw content
     */
    public function getFrontmatterAndContent($id)
    {
        $content = trim($this->mempad->mpContent[$id]);

        if (preg_match('/^[=]{3}\s*\n(.*)\n[=]{3}\s*(.*)$/ms', $content, $m)) {
            $fm = new ZenConfig(true);
            $fm->parseString($m[1]);
            return [$fm, $m[2]];
        }

        return [null, $content];
    }

    /**
     * pagify will add infos to an element
     * like previous and next element,
     * render the frontmatter...
     * 
     * @param  mixed $elt
     * @return void
     */
    private function pagify(&$elt)
    {
        if (!$elt) {
            return;
        }

        $conf = $this->conf;
        $elt->rawPage = $this->mempad->mpContent[$elt->id];
        $a = $this->mempad->getPreviousAndNextSibblings($elt->id);
        if (!$this->isPageAccessible($a[0]->url ?? false))
            $a[0] = false;
        if (!$this->isPageAccessible($a[1]->url ?? false))
            $a[1] = false;

        $elt->previous = $a[0];
        $elt->next = $a[1];


        // hooks


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
                    foreach ($array['site'] as $prop => $value) {

                        $conf("site.$prop", $value);
                    }
                    // $conf('site', $array['site'],true);
                }

                if (($array['pages'] ?? false)) {
                    foreach ($array['pages'] as $prop => $value) {
                        $conf("pages.$prop", $value);
                    }


                }
                $pages = $conf->value('pages');

                if ($pages) {
                    foreach ($pages as $prop => $value) {
                        $elt->$prop = $value;
                    }
                }

                if ($array['page'] ?? false) {
                    foreach ($array['page'] as $prop => $value) {
                        $elt->$prop = $value;
                        // $elt->$prop = $this->shortcodes->process($value);

                    }
                }
            }
            //hooks

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

    private function fSlide($str, $level, $index2, $index3 = 0)
    {
        $index2++;
        $index3++;
        global $config;

        if ($index2 === 1) {
            $level = '';
        } else if ($this->conf->value('site.slides.numbered')) {
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

    /**
     * isPageAccessible
     * When a page title start with "!" it should be visible only if cnnected in the backend
     * when a page starts with "." it is not rendered
     * when a page does not exist, it returns false
     * @param  string $url
     * @return boolean 
     */

    public function isPageAccessible($url)
    {
        $elt = $this->mempad->getElementByUrl($url);

        if (!$elt)
            return false;
        if (strpos($elt->title, '.') === 0)
            return false;
        if (strpos($elt->title, '!') === 0)
            return $this->isAuthed;
        if (strpos($elt->path, '/!') !== false)
            return $this->isAuthed;
        if (strpos($elt->path, '!') !== false)
            return $this->isAuthed;

        return true;
    }

    public function isElementAccessible($elt)
    {
        // $elt = $this->mempad->getElementByUrl($url);

        if (!$elt)
            return false;
        if (strpos($elt->title, '.') === 0)
            return false;
        if (strpos($elt->title, '!') === 0)
            return $this->isAuthed;
        if (strpos($elt->path, '/!') !== false)
            return $this->isAuthed;
        if (strpos($elt->path, '!') !== false)
            return $this->isAuthed;

        return true;
    }
    /**
     * setKeys
     * add keys to attributes with only numeric keys
     * replace for example: [0 => "info"] with ["info" => "info"]
     * @param  array $attributes
     * @return void 
     */
    public function setKeys(&$attributes)
    {

        foreach ($attributes as $key => $attribute) {
            if (is_integer($key)) {
                $attributes[$attribute] = $attribute;
                unset($attributes[$key]);
            }
        }
    }
    /**
     * shortCode2Template
     * finds the right template for a shortcode.
     * example : [offcanvas id="toto" position="left"] content [/offcanvas]
     * @param  $attributes, $content, $tagName
     * @return string rendered content
     */
    public function shortCode2Template($shortcode, $template = null, $selfRemoveOnEmpty = true, $initArray = [])
    {
        if ($template === null)
            $template = $shortcode;

        $this->shortcodes->addShortcode($shortcode, function ($attributes, $content) use ($shortcode, $selfRemoveOnEmpty, $template, $initArray) {

            // $attributes['type'] = $shortcode;
            $attributes = array_merge($attributes, $initArray);

            if (isset($attributes['elts'])) {
                $elts = $attributes['elts'];

                if ($elts === '.') {
                    $url = $this->conf->value('page.url');
                    $elts = $this->mempad->getElementByUrl($url)->children;
                } else if ($elts === '//') {
                    $elts = $this->mempad->getRootElements();
                } else
                    if (substr($elts, -2) === '/*') {
                        $elts = substr($elts, 0, strlen($elts) - 2);

                        $elts = $this->mempad->getElementByUrl($elts)->children;
                    } else {
                        $elts = $this->mempad->getElementByUrl($elts);
                    }
                $attributes['elts'] = $elts;


            }
            static $rec = 0;
            $content = trim($content);

            if ($selfRemoveOnEmpty && $content == '') {
                return '';
            }

            $rec++;

            if ($rec > 10) {
                die('recurtion spotted in ' . $template);
            }

            $theme = ($this->conf)('site.theme');
            $templateFile = "$theme/$template.html";

            $content = $this->renderBlock($content);

            $attributes['content'] = $content;
            ob_start();

            $this->include($templateFile, $attributes);
            $html = ob_get_clean();


            $rec--;

            return $this->shortcodes->process($html);
        });
    }
    /**
     * renderTemplate($attributes, $content, $tagName)
     *  example : [offcanvas id="toto" position="left"] content [/offcanvas]
     * @param  $attributes, $content, $templateName
     * @return string rendered content
     */
    private function renderTemplate($attributes, $content, $templateName, $selfRemoveOnEmpty)
    {
        static $rec = 0;

        if ($selfRemoveOnEmpty && trim($content) == '') {
            return '';
        }

        $rec++;

        if ($rec > 10) {
            die('recurtion spotted in ' . $templateName);
        }
        //   $templateFile = $this->resolve_template($tagName);
        $theme = ($this->conf)('site.theme');
        $templateFile = "$theme/$templateName.html";

        ob_start();
        $this->include($templateFile, $attributes);
        $html = ob_get_clean();
        // $content = $this->markdownParser->transform($content);
        // $content = $this->shortcodes->process($content);
        // $attributes['content'] = trim($content);

        $rec--;
        return $html;
        return $this->shortcodes->process($html);
    }



}
