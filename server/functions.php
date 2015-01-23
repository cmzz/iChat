<?php

/**
 * @param $obj
 */
function dump($obj) {
    ob_start();
    print_r($obj);
    $output = ob_get_clean();
    if (!extension_loaded('xdebug')) {
        $output = preg_replace("/\]\=\>\n(\s+)/m", '] => ', $output);
        $output = '<pre>'. htmlspecialchars($output, ENT_QUOTES) . '</pre>';
    }

    echo $output;
}

