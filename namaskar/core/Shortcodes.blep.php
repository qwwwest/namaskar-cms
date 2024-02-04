<?php

namespace Qwwwest\Namaskar;

/**
 * This is my modified version of Badcow's modified version 
 * of WordPress' shortcode feature for use outside of WordPress. 
 * we use {shortcode} instead of [shortcode]
 * 
 * Class Shortcodes
 * from https://github.com/Badcow/Shortcodes/blob/master/lib/Shortcodes.php
 *
 * @package Template  
 */

 
class Shortcodes
{
    private $attrPattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
    private $shortcodes = array();
    public function addShortcode($tag, $function)
    {
        if (!is_callable($function)) {
            throw new \ErrorException("Function must be callable");
        }

        $this->shortcodes[$tag] = $function;
    }
    public function process($content)
    {
        if (empty($this->shortcodes)) {
            return $content;
        }

     
        return preg_replace_callback($this->shortcodeRegex(), array($this, 'processTag'), $content);
    }
    private function processTag(array $tag)
    {
        if ($tag[1] == '{' && $tag[6] == '}') {
            return substr($tag[0], 1, -1);
        }

        $tagName = $tag[2];
        $attr = $this->parseAttributes($tag[3]);

        if (isset($tag[5])) {
            return $tag[1] . call_user_func($this->shortcodes[$tagName], $attr, $tag[5], $tagName) . $tag[6];
        } else {
            return $tag[1] . call_user_func($this->shortcodes[$tagName], $attr, null, $tagName) . $tag[6];
        }
    }
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
    private function shortcodeRegex()
    {
        $tagRegex = join('|', array_map('preg_quote', array_keys($this->shortcodes)));

        return
            '/\\{(\\{?)('.$tagRegex.')(?![\\w-])([^}\\/]*(?:\\/(?!\\])[^}\\/]*)*?)(?:(\\/)\\}|\\}(?:([^{]*+(?:\\{(?!\\/\\2\\})[^\\{]*+)*+)\\{\\/\\2\\})?)(\\}?)/s';
    }
}
