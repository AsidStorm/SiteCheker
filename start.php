<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/core/include.php');

$arResult = array(
    'STATUS' => 'failed'
);

if(!empty($_REQUEST['URL'])){
    $_REQUEST['URL'] = \Core\Page::Root($_REQUEST['URL']);

    $strDirPath  = md5($_REQUEST['URL']);
    $strFullPath = $_SERVER['DOCUMENT_ROOT'] . '/process/' . $strDirPath;

    if($_REQUEST['CLEAR_START'] === 'Y'){
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($strFullPath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $clPath) {
            $clPath->isDir() && !$clPath->isLink() ? rmdir($clPath->getPathname()) : unlink($clPath->getPathname());
        }

        rmdir($strFullPath);
    }

    if(!is_dir($strFullPath)) {
        mkdir($strFullPath);

        mkdir($strFullPath . '/to-check');

        mkdir($strFullPath . '/to-check/priority');
        mkdir($strFullPath . '/to-check/css');
        mkdir($strFullPath . '/to-check/js');
        mkdir($strFullPath . '/to-check/url');
        mkdir($strFullPath . '/to-check/img');
        mkdir($strFullPath . '/to-check/file');

        mkdir($strFullPath . '/checked');

        $objFile = @fopen($strFullPath . '/manifest.json', 'a+');

        fwrite($objFile, json_encode(array(
            'ROOT'   => $_REQUEST['URL'],
            'DOMAIN' => parse_url($_REQUEST['URL'], PHP_URL_HOST)
        )));

        fclose($objFile);

        $objFile = @fopen($strFullPath . '/to-check/priority/' . md5($_REQUEST['URL']) . '.json', 'a+');

        fwrite($objFile, json_encode(array(
            'CLASS' => 'URL',
            'URL'   => $_REQUEST['URL']
        )));

        fclose($objFile);
    }

    $arResult = array(
        'STATUS' => 'success',
        'DIR'    => $strDirPath
    );
}
else
    $arResult['DESC'] = 'Empty URL.';

print json_encode($arResult);