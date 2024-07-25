<?php
namespace Qwwwest\Namaskar;


use Qwwwest\Namaskar\Kernel;


/*
 *  Form Class
 *  Creates HTML Forms with browser and server-side validation
 */

class Form
{

    private static $forms = [];
    private string $id;
    private string $mode;
    private bool $isPost;
    private static $renderer;
    private $conf;

    public function __construct($renderer)
    {
        if (self::$renderer == null) {
            self::$renderer = $renderer;

            $this->addShortcodes($renderer);
        }

        $this->conf = Kernel::service('ZenConfig');
        $this->mode = $_SERVER['REQUEST_METHOD'];
        $this->isPost = $_SERVER['REQUEST_METHOD'] === 'post';

        $forms[] = $this;

    }


    public function render(): self
    {

    }

    public function validate()
    {

    }

    public function sanitize()
    {

    }



    public function addShortcodes($renderer)
    {
        $renderer->addShortcode('contact_form', [$this, 'contact_form']);
        $renderer->addShortcode('form', [$this, 'form']);
        $renderer->addShortcode('input', [$this, 'input']);
        $renderer->addShortcode('select', [$this, 'select']);
        $renderer->addShortcode('submit', [$this, 'submit']);
    }



    public function form($attributes, $content, $tagName): string
    {
        $countPost1 = count($_POST);

        $id = $attributes['id'] ?? $tagName;

        ($this->conf)('form.id', $id);


        $content = self::$renderer->renderBlock($content);
        $countPost2 = count($_POST);
        $classes = self::$renderer->getCssClasses($attributes);
        $classes = $classes ? ' ' . $classes : '';

        ($this->conf)('form.id', null);
        return <<<HTML
        <form method=post class="mb-3 row$classes">$content</form>
        HTML;


    }

    public function contact_form($attributes, $content, $tagName): string
    {
        $id = $attributes['id'] ?? $tagName;
        ($this->conf)('form.id', $id);

        $content = self::$renderer->renderBlock($content);

        $classes = self::$renderer->getCssClasses($attributes);
        $classes = $classes ? ' ' . $classes : '';
        $post = ($_POST) ? dump($_POST, '$_POST', true) : '';

        ($this->conf)('form.id', null);
        return <<<HTML
        <form method=post class="mb-3 row$classes">$content</form>
        $post
        HTML;

    }
    public function select($attributes, $content, $tagName): string
    {
        // [field text firstname]
        $type = $attributes[0];
        $id = $attributes[1];

        $classes = self::$renderer->getCssClasses($attributes);

        //$classes = $classes ? $classes . ' ' : '';

        $content = <<<HTML
        <div class="$classes">
            <label for="$id" class="form-label">State</label>
            <select id="$id" name="$id" class="form-select">
            <option selected>Choose...</option>
            <option>1</option>
            <option>2</option>
            </select>
        </div>
    
        HTML;

        return $content;


    }

    public function submit($attributes, $content, $tagName): string
    {
        // [submit Submit ]
        // $type = $attributes[0];
        // $id = $attributes[1];
        $label = $attributes[0];

        // $classes = self::$renderer->getCssClasses($attributes);

        //$classes = $classes ? $classes . ' ' : '';
        $classes = self::$renderer->getCssClasses($attributes);
        $content = <<<HTML
        <div class="col-12 text-center ">
        <button type="submit" id="submit" name="submit" value="submit" class="btn btn-lg mb-3 $classes">$label</button>
        </div>
        
        HTML;

        return $content;


    }

    public function input($attributes, $content, $tagName): string
    {
        // [input text "First name"]
        // [input email "E-mail"]
        $type = $attributes[0];


        $formId = ($this->conf)('form.id');
        $label = $attributes[2];
        $id = $attributes[2];
        $id = $formId . '_' . preg_replace('/\s+/', '', $id);

        $required = in_array('*', $attributes) ? ' required' : '';

        $asteriks = $required ? '' : '';
        $label .= $asteriks;
        $value = '';
        if (isset($_POST[$id])) {
            $value = trim($_POST[$id]);
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }

        $classes = self::$renderer->getCssClasses($attributes);

        $classes = $classes ? $classes . ' ' : 'col-12';
        if ($type === 'textarea') {
            $height = $attributes['height'] ?? '10rem';
            $content = <<<HTML
                <div class="form-floating mb-3">
                <textarea class="form-control" placeholder="$label" id="$id" style="height: $height" name="$id" $required>$value</textarea>
                <label for="$id">$label</label>
                </div>
                HTML;

        } elseif ($type === 'checkbox')
            $content = <<<HTML
   
        <div class="col-12 mb-3">
            <div class="form-check">
            <input class="form-check-input" type="checkbox" id="$id" name="$id" value="$value" $required>
            <label class="form-check-label" for="$id">$label</label>
            </div>
        </div>
        HTML;
        else
            $content = <<<HTML
        
            <div class="form-floating $classes mb-2">
            <input type="$type" class="form-control" id="$id" name="$id" aria-label="$id" value="$value" $required placeholder="$label">
            <label for="$id">$label</label>
            </div>
            HTML;

        return $content;
    }
}