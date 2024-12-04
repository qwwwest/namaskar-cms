<?php
namespace Qwwwest\Namaskar;

class Router
{
    private $allRoutes = null;
    private $allMatchingRoutes = null;
    private $controllers = [];
    private $objControllers = [];
    private $url = null;
    private $allowedCharsInURLParam = "a-zA-Z0-9-_\.";

    public function __construct($url, $folder)
    {
        $this->allRoutes = $this->buildRoutes($url, $folder);
    }

    public function getAllRoutes()
    {
        return $this->allRoutes;
    }

    public function matches()
    {
        return $this->allMatchingRoutes;
    }

    public function getControllers()
    {
        return $this->controllers;
    }
    private function buildRoutes($url, $folder)
    {
        $this->allRoutes = [];
        $this->allMatchingRoutes = [];
        $controllers = [];
        $routes = [];
        if (!is_dir($folder))
            die('invalid Controller folder=' . $folder);



        foreach (glob("$folder/*Controller.php") as $filename) {

            $controllers[] = $filename;

            $this->controllers[] = basename($filename);
        }

        $currentUser = Kernel::service('CurrentUser');



        foreach ($controllers as $controllerFile) {


            $code = file_get_contents($controllerFile);
            preg_match("/#\[IsGranted\('(.*?)'\)\]\s*?\n\s*?class /s", $code, $isGranted);
            $role = $isGranted ? $isGranted[1] : '';

            // for controllers with the IsGranted attribute above the class
            //this class will be added only if user has the right priviledge
            if ($isGranted && !$currentUser->isGranted($role))
                continue;

            preg_match("/#\[Route\('(.*?)'\)\]\s*?\n\s*?class /s", $code, $globalRoute);
            $globalRoute = $globalRoute ? $globalRoute[1] : '';

            preg_match_all("/#\[Route\(([^\n]*?)\)\]\s*?\n\s*?public function (\w+) ?\(([^)]*)\)[^\n]/s", $code, $matches);
            //var_dump($matches);

            [, $routes, $functions, $params] = $matches;
            foreach ($routes as $key => $route) {

                $json = 'route: ' . $route;
                $newJSON = '';

                $jsonLength = strlen($json);
                for ($i = 0; $i < $jsonLength; $i++) {
                    if ($json[$i] == '"' || $json[$i] == "'") {
                        $nextQuote = strpos($json, $json[$i], $i + 1);
                        $quoteContent = substr($json, $i + 1, $nextQuote - $i - 1);
                        $newJSON .= '"' . str_replace('"', "'", $quoteContent) . '"';
                        $i = $nextQuote;
                    } else {
                        $newJSON .= $json[$i];
                    }
                }

                $str = str_replace('route:', '"route":', $newJSON);
                $str = str_replace('methods:', '"methods":', $str);
                $str = str_replace('name:', '"name":', $str);
                $arr = json_decode('{' . $str . '}', true);

                if (!isset($arr['methods']))
                    $arr['methods'] = ['GET', 'POST'];

                $arr['route'] = $globalRoute . $arr['route'];
                $arr['route'] = rtrim($arr['route'], '/');

                $name = basename($controllerFile);
                $name = str_replace('Controller.php', '', $name);
                if (!isset($arr['name']))
                    $arr['name'] = strtolower($name) . '.' . $functions[$key];
                $arr['regex'] = preg_replace('#{[a-zA-Z0-9]+}#', '([' . $this->allowedCharsInURLParam . ']+)', $arr['route']);
                $arr['regex'] = preg_replace('#/{[a-zA-Z0-9]+\?}#', '/?(?:([' . $this->allowedCharsInURLParam . ']+))?', $arr['regex']);
                $arr['regex'] = preg_replace('#{[a-zA-Z0-9]+\+}#', '([' . $this->allowedCharsInURLParam . '//]+)?', $arr['regex']);
                $arr['regex'] = preg_replace('#/{[a-zA-Z0-9]+\*}#', '/?(?:([' . $this->allowedCharsInURLParam . '//]+))?', $arr['regex']);
                $arr['regex'] = '#^' . $arr['regex'] . '[/]?$#';

                $arr['file-location'] = $controllerFile;
                $arr['classname'] = basename($controllerFile, '.php');
                $arr['function-name'] = $functions[$key];
                $arr['function-param-names'] = [];
                ;
                preg_match($arr['regex'], $url . "", $matches);
                $validRequestmethod = in_array($_SERVER['REQUEST_METHOD'], $arr['methods']);
                if ($matches && $validRequestmethod) {

                    preg_match_all('#{([a-zA-Z0-9_]+)[?*+]?}#', $arr['route'], $keyparams);
                    $keyparams = $keyparams[1];
                    $values = array_slice($matches, 1);
                    $paramValues = [];
                    foreach ($keyparams as $keyp => $keyparam) {
                        $paramValues[$keyparam] = $values[$keyp] ?? null;
                    }
                    $arr['url-params'] = $paramValues;
                    $arr['function-params'] = [];
                    if (trim($params[$key]) !== '') {
                        $functionParams = explode(',', $params[$key]);

                        foreach ($functionParams as $functionParam) {
                            preg_match("#(?:([a-zA-Z_]+) )?[$]([a-zA-Z0-9_]+)#", $functionParam, $paramMatch);

                            $type = $paramMatch[1];
                            if ($type === 'string')
                                $type = '';
                            $arr['function-param-names'][] = $paramMatch[2];
                            $functionParamValue = $arr['url-params'][$paramMatch[2]];
                            if ($functionParamValue !== null) {

                                if ($type)
                                    \settype($functionParamValue, $type);
                                $arr['function-params'][] = $functionParamValue;

                            }

                        }

                    }
                    $this->allMatchingRoutes[] = $arr;
                }
                $this->allRoutes[] = $arr;
                debug('in routes', $arr['route']
                    . " ($str) in $arr[classname]::"
                    . $arr['function-name']);
            }
        }

        foreach ($this->allMatchingRoutes as $arr) {
            debug('match', $arr['classname'] . '::' . $arr['function-name'] . " for route: $arr[route]");
        }


        return $this->allRoutes;
    }

    public function findRoute(): Response
    {
        $controllers = [];

        foreach ($this->matches() as $key => $route) {
            ///  echo $route['classname'] . '::' . $route['function-name'];
            $location = $route['file-location'];
            if (!isset($controllers[$location])) {

                require_once $location;
                $fullClassName = 'App\\Controller\\' . $route['classname'];
                // $controllers[$location] = new $fullClassName($this->zenconf);
                $controllers[$location] = new $fullClassName();
            }
            //echo "$location :: ". $route['function-name'];
            // we dynamically call the Conthroller method with the right parameters.
            $response = $controllers[$location]->{$route['function-name']}(...$route['function-params']);
            if ($response) {
                debug('route:', $route['classname'] . '::' . $route['function-name']);

                return $response;
            }
        }
        debug('Route not found:', '');
        return new Response('not found', Response::HTTP_NOT_FOUND); // no match at all. sigh.
        // return new Response('not found');
    }



    private static function isRouteMatch($route, $url)
    {

        $arr = [];
        $allowedCharsInURLParam = "a-zA-Z0-9-_\.";
        $arr['regex'] = preg_replace('#{[a-zA-Z0-9]+}#', '([' . $allowedCharsInURLParam . ']+)', $route);
        $arr['regex'] = preg_replace('#/{[a-zA-Z0-9]+\?}#', '/?(?:([' . $allowedCharsInURLParam . ']+))?', $arr['regex']);
        $arr['regex'] = preg_replace('#{[a-zA-Z0-9]+\+}#', '([' . $allowedCharsInURLParam . '//]+)?', $arr['regex']);
        $arr['regex'] = preg_replace('#/{[a-zA-Z0-9]+\*}#', '/?(?:([' . $allowedCharsInURLParam . '//]+))?', $arr['regex']);
        $arr['regex'] = '#^' . $arr['regex'] . '[/]?$#';

        preg_match($arr['regex'], $url . "", $matches);

        return $matches != null;




    }


}