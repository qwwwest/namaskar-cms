<?php

namespace Qwwwest\Namaskar;


// strict_variables option is set to false;

/*
{# equivalent to the non-working foo.data-foo #}
{{ attribute(foo, 'data-foo') }}
/ {% set foo = {'foo': 'bar'} %}
{{ name|striptags|title }}

Filters that accept arguments have parentheses around the arguments. This example joins the elements of a list by commas:
{{ list|join(', ') }}

~

*/




class TemplateBuilder
{

    private $templateFolder;
    private $escSpace = '["""5P@c3"""]';

    private $varz = null;

    private function __construct()
    {


    }

    public static function build($tFolders)
    {
        $tb = new TemplateBuilder();

        foreach ($tFolders as $key => $templateFolder) {
            $tb->_build($templateFolder);

        }

    }

    private function _build($templateFolder)
    {
        $this->templateFolder = $templateFolder;

        if (!is_dir($this->templateFolder)) {
            echo debug();
            die('invalid template folder:' . $this->templateFolder);
        }

        chdir($templateFolder);
        $files = scandir($templateFolder);
        foreach ($files as $file) {
            $is_php = pathinfo($file, PATHINFO_EXTENSION) === 'php';

            if (is_dir($file) || $is_php)
                continue;
            $basename = basename($file);
            $content = $this->parse(file_get_contents($file));
            file_put_contents("$this->templateFolder/$basename.php", $content);

        }
    }

    private function parse($markup, $no_escape = null, $vars = array())
    {

        $varRegex = '[a-zA-Z][a-zA-Z0-9._]*[\\s]*[^(]';
        $simpleVarRegex = '[a-zA-Z][a-zA-Z0-9_]*[\\s]*[^(]';
        $filePathRegex = '[a-zA-Z][a-zA-Z0-9._/-]*';
        $key = '[a-zA-Z][a-zA-Z0-9_]*';
        $s = '[\\s]';
        $keyValue = "$s*($key)$s?:$s*($[a-zA-Z][a-zA-Z0-9._]*)$s*";

        $code = preg_replace(
            [
                '/({#.+?#})/s',
                '#\{%[\\s]*else[\\s]*%}#',
                '#\{%[\\s]*end[ ]*for[\\s]*%}#',
                '#\{%[\\s]*end[ ]*if[\\s]*%}#',
                '#\{   {([^ }]+)\}}#',
                '#\{%\?\?[\\s]+(\w+)[\\s]+[\\s]+(\w*)[\\s]*}}#',

            ],
            [
                "",
                "<?php else: ?>",
                "<?php endforeach; ?>
                <?php endif; ?>",
                "<?php endif; ?>",
                "<?= \$\\1 ?>",
                "<?= \$\\3 \? \"\$\\1$\\2$\\3\" : '' ?>",

            ],
            $markup
        );

        $code = preg_replace_callback(
            '#\{\{[\\s]*([^}]+)}}#',
            function ($matches) {

                $res = $this->toPhpExpresssion($matches[1]);
                return "<?= $res ?>";

            },
            $code
        );

        // for ITEM in LIST.OF.ITEMS
        $code = preg_replace_callback(
            "#\{%$s*for$s+($simpleVarRegex)[\\s]+in[\\s]+($varRegex)[\\s]*%}#",
            function ($matches) {

                $item = trim($matches[1]);
                $items = trim($matches[2]);
                $first = explode('.', $items)[0];

                $items = "\$this->getValue('$items', \$$first ?? null)";


                $foreach = " 
                <?php if( ($items)) : ?>\n
                <?php foreach($items as \$key => \$$item) : ?>
                <?php 
                \$loop = [];
                \$loop['index'] =  \$key + 1;
                \$loop['index0'] =  \$key;
                \$loop['key'] =  \$key;
                \$loop['first'] =  \$key === 0 ;
                \$loop['last'] =  \$key === count($items) -1 ;
                \$item['_'] =  \$loop ;

                ?>    
               ";

                return $foreach;
            },
            $code
        );

        // include TEMPLATE/FILE with only ...
        $code = preg_replace_callback(
            "#\{%[\\s]*include[\\s]+($filePathRegex)[\\s]+with:[\\s]*\n"
            . "(.*?)\n[\\s]*%}#s",
            function ($matches) {

                $template = trim($matches[1]);
                $items = explode("\n", trim($matches[2]));
                $s = '[\\s]';
                $vars = '';
                foreach ($items as $key => $item) {
                    $item = trim($item);
                    $keyValueRegex = "/-$s*([a-zA-Z][a-zA-Z0-9_]*)$s*:$s*(.*?)\$/";
                    preg_match($keyValueRegex, $item, $kvmatches);
                    if ($kvmatches) {
                        $expr = $this->toPhpExpresssion($kvmatches[2]);
                        $vars .= "'$kvmatches[1]' => $expr,\n";

                    } else
                        die("ivalid key value: $item in $matches[0]");

                }

                $include = "\$this->include('$template', [\n$vars])";

                return "<?php $include ?" . ">";
            },
            $code
        );

        // include regular TEMPLATE/FILE 
        $code = preg_replace_callback(
            "#\{%[\\s]*include[\\s]+($filePathRegex)[\\s]*%}#s",
            function ($matches) {

                $template = trim($matches[1]);
                $comment = "'$template'";
                $parts = explode("/", trim($matches[1]));
                if (\count($parts) === 1) {

                    $template = '$this->theme/' . $parts[0];
                    $comment = '$this->theme."/"."' . $parts[0] . '"';
                }


                return <<<MYPHP
                    
                    <!-- <?= $comment ?> -->
                    <?php foreach(N('folder.templates') as \$key => \$folder) : ?>
                        <?php if(is_file("\$folder".'/'."$template.php")): ?>
                            <?php require "\$folder".'/'."$template.php"; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <!-- end <?= $comment ?> -->

                    MYPHP;



            },
            $code
        );

        // {{ IF EXPRESSION.param < VALEUR2 && blabla... }}
        $code = preg_replace_callback(
            '#\{%[\\s]*if ([^}]*?)[\\s]*%}#',
            function ($matches) {
                $res = $this->toPhpExpresssion($matches[1]);
                return "<?php if($res): ?>";
            },
            $code
        );

        ob_start();
        echo $code;
        return ob_get_clean();
    }
    private function escapeSpace($str)
    {

        $RX_inAnyQuotes = '/("((\\\\.|[^"])*)")|(\'((\\\\.|[^\'])*)\')/';
        $str = preg_replace_callback($RX_inAnyQuotes, function ($matches) {
            $rep = str_replace(' ', $this->escSpace, $matches[0]);

            return $rep;
        }, $str);

        return $str;
    }
    private function toPhpExpresssion($str)
    {

        $str = $this->escapeSpace($str);

        $parts = explode(' ', $str);
        $res = [];
        foreach ($parts as $key => $part) {
            $part = trim($part);
            if (strpos($part, 'isElementAccessible') === 0) {
                $res[] = '$this->' . $part;
            } else
                if (strpos($part, 'isPageAccessible') === 0) {
                    $res[] = '$this->' . $part;
                } else
                    if ($part === 'true' || $part === 'false' || $part === 'null') {
                        $res[] = "$part";
                    } else if ($part === '&&' || $part === '||') {
                        $res[] = "\n$part";
                    } else {
                        preg_match('/^[a-zA-Z][a-zA-Z0-9._]*(\.#)?$/', $part, $matches);
                        if ($matches) {
                            $first = explode('.', $part)[0];

                            $res[] = "\$this->getValue('$part', \$$first ?? null)";
                        } else {
                            $res[] = "$part";
                        }
                    }

        }

        $res = implode(' ', $res);

        $res = str_replace($this->escSpace, ' ', $res);
        return $res;
    }

}