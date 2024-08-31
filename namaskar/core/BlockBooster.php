<?php

namespace Qwwwest\Namaskar;

use Qwwwest\Namaskar\Kernel;


class BlockBooster
{

    private $blocks = [];

    private $firstLevelBlocks = [];
    private $num = 0;
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
        $renderer->addShortcode('====', [$this, 'blocksRenderer']);

        $renderer->addShortcode('row', [$this, 'blocksRenderer']);
        $renderer->addShortcode('block', [$this, 'blocksRenderer']);

        $renderer->addShortcode('zigzag', [$this, 'blocksRenderer']);
        $renderer->addShortcode('hero', [$this, 'blocksRenderer']);

    }



    public function hero($attributes, $content, $tagName)
    {

        ($this->conf)("default.blockid", 0);

        $attributes = $this->setAttributes($attributes, $tagName);

        $attributes['id'] = " id=block0";

        ($this->conf)('page.body_classes[]', 'has_hero');


        $hero = $this->includeTemplate($attributes, $content, 'block-hero', false);

        ($this->conf)("site.regions.hero", $hero);

        return '';


    }

    public function zigzag($attributes, $content, $tagName)
    {


        static $order = true;
        static $ratio = '6/6';
        static $num = 0;


        $order = !$order;

        $ratio = $attributes['ratio'] ?? ($this->conf)("default.zigzag.ratio") ?? $ratio;

        $attributes = $this->setAttributes($attributes, $tagName);

        $num++;

        if (isset($attributes['img'])) {
            $media = <<<MEDIA
            [img "$attributes[img]"]
            MEDIA;

        } else if (isset($attributes['bg'])) {
            $media = <<<MEDIA
            [img "$attributes[bg]"]
            MEDIA;

        } else {
            $exploded = explode("\n====", $content);
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


    public function blocksRenderer($attributes, $content, $tagName)
    {
        $this->num++;
        $classes = "block";


        $blocks = explode('[==== ', $content);
        if (count($blocks) > 1) {

            $blocks = join("[/====]\n\n[==== ", $blocks);
            $attr = '';
            foreach ($attributes as $key => $value) {
                if (is_int($key))
                    $attr .= " $value";
                else
                    $attr .= " $key=\"$value\"";
            }

            return $this->renderBlock("[==== $attr ]\n" . $blocks . "\n[/====]");
        }

        $blocks = explode('[block ', $content);
        if (count($blocks) > 1) {

            $blocks = join("[/block]\n\n[block ", $blocks);
            $attr = '';
            foreach ($attributes as $key => $value) {
                if (is_int($key))
                    $attr .= " $value";
                else
                    $attr .= " $key=\"$value\"";
            }

            return $this->renderBlock("[block $attr ]\n" . $blocks . "\n[/block]");
        }

        $block_type = $attributes[0] ?? null;

        $attributes = $this->setAttributes($attributes, $tagName);

        if ($block_type === 'zigzag' || $block_type === 'zz') {
            array_shift($attributes);
            return $this->zigzag($attributes, $content, 'zigzag');
        } elseif ($block_type === 'hero') {
            array_shift($attributes);
            return $this->hero($attributes, $content, 'hero');
        } elseif ($tagName === 'row') {

            return $this->row($attributes, $content, 'row');
        }

        return $this->block($attributes, $content, 'block');

    }
    public function block($attributes, $content, $tagName)
    {

        static $num = 0;
        $classes = "block";

        // $blocks = explode('[block ', $content);
        // if (count($blocks) > 1) {

        //     $blocks = join("[/block]\n\n[block ", $blocks);
        //     $attr = '';
        //     foreach ($attributes as $key => $value) {
        //         if (is_int($key))
        //             $attr .= " $value";
        //         else
        //             $attr .= " $key=\"$value\"";
        //     }

        //     return $this->renderBlock("[block $attr ]\n" . $blocks . "\n[/block]");
        // }


        return $this->includeTemplate($attributes, $content, 'block', false);


    }


    private function renderBlock($blep)
    {
        return self::$renderer->renderBlock($blep);
    }

    private function includeTemplate($attributes, $content, $template, $auto)
    {
        return self::$renderer->includeTemplate($attributes, $content, $template, $auto);
    }


    private function setAttributes($attributes, $tagName)
    {

        static $numtype = [];

        if (!isset($numtype[$tagName]))
            $numtype[$tagName] = -1;

        $num = $numtype[$tagName] += 1;

        $classes = "block $tagName";

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

        $rgba = $attributes['rgba'] ?? null;

        $dark = $attributes['dark'] ?? ($this->conf)("default.$tagName.dark");

        if ($dark) {
            $rgba = '#000000' . (dechex(round(trim($dark, '%') * 255 / 100)));
        }

        $light = $attributes['light'] ?? ($this->conf)("default.$tagName.light");
        if ($light) {
            $rgba = '#ffffff' . (dechex(round(trim($light, '%') * 255 / 100)));
        }

        if ($rgba)
            $attributes['rgba'] = $rgba;

        if (isset($attributes['height'])) {
            $classes .= ' h' . trim($attributes['height'] ?? '50', '%');
        }

        $height = $attributes['h'] ?? ($this->conf)("default.$tagName.h") ?? 100;
        $classes .= ' h' . trim($height, '%');

        $pos = $attributes['pos'] ?? ($this->conf)("default.$tagName.pos") ?? 5;


        $x = ($pos - 1) % 3;
        $y = intdiv(($pos - 1), 3);

        $classes .= " x$x y$y";


        $blockid = ($this->conf)("default.blockid");

        if ($blockid === null) {
            $blockid = 0;

        } else {
            $blockid++;

        }

        $swapClasses = explode('/', ($this->conf)("default.$tagName.classes") ?? "");

        $swapClasses = $swapClasses[$num % count($swapClasses)] ?? '';
        //$classes .= $this->getCssClasses($attributes);

        if ($swapClasses)
            $classes .= " $swapClasses";

        $attributes['classes'] = $classes . ' ' . self::$renderer->getCssClasses($attributes);



        ($this->conf)("default.blockid", $blockid);

        $id = self::$renderer->id($attributes);
        if ($id)
            $id = " id='$id'";
        else {

            $id = " id='block$blockid'";

        }

        $attributes['id'] = $id;

        return $attributes;
    }

}