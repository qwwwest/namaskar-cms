<?php

namespace Qwwwest\Namaskar;

use Qwwwest\Namaskar\MemPad;
use Qwwwest\Namaskar\ZenConfig;
use Qwwwest\Namaskar\Kernel;
use Qwwwest\Namaskar\Shortcodes;



class PageModel
{

    private $mempad = null;

    private $conf = null;
    public $codeStatus = 200;
    private $toc = '';
    private $isAuthed = false;


    public function __construct()
    {
        $this->conf = Kernel::service('ZenConfig');

    }


    public function Page404()
    {

        $content = (($this->conf)('site.404')
            ?? '# 404 page not found 
## [= page.url] ');


        $this->codeStatus = 404;

        return $content;
    }


    /**
     * laConf
     * get the Language Aware Configuration settings, 
     * [[ en ]]
     * ...
     * 
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
     * buildModel
     * create the object containing all the configuration settings, page content...
     * it is quite opiniated... it handles the main menu, languages from url,
     * @param  string $url
     * @return string rendered page in html
     */

    public function buildModel(string $url): ?string
    {
        $conf = $this->conf;

        $absroot = $conf('absroot');
        $mempadFile = $conf('mempadFile');
        $this->mempad = new MemPad($mempadFile, $absroot);
        $conf('MemPad', $this->mempad);

        $conf('asset', "$absroot/asset");
        $conf('media', "$absroot/media");
        $conf('homepath', "$absroot");


        $this->mempad = $conf('MemPad');



        $url = \trim($url, '/');

        if ($url === '') {
            $url = '/';
        }

        $found = $this->urlManager($url); // is the url found in mempad

        $notFound = substr($url, strlen($found)); //is the rest, to try to find as regular files

        $elt = $this->mempad->getElementByUrl($found);

        // Languages found in url
        $languagesInUrl = []; //will be something like ['fr', 'en', 'ru']
        $languageMenu = $conf('site.language.menu');
        if ($languageMenu) {
            foreach ($languageMenu as $value) {

                $languageFound = $value['url'] ?? false;
                if ($languageFound) {
                    $languagesInUrl[$languageFound] = $languageFound;
                }
            }
        }
        // $languageMenu = $conf('site.menu.language');
        // if ($languageMenu) {
        //     foreach ($languageMenu as $value) {
        //         $language = $value['url'] ?? false;
        //         if ($language) {
        //             $languages[$language] = $language;
        //         }
        //     }
        // }

        // *BREADCRUMB*
        $parts = explode("/", trim($found, '/'));
        $slug = array_shift($parts);
        $breadcrumb = [
            [
                'title' => null,
                'url' => $languagesInUrl[$slug] ?? '',
            ]
        ];

        $path = '';

        //HOME
        if (
            $found === '' || $found === "/"
            || $found === ($languagesInUrl[$slug] ?? null)
        ) {
            $elt = $this->mempad->getElementByUrl($languagesInUrl[$slug] ?? '');

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
                !in_array($elt->url, $languagesInUrl)
                    && $elt && $breadcrumb[] = [
                        'title' => $elt->title,
                        'url' => $elt->url ?? '',
                        'active' => ($slug === null),
                    ];
            }
        }

        $conf('page', $elt);
        $languageMenu = $conf('site.language.menu');
        $language = $conf('site.language.default');
        $languageKey = 0;
        if ($languageMenu) {



            foreach ($languageMenu as $key => $item) {

                //ex:  "en" or "de" or "" for home
                $languageSlug = $item['url'];

                $languageMenu[$key]['active'] = false;
                // $languageSlug != "" and url start with a language
                if ($languageSlug && strpos($url, $languageSlug) === 0) {
                    $language = $languageSlug;
                    $languageKey = $key;
                    break;
                }
            }


            $languageMenu[$languageKey]['active'] = true;
            $languageInUrl = $languageMenu[$languageKey]['url']; // "" or "de" or "en"
            $conf('site.language.menu', $languageMenu);

            // homepage for current language: 
            $conf('site.homeURL', $languageMenu[$languageKey]['url']);
            $conf('page.homeURL', $languageMenu[$languageKey]['url']);

            $conf('site.isAuthed', $this->isAuthed);
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

        //        $conf('page', $elt);
        $conf('page.language', $language);

        // if ($languageKey ?? false)
        //     $conf('page.languageHome', $languageMenu[$languageKey]['url']);
        // else
        //     $conf('page.languageHome', '/');

        $conf('page.breadcrumb', $breadcrumb);
        $conf('page.url', $url);
        $conf('page.urlFound', $found);
        $conf('page.urlNotFound', $notFound);
        $conf('page.urlParts', explode('/', $url));

        $url = $this->conf->value('page.urlFound');


        $this->siteMenuMain($url);


        $content = $conf('page.rawContent');

        if ($conf('site.seo'))
            $this->buildPageSEO();

        if ($conf('theme.fonts')) {

            $fonts = $this->includeGoogleFonts(explode(
                '|',
                $conf('theme.fonts.load')
            ));
            $conf('site.fonts', $fonts);

        }



        // AUTOLINK: 
        $content = preg_replace('"\n(http[s]?://[^\n]+?)\n"', '<a href="$1" target="_blank">$1</a>' . "\n", $content);

        // encode emails
        $content = preg_replace(
            '"\nmailto:(.+?)\n"',
            'mailto:[encode $1 /]' . "\n",
            $content
        );

        if ($conf('site.auto.title') === 'yes') {
            $content = "# " . $conf('page.title') . "\n\n$content";
        }

        if ($conf('page.type') === 'slides') {
            $content = str_replace("\n### ", "\n---\n### ", $content);
            $content = str_replace("\n## ", "\n--\n## ", $content);
        }

        if (
            $conf('page.urlNotFound') !== ''
            //  || !$this->isPageAccessible($conf('page.url'))
        ) {

            $content = $this->Page404();
        }




        $conf('page.content', trim($content));

        /******** end buildModel ********/
        return $content;
    }


    private function buildPageSEO()
    {

        $conf = $this->conf;
        $content = $conf('page.rawContent');


        $conf('seo.description', $conf('page.seo.description') ?? $this->getDescription($content));
        $conf('seo.locale', $conf('site.seo.locale'));



    }

    private function getDescription($markdown)
    {
        // Split the markdown string into lines

        $markdown = str_replace('<br>', ' ', $markdown);
        $markdown = str_replace('*', '', $markdown);
        $markdown = strip_tags($markdown);
        $lines = explode("\n", $markdown);
        $headers = [];

        foreach ($lines as $line) {
            // Check if the line starts with "## "
            if (strpos($line, '# ') === 0) {
                // Remove the "## " prefix and trim the line
                $headers[] = trim(substr($line, 2));
            } else
                if (strpos($line, '## ') === 0) {
                    // Remove the "## " prefix and trim the line
                    $headers[] = trim(substr($line, 3));
                }
        }

        // Concatenate the headers with a space between them
        return implode(' - ', $headers);
    }

    private function isUrlActive($itemUrl): bool
    {
        $url = ($this->conf)('page.url');
        return ($url === $itemUrl)
            || $itemUrl && strpos($url, $itemUrl) === 0
            && ($this->conf)('page.homeURL') !== $itemUrl && $itemUrl !== ''
            ? true : false;
    }
    private function siteMenuMain($url)
    {


        $conf = $this->conf;
        $url = trim($url, '/');

        $menuauto = $this->laConf("site.auto.menu.main");
        if ($menuauto) {
            $menu2 = [];
            $elt = $this->mempad->getElementByPath($menuauto);
            $children = $elt->children;

            // $items = $this->mempad->getChildren($id);

            //dd($elt);
            foreach ($children as $child) {
                if (!$this->isPageAccessible($child->url))
                    continue;

                $menuItem = [];

                $menuItem['label'] = str_replace(' ', '&nbsp;', $child->title);
                $menuItem['url'] = $child->url;
                // $tmpElt = $this->mempad->getElementByUrl($item['url']);
                // if (strpos($tmpElt->title, '!') === 0)
                //     $item['label'] = '! ' . $item['label'];

                $menuItem['active'] = $this->isUrlActive($child->url);

                $menu2[] = $menuItem;
            }


            //$conf("site.menu.main", null);
            //if($this->isAuthed) $menu2[] =['label'=>'! Admin', 'url'=>$GLOBALS['USER']['adminPage']];
            if ($this->isPageAccessible('help'))
                $menu2[] = ['label' => '! Help', 'url' => 'help'];

            $conf("site.menu.main", $menu2);


            return;
        }


        $menu = $this->laConf("site.menu.main");

        if ($menu) {
            $menu2 = [];
            foreach ($menu as $key => &$item) {
                if (!$this->isPageAccessible($item['url']))
                    continue;
                // $item['label'];

                $item['label'] = str_replace(' ', '&nbsp;', $item['label']);
                $tmpElt = $this->mempad->getElementByUrl($item['url']);
                if (strpos($tmpElt->title, '!') === 0)
                    $item['label'] = '! ' . $item['label'];

                $item['active'] = ($url === $item['url'])
                    || $item['url'] && strpos($url, $item['url']) === 0
                    && $conf('page.homeURL') !== $item['url'] && $item['url'] !== ''
                    ? true : false;

                $menu2[] = $item;
            }


            //$conf("site.menu.main", null);
            //if($this->isAuthed) $menu2[] =['label'=>'! Admin', 'url'=>$GLOBALS['USER']['adminPage']];
            if ($this->isPageAccessible('help'))
                $menu2[] = ['label' => '! Help', 'url' => 'help'];

            $conf("site.menu.main", $menu2);

        }
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

        if (preg_match('/^[=]{3}\s*\n(.*?)\n[=]{3}\s*(.*)$/s', $content, $m)) {
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

            $elt->rawContent = trim($a[1]);
            return;
        }

        $pages = $conf->value('pages');
        if ($pages) {
            foreach ($pages as $prop => $value) {
                $elt->$prop = $value;
            }
        }

        $elt->rawContent = trim($elt->rawPage);
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
        if (strpos($elt->path, '/.') !== false)
            return false;


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


    function includeGoogleFonts(array $fontNames): string
    {

        // List of 50 most popular Google Fonts with their valid names
        $popularFonts = [
            'Roboto',
            'Open Sans',
            'Lato',
            'Montserrat',
            'Oswald',
            'Raleway',
            'Poppins',
            'Roboto Slab',
            'Playfair Display',
            'Merriweather',
            'Noto Sans',
            'Ubuntu',
            'Nunito',
            'Work Sans',
            'Arimo',
            'Source Sans Pro',
            'Cabin',
            'PT Sans',
            'Lora',
            'Fjalla One',
            'Quicksand',
            'Titillium Web',
            'PT Serif',
            'Karla',
            'Indie Flower',
            'Rubik',
            'Bitter',
            'Heebo',
            'Libre Baskerville',
            'Overpass',
            'Mukta',
            'Dosis',
            'Josefin Sans',
            'Anton',
            'Comfortaa',
            'Varela Round',
            'Asap',
            'Barlow',
            'Cormorant Garamond',
            'Abel',
            'Signika Negative',
            'Assistant',
            'Dancing Script',
            'Exo 2',
            'Muli',
            'Amatic SC',
            'Zilla Slab',
            'Oxygen',
            'Slabo 27px',
            'Spectral',
            'Manrope'
        ];

        // Filter the fonts to include only valid names
        $validFonts = array_intersect($fontNames, $popularFonts);

        // If no valid fonts, return an empty string
        if (empty($validFonts)) {
            die("PageModel::includeGoogleFonts: no valid font found '$fontNames'");
        }

        // Prepare the Google Fonts URL
        $encodedFonts = array_map('urlencode', $validFonts);

        $families = [];
        $styles = "";

        $colors = "";

        $themeColors = N("theme.colors");
        if ($themeColors)
            foreach ($themeColors as $key => $color) {

                $colors .= "--bs-$key: $color;\n";

            }

        $fontText = N('theme.fonts.text');
        //$fontHeaders = N('theme.fonts.title') ?? $fontText;
        [$fontHeaders, $fontHeadersColor] = $this->getFontAndColor(N('theme.fonts.title') ?? $fontText);

        $fontBrandname = N('theme.fonts.brandname') ?? $fontText;

        $fontCode = N('theme.fonts.code') ?? 'monospace';

        $styles = <<<html
        
        :root {
        $colors
            
        --font-text: '$fontText';
        --font-title: '$fontHeaders';
        --font-brandname: '$fontBrandname';
        --font-code: '$fontCode';

        --bs-font-size-base: 1.2rem;
        --bs-body-font-size: var(--bs-font-size-base);
        --bs-heading-color: $fontHeadersColor;

        }
        html;



        foreach ($encodedFonts as $key => $font) {
            $families[] = "family=$font:ital,wght@0,400;0,700;1,400;1,700&display=swap";

        }
        $families = implode('&', $families);
        $code = <<<html
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?{$families}&display=swap" rel="stylesheet">
        <style>$styles</style>
        html;


        return $code;
    }


    public function getFontAndColor($str): array
    {
        $arr = explode(' ', $str);

        if (!isset($arr[1]))
            $arr[1] = 'body-color';

        $font = $arr[0];
        $color = "var(--bs-$arr[1])";


        return [$font, $color];



    }




}
