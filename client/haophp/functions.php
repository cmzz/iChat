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

function session($k,$v="") {
    if(!$k) return $_SESSION;
    if(!$v) {
        $ret = $_SESSION[$k];
        return $ret;
    } else {
        if(is_array($v)) {
            foreach($v as $vk => $vv) {
                $_SESSION[$k][$vk] = $vv;
            }
        } else {
            $_SESSION[$k] = $v;
        }
    }

    return true;
}

/**
 * 模板路径
 * @param string $template
 * @return string
 * @throws Exception
 */
function template($template="") {

    $file = $template ? $template : ACTION ;
    $tempfiel = APP_PATH.MODULE.'view/'.CONTROLLER.'/'.$file.'.html';

    if(!file_exists($tempfiel)) {
        throw new \Exception("模板不存在: ".$tempfiel);
    }

    return $tempfiel;
}

/**
 * 目前只支持控制器和方法的跳转
 * @param $url
 * @param string $vars 字符串形式
 * @param string $suffix
 * @param bool $showdomain
 */
function url($url, $vars="", $suffix=".html", $showdomain=false, $redirect=false) {
    $tmp = explode('/',$url);
    if (count($tmp) > 1) {
        $controller = array_shift($tmp);
    }
    $action = array_shift($tmp);

    $url = APP_ROOT.str_replace('//','/','index.php/'.$controller.'/'.$action);
    if($vars) {
        $vars = str_replace(array('=','&'),'/',$vars);
        $url .= $vars;
    }

    $url .= $suffix;

    if($showdomain) {
        $url = "http://".$_SERVER['HTTP_HOST'].$url;
    }

    if($redirect) {
        header("location:".$url);
    }

    return $url;
}