#! /usr/bin/env php
<?php
$paths = explode('/', $argv[0]);
$SHELL_NAME = $paths[count($paths) - 1];

$USAGE=<<<_END
类似git中的cherrypick，不过文件的修改是完整的copy过去，
也就是说并没有针对源文件内容的修改去修改目标文件，只是将修改的源文件直接copy覆盖过去
必须在svn跟目录下运行
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
    // 获取修改文件的路径的<path></path>内容
    preg_match_all("|<path[^>]*>.*</path>|", $svnlog, $pathTags);

    foreach ($pathTags[0] as $pathTag) {
        echo "pathTag = $pathTag\n";

        // 获取action
        preg_match("|action=\"(.*)\"|", $pathTag, $matches);
        $pathAction = $matches[1];
        echo "action = $pathAction\n";

        // 获取文件类型
        preg_match("|kind=\"(.*)\"|", $pathTag, $matches);
        $pathKind = $matches[1];
        echo "action = $pathKind\n";

        // 获取路径
        preg_match("|>(.*)<|", $pathTag, $matches);
        $path = $matches[1];
        echo "path = $path\n";

        // 去掉开头的‘/’
        $path = substr_replace($path,'', 0, 1);
        $arrFilePath = explode('/', $path);
        if (strpos($path, "trunk") === 0) {
            // 去掉trunk
            unset($arrPath[0]);
            $toFilePath = $toDir . '/' . implode('/', $arrPath);
        } else if (strpos($path, "branches") === 0) {
            // 去掉 branches 和 分支目录
            unset($arrPath[0]);
            unset($arrPath[1]);
            $toFilePath = $toDir . '/' . implode('/', $arrPath);
        } else {
            echo "错误分支!!!\n";
            return -2;
        }

        if ($pathKind == "file") {
            switch ($pathAction) {
            case "M":
            case "A":
                echo "copy file: ". $path . "\n";
                echo "       to: " . $toFilePath . "\n";
                $succ = copy($path, $toFilePath);
                if (!$succ) {
                    echo "拷贝文件出错!!!\n";
                }
                break;
            case "D":
                echo "delete file: ". $path . "\n";
                echo "         to: " . $toFilePath . "\n";
                $succ = unlink($path);
                if (!$succ) {
                    echo "删除文件出错!!!\n";
                }
                break;
            default:
                echo "无法识别的命令!!!\n";
                break;
            }
        } else if ($pathKind == "dir") {
            switch ($pathAction) {
            case "M":
                echo "modify dir: " . $path . "\n";
                echo "暂不支持文件夹修改!!!\n";
                return -1;
            case "A":
                $stats = stat($path);
                $mode = decoct($stats['mode']);
                $mode = substr($mode, -3, 3);
                echo "add dir mode(0$mode): " . $path . "\n";
                echo "                  to: " . $toFilePath . "\n";
                $succ = mkdir($toFilePath, intval($mode));
                if (!$succ) {
                    echo "创建文件夹出错!!!\n";
                }
                break;
            case "D":
                echo "delete dir: " . $path . "\n";
                echo "        to: " . $toFilePath . "\n";
                $succ = rmdir($path);
                if (!$succ) {
                    echo "删除文件夹出错!!!\n";
                }
                break;
            default:
                echo "无法识别的命令!!!\n";
                break;
            }
        } else {
            echo "文件类型 $pathKink 无法处理!!!\n";
        }
    }
}
