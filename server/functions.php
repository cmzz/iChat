<?php

/**
 * @param $obj
 */
function dump($obj) {
    ob_start();
    var_dump($obj);
    $output = ob_get_clean();
    if (!extension_loaded('xdebug')) {
        $output = preg_replace("/\]\=\>\n(\s+)/m", '] => ', $output);
        $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
    }

    echo $output;
}

