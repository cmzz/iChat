<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <title>系统发生错误</title>
    <style type="text/css">
        * {
            padding: 0;
            margin: 0;
        }
        html {
            overflow-y: scroll;
        }
        body {
            background: #fff;
            font-family: '微软雅黑';
            color: #333;
            font-size: 16px;
        }
        img {
            border: 0;
        }
        .error {
            padding: 24px 48px;
        }
        .face {
            font-size: 100px;
            font-weight: normal;
            line-height: 120px;
            margin-bottom: 12px;
        }
        h1 {
            font-size: 32px;
            line-height: 48px;
        }
        .error .content {
            padding-top: 10px
        }
        .error .info {
            margin-bottom: 12px;
        }
        .error .info .title {
            margin-bottom: 3px;
        }
        .error .info .title h3 {
            color: #000;
            font-weight: 700;
            font-size: 16px;
        }
        .error .info .text {
            line-height: 24px;
        }
        .copyright {
            padding: 12px 48px;
            color: #999;
        }
        .copyright a {
            color: #000;
            text-decoration: none;
            display: block;
            margin-top: 30px;
            font-size: 20px;
        }
    </style>
</head>

<body>
<div class="error">
    <p class="face">:(</p>
    <h1><?php echo $e->getMessage(); ?></h1>
    <div class="content">
        <div class="info">
            <div class="title">
                <h3>错误位置</h3>
            </div>
            <div class="text">
                <p>FILE: <?php
                        echo "{APP_PATH}/".str_replace(APP_PATH,"" , $e->getFile());
                    ?> &#12288;LINE: <?php echo $e->getLine(); ?></p>
            </div>
        </div>
        <div class="info">
            <div class="title">
                <h3>TRACE</h3>
            </div>
            <div class="text">
                <p><?php
                        foreach($e->getTrace() as $num => $error) {
                            echo "#".$num."&nbsp;&nbsp;&nbsp;";
                            echo str_replace(APP_PATH,"{APP_PATH}/" ,$error['file']);
                            echo "&nbsp;:&nbsp;";

                            if($error['class']) {
                                echo $error['class']."::";
                            }

                            echo $error['function']."(";
                            if($error['args']) {
                                echo '"'.implode('","', str_replace(APP_PATH,"{APP_PATH}/",$error['args'])).'"';
                            }
                            echo ") <br />";
                        }
                    ?>#<?php echo $num+1;?>&nbsp;:&nbsp;{main}</p>
            </div>
        </div>
    </div>
</div>
<div class="copyright">
    <p>{APP_PATH}为APP_PATH常量值，为了安全考虑未作显示</p>
    <h3><a title="" href="">HaoPHP</a></h3>
</div>
</body>

</html>
