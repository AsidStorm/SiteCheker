<?
error_reporting(0);

$arClasses = array(
    'css', 'img', 'js', 'url', 'file',
    'page',
    'curl'
);

foreach($arClasses as $strClassName){
    $strFullPath = $_SERVER['DOCUMENT_ROOT'] . '/core/' . $strClassName . '.class.php';

    if(file_exists($strFullPath))
        require_once($strFullPath);
}