<?php
use Qwwwest\Namaskar\Kernel;

$this->registerComponent('offcanvas');
$this->registerComponent('sidebar');
$this->registerComponent('breadcrumb', false);
$this->registerComponent('quote');

$this->registerHtmlElement('p');
$this->registerHtmlElement('div');

$this->shortCode2Template('alert');
$this->shortCode2Template('highlight', 'alert', true, ['type' => 'highlight']);
$this->shortCode2Template('info', 'alert', true, ['type' => 'info']);
$this->shortCode2Template('danger', 'alert', true, ['type' => 'danger']);
$this->shortCode2Template('warning', 'alert', true, ['type' => 'warning']);





$this->shortcodes->addShortcode('submenu', function ($attributes, $content, $tagName) {


    $dynamic = isset($attributes[1]) && $attributes[1] !== 'full';
    $type = $dynamic ? 'dynamic' : 'full';
    $attributes['type'] = $type;
    $attributes['level'] = $type === 'full' ? 1000 : $attributes[1];
    $attributes['dynamic'] = $dynamic;
    $attributes['full'] = !$dynamic;

    $elts = null;
    $url = $attributes[0];
    //  die($url);
    if ($url === '//') {
        $elts = $this->mempad->getRootElements();
    } else if ($url === '/') {
        $elts = $this->mempad->getHome()->children;
    } else {

        $elts = $this->mempad->getElementByUrl($attributes[0]);
        if ($elts) {
            $elts = $elts->children;
        } else
            die("[submenu $url /] no children for: $url");

    }


    $attributes['elts'] = $elts;

    // die("submenu: not found elements:'$url'");


    return $this->renderTemplate($attributes, $content, 'submenu', false);
});




$this->shortcodes->addShortcode('youtube', function ($attributes, $content, $tagName) {
    $id = $attributes[0];
    $ratio = $attributes[1] ?? '16x9';

    if (in_array("thumbnail", $attributes)) {
        return "<img src=\"https://img.youtube.com/vi/$id/hqdefault.jpg\" />";
    }
    return <<<HTML
    <div class="ratio ratio-$ratio" style="margin: 8px 0">
    <iframe src="https://www.youtube.com/embed/$id" title="YouTube video"  frameborder="0"  allowfullscreen></iframe>
    </div>
    HTML;
});

$this->shortcodes->addShortcode('youtube-videos', function ($attributes, $content, $tagName) {

    $conf = $this->conf;
    $url = $this->conf->value('page.url');

    $youtubeVideos = $this->mempad->getElementByUrl($url)->children;


    $items = [];

    foreach ($youtubeVideos as $item) {

        // post starting with a dot are ignored.
        if (strpos($item->title, '.') === 0) {
            continue;
        }

        $this->pagify($item);
        //$item->date = $item->frontmatter['date'];

        $text = (explode('{...}', trim($item->rawContent)))[0];

        //we get the h1 and the first youtube video id
        // preg_match('/^# (.+?)[\r\n]/', $text, $matches);
        //$item->h1 = $matches[1];

        //we catch the id of the first youtube video
        preg_match('/{youtube ([^\}]*)/', $text, $matches);
        $item->youtubeId = $matches[1] ?? false;

        if ($item->youtubeId)
            $items[] = (array) $item;
    }


    $conf("page.youtube-videos", $items);

    $content = $this->renderBlock("{> youtube-videos}");
    $content = $this->shortcodes->process($content);




    return '<section class="youtubeVideos">' . $content . '</section>';
});

$this->shortcodes->addShortcode('video-background', function ($attributes, $content, $tagName) {
    $id = $attributes[0];
    $loop = $attributes['loop'] ?? '';

    return <<<HTML
        <section class="video-bg-container">
        <video class="video-bg-fullscreen" src="media/video/$id" playsinline autoplay muted $loop>
        </video>
        <div class="video-bg-top">
          
        </div>
        <div class="video-bg-content">
            $content
        </div>

        </section>
        HTML;
});

$this->shortcodes->addShortcode('video-bg-fullscreen', function ($attributes, $content, $tagName) {
    $id = $attributes[0];
    $loop = $attributes['loop'] ?? '';

    $html = <<<HTML
         
        {background}  
        <video class="background-fullscreen" src="media/video/$id" playsinline autoplay muted $loop>
        </video>
        {/background}
        HTML;

    return $this->shortcodes->process($html);

});
//////
$this->shortcodes->addShortcode('mp3', function ($attributes, $content, $tagName) {
    // $path = $attributes[0];

    //$folder =  ($this->conf)($attributes[0]);
    //$playlist =  ($this->conf)($attributes[0]);

    $content = $this->renderBlock("{> audio-player $attributes[0]}");
    $content = $this->shortcodes->process($content);
    return $content;
});

$this->shortcodes->addShortcode('.audio', function ($attributes, $content, $tagName) {

    $config = '{"shide_top":true,"shide_btm":false,"auto_load":true}';
    $config = '{"shide_top":false,"shide_btm":true,"auto_load":false}';
    if (!isset($attributes['file']))
        $attributes['this.items'] = $attributes[0];
    else {




        ($this->conf)("page.mp3playerSingleItem", [
            'items' => [
                'src' => $attributes['file'],
                'time' => "",
                'artist' => "",
                'title' => $attributes['file'],
            ]
        ]);

        $attributes['this.items'] = "page.mp3playerSingleItem";

        /////
    }

    return $this->templateHandler($attributes, null, 'audio-player', false);
});


$this->shortcodes->addShortcode('vimeo', function ($attributes, $content, $tagName) {
    $id = $attributes[0];

    $ratio = $attributes[1] ?? '16x9';

    return <<<HTML
<div class="ratio ratio-$ratio" style="margin: 8px 0">
<iframe src="https://player.vimeo.com/video/$id" 
    width="640" height="360" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen>
</iframe>
</div>
HTML;
});

$this->shortcodes->addShortcode('====', function ($attributes, $content, $tagName) {
    $content = explode("\n[==]", $content);
    $nb = count($content);
    $cols = '';
    foreach ($content as $key => $value) {

        $value = $this->shortcodes->process(($value));
        $cols .= <<<COL
<div class="col">
        $value
</div>
COL;
    }

    return <<<HTML

  <div class="row row-cols-1 row-cols-lg-$nb">
  $cols
  </div>

HTML;
});

$this->shortcodes->addShortcode('===', function ($attributes, $content, $tagName) {
    $content = explode("\n[===]", $content);
    $nb = count($content);
    $cols = '';
    foreach ($content as $key => $value) {

        $value = $this->renderBlock(trim($value));
        $cols .= <<<COL
<div class="col">
        $value
</div>
COL;
    }

    return <<<HTML

  <div class="row row-cols-1 row-cols-lg-$nb">
  $cols
  </div>

HTML;
});

$this->shortcodes->addShortcode('?', function ($attributes, $content, $tagName) {

    $var = $this->conf->value($attributes[0]);

    if ($var) {
        return $attributes[1];
    }
    return $attributes[2] ?? '';
});

$this->shortcodes->addShortcode('dump', function ($attributes, $content, $tagName) {

    $var = $this->conf->value($attributes[0]);
    ob_start();
    var_dump($var);
    $var_dump = ob_get_clean();

    return <<<html
    <pre><code>
    $attributes[0] =
    $var_dump
    </code></pre>
html;
});

$this->shortcodes->addShortcode('meta', function ($attributes, $content, $tagName) {

    $meta = <<<META
    <meta name="description" content="{= page.description}" />
    <meta name="robots" content="index, follow" />
    <meta property="og:title" content="{= page.title}" />
    <meta property="og:description" content="{= page.description}" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{= page.url}" />
    <meta property="og:site_name" content="{= site.name}" />
    <meta property="og:image" content="{= page.description}" />
    <meta property="og:locale" content="{= site.language}" />

    <meta name="twitter:card" content="{= page.summary}" />
    <meta name="twitter:title" content="{= page.title}" />
    <meta name="twitter:description" content="{= page.description}" />
    <meta name="twitter:image" content="{= page.description}" />

META;
    return $this->shortcodes->process($meta);
});

// foreach item in LIST
// foreach file in MASK (ex:foreach file in "logo/*.svg")
$this->shortcodes->addShortcode('foreach', function ($attributes, $content, $tagName) {

    $html = '';
    $varname = $attributes[0];
    $op = $attributes[1];

    if ($op !== 'in') {
        die('foreach syntax is "foreach item/file in list/dir" ');
    }

    $content = trim($content);

    //foreach file in dir
    if ($varname === "file") {
        foreach (glob('media/' . $attributes[2] . '/*') as $filename) {

            $file = pathinfo($filename);
            $file['size'] = filesize($filename);
            $file['url'] = $filename;
            $html .= preg_replace_callback(
                "|\{= $varname\.(.+?)\}|",
                function ($matches) use ($file) {
                    $tmp = $file[$matches[1]];
                    return $tmp;
                },

                $content
            );
        }
        if ($html === '') {
            $html = "nothing found :media/" . $attributes[2];
        }
        //  end of folder scanning

        return $this->shortcodes->process($html);
    }

    // foreach item in LIST
    $list = $this->conf->value($attributes[2]);


    if (is_array($list)) {
        foreach ($list as $key => $item) {

            $item['index'] = $key;
            $item['isFirst'] = ($key === 0) ? 'true' : 'last';
            $item['isLast'] = ($key === count($list) - 1) ? 'true' : 'last';

            $plop = preg_replace_callback(
                "|\{= item\.(.+?)\}|",
                function ($matches) use ($item) {

                    $tmp = $item[$matches[1]] ?? '';
                    return "$tmp";
                },
                $content
            );

            $html .= preg_replace_callback(
                "|\{if item\.(.+?)\}|",
                function ($matches) use ($item) {

                    $tmp = $item[$matches[1]] ?? false ? 'true' : 'false';

                    return "{if $tmp}";
                },

                $plop
            );
        }
    }

    return $this->shortcodes->process($html);
});

$this->shortcodes->addShortcode('+', function ($attributes, $content, $tagName) {
    static $rec = 0;
    $rec++;
    if ($rec > 20) {
        die('recurtion spotted in include ' . $attributes[0]);
    }

    if (!isset($attributes[0]) || $attributes[0] === 'ALL' || $attributes[0] === '.*') {
        $children = $this->mempad->getElementByPath(($this->conf)("page.path"))->children;
        $content = '';
        foreach ($children as $element) {
            if (strpos($element->title, '.') === 0) {
                $content .= $this->mempad->getContentById($element->id) . "\n";
            }
        }
        return $this->shortcodes->process($content);
    }
    $path = ($this->conf)("page.path") . "/" . $attributes[0];
    $path = $this->get_absolute_mempad_path($path);
    $content = $this->mempad->getContentByPath($path);
    if ($content === null) {
        return "include not found : $attributes[0]";
    }

    return $this->shortcodes->process($content);
    //return $content;
});

$this->shortcodes->addShortcode('include', function ($attributes, $content, $tagName) {
    static $rec = 0;
    $rec++;
    if ($rec > 20) {
        die('recurtion spotted in include ' . $attributes[0]);
    }

    $path = ($this->conf)("page.path") . "/" . $attributes[0];

    $content = $this->mempad->getContentByPath($path);
    if ($content === null) {
        return "include not found : $attributes[0]";
    }

    return $this->shortcodes->process($content);
    //return $content;
});

$this->shortcodes->addShortcode('featurette', function ($attributes, $content, $tagName) {
    static $even = true;
    $even = !$even;

    $order1 = $order2 = '';

    if ($even) {

        $order1 = ' order-md-2';
        $order2 = ' order-md-1';
    }
    $title = $attributes['title'] ?? '';
    $subtitle = $attributes['subtitle'] ?? false;
    $img = $attributes['img'] ?? false;
    $video = $attributes['video'] ?? false;
    $link = $attributes['link'] ?? '';
    $loop = $attributes['loop'] ?? '';

    if ($link)
        $content = trim($content) . "   [...]";
    if ($title)
        $title = "<h2 class='featurette-heading'>$title<span class='text-muted'>$subtitle</span></h2>";

    $content = $this->renderBlock($content);
    $content = $this->shortcodes->process($content);

    $astart = ($link) ? "<a href='$link'>" : '';
    $aend = ($link) ? '</a>' : '';
    if ($link)
        $content = "<a href='$link'>$content</a> ";

    if ($img)
        $media = <<<HTML
 
          <img class="bd-placeholder-img bd-placeholder-img-lg featurette-image img-fluid mx-auto" 
          width="500" height="500" src="media/img/$img" />
          
    HTML;

    if ($video)
        $media = <<<HTML
    <video class="video-bg-fullscreen" src="media/video/$video" playsinline autoplay $loop
    width="500" height="500"></video>
    
HTML;

    $content = <<<HTML
<div class="row featurette">
    <div class="col-md-7$order1">
    $astart
      $title
      <p class="lead">$content</p>
      $aend
    </div>
    <div class="col-md-5$order2">
    $astart
      $media
      $aend
    </div>
  </div>
 <hr class="featurette-divider">
HTML;




    return $content;
});

$this->shortcodes->addShortcode('partial', function ($attributes, $content, $tagName) {
    static $rec = 0;
    $rec++;

    // relative path are not allowed
    if (strpos($attributes[0], '/') !== false) {
        die('partial ' . $attributes[0] . ' not allowed');
    }

    $content = $this->resolve_template('_' . $attributes[0]);
    if ($rec > 20) {
        die('recurtion spotted in partial ' . $attributes[0]);
    }

    if (count($attributes) > 1 ?? false) {
        for ($i = 1; $i < count($attributes); $i++) {
            $content = str_replace(
                '$attributes[' . $i . ']',
                $attributes[$i],
                $content
            );
        }
    }
    $rec--;
    return ($this->shortcodes->process($content));
});

$this->shortcodes->addShortcode('>', function ($attributes, $content, $tagName) {
    static $rec = 0;
    $rec++;
    return ($this->shortcodes->process("BLEP> " . $content));
    // relative path are not allowed
    if (strpos($attributes[0], '/') !== false) {
        die('partial(>) ' . $attributes[0] . ' not allowed');
    }

    $content = $this->resolve_template('_' . $attributes[0]);
    if ($rec > 20) {
        die('recurtion spotted in partial ' . $attributes[0]);
    }

    // $elt = ($this->conf)($attributes[1]);

    if (count($attributes) > 1 ?? false) {
        for ($i = 1; $i < count($attributes); $i++) {
            $content = str_replace(
                '$attributes[' . $i . ']',
                $attributes[$i],
                $content
            );
        }
    }
    $rec--;
    return ($this->shortcodes->process($content));
});

$this->shortcodes->addShortcode('render', function ($attributes, $content, $tagName) {

    $content = ($this->conf)($attributes[0]);

    if ($content === null)
        return '';

    return $this->renderBlock($content);
});


$this->shortcodes->addShortcode('code', function ($attributes, $content, $tagName) {
    $language = $className = 'ini';
    $class = '';

    if (strlen($attributes[0] ?? '')) {
        $language = $className = $attributes[0];
        if ($language === 'html') {
            $className = 'xml';
        }
        //highlight.js use a 'language-xml' class for html documents
        $class = " class='hljs language-$className'";
    }

    $code = htmlspecialchars(trim($content));
    //  $code = str_replace(['[', ']'], ['&#91;', '&#93;'], $code);

    $show = '';
    if (isset($attributes[1])) {

        if ($attributes[1] === 'html' && $attributes[0] === 'markdown') {
            $content = $this->markdownParser->transform($content);
            $content = $this->shortcodes->process($content);
        }

        $show = <<<html
        <div class="position-relative">
        <span class="badge rounded-pill bg-dark mypill" >$attributes[1]</span>
        <div class='show rendered'>$content</div>
        </div>
        html;
    }
    return <<<html
            <div class="position-relative">
            <span class="badge rounded-pill bg-dark mypill" >$language</span>
            <pre><code $class>$code</code></pre>
            </div>
            $show
            html;
});

$this->shortcodes->addShortcode('#', function ($attributes, $content, $tagName) {
    return '';
});


$this->shortcodes->addShortcode('all', function ($attributes, $content, $tagName) {

    $content = "\n";
    if ($attributes[0] === 'links') {


        foreach ($this->mempad->elts as $v) {

            if ($v->title === '$CONF')
                continue;
            if (substr($v->path, 0, 5) === '!Help')
                continue;

            $firstChar = ($v->title)[0];



            $class = '';
            if (strpos($v->title, '.') !== false)
                $class = 'red';
            if (strpos($v->path, '!') !== false)
                $class = 'orange';

            $content .= <<<blep
<div class="help level{$v->level} $class"><span class="title">$v->title</span><br><span class="link">{link "<a href="$v->url">$v->url</a>"}</span></div>
blep;
        }
    }

    if ($attributes[0] === 'images') {
        $files = glob('media/img/{,*/}*{jpg,gif,png}', GLOB_BRACE);
        $content = "nombre d'images : " . count($files) . "\n\n";
        $images = "";
        foreach ($files as $file) {
            $src = substr($file, 10);
            $arr = getimagesize($file);
            $filesize = filesize($file); // bytes
            $filesize = round($filesize / 1024);
            $images .= <<<image
        <div class="thumbhelp">
        {img "$src" "desc"}<br>
        $arr[0] x $arr[1] ($filesize ko)
        <img src="$file" /><br>
        </div>
        image;

            $content = <<<images

        <div class="allimages">
        $images
        </div>
        images;

        }
    }

    return $content;
});



$this->shortcodes->addShortcode('posts', function ($attributes, $content, $tagName) {
    //$url = $attributes[0] ?  ?? '.';
    $conf = $this->conf;
    $url = '.';
    if ($url == '.')
        $url = $this->conf->value('page.url');

    $posts = $this->mempad->getElementByUrl($url)->children;


    $items = [];

    foreach ($posts as $item) {

        // post starting with a dot are ignored.
        if (strpos($item->title, '.') === 0) {

            continue;
        }

        if (strpos($item->title, '!') === 0 && !$this->isAuthed) {

            continue;
        }

        $this->pagify($item);
        $item->date = $item->frontmatter['date'];

        $text = (explode('{...}', trim($item->rawContent)))[0];
        preg_match('/(?:^|[\r\n])# (.+?)[\r\n]+(.+)$/s', $text, $matches);

        $item->h1 = $matches[1];
        $item->summary = $this->markdownParser->transform($matches[2]);
        ;

        $items[] = (array) $item;
    }


    $conf("page.posts", $items);

    $content = $this->renderBlock("{> post-summary-list}");
    $content = $this->shortcodes->process($content);



    return '<section class="posts">' . $content . '</section>';
});

// in a post, we use {...} to separate the summary from the rest of the post
$this->shortcodes->addShortcode('...', function ($attributes, $content, $tagName) {
    return "\n";
});

$this->shortcodes->addShortcode('title', function ($attributes, $content, $tagName) {

    return '<h1>' . $this->conf->value('page.title') . '</h1>';
});

$this->shortcodes->addShortcode('=', function ($attributes, $content, $tagName) {

    return ($this->conf)($attributes[0]);
});

$this->shortcodes->addShortcode('link', function ($attributes, $content, $tagName) {
    $href = $attributes[0];
    $absroot = ($this->conf)('absroot');
    if (isset($attributes[1])) {
        $text = $attributes[1];
    } else if ($content) {
        $text = $this->renderBlock($content);
    } else {
        $text = $this->mempad->getElementByUrl($href)->title ?? $href;
    }
    $target = '';
    if (strpos($href, 'http://') !== false || strpos($href, 'https://') !== false) {
        $target = ' target="_blank"';
    } else {
        if ($absroot)
            $href = $absroot . $href;
    }

    $html = '<a href="' . $href . '"' . $target . '>' . $text . '</a>';
    return $html;
});

$this->shortcodes->addShortcode('img', function ($attributes, $content, $tagName) {
    $path = $attributes[0];
    $zen = Kernel::service('ZenConfig');
    $media = $zen('media');
    $uAttr = $this->uAttr($attributes);
    $title = $attributes['title'] ?? '';
    $caption = $attributes['caption'] ?? '';
    $alt = $title ?? $attributes['alt'] ?? $attributes[0];

    if (in_array("modal", $attributes)) {
        $html = <<<MODAL
        <a href="$media/img/$path"  data-bs-toggle="modal" data-bs-target="#lightboxModal"  $caption
        data-bs-title="$title"><img src="media/img/$path"  $uAttr /></a>

        MODAL;

        $html = ($this->shortcodes->process($html));
    }if ($caption) {

        $html = <<<IMG
        <figure class="figure">
        <img src="$media/img/$path" class="figure-img img-fluid rounded" alt="$caption">
        <figcaption class="figure-caption text-center">$caption</figcaption>
        </figure>
        IMG;
    } else {
        $title = $title ?? $alt;
        $html = <<<IMG
        <img src="$media/img/$path" $caption $uAttr>
        IMG;
    }

    return trim($html);
});

$this->shortcodes->addShortcode('region', function ($attributes, $content, $tagName) {

    $region = $attributes[0];

    $start = '';

    $id = 'region-' . str_replace('.', '-', $attributes[0]);
    $end = '';
    if (($this->conf)("page.show.regions")) {
        $start = '<div class="regions" id="' . $id . '">[' . $region . ']';
        $end = "</div>";
    } else {
        $start = '<div id="' . $id . '">';
        $end = "</div>";

    }

    $absroot = ($this->conf)('absroot');
    $array_merge = [];


    $array = ($this->conf)("site.regions.$region");
    if (is_array($array)) {
        $array_merge[] = $array;

    }

    $array = ($this->conf)("page.regions.$region");

    if (is_array($array)) {
        $array_merge[] = $array;


    }

    $array_merged = array_merge(...$array_merge);
    if ($array_merge)

        if (count($array_merged)) {
            $html = '';

            foreach ($array_merged as $key => $value) {

                $html .= $this->renderBlock($value);


            }
            return "$start$html$end";
        }


    $render = ($this->conf)("page.regions.$region")
        ?? ($this->conf)("pages.regions.$region")
        ?? ($this->conf)("site.regions.$region")
        ?? null;

    if (!$render)
        return "$start$end";
    if (is_array($render)) {
        $html = '';
        foreach ($render as $key => $value) {

            $html .= $this->renderBlock($value);
        }
        return "$start$html$end";
    }


    if (isset($attributes[1])) {
        $template = $attributes[1];
    } else if ($content) {
        $html = $this->renderBlock($content);
    }


    $html = $this->renderBlock($render);


    return "$start$html$end";
});

$this->shortcodes->addShortcode('background', function ($attributes, $content, $tagName) {
    $path = $content ? $content : $attributes[0];
    $conf = ($this->conf);

    $uAttr = $this->uAttr($attributes);

    if ($content !== '') {
        $html = <<<DIV
            <div $uAttr>
            $content
            </div>
            DIV;
    } else {
        $html = <<<IMG
            <img src="media/img/$path" alt='' $uAttr>
            IMG;
    }


    $backgrounds = $conf('page.backgrounds');
    $backgrounds .= trim($html);
    $conf('page.backgrounds', $backgrounds);

    return '';
});




$this->shortcodes->addShortcode('figure', function ($attributes, $content, $tagName) {
    $path = $attributes[0];
    $alt = $attributes[1] ?? $attributes[0];
    $str = $this->uAttr($attributes);

    $title = $alt;

    $html = <<<HTML

<figure class="figure mx-auto">
  <img src="media/img/$path" class="figure-img img-fluid" alt="$alt" $str>
  <figcaption class="figure-caption text-center">$alt</figcaption>
</figure>
HTML;
    return trim($html);
});

$this->shortcodes->addShortcode(
    'bg',
    function ($attributes, $content, $tagName) {
        $img = $attributes[0];
        $id = '';
        $str = 'background: url(./media/img/' . $img . ') no-repeat fixed;';
        for ($i = 1; $i < count($attributes); $i++) {
            $attr = $attributes[$i];

            if (strpos($attr, '#') === 0) {
                $id = ' id="' . substr($attr, 1) . '"';
            } else
                if (strpos($attr, ':') !== false) {
                    $str .= "$attr;";
                } else
                    if ($attr === 'cover') {
                        $str .= 'background-size: cover;';
                    }
        }

        return trim("<div$id class='background' style='$str'></div>");
    }
);

$this->shortcodes->addShortcode('toc', function ($attributes, $content, $tagName) {
    $title = $attributes[0] ? $attributes[0] : "On This Page";

    return "\n<div id=\"table-of-contents\" data-toc-header=\"$title\"></div>";
});

$this->shortcodes->addShortcode('lorem', function ($attributes, $content, $tagName) {

    $count = 1;
    $max = 20;
    $std = true;

    $out = '';
    if ($std) {
        $out = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.';
    }

    $rnd = explode(' ', $out);
    $max = $max <= 3 ? 4 : $max;
    for ($i = 0, $add = $count - (int) $std; $i < $add; $i++) {
        shuffle($rnd);
        $words = array_slice($rnd, 0, mt_rand(3, $max));
        $out .= (!$std && $i == 0 ? '' : ' ') . ucfirst(implode(' ', $words)) . '.';
    }
    return $out;
});

$this->shortcodes->addShortcode('date', function ($attributes, $content, $tagName) {
    $format = $attributes[0] ?? "Y-M-d H:i:s";

    return date($format);
});

$this->shortcodes->addShortcode('load-js', function ($attributes, $content, $tagName) {
    $str = "";

    foreach ($attributes as $file) {
        $str .= '<script src="' . $file . '"></script>';
    }

    return $str;
});

$this->shortcodes->addShortcode('script', function ($attributes, $content, $tagName) {
    $str = "";

    foreach ($attributes as $file) {
        $str .= '<script src="assets/' . $file . '"></script>';
    }

    return $str;
});

$this->shortcodes->addShortcode('encode', function ($attributes, $content, $tagName) {

    $character_set = "+-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz";

    $this->shortcodes->process($content);
    $content = $attributes[0] ?? strrev($content);

    $key = str_shuffle($character_set);
    $cipher_text = '';
    $id = 'e' . rand(1, 999999999);
    for ($i = 0; $i < strlen($content); $i++) {

        if (strpos($character_set, $content[$i]) === false) {
            $cipher_text .= ($content[$i] === '"') ? '\"' : $content[$i];
        } else {
            $cipher_text .= $key[strpos($character_set, $content[$i])];
        }
    }
    $js = <<<SCR
<span id="$id">[protected by js]</span><script>
    (function (){let a="$key", b, c="$cipher_text", d="";b=a.split("").sort().join("");for(let e=c.length-1;e>-1;e--)
    if(a.indexOf(c.charAt(e) !== false)) d+=b.charAt(a.indexOf(c.charAt(e)));else d+= c.charAt(e);d = d.split("").reverse().join("");
    document.getElementById("$id").innerHTML=d;
    }());
    </script>
SCR;

    return trim($js);
});

$this->shortcodes->addShortcode('slides', function ($attributes, $content, $tagName) {

    $content = ($this->conf)('page.content');
    $slides = explode("\n<h2>", $content);
    $tmp = "";
    die('toto');
    foreach ($slides as $index2 => $slide) {

        //do we have vertical slides ?
        $subSlides = explode("\n<h3>", $slide);
        $firstSlide = array_shift($subSlides);
        if (strpos($slide, "\n<h3>") !== false) {
            $subtmp = "";
            foreach ($subSlides as $index3 => $subSlide) {
                $subtmp .= $this->fSlide($subSlide, 3, $index2, $index3);
            }
        }

        if ($subSlides) {
            $tmp .= '<section>' . $this->fSlide($firstSlide, 2, $index2) . $subtmp . '</section>';
        } else {
            $tmp .= $this->fSlide($firstSlide, 2, $index2);
        }
    }
    $content = $tmp;
    //$this->shortcodes->process($content);
    //$content = $this->markdownParser->transform($content);

    return $content;
});

$this->shortcodes->addShortcode('gallery', function ($attributes, $content, $tagName) {
    static $id = 0;
    $id++;
    $elementId = $tagName . '_' . $id;
    ($this->conf)("$elementId.id", $elementId);
    if (isset($attributes['folder'])) {
        $files = $this->scan($attributes['folder']);
        $content = '';
        foreach ($files as $key => $file) {
            $file = substr($file, 10);
            $content .= "img: \"$file\"";
            if ($key < count($files) - 1) {
                $content .= "\n---\n";
            }
        }
    }

    $str = $this->formatSimpleArray($content, "$elementId.items");

    $this->conf->parseString($str);

    $attributes = array_merge($attributes, ['this' => "$elementId"]);
    foreach ($attributes as $key => $attribute) {
        if (is_integer($key)) {
            $attributes[$attribute] = $attribute;
            ($this->conf)("$elementId.$attribute", $attribute);
        } else {
            ($this->conf)("$elementId.$key", $attribute);
        }
    }

    return ($this->templateHandler($attributes, null, $tagName, false));
});

$this->shortcodes->addShortcode('carousel', function ($attributes, $content, $tagName) {
    static $id = 0;
    $id++;

    $elementId = $tagName . '_' . $id;
    ($this->conf)("$elementId.id", $elementId);

    $str = $this->formatSimpleArray($content, "$elementId.items");
    $this->conf->parseString($str);

    $attributes = array_merge($attributes, ['this' => "$elementId"]);
    foreach ($attributes as $key => $attribute) {
        if (is_integer($key)) {
            $attributes[$attribute] = $attribute;
            ($this->conf)("$elementId.$attribute", $attribute);
        } else {
            ($this->conf)("$elementId.$key", $attribute);
        }
    }

    return $this->templateHandler($attributes, null, $tagName, false);
});

$this->shortcodes->addShortcode('lightboxModal', function ($attributes, $content, $tagName) {

    return $this->templateHandler($attributes, null, $tagName, false);
});

$this->shortcodes->addShortcode('demo', function ($attributes, $content, $tagName) {

    //$content = trim($content);
    $showHTML = $attributes[0] === '+html' ?? false;

    $code = trim($content);

    $html = $this->renderBlock($content);

    $htmlCode = '';
    if ($showHTML) {
        $htmlCode = htmlspecialchars($html);
        $html = <<<html
        <div class="position-relative">
            <span class="badge rounded-pill bg-dark mypill" >html</span>
            <pre><code class='hljs language-xml'>$htmlCode</code></pre>
        </div>
        html;
    }

    return <<<html
    <div class="namaskar-demo">
    <div class="position-relative">
    <span class="badge rounded-pill bg-dark mypill" >input</span>
    <pre><code class='hljs language-text'>$code</code></pre>
    </div>
    $htmlCode
    <div class="position-relative">
        <span class="badge rounded-pill bg-dark mypill" >output</span>
        <div class='show rendered'>$html</div>
        </div>
    </div>
    html;

});

$this->shortcodes->addShortcode('content', function ($attributes, $content, $tagName) {

    return ($this->conf)('page.content');

});


$this->shortcodes->addShortcode('scrolly', function ($attributes, $content, $tagName) {

    return "<div id=\"scrolly\" class=\"$attributes[0]\"/></div>";
});

$this->shortcodes->addShortcode('list', function ($attributes, $content, $tagName) {
    $separtor = $attribute['separator'] ?? '---';
    $id = $attribute['id'] ?? die("{list} id is missing");

    $str = $this->formatSimpleArray($content, "$id.items");
});


$this->shortcodes->addShortcode('menumd', function ($attributes, $content, $tagName) {

    $folder = 'media/' . $attributes[0] . '/';
    $root = $this->conf->value('page.url');

    $html = '';

    if (!$folder) {
        return '';
    }

    foreach (glob("$folder/{,*/,*/*/,*/*/*/}*.md", GLOB_BRACE) as $filename) {

        [$title, $content] = $this->getTitleAndContentFromMarkdownFile($filename);

        $slug = preg_replace(
            '/^[0-9-]+[.-]/',
            '',
            pathinfo($filename, PATHINFO_FILENAME)
        );
        [, $dirname] = explode($folder, $filename);

        $dirname = basename(dirname($dirname));

        $dirname = str_replace('media/' . $attributes[0] . '/', '', $dirname);

        if ($title === null) {
            $title = $slug;
        }

        $html .= "<li><a href=\"$root/$dirname/$slug\">$title</a> </li>\n";
    }

    //  end of folder scanning
    return '<nav class="sidemenu"><ul>' . $html . '</ul></nav>';
});

$this->shortcodes->addShortcode('___mount', function ($attributes, $content, $tagName) {
    $folder = getcwd() . '/media/' . $attributes[0];

    $file = $this->conf->value('page.urlNotFound');

    $defaults = ['index.md', 'README.md', 'article.md'];
    $default = '';
    if ($file === '')
        foreach ($defaults as $key => $value) {
            if (is_file("$folder/$value")) {
                $file = $value;
                break;
            }
        }


    $parts = pathinfo($file);
    $content = null;


    if (is_file("$folder/$file")) {
        // "Root"
        $assetsFolder = "media/$attributes[0]/$file/";
        $glob = glob("$folder/$file", GLOB_BRACE);
    } else {
        $glob = glob("$folder/$parts[dirname]/*$parts[basename].md");
        $assetsFolder = "media/$attributes[0]/$parts[dirname]/";
    }

    echo "assetsFolder = $assetsFolder";

    if ($glob[0] ?? false) {

        [$md, $content] = $this->getTitleAndContentFromMarkdownFile($glob[0]);
        $content = $this->markdownParser->transform($content);
        $content = $this->shortcodes->process($content);

        $root = $this->conf->value('page.url');

        $content = preg_replace(
            '# (href|action) *= *"([^:"]*)("|(?:(?:%20|\s|\+)[^"]*"))#m',
            ' $1="' . $root . '/$2$3',
            $content
        );
        $content = preg_replace(
            '# (src) *= *"([^:"]*)("|(?:(?:%20|\s|\+)[^"]*"))#m',
            ' $1="' . "/$assetsFolder" . '/$2$3',
            $content
        );

        ($this->conf)('page.urlNotFound', ''); //this way we don't have a 404.
        return "<div>$content\n</div>";
    }

    return $this->Page404();
});


$this->shortcodes->addShortcode('newsletter-form', function ($attributes, $content, $tagName) {

    $conf = $this->conf;
    $conf('page.post', $_POST);
    //$content ="";


    $content = '';

    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $email = $_POST['email'] ?? '';
    $challenge = (int) ($_POST["challenge"] ?? 0);
    $blep = $_POST['blep'] ?? null;
    $submit = $_POST['submit'] ?? null;

    $formHasError = false;
    $done = false;

    $message = '';

    if ($submit && $firstname && $email && $blep) {
        if ($challenge > 0 && md5($this->conf->value('site.newsletter.salt') . $challenge) === $blep) {
            $message = $this->conf->value('site.newsletter.ok');
            $date = date('Y-d-m H:i:s', time());
            $ip = $_SERVER['REMOTE_ADDR'];
            $file = file_get_contents(NL_FILE);
            $file .= <<<CSV
$firstname\t$lastname\t$email\t$ip\t$date\n
CSV;
            file_put_contents(NL_FILE, $file);
            $done = true;
        } else {
            $message = $this->conf->value('site.newsletter.challengeFail');


        }
    } else {


        if ($submit)
            $message = "Tous les champs sont obligatoires";

    }

    $conf('site.newsletter.firstname', $firstname);
    $conf('site.newsletter.lastname', $lastname);
    $conf('site.newsletter.email', $email);
    $conf('site.newsletter.done', $done);

    $a = random_int(1, 10);
    $b = random_int(1, 10);
    $newchallenge = "$a + $b";


    $conf('site.newsletter.challenge', "$a + $b");
    $blep = md5($this->conf->value('site.newsletter.salt') . ($a + $b));

    $conf('site.newsletter.blep', $blep);

    if ($message)
        $content .= '<strong class="message"> ' . $message . '</strong>';

    if ($done === false) {
        $form = $this->renderBlock("{partial $attributes[partial]}");
        $content .= $this->shortcodes->process($form);
    }



    return $content;
});