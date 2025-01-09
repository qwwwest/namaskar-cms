<?php

namespace Qwwwest\Namaskar;

use Qwwwest\Namaskar\Kernel;


class BlockBooster
{

    private $blocks = [];

    private $firstLevelBlocks = [];
    private $num = -1;
    private $blockid = 0;
    private $in_row = false;

    private static $renderer;
    private $conf;


    public function __construct($renderer)
    {
        if (self::$renderer == null) {
            self::$renderer = $renderer;

            $this->addShortcodes($renderer);
        } else
            die('BlockBooster, one instance only.');

        $this->conf = Kernel::service('ZenConfig');

    }

    public function addShortcodes($renderer)
    {
        // $renderer->addShortcode('====', [$this, 'blocksRenderer']);

        $renderer->addShortcode('row', [$this, 'blocksRenderer']);
        $renderer->addShortcode('block1', [$this, 'blocksRenderer']);
        $renderer->addShortcode('block', [$this, 'blocksRenderer']);

        $renderer->addShortcode('zigzag', [$this, 'blocksRenderer']);
        #$renderer->addShortcode('txt+img', [$this, 'blocksRenderer']);
        #$renderer->addShortcode('img+txt', [$this, 'blocksRenderer']);
        $renderer->addShortcode('hero', [$this, 'blocksRenderer']);
        // $renderer->addShortcode('@@@@@@', [$this, 'columns']);
        $renderer->addShortcode('cols', [$this, 'columns']);
        $renderer->addShortcode('col', [$this, 'columns']);

    }


    public function reShape($content)
    {

        // separators = "@@@", ">>>", "===="

        $split_blocks = preg_split("/\[(@@@|>>>|>>>>) /", $content, 2);

        if (count($split_blocks) === 1)
            return $content;
        $blocks = preg_split("/\[(@@@|>>>) /", $split_blocks[1]);


        $blocks = join("\n[/block]\n\n[block ", $blocks);
        $content = "$split_blocks[0]\n\n[block $blocks [/block]\n";
        return $content;

    }

    public function columns($attributes, $content, $tagName)
    {

        static $equal_columns = true;
        static $col_num = 0;

        if ($tagName === 'cols') {

            $equal_columns = !isset($attributes['md']);
            $col_num = 0;
        }
        $col_num++;

        // $columns = explode("\n[@@@@@@]", $content);

        $classes = self::$renderer->getCssClasses($attributes);

        if (!$equal_columns) {
            die("reminder: only same eq cols for now.");
        }

        if ($equal_columns && $tagName === 'cols') {
            $cols = explode("\n[col", $content);
            $nb = count($cols);
            if ($classes) {
                $classes = " class='$classes'";
            }

            $content = "[col$classes]\n" . join("[/col]\n[col ", $cols) . "[/col]\n";


            // die($content);
            $content = $this->renderBlock($content);
            return <<<HTML
    
        <div class="row row-cols-1 row-cols-lg-$nb">
        $content
        </div>
        HTML;
        }


        $content = $this->renderBlock($content);
        return <<<HTML
            <div class="col $classes">
                    $content
            </div>
            HTML;


    }

    public function hero($attributes, $content, $tagName)
    {

        ($this->conf)("default.blockid", 0);

        if (!isset($attributes['img']) && isset($attributes[0])) {
            $attributes['img'] = $attributes[0];

        }


        ($this->conf)('page.body_classes[]', 'has_hero');


        $hero = $this->includeTemplate($attributes, $content, 'block-hero', false);

        ($this->conf)("site.regions.hero", $hero);

        return '';


    }

    public function cover($attributes, $content, $tagName)
    {

        if (!isset($attributes['img']) && isset($attributes[0])) {
            $attributes['img'] = $attributes[0];

        }
        $cover = $this->includeTemplate($attributes, $content, 'block-cover', false);



        return $cover;


    }

    public function zigzag($attributes, $content, $tagName)
    {

        static $order = true;
        static $ratio = '6/6';
        static $num = 0;


        $order = !$order;

        //was :  $ratio = $attributes['ratio'] ?? ($this->conf)("default.zigzag.ratio") ?? $ratio;
        $ratio = $attributes['ratio'] ?? $this->getDefault('zigzag', 'ratio') ?? $ratio;

        // $attributes = $this->setBlockAttributes($attributes, $tagName);

        $num++;

        $img = $attributes['img'] ?? $attributes['bg'] ?? "";

        if ($img) {
            $media = <<<MEDIA
            [img "$img"]
            MEDIA;

        } else {
            $exploded = explode("\n===", $content, 2);
            if (!isset($exploded[1])) {
                dump("block: zigzag");
                dump($attributes);
                dd($content);

            }
            [$media, $content] = $exploded;

        }


        if (isset($attributes['order'])) {
            $order = $attributes['order'] === 'left';
        }

        $order1 = $order2 = '';

        if ($order) {

            $order1 = 'order-md-2';
            $order2 = 'order-md-1';
        }


        $r = explode('/', $ratio);

        if (count($r) != 2 || $r[0] + $r[1] != 12)
            die('zigzag: ratio incorrect: ' . $ratio);

        $ratio1 = intval($r[0]);
        $ratio2 = intval($r[1]);

        $media = $this->renderBlock($media);
        $content = $this->renderBlock($content);


        $attributes['media'] = $media;
        $attributes['ratio1'] = $ratio1;
        $attributes['ratio2'] = $ratio2;
        $attributes['order1'] = $order1;
        $attributes['order2'] = $order2;




        return $this->includeTemplate($attributes, $content, 'block-zigzag', false);

    }


    public function row($attributes, $content, $tagName)
    {


        $classes = 'row rowblock ';


        if (isset($attributes['height'])) {
            $classes .= ' h' . trim($attributes['height'] ?? '50', '%');
        }

        if (isset($attributes['h'])) {
            $classes .= ' h' . trim($attributes['h'] ?? '50', '%');
        }

        $classes .= ' ' . $this->getCssClasses($attributes); //. " " . $tagName;
        $classes = trim($classes);

        ($this->conf)('in_row', true);
        $this->in_row = true;
        $content = $this->renderBlock($content);
        $this->in_row = false;
        ($this->conf)('in_row', false);

        return <<<HTML
            <div class="$classes">
            $content
            </div>
            HTML;


    }

    public function title($attributes, $content, $tagName)
    {


        $classes = self::$renderer->getCssClasses($attributes);
        $classes = trim($classes);


        $content = $this->renderBlock($content);

        if ($classes)
            return <<<HTML
            <div class="$classes">
            $content
            </div>
            HTML;


        return $content;
    }


    public function blocksRenderer($attributes, $content, $tagName)
    {
        $this->num++;
        $classes = "";


        //$block_type = $attributes[0] ?? null;
        $block_type = strtolower($attributes[0] ?? '');

        // alias
        if ($block_type === 'zz')
            $block_type === 'zigzag';
        // alias
        if ($block_type === 'cover')
            $block_type = 'cover2';

        $attributes = $this->setTopBlockAttributes($attributes, $block_type);

        if ($block_type === 'title') {
            array_shift($attributes);
            return $this->title($attributes, $content, 'title');

        }

        if ($block_type === 'zigzag') {
            array_shift($attributes);
            return $this->zigzag($attributes, $content, 'zigzag');

        } elseif ($block_type === 'hero') {
            array_shift($attributes);
            return $this->hero($attributes, $content, 'hero');

        } elseif ($block_type === 'cover2') {
            array_shift($attributes);
            return $this->cover($attributes, $content, 'cover');

        } elseif ($tagName === 'row') {

            return $this->row($attributes, $content, 'row');
        } elseif ($block_type === 'content') {
            array_shift($attributes);
            return $this->content($attributes, $content, 'content');
        }

        die("unknown block:$block_type");
        // return $this->block($attributes, $content, 'block');

    }
    public function block($attributes, $content, $tagName)
    {

        static $num = 0;
        $classes = "block";


        return $this->includeTemplate($attributes, $content, 'block', false);


    }

    public function content($attributes, $content, $tagName)
    {
        static $ratio = '6';
        static $orderLeft = false;

        $attributes['media'] = false;

        $classes = "content";

        $content = $this->renderBlock($content);

        $img = $attributes['img'] ?? null;


        if ($img) {
            $caption = $attributes['caption'] ?? null;
            // was $ratio = $attributes['ratio'] ?? ($this->conf)("default.content.ratio") ?? $ratio;
            $ratio = intval($attributes['ratio'] ?? $this->getDefault('content', 'ratio') ?? $ratio);

            if (self::$renderer->isSetValue($attributes, 'left'))
                $orderLeft = self::$renderer->isSetValue($attributes, 'left');


            $img = $this->renderBlock("[img \"$img\" caption=\"$caption\" ]");

            $order1 = $order2 = '';

            if ($orderLeft) {

                $order1 = 'order-md-2';
                $order2 = 'order-md-1';
            }


            $attributes['media'] = $img;
            $attributes['ratio1'] = 12 - $ratio;
            $attributes['ratio2'] = $ratio;
            $attributes['order1'] = $order1;
            $attributes['order2'] = $order2;

        }



        //   $attributes = $this->setBlockAttributes($attributes, $tagName);



        return $this->includeTemplate($attributes, $content, 'block-content', false);


    }

    private function renderBlock($blep)
    {
        //return self::$renderer->renderBlock($blep);
        return $this->renderTopBlock($blep);
    }

    public function renderTopBlock($content)
    {
        $content = trim($content);

        if ($content === '')
            return '';

        $content = self::$renderer->renderBlock($content);

        return $content;
    }

    private function includeTemplate($attributes, $content, $template, $auto)
    {
        return self::$renderer->includeTemplate($attributes, $content, $template, $auto);
    }


    private function getDefault($tagName, $attr)
    {
        return ($this->conf)("default.$tagName.$attr")
            ?? ($this->conf)("theme.default.$tagName.$attr")
            ?? null;
    }
    private function setTopBlockAttributes($attributes, $tagName)
    {

        static $numtype = [];

        if (!isset($numtype[$tagName]))
            $numtype[$tagName] = -1;

        $num = $numtype[$tagName] += 1;

        $classes = "topblock block $tagName";

        $responsive = '';
        if (isset($attributes['sd']))
            $responsive .= ' col-sd-' . $attributes['sd'];
        if (isset($attributes['md']))
            $responsive .= ' col-md-' . $attributes['md'];
        if (isset($attributes['lg']))
            $responsive .= ' col-lg-' . $attributes['lg'];

        if (isset($attributes['xl']))
            $responsive .= ' col-xl-' . $attributes['xl'];

        if ($this->in_row && $responsive === '') {
            $classes .= " col-md";
        }

        $classes .= " $responsive";

        $rgba = $attributes['rgba'] ?? '#00000000';

        $dark = $attributes['dark'] ?? $this->getDefault($tagName, 'dark');


        if ($dark !== null) {
            $rgba = '#000000' . (dechex(round(trim($dark, '%') * 255 / 100)));

        }



        $light = $attributes['light'] ?? $this->getDefault($tagName, 'light');
        if ($light !== null) {
            $rgba = '#ffffff' . (dechex(round(trim($light, '%') * 255 / 100)));
            $classes .= " light";
        }

        $attributes['rgba'] = $rgba;

        if (isset($attributes['height'])) {
            $classes .= ' h' . trim($attributes['height'] ?? '100', '%');
        }

        $height = $attributes['h'] ?? $this->getDefault($tagName, 'h');

        if ($height)
            $attributes['height'] = trim($height, '%');



        // $height = $attributes['h'] ?? ($this->conf)("default.$tagName.h") ?? null;
        // if ($height)
        //     $attributes['style'] = 'height:' . trim($height, '%') . "vh";


        $pos = $attributes['pos'] ?? $this->getDefault($tagName, 'pos') ?? 5;

        $x = ($pos - 1) % 3;
        $y = intdiv(($pos - 1), 3);

        $classes .= " x$x y$y";


        $blockid = ($this->conf)("default.blockid");

        if ($blockid === null) {
            $blockid = 0;

        } else {
            $blockid++;

        }

        $swapClasses = explode('/', $this->getDefault($tagName, 'classes') ?? "");


        $swapClasses = $swapClasses[$num % count($swapClasses)] ?? '';

        if ($swapClasses)
            $classes .= " $swapClasses";

        $attributes['classes'] = $classes . ' ' . self::$renderer->getCssClasses($attributes);



        ($this->conf)("default.blockid", $blockid);

        $id = self::$renderer->id($attributes);
        if ($id)
            $id = " id=$id";
        else {

            // $id = " id='block$blockid'";
            $id = " id=block$blockid";

        }

        $attributes['id'] = $id;

        return $attributes;
    }



}