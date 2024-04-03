<?php
use Qwwwest\Namaskar\Kernel;


$this->shortCode2Template('quote');
$this->shortCode2Template('highlight', 'alert', true, ['type' => 'highlight']);
$this->shortCode2Template('info', 'alert', true, ['type' => 'info']);
$this->shortCode2Template('danger', 'alert', true, ['type' => 'danger']);
$this->shortCode2Template('warning', 'alert', true, ['type' => 'warning']);
$this->shortCode2Template('img2', 'templates', false);



$this->addShortcode('alert', function ($attributes, $content, $tagName) {



    $type = $attributes[0];
    if (isset ($attributes[1])) {
        $title = $attributes[1];
        return $this->renderTemplate($attributes, $content, 'alert-with-title', false);
    }

    return $this->renderTemplate($attributes, $content, 'alert', false);


});


$this->addShortcode('youtube', function ($attributes, $content, $tagName) {
    $id = $attributes[0];
    $ratio = $attributes[1] ?? '16x9';

    if (in_array("thumbnail", $attributes)) {
        return "<img src=\"https://img.youtube.com/vi/$id/hqdefault.jpg\" />";
    }
    return <<<HTML
    <div class="youtube" style="margin: 8px auto">
    <div class="ratio ratio-$ratio" >
    <iframe src="https://www.youtube.com/embed/$id" title="YouTube video"  frameborder="0"  allowfullscreen ></iframe>
    </div>
    </div>
    HTML;
});

$this->addShortcode('youtube-videos', function ($attributes, $content, $tagName) {

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
    $content = $this->processShortcodes($content);




    return '<section class="youtubeVideos">' . $content . '</section>';
});

$this->addShortcode('video-background', function ($attributes, $content, $tagName) {
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

$this->addShortcode('video-bg-fullscreen', function ($attributes, $content, $tagName) {
    $id = $attributes[0];
    $loop = $attributes['loop'] ?? '';

    $html = <<<HTML
         
        {background}  
        <video class="background-fullscreen" src="media/video/$id" playsinline autoplay muted $loop>
        </video>
        {/background}
        HTML;

    return $this->processShortcodes($html);

});
//////

$this->addShortcode('.audio', function ($attributes, $content, $tagName) {

    $config = '{"shide_top":true,"shide_btm":false,"auto_load":true}';
    $config = '{"shide_top":false,"shide_btm":true,"auto_load":false}';
    if (!isset ($attributes['file']))
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


$this->addShortcode('.vimeo', function ($attributes, $content, $tagName) {
    $id = $attributes[0];

    $ratio = $attributes[1] ?? '16x9';

    return <<<HTML
<div class="ratio ratio-$ratio" style="margin: 8px auto">
<iframe src="https://player.vimeo.com/video/$id" 
    width="640" height="360" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen>
</iframe>
</div>
HTML;
});

$this->addShortcode('====', function ($attributes, $content, $tagName) {
    $content = explode("\n[==]", $content);
    $class = $attributes['class'] ?? "";
    $nb = count($content);
    $cols = '';
    foreach ($content as $key => $value) {

        $value = $this->renderBlock($value);
        $cols .= <<<COL
<div class="col">
        $value
</div>
COL;
    }

    return <<<HTML

  <div class="row row-cols-1 row-cols-lg-$nb $class">
  $cols
  </div>

HTML;
});


$this->addShortcode('?', function ($attributes, $content, $tagName) {

    $var = $this->conf->value($attributes[0]);

    if ($var) {
        return $attributes[1];
    }
    return $attributes[2] ?? '';
});

$this->addShortcode('dump', function ($attributes, $content, $tagName) {

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

$this->addShortcode('.meta', function ($attributes, $content, $tagName) {

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
    return $this->processShortcodes($meta);
});

// foreach item in LIST
// foreach file in MASK (ex:foreach file in "logo/*.svg")
$this->addShortcode('.foreach', function ($attributes, $content, $tagName) {

    $html = '';
    $varname = $attributes[0];
    $op = $attributes[1];

    if ($op !== 'in') {
        die ('foreach syntax is "foreach item/file in list/dir" ');
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

        return $this->processShortcodes($html);
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

    return $this->processShortcodes($html);
});

$this->addShortcode('include', function ($attributes, $content, $tagName) {
    static $rec = 0;
    $rec++;
    if ($rec > 20) {
        die ('recurtion spotted in include ' . $attributes[0]);
    }

    if (!isset ($attributes[0]) || $attributes[0] === 'ALL' || $attributes[0] === '.*') {
        $children = $this->mempad->getElementByPath(($this->conf)("page.path"))->children;
        $content = '';
        foreach ($children as $element) {
            if (strpos($element->title, '.') === 0) {
                $content .= $this->mempad->getContentById($element->id) . "\n";
            }
        }
        return $this->processShortcodes($content);
    }
    $path = ($this->conf)("page.path") . "/" . $attributes[0];
    $path = $this->get_absolute_mempad_path($path);
    $content = $this->mempad->getContentByPath($path);
    if ($content === null) {
        return "include not found : $attributes[0]";
    }

    return $this->processShortcodes($content);
    //return $content;
});

$this->addShortcode('.include', function ($attributes, $content, $tagName) {
    static $rec = 0;
    $rec++;
    if ($rec > 20) {
        die ('recurtion spotted in include ' . $attributes[0]);
    }

    $path = ($this->conf)("page.path") . "/" . $attributes[0];

    $content = $this->mempad->getContentByPath($path);
    if ($content === null) {
        return "include not found : $attributes[0]";
    }

    return $this->processShortcodes($content);
    //return $content;
});

$this->addShortcode('featurette', function ($attributes, $content, $tagName) {
    static $order = true;
    $order = !$order;
    if (isset ($attributes['order'])) {
        $order = $attributes['order'] === 'left';
    }

    $order1 = $order2 = '';

    if ($order) {

        $order1 = 'order-md-2';
        $order2 = 'order-md-1';
    }
    $title = $attributes['title'] ?? '';
    $subtitle = $attributes['subtitle'] ?? false;
    $img = $attributes['img'] ?? false;
    $size = $attributes['size'] ?? '500';
    $video = $attributes['video'] ?? false;
    $link = $attributes['link'] ?? '';
    $loop = $attributes['loop'] ?? '';
    $hr = isset ($attributes['hr']) ? '<hr class="featurette-divider">' : '';
    $caption = $attributes['caption'] ?? '';
    $id = $this->id($attributes);
    if ($id)
        $id = " id='$id'";
    $class = $this->getCssClasses($attributes);
    $ratio1 = intval($attributes['ratio'] ?? '7');

    if ($ratio1 < 1 || $ratio1 > 11)
        die ("featurette: ratio invalid: " . $ratio1 . "<br>" . $title . "<br>" . $content);
    $ratio2 = 12 - $ratio1;



    if ($link)
        $content = trim($content) . "   [...]";
    if ($title)
        $title = "<h2 class='featurette-heading'>$title<span class='text-muted'>$subtitle</span></h2>";

    $content = $this->renderBlock($content);
    //$content = $this->processShortcodes($content);

    $astart = ($link) ? "<a href='$link'>" : '';
    $aend = ($link) ? '</a>' : '';
    if ($link)
        $content = "<a href='$link'>$content</a> ";


    $media = ($this->conf)('media');
    if (!$caption && $img)
        $media = <<<HTML
 
          <img class="bd-placeholder-img bd-placeholder-img-lg featurette-image img-fluid mx-auto" 
          width="$size" height="$size" src="{$media}/img/$img" data-toggle="lightbox"  />
          
    HTML;
    if ($caption && $img) {
        $media = <<<IMG
    <figure class="figure">
    <img src="{$media}/img/$img" class="figure-img featurette-image img-fluid mx-auto " 
    width="$size" height="$size" alt="$caption" data-toggle="lightbox"  />
    <figcaption class="figure-caption text-center">$caption</figcaption>
    </figure>
    IMG;
    }
    if ($video)
        $media = <<<HTML
    <video class="video-bg-fullscreen" src="media/video/$video" playsinline autoplay $loop
    width="$size" height="$size"></video>
    
HTML;



    $content = <<<HTML
<div class="row featurette$class">
    <div class="col-md-$ratio1 $order1">
    $astart
      $title
      <p class="lead">$content</p>
      $aend
    </div>
    <div class="col-md-$ratio2 $order2 featurette-media ">
    $astart
      $media
      $aend
    </div>
  </div>
  $hr
HTML;




    return $content;
});


$this->addShortcode('render', function ($attributes, $content, $tagName) {

    $content = ($this->conf)($attributes[0]);

    if ($content === null)
        return '';

    return $this->renderBlock($content);
});


$this->addShortcode('code', function ($attributes, $content, $tagName) {
    $language = '';
    $className = 'ini';
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

    $code = str_replace(
        ['[', ']', '{', '}', '%'],
        ['&#91;', '&#93;', '&lcub;', '&rcub;', '&percnt;'],
        $code
    );

    $show = '';
    if (isset ($attributes[1])) {

        if ($attributes[1] === 'html' && $attributes[0] === 'markdown') {
            $content = $this->markdownParser->transform($content);
            $content = $this->processShortcodes($content);
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

$this->addShortcode('#', function ($attributes, $content, $tagName) {
    return '';
});


$this->addShortcode('_all', function ($attributes, $content, $tagName) {

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



$this->addShortcode('posts', function ($attributes, $content, $tagName) {
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
    $content = $this->processShortcodes($content);



    return '<section class="posts">' . $content . '</section>';
});

// in a post, we use {...} to separate the summary from the rest of the post
$this->addShortcode('...', function ($attributes, $content, $tagName) {
    return "\n";
});

$this->addShortcode('title', function ($attributes, $content, $tagName) {

    return '<h1>' . $this->conf->value('page.title') . '</h1>';
});

$this->addShortcode('=', function ($attributes, $content, $tagName) {

    return ($this->conf)($attributes[0]);
});
$this->addShortcode('shortcodes', function ($attributes, $content, $tagName) {

    $shortcodes = $this->getShortcodes();
    $list = '';
    foreach ($shortcodes as $tag => $shortcode) {
        // shortcodes starting with '_' or '.' are for internal use.
        if (strpos($tag, '.') === 0 || strpos($tag, '_') === 0)
            continue;

        if (preg_match('/^[A-Za-z]/', $tag))
            $list .= ($list) ? " &bull; <b>$tag</b> " : "<b>$tag</b>"; # code...
    }

    return $list;
});
$this->addShortcode('link', function ($attributes, $content, $tagName) {
    $href = $attributes[0];

    if ($href === '..') {
        $url = ($this->conf)('url');
        $parentId = $this->mempad->getElementByUrl($url)->parent;
        if ($parentId) {
            $href = $this->mempad->getElementById($parentId)->url;
        }
    }
    $absroot = ($this->conf)('absroot');
    if (isset ($attributes[1])) {
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

$this->addShortcode('img', function ($attributes, $content, $tagName) {
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

        $html = ($this->processShortcodes($html));
    }

    if ($caption) {

        $html = <<<IMG
        <figure class="figure">
        <img src="$media/img/$path" class="figure-img img-fluid rounded" alt="$caption" data-toggle="lightbox" >
        <figcaption class="figure-caption text-center">$caption</figcaption>
        </figure>
        IMG;
    } else {
        $title = $title ?? $alt;
        $html = <<<IMG
        <img src="$media/img/$path" class="img-fluid" alt="$alt" title="$title" $uAttr data-toggle="lightbox" />
        IMG;
    }

    return trim($html);
});

$this->addShortcode('region', function ($attributes, $content, $tagName) {

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


    if (isset ($attributes[1])) {
        $template = $attributes[1];
    } else if ($content) {
        $html = $this->renderBlock($content);
    }


    $html = $this->renderBlock($render);


    return "$start$html$end";
});

// foreach item in LIST
// foreach file in MASK (ex:foreach file in "logo/*.svg") 
$this->addShortcode('for', function ($attributes, $content, $tagName) {

    $html = '';
    $varname = $attributes[0];
    $op = $attributes[1];

    if ($op !== 'in')
        die ('foreach syntax is "foreach item/file in list/dir" ');

    $content = trim($content);
    if (strpos('..', $attributes[2]) != false)
        die (" '..' not allowed in for");
    if ($varname === "file") {
        foreach (glob('media/' . $attributes[2] . '/*') as $filename) {

            $file = pathinfo($filename);
            $file['size'] = filesize($filename);
            $file['url'] = $filename;
            $html .= preg_replace_callback(
                "|\{\{ $varname\.(.+?) \}\}|",
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
        return $this->processShortcodes($html);
    }


    $list = ($this->conf)($attributes[2]);
    $html = '';

    if (is_array($list))
        foreach ($list as $item) {
            $html .= preg_replace_callback(
                "|\{\{ item\.(.+?) \}\}|",
                function ($matches) use ($item) {
                    $tmp = $item[$matches[1]] ?? '';
                    return $tmp;
                },
                $content
            );

        }

    return $this->processShortcodes($html);
});

$this->addShortcode('background', function ($attributes, $content, $tagName) {
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




$this->addShortcode('figure', function ($attributes, $content, $tagName) {
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

$this->addShortcode(
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

$this->addShortcode('toc', function ($attributes, $content, $tagName) {
    $title = $attributes[0] ? $attributes[0] : "On This Page";

    return "\n<div id=\"table-of-contents\" data-toc-header=\"$title\"></div>";
});

$this->addShortcode('lorem', function ($attributes, $content, $tagName) {

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

$this->addShortcode('date', function ($attributes, $content, $tagName) {
    $format = $attributes[0] ?? "Y-M-d H:i:s";

    return date($format);
});

$this->addShortcode('load-js', function ($attributes, $content, $tagName) {
    $str = "";

    foreach ($attributes as $file) {
        $str .= '<script src="' . $file . '"></script>';
    }

    return $str;
});

$this->addShortcode('script', function ($attributes, $content, $tagName) {
    $str = "";

    foreach ($attributes as $file) {
        $str .= '<script src="assets/' . $file . '"></script>';
    }

    return $str;
});

$this->addShortcode('encode', function ($attributes, $content, $tagName) {

    $character_set = "+-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz";

    $this->processShortcodes($content);
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
<span id="$id">[protected by js]</span>

<script>
(function (){let a="$key", b, c="$cipher_text", d="";b=a.split("").sort().join("");for(let e=c.length-1;e>-1;e--)
if(a.indexOf(c.charAt(e) !== false)) d+=b.charAt(a.indexOf(c.charAt(e)));else d+= c.charAt(e);d = d.split("").reverse().join("");
document.getElementById("$id").innerHTML=d;
}());
</script>
SCR;

    return trim($js);
});

$this->addShortcode('slides', function ($attributes, $content, $tagName) {

    $content = ($this->conf)('page.content');
    $slides = explode("\n<h2>", $content);
    $tmp = "";
    die ('toto');
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
    //$this->processShortcodes($content);
    //$content = $this->markdownParser->transform($content);

    return $content;
});

$this->addShortcode('gallery', function ($attributes, $content, $tagName) {

    if (isset ($attributes['items'])) {

        $items = ($this->conf)($attributes['items']);
        //dd($items);
        $attributes['items'] = $items;
        return $this->includeTemplate($attributes, $content, 'gallery', false);

        //return $this->renderTemplate($attributes, $content, 'gallery', false);
    }

    die ('rr');

    $type = $attributes[0];
    if (isset ($attributes[1])) {
        $title = $attributes[1];
        return $this->renderTemplate($attributes, $content, 'alert-with-title', false);
    }



    ($this->conf)("media") . "/gallery/$attributes[folder]";

    $directory = ($this->conf)("media") . "/gallery/$attributes[folder]";
    $items = glob($directory . "/*.*");

    $attributes['items'] = $items;
    $attributes['items'] = ['TOTO.jpg'];

    return "gaaaaallery";
    return $this->renderTemplate($attributes, $content, 'gallery', false);

});

$this->addShortcode('__blep', function ($attributes, $content, $tagName) {

    $directory = ($this->conf)("media") . "/gallery/$attributes[folder]";
    $items = glob($directory . "/*.*");

    $attributes['items'] = $items;
    $attributes['items'] = ['TOTO.jpg'];

    return $this->renderTemplate($attributes, $content, 'blep', false);

});

$this->addShortcode('carousel', function ($attributes, $content, $tagName) {
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

$this->addShortcode('.lightboxModal', function ($attributes, $content, $tagName) {

    return $this->templateHandler($attributes, null, $tagName, false);
});

$this->addShortcode('demo', function ($attributes, $content, $tagName) {

    //$content = trim($content);
    $showHTML = $attributes[0] === '+html' ?? false;

    $code = trim($content);

    $html = $this->renderBlock($content);

    $htmlCode = '';
    if ($showHTML) {
        $htmlCode = htmlspecialchars($html);
        $htmlCode = str_replace(
            ['[', ']', '{', '}', '%'],
            ['&#91;', '&#93;', '&lcub;', '&rcub;', '&percnt;'],
            $htmlCode
        );
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

$this->addShortcode('content', function ($attributes, $content, $tagName) {

    return ($this->conf)('page.content');

});


$this->addShortcode('scrolly', function ($attributes, $content, $tagName) {

    return "<div id=\"scrolly\" class=\"$attributes[0]\"/></div>";
});

$this->addShortcode('list', function ($attributes, $content, $tagName) {
    $separtor = $attribute['separator'] ?? '---';
    $id = $attribute['id'] ?? die ("{list} id is missing");

    $str = $this->formatSimpleArray($content, "$id.items");
});



$this->addShortcode('submenu', function ($attributes, $content, $tagName) {

    $attributes[1] ?? $attributes[1] = 'full';
    $dynamic = $attributes[1] !== 'full';
    $type = $dynamic ? 'dynamic' : 'full';
    $attributes['type'] = $type;
    $attributes['level'] = $type === 'full' ? 1000 : intval($attributes[1]);
    $attributes['dynamic'] = $dynamic;
    $attributes['full'] = !$dynamic;
    $attributes['depth'] = 1;

    $elts = null;
    $url = $attributes[0];


    if ($url === '//') {
        $elts = $this->mempad->getRootElements();
    } else if ($url === '/') {
        $elts = $this->mempad->getHome()->children;
    } else {
        $elts = $this->mempad->getElementByUrl($attributes[0]);
        if ($elts) {
            $elts = $elts->children;
        } else
            die ("[submenu $url $attributes[1] ] no children for: $url");

    }


    $attributes['elts'] = $elts;

    return $this->includeTemplate($attributes, $content, 'submenu', false);
});





