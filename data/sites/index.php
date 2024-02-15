<?php

 
$directories = glob(__DIR__ . '/*', GLOB_ONLYDIR);
 
foreach ($directories as $dir) {

    $basename = basename($dir);


    echo "<a href=\"./$basename\">$basename</a><br>\n";

}

?>