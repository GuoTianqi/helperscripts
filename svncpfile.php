#! /usr/bin/env php
<?php
$paths = explode('/', $argv[0]);
$SHELL_NAME = $paths[count($paths) - 1];

$USAGE=<<<_END
必须在svn跟目录下运行
目前只支持trunk和branches之间的拷贝
Usage:
$SHELL_NAME toDir versionNum1 versionNum2 ...
Example:
1. 拷贝版本1999和2011修改的文件到trunk下
   $SHELL_NAME trunk 1999 2011
2. 拷贝到branches/devserver下:
   $SHELL_NAME branches/devserver 1999 2011

_END;

if ($argc < 3 || !file_exists('.svn')) {
    echo $USAGE;
    return -1;
}

$toDir = $argv[1];

// 判断 version num 是否合法
for ($index = 2; $index < $argc; $index++) {
    $versionNum = $argv[$index];
    if (!is_numeric($versionNum) || $versionNum < 0) {
        echo $USAGE;
        return -1;
    }
}



// 去掉开头的‘/’
if (strpos($toDir, '/') === 0) {
    $toDir = substr_replace($toDir,'', 0, 1);
    echo "$toDir\n";
}

for ($index = 2; $index < $argc; $index++) {
    $versionNum = $argv[$index];

    echo "---- version: $versionNum ----\n";
    
    $svnlog = shell_exec("svn log --xml -v -r$versionNum");
    // 获取修改文件的路径
    preg_match_all("|<path[^>]*>(.*)</path>|", $svnlog, $matches);

    foreach ($matches[1] as $filePath) {
        // 去掉开头的‘/’
        $filePath = substr_replace($filePath,'', 0, 1);
        $arrFilePath = explode('/', $filePath);
        if (strpos($filePath, "trunk") === 0) {
            // 去掉trunk
            unset($arrFilePath[0]);
            $toFilePath = $toDir . '/' . implode('/', $arrFilePath);
        } else if (strpos($filePath, "branches") === 0) {
            // 去掉 branches 和 分支目录
            unset($arrFilePath[0]);
            unset($arrFilePath[1]);
            $toFilePath = $toDir . '/' . implode('/', $arrFilePath);
        } else {
            echo "错误分支!!!\n";
            return -2;
        }

        echo "copy from: ". $filePath . "\n";
        echo "       to: " . $toFilePath . "\n";

        $succ = copy($filePath, $toFilePath);
        if (!$succ) {
            echo "拷贝文件出错!!!\n";
        }
    }
}
