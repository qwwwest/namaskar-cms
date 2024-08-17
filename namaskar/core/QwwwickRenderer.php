<?php

namespace Qwwwest\Namaskar;

use Qwwwest\Namaskar\Kernel;
use Qwwwest\Namaskar\ZenConfig;
use Michelf\MarkdownExtra;


class QwwwickRenderer
{
    private $attrPattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';

    private $attrTokenPattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=[^=]\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';

    private $shortcodes = array();
    private $markdownParser;

    private $tokens = array();
    private $conf;
    private $mempad;

    private $basename;
    private $context;
    private $tfolders;
    private $theme;
    private $config;
    private $currentExp = '';
    private $currentTemplate;


    public function __construct($templateFolders)
    {

        $this->tfolders = $templateFolders;


        $this->conf = Kernel::service('ZenConfig');
        $this->mempad = ($this->conf)('MemPad');
        // if ($this->mempad === null)
        //     die('MemPad is NOT set');
        $this->context = [$this->conf];
        $this->currentTemplate = [];

        $this->setTokens();

        require_once __DIR__ . '/NamaskarShortcodes.php';
        $this->markdownParser = new MarkdownExtra;
        $this->markdownParser->hard_wrap = true;
    }

    public function newContext($str)
    {

        $this->currentTemplate[] = $str;
        $this->context[] = new ZenConfig();
    }

    public function oldContext()
    {
        array_pop($this->currentTemplate);
        array_pop($this->context);
    }

    public function setContextValue($key, $value)
    {
        $context = end($this->context);
        // $context = &$this->context[count($this->context) - 1];
        $context($key, $value);

    }

    public function getContextValue($key)
    {
        // 'string' => string
        if (strlen($key) > 1 && substr($key, 0, 1) === "'" && substr($key, -1, 1) === "'") {
            return substr($key, 1, -1);
        }

        //  true
        if ($key === 'true') {
            return true;
        }

        //  false
        if ($key === 'false') {
            return false;
        }

        // page.title => ZenConfig page title value
        $context = end($this->context);
        $found = null;
        do {
            $found = $context($key);

        } while ($found === null && $context = prev($this->context));

        return $found;
    }

    public function issetContextValue($key)
    {
        // 'string' => string
        if (strlen($key) > 1 && substr($key, 0, 1) === "'" && substr($key, -1, 1) === "'") {
            return true;
        }

        //  true
        if ($key === 'true') {
            return true;
        }

        //  false
        if ($key === 'false') {
            return false;
        }

        // page.title => ZenConfig page title value
        $context = end($this->context);
        $found = null;
        do {
            $found = $context($key);

        } while (!$found && $context = prev($this->context));

        return $found;
    }
    /**
     * @param string $tag
     * @param callable $function
     * @throws \ErrorException
     */
    public function addShortcode($tag, $function)
    {
        if (!is_callable($function)) {
            throw new \ErrorException("Function must be callable ($tag)");
        }

        $this->shortcodes[$tag] = $function;
    }

    private function addToken($tag, $function)
    {
        if (!is_callable($function)) {
            throw new \ErrorException("Function must be callable");
        }

        $this->tokens[$tag] = $function;
    }

    private function isToken($tag)
    {
        return isset($this->tokens[$tag]);
    }

    /**
     * @return array
     */
    public function getShortcodes()
    {
        return $this->shortcodes;
    }
    public function processShortcodes($content)
    {
        if (empty($this->shortcodes)) {
            return $content;
        }

        return preg_replace_callback($this->shortcodeRegex(), array($this, '_processShortCode'), $content);
    }

    private function _processShortCode(array $tag)
    {
        // allow [[foo]] syntax for escaping a tag
        if ($tag[1] == '[' && $tag[6] == ']') {
            return substr($tag[0], 1, -1);
        }

        $tagName = $tag[2];
        $attr = $this->parseAttributes($tag[3]);

        if (isset($tag[5])) {
            // enclosing tag - extra parameter
            return $tag[1] . call_user_func($this->shortcodes[$tagName], $attr, $tag[5], $tagName) . $tag[6];
        } else {
            // self-closing tag
            return $tag[1] . call_user_func($this->shortcodes[$tagName], $attr, null, $tagName) . $tag[6];
        }
    }

    public function processTokens($content)
    {
        if (empty($this->tokens)) {
            return $content;
        }

        //remove comments
        $content = preg_replace("/([\s]*{#.*?#}[\s]+)/s", '', $content);


        $content = preg_replace('/{{[\s]*?(.+?)[\s]*?}}/', '{%= $1 %}', $content);

        $content = $this->preprocessTemplate($content);


        return preg_replace_callback($this->tokenRegex(), array($this, '_processToken'), $content);
    }

    private function preprocessTemplate($html): string
    {

        $pattern = "#({% if |{% for |{% endif %}|{% endfor %})#s";


        $html = preg_replace_callback($pattern, function ($matches) {
            static $levelIf = 0;
            static $levelFor = 0;
            //dd($matches);
            if ($matches[1] === '{% if ') {
                $levelIf++;
                return "{% if$levelIf ";
            }
            if ($matches[1] === '{% endif %}') {
                $val = "{% endif$levelIf %}";
                $levelIf--;
                return $val;
            }
            if ($matches[1] === '{% for ') {
                $levelFor++;
                return "{% for$levelFor ";
            }
            if ($matches[1] === '{% endfor %}') {
                $val = "{% endfor$levelFor %}";
                $levelFor--;
                return $val;
            }
            return 'blep';
        }, $html);



        return $html;

    }
    private function _processToken(array $tag)
    {

        // if ($tag[1] == '{' && $tag[6] == '}') {
        //     return substr($tag[0], 1, -1);
        // }
        // if ($tag[2] === '=')
        //     dump($tag[3]);
        $tagName = $tag[2];
        $attr = $this->parseTokenAttributes($tag[3]);
        return call_user_func($this->tokens[$tagName], $attr, $tag[5] ?? null, $tagName, $tag[3]);

    }

    /**
     * Retrieve all attributes from the shortcodes tag.
     *
     * The attributes list has the attribute name as the key and the value of the
     * attribute as the value in the key/value pair. This allows for easier
     * retrieval of the attributes, since all attributes have to be known.
     *
     *
     * @param string $text
     * @return array List of attributes and their value.
     */
    private function parseAttributes($text)
    {
        $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);

        if (!preg_match_all($this->attrPattern, $text, $matches, PREG_SET_ORDER)) {
            return array(ltrim($text));
        }

        $attr = array();

        foreach ($matches as $match) {
            if (!empty($match[1])) {
                $attr[strtolower($match[1])] = stripcslashes($match[2]);
            } elseif (!empty($match[3])) {
                $attr[strtolower($match[3])] = stripcslashes($match[4]);
            } elseif (!empty($match[5])) {
                $attr[strtolower($match[5])] = stripcslashes($match[6]);
            } elseif (isset($match[7]) && strlen($match[7])) {
                $attr[] = stripcslashes($match[7]);
            } elseif (isset($match[8])) {
                $attr[] = stripcslashes($match[8]);
            }
        }

        return $attr;
    }

    private function parseTokenAttributes($text)
    {
        $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);

        if (!preg_match_all($this->attrTokenPattern, $text, $matches, PREG_SET_ORDER)) {
            return array(ltrim($text));
        }

        $attr = array();

        foreach ($matches as $match) {
            if (!empty($match[1])) {
                $attr[strtolower($match[1])] = stripcslashes($match[2]);
            } elseif (!empty($match[3])) {
                $attr[strtolower($match[3])] = stripcslashes($match[4]);
            } elseif (!empty($match[5])) {
                $attr[strtolower($match[5])] = stripcslashes($match[6]);
            } elseif (isset($match[7]) && strlen($match[7])) {
                $attr[] = stripcslashes($match[7]);
            } elseif (isset($match[8])) {
                $attr[] = stripcslashes($match[8]);
            }
        }

        return $attr;
    }
    /**
     * Retrieve the shortcode regular expression for searching.
     *
     * The regular expression combines the shortcode tags in the regular expression
     * in a regex class.
     *
     * The regular expression contains 6 different sub matches to help with parsing.
     *
     * 1 - An extra [ to allow for escaping shortcodes with double [[]]
     * 2 - The shortcode name
     * 3 - The shortcode argument list
     * 4 - The self closing /
     * 5 - The content of a shortcode when it wraps some content.
     * 6 - An extra ] to allow for escaping shortcodes with double [[]]
     *
     * @return string The shortcode search regular expression
     */
    private function shortcodeRegex()
    {
        $tagRegex = join('|', array_map('preg_quote', array_keys($this->shortcodes)));

        return
            '/\\[(\\[?)' . "($tagRegex)"
            . '(?![\\w-])([^\\]\\/]*(?:\\/(?!\\])[^\\]\\/]*)*?)(?:(\\/)\\]|\\](?:'
            . '([^\\[]*+(?:\\[(?!\\/\\2\\])[^\\[]*+)*+)\\[\\/\\2\\])?)(\\]?)/s';
    }
    private function tokenRegex()
    {

        $tokens = join('|', array_map('preg_quote', array_keys($this->tokens)));

        return
            '/{(%)[ ]?(' . $tokens . ')(?![\\w-])([^}\\/]*(?:\\/(?!\\])[^}\\/]*)*?)'
            . '(?:(\\/)}}|}}|%}(?:([^{]*+(?:{(?!%[\s]*end\\2[\s]*%)[^{]*+)*+)'
            . '{%[\s]*end\\2[\s]*%)?)(}?)/s';

    }



    public function renderBlock($content)
    {
        $content = trim($content);

        $content = $this->processShortcodes($content);

        $content = $this->processTokens($content);

        $content = $this->markdownParser->transform($content);
        if (
            strpos($content, '<p>') === 0
            && strpos($content, '</p>', strlen($content) - 5)
            && strpos($content, '<p>', 4) === false
        )
            $content = trim(substr($content, 3, -5)); // remove '<p>' tags
        return $content;
    }

    /*
     * Render To html
     */

    public function renderPage($template)
    {

        $file = $this->resolveTemplate("$template/index.html");
        if (!$file) {
            die("template : '$file' not found in Renderer::render");
        }

        $this->newContext('index.html');

        $template = file_get_contents($file);

        $content = trim(($this->conf)('page.content'));
        $content = $this->renderBlock($content);
        $html = $this->processShortcodes($content);
        $this->postContentProcessing();
        ($this->conf)('page.content', trim($content));



        $html = $this->processTokens($template);
        $html = $this->processShortcodes($html);
        $this->oldContext();

        return $html;
    }

    public function postContentProcessing()
    {

        $conf = $this->conf;

        if ($conf('page.show.regions')) {
            $conf('page.body_classes[]', 'show-regions');
        }


        if ($conf('theme.navbar.fixed')) {
            $conf('page.body_classes[]', 'navbar-fixed');
        }

        $bodyclasses = $conf('page.body_classes');



        if (is_array($bodyclasses)) {
            $classes = implode(' ', $bodyclasses);
            $conf('page.body_class_attribute', " class='$classes'");

        }

    }

    public function renderLoginPage()
    {

        $file = $this->resolveTemplate("admin/login.html");
        if (!$file) {
            die("template : '$file' not found in Renderer::render");
        }

        $this->newContext('login.html');

        $template = file_get_contents($file);

        // $content = trim(($this->conf)('page.content'));
        // $content = $this->renderBlock($content);
        // $html = $this->processShortcodes($content);
        // ($this->conf)('page.content', trim($content));

        $html = $this->processTokens($template);
        $html = $this->processShortcodes($html);
        $this->oldContext();

        return $html;
    }

    public function renderAdminPage()
    {

        $file = $this->resolveTemplate("admin/dashboard.html");
        if (!$file) {
            die("template : admin/dashboard.html not found in Renderer::render");
        }

        $this->newContext('dashboard.html');

        $template = file_get_contents($file);

        // $content = trim(($this->conf)('page.content'));
        // $content = $this->renderBlock($content);
        // $html = $this->processShortcodes($content);
        // ($this->conf)('page.content', trim($content));

        $html = $this->processTokens($template);
        $html = $this->processShortcodes($html);
        $this->oldContext();

        return $html;
    }
    /*
     * include and render template file in new context with only local variables
     */
    public function include($__template, $__vars = null)
    {
        static $__depth = 0;
        $__depth++;
        if ($__depth > 12)
            die('infinite recursivity in ' . $__template); // to avoid infinite recursivity



        $__file = $this->resolveTemplate($__template);
        if (!$__file) {

            die("template : '$this->theme/$__template' not found in Renderer::include");
        }

        $this->newContext($__template);

        if ($__vars)
            foreach ($__vars as $key => $var) {
                if (is_string($key))
                    $this->setContextValue($key, $var);
                if (is_int($key)) {
                    $this->setContextValue("_$key", $var);
                    $this->setContextValue("attribute$key", $var);
                }


            }
        // if ($__template === 'bootstrap5/alert.html')
        //     dump($__template);
        $__html = file_get_contents($__file);
        $__html = $this->processTokens($__html);
        $__html = $this->processShortcodes($__html);
        $this->oldContext();


        $__depth--;
        $__template = substr($__template, 0, -5);
        return "\n<!-- $__template -->\n$__html \n<!-- /$__template -->\n";



    }

    public function resolveTemplate($template)
    {


        if (strpos($template, '/') !== false) {
            // bootstrap5/index.html
            $parts = explode('/', $template);
            $theme = $parts[0];
            $basename = $parts[1];
        } else {
            // index.html
            $theme = N('page.theme') ?? N('site.theme') ?? 'bootstrap5';
            $basename = $template;
        }


        foreach ($this->tfolders as $tfolder) {

            $file = "$tfolder/$theme/$basename";

            if (\file_exists($file)) {
                return $file;
            }


        }
        foreach ($this->tfolders as $tfolder) {

            $file = "$tfolder/bootstrap5/$basename";

            if (\file_exists($file)) {
                return $file;
            }


        }
        return false;


    }

    private function includeTemplate($attributes, $content, $templateName, $selfRemoveOnEmpty)
    {
        static $rec = 0;

        if ($selfRemoveOnEmpty && trim($content) == '') {
            return '';
        }

        $rec++;

        if ($rec > 20) {
            die('recurtion spotted in ' . $templateName);
        }
        //   $templateFile = $this->resolve_template($tagName);
        $theme = ($this->conf)('site.theme');
        $templateFile = "$theme/$templateName.html";


        $vars = [];
        foreach ($attributes as $key => $value) {
            $vars[$key] = $value;
        }

        // $vars['content'] = $content;
        $vars['content'] = $this->renderBlock($content);
        ;
        // $vars = [...$attributes, 'content' => $content];

        $html = $this->include($templateFile, $vars);
        // $html = $this->processMarkdown($html);

        $rec--;
        return $html;

    }

    private function setTokens()
    {
        // {{= page.language}}
        // {{= theme.colormode ? theme.colormode : 'auto '}}
        $this->addToken('=', function ($attributes, $content, $tagName, $attrStr) {
            $z = $this->conf;

            $this->currentExp = $attrStr;
            return $this->expEval(trim($attrStr));



        });

        $this->addToken('!--', function ($attributes, $content, $tagName, $attrStr) {
            return '';
        });

        $this->addToken('#', function ($attributes, $content, $tagName, $attrStr) {
            return '';
        });



        for ($i = 1; $i < 6; $i++) {
            $this->addToken("if$i", array($this, '_if'));
            $this->addToken("for$i", array($this, '_for'));
        }



        /* {% set value = 'value2' %}
        {% switch value %}
            {% case 'value1' %}
                <p>value is "value1"</p>
            {% case 'value2' %}
                <p>value is "value2"</p>
            {% case 'value3' %}
            {% case 'value4' %}
                <p>value is "value3" or "value4"</p>
            {% default %}
                <p>value is something else</p>
        {% endswitch %} */
        $this->addToken('switch', function ($attributes, $content, $tagName, $attrStr) {

            if (count($attributes) !== 1)
                dd($attributes, "switch error");

            $regex = '/{% (case|default) (.*?)?[ ]?%}([^{]+)(?={%|$)/s';
            preg_match_all($regex, $content, $matches);

            $switchValue = $this->getContextValue($attributes[0]);

            foreach ($matches[1] as $key => $type) {
                $caseValue = trim($matches[2][$key], "'");
                $result = trim($matches[3][$key]);

                if ($type === 'default' || $switchValue === $caseValue)
                    return $this->process($result);

            }



        });

        //   {% for item in site.menu.main %} ...    {% endfor %}

        $this->addToken('include', function ($attributes, $content, $tagName, $attrStr) {

            //example: {{ include "navbar.html" }}
            $template = array_shift($attributes);
            if (count($attributes) === 0) {
                return $this->include($template);
            }

            if (count($attributes) === 2 && $attributes[0] === 'if') {


                if ($this->getContextValue($attributes[1])) {
                    return $this->include($template);
                }
                return '';

            }

            if ($attributes[0] === 'with') {

                array_shift($attributes);

                $params = explode("\n", $attrStr);
                array_shift($params);

                $withAttr = [];
                foreach ($params as $param) {

                    $keyval = explode(" = ", $param, 2);

                    if (count($keyval) === 2)
                        $withAttr[trim($keyval[0])] = $this->expEval(trim($keyval[1]));

                }



                // foreach ($attributes as $key => $value) {
                //     $attributes[$key] = $this->eval($value);

                // }

                return $this->include($template, $withAttr);
                ;

            }

            dd($attributes);

        });

        $this->addToken('set', function ($attributes, $content, $tagName, $attrStr) {

            //example: {% set value = 'toto' %}

            if (count($attributes) === 1) {
                $key = array_key_first($attributes);
                $this->setContextValue($key, $attributes[$key]);
                $this->getContextValue($key);
                return '';

            }
            dd($attributes, 'TwiQ :: set Token mismatch');

        });

    }

    private function _if($attributes, $content, $tagName, $attrStr)
    {

        $parts = preg_split("/{%[\s]*else[\s]*%}/", $content);

        $code = null;

        $eval = $this->expEval($attrStr);
        if ($eval)
            $code = $parts[0];
        else
            $code = $parts[1] ?? '';

        if ($code === '')
            return '';

        $html = $this->processTokens($code);
        return $this->processShortcodes($html);



    }
    private function __OLD_if($attributes, $content, $tagName, $attrStr)
    {
        $z = $this->conf;
        //$content = explode('{{else}}', $content, 1);
        //$parts = preg_split("/{{[\s]*else[\s]*}}/", $content);
        $parts = preg_split("/{%[\s]*else[\s]*%}/", $content);

        $code = null;
        //    dump($attrStr);
        $eval = $this->expEval($attrStr);
        if ($eval)
            $code = $parts[0];
        else
            $code = $parts[1] ?? '';

        if ($code === null)
            dd($attributes, 'if out of range');

        $html = $this->processTokens($code);
        return $this->processShortcodes($html);

        // {{if page.language}}
        if (count($attributes) === 1) {
            $val = $this->getContextValue($attributes[0]);
            if ($val)
                $code = $parts[0];
            else
                $code = $parts[1] ?? '';
        }
        // {{if page.language && page.title }} 
        elseif (count($attributes) === 3 && $attributes[1] === '&&') {

            if ($this->eval($attributes[0]) && $this->eval($attributes[2]))
                $code = $parts[0];
            else
                $code = $parts[1] ?? '';
        }
        // {{if ! page.language }} 
        elseif (count($attributes) === 2 && $attributes[0] === '!') {
            if (!$this->getContextValue($attributes[1]))
                $code = $parts[0];
            else
                $code = $parts[1] ?? '';
            // } elseif (count($attributes) === 2 && reset($attributes) === '==') {

            //     $var = array_key_first($attributes);

            //     if ($this->getContextValue($var) === $this->getContextValue($attributes[0]))
            //         $code = $parts[0];
            //     else
            //         $code = $parts[1] ?? '';
        }



        // {{if page.language || page.title }} 
        else if (count($attributes) === 3 && $attributes[1] === '||') {
            if ($this->getContextValue($attributes[0]) || $this->getContextValue($attributes[2]))
                $code = $parts[0];
            else
                $code = $parts[1] ?? '';

        } else if (count($attributes) === 3 && $attributes[1] === '===') {
            if (
                $this->getContextValue($attributes[0])
                === $this->getContextValue($attributes[2])
            )
                $code = $parts[0];
            else
                $code = $parts[1] ?? '';

        }

        if ($code === null)
            dd($attributes, 'if out of range');

        $html = $this->processTokens($code);
        return $this->processShortcodes($html);
        //return $this->processShortcodes($code) . "<!-- $code -->";
        //return $this->process($code) . "<!-- $code -->";


    }

    private function _for($attributes, $content, $tagName, $attrStr)
    {
        //$z = $this->conf;
        //$content = explode('{{else}}', $content, 1);
        //$parts = preg_split("/{{[\s]*else[\s]*}}/", $content);


        $items = $this->getContextValue($attributes[2]);

        if ($items === null) {
            return '';
        }

        $__html = '';
        foreach ($items as $key => $item) {
            $this->newContext('for ' . $attrStr);
            $loop = [];
            $loop['index'] = $key + 1;
            $loop['index0'] = $key;
            $loop['key'] = $key;
            $loop['first'] = $key === 0;
            $loop['last'] = $key === count($items) - 1;


            $this->setContextValue($attributes[0], $item);


            $this->setContextValue('loop', $loop);

            $__block = $this->processTokens($content);
            $__block = $this->processShortcodes($__block);

            $__html .= $__block;
            $this->oldContext();
        }


        return $__html;

    }

    public function ___eval($value)
    {
        if (strlen($value) > 1 && substr($value, 0, 1) === "'" && substr($value, -1, 1) === "'") {
            return substr($value, 1, -1);
        }

        //  true
        if ($value === 'true') {
            return true;
        }

        //  false
        if ($value === 'false') {
            return false;
        }

        if (preg_match('/^!(.+)$/', $value, $matches)) {

            return !$this->eval($matches[1]);
        }

        if (preg_match('/^[0-9.]+$/', $value, $matches)) {
            return floatval($value);
        }

        if (preg_match('/^[a-z_.]+$/', $value, $matches)) {
            return $this->getContextValue($value);
        }

        if (preg_match('/^([a-z_.]+)\+1$/', $value, $matches)) {
            return $this->getContextValue($matches[1]) + 1;
        }
        if (preg_match('/^([a-z_.]+)-1$/', $value, $matches)) {
            return $this->getContextValue($matches[1]) - 1;
        }

        if (preg_match('/^([a-zA-Z_]+)\(([a-z_.]+)\)$/', $value, $matches)) {
            if ($matches[1] === 'isElementAccessible') {
                $elt = $this->getContextValue($matches[2]);

                return $this->isElementAccessible($elt);
            }


        }

        echo "parsing '$value' failed";
    }

    public function expEval(string $value)
    {
        // ()
        $value = trim($value);
        // bool ? exp : exp
        if (preg_match('/^(.+) \? (.+) : (.+)$/', $value, $matches)) {

            return $this->expEval($matches[1]) ?
                $this->expEval($matches[2])
                :
                $this->expEval($matches[3]);
        }
        // exp ?? exp
        if (preg_match('/^(.+) \?\? (.+)$/', $value, $matches)) {
            return $this->expEval($matches[1]) ?? $this->expEval($matches[2]);
        }
        // exp || exp || exp
        if (preg_match('/^(.+) \|\| (.+)$/', $value, $matches)) {
            return $this->expEval($matches[1]) || $this->expEval($matches[2]);
        }
        // exp && exp && exp
        if (preg_match('/^(.+) && (.+)$/', $value, $matches)) {
            return $this->expEval($matches[1]) && $this->expEval($matches[2]);
        }
        // exp != exp 
        if (preg_match('/^(.+) !=[=]? (.+)$/', $value, $matches)) {
            return $this->expEval($matches[1]) !== $this->expEval($matches[2]);
        }
        // exp == exp
        if (preg_match('/^(.+) ==[=]? (.+)$/', $value, $matches)) {
            return $this->expEval($matches[1]) === $this->expEval($matches[2]);
        }
        // exp <,>,<=,>= exp 
        if (preg_match('/^(.+) (<|>|<=|>=) (.+)$/', $value, $matches)) {
            switch ($matches[2]) {
                case '<':
                    return $this->expEval($matches[1]) < $this->expEval($matches[3]);
                case '>':
                    return $this->expEval($matches[1]) > $this->expEval($matches[3]);
                case '<=':
                    return $this->expEval($matches[1]) <= $this->expEval($matches[3]);
                case '>=':
                    return $this->expEval($matches[1]) >= $this->expEval($matches[3]);
            }
        }
        // exp +,- exp 
        if (preg_match('/^(.+) ([+-]) (.+)$/', $value, $matches)) {
            switch ($matches[2]) {
                case '+':
                    return $this->expEval($matches[1]) + $this->expEval($matches[3]);
                case '-':
                    return $this->expEval($matches[1]) - $this->expEval($matches[3]);
            }
        }

        // exp *,/,% exp 
        if (preg_match('/^(.+) ([*\/%]) (.+)$/', $value, $matches)) {
            switch ($matches[2]) {
                case '*':
                    return $this->expEval($matches[1]) * $this->expEval($matches[3]);
                case '/':
                    return $this->expEval($matches[1]) / $this->expEval($matches[3]);
                case '%':
                    return $this->expEval($matches[1]) % $this->expEval($matches[3]);
            }
        }

        // -exp !exp
        if (preg_match('/^([!-])(.+)$/', $value, $matches)) {
            switch ($matches[1]) {
                case '!':
                    return !$this->expEval($matches[2]);
                case '-':
                    return -$this->expEval($matches[2]);
            }
        }

        if (strlen($value) > 1 && substr($value, 0, 1) === "'" && substr($value, -1, 1) === "'") {
            return substr($value, 1, -1);
        }

        //  true
        if ($value === 'true') {
            return true;
        }

        //  false
        if ($value === 'false') {
            return false;
        }

        if (preg_match('/^[0-9]+$/', $value, $matches)) {
            return intval($value);
        }

        if (preg_match('/^[0-9.]+$/', $value, $matches)) {
            return floatval($value);
        }

        if (preg_match('/^[a-zA-Z0-9_.]+$/', $value, $matches)) {
            return $this->getContextValue($value);
        }



        if (preg_match('/^([a-zA-Z_]+)\(([a-z_.]+)\)$/', $value, $matches)) {
            if ($matches[1] === 'isElementAccessible') {
                $elt = $this->getContextValue($matches[2]);

                return $this->isElementAccessible($elt);
            }


        }

        echo "<br>parsing failed: <b>$value</b>";
        if ($value !== $this->currentExp)
            echo "<br>in expression: <b>$this->currentExp</b> ";
        echo '<br>in template file: <b>' . implode(' > ', $this->currentTemplate) . '</b>';
        die();
    }

    public function isElementAccessible($elt)
    {
        // $elt = $this->mempad->getElementByUrl($url);

        //TODO: TEMPORARY, WHILE WRITING AUTH
        if (!$elt)
            return false;
        if (
            strpos($elt->title, '.') === 0
            || strpos($elt->title, '!') === 0
            || strpos($elt->path, '/!') !== false
            || strpos($elt->path, '!') === 0
        )
            return false;


        return true;


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

        $this->addShortcode($shortcode, function ($attributes, $content) use ($shortcode, $selfRemoveOnEmpty, $template, $initArray) {

            // $attributes['type'] = $shortcode;
            $attributes = array_merge($attributes, $initArray);


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


            // $html   = $this->includeTemplate ($templateFile, $attributes);
            $html = $this->includeTemplate($attributes, $content, $template, $selfRemoveOnEmpty);



            $rec--;

            return $this->processShortcodes($html);
        });
    }



    /**
     * uAttr
     * create universal attributes for shortcodes 
     * which are id, class, style, title, lang.
     * class="toto titi" id="plop" title="blep" lang="fr"
     * also supported:
     *  .toto .titi #plop
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
            if (is_numeric($key) && preg_match('/^([.#])([_a-zA-Z][_a-zA-Z0-9-])*$/', $value, $matches)) {
                if ($matches[1] === '.') {
                    $classes = ($classes ? ' ' : '') . $matches[2];
                }
                if ($matches[1] === '#') {
                    $id = $matches[2];
                }
            }
            ;

        }

        if ($classes !== '')
            $str .= ' class="' . trim($classes) . '"';
        if ($id !== '')
            $str .= ' id="' . $id . '"';
        return trim($str);
    }



    public function id($attributes): string
    {

        if (isset($attributes['id'])) {
            return $attributes['id'];
        }

        foreach ($attributes as $key => $value) {
            if (is_numeric($key) && preg_match('/^([.#])([_a-zA-Z][_a-zA-Z0-9-])*$/', $value, $matches)) {

                if ($matches[1] === '#') {
                    return $matches[2];
                }
            }

        }

        return '';
    }

    public function getCssClasses($attributes): string
    {

        if (isset($attributes['class'])) {
            return $attributes['class'];
        }


        $classes = '';
        foreach ($attributes as $key => $value) {
            if (
                is_numeric($key)
                && preg_match('/^\.([_a-zA-Z][_a-zA-Z0-9-]*)$/', $value, $matches) === 1
            ) {


                $classes .= " $matches[1]";
            }

        }

        return $classes;
    }


}
