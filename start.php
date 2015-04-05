<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/core/include.php');

$arResult = array(
    'STATUS' => 'failed'
);

if(!empty($_REQUEST['URL'])){
    $_REQUEST['URL'] = \Core\Page::Root($_REQUEST['URL']);

    $strDomain = $_REQUEST['URL'];

    $clCURL = new \Core\CURL($strDomain, true);

    $arTrace = $clCURL->GetTrace();

    $arManifest = array(
        'ROOT'     => '',
        'DOMAIN'   => '',
        'WARNINGS' => array(
            'WWW' => 'Y'
        )
    );

    if($clCURL->GetCode() !== 200){
        print json_encode(array(
            'STATUS' => 'failed',
            'DESC'   => 'Ответ сервера: ' . $clCURL->GetCode() . '. Не позволяет провести проверку.'
        ));

        die();
    }

    if(count($arTrace) > 0){
        $arDomain  = array_pop($arTrace);
        $strTmpDomain = \Core\Page::Root($arDomain['URL']);

        $arManifest['ROOT_PAGE'] = $arDomain['URL'];

        if($strTmpDomain === $strDomain){ // У нас совпало всё, значит нужно попробывать запросить версию с или без WWW.

        }
        else{ // Нужно учесть что мы ещё можем попасть на страницу /index.htm например
            $strTmpUrlHost = parse_url($strTmpDomain, PHP_URL_HOST);
            $strUrlHost    = parse_url($strDomain, PHP_URL_HOST);

            if( ( strpos($strTmpUrlHost, 'www.') === 0 && strpos($strUrlHost, 'www.') !== 0 ) OR ( strpos($strTmpUrlHost, 'www.') !== 0 && strpos($strUrlHost, 'www.') === 0 ) ){
                $arManifest['WARNINGS']['WWW'] = 'N';
                $strDomain = $strTmpDomain;
                $strHost   = $strTmpUrlHost;
            }
            else{
                // У нас есть редиректы. Но, ни один из них не привёл к желаемому результату. Пока не будем обрабатывать эту ситуацию.
            }
        }
    }
    else{
        $arManifest['ROOT_PAGE'] = $strDomain;

        $strHost = parse_url($strDomain, PHP_URL_HOST);

        $arUrlParse = parse_url($strDomain);

        if(strpos($strHost, 'www.') === 0){ // Если мы на версии с WWW, то нужно запросить версию без неё
            $strTmpDomain = \Core\Page::Root($arUrlParse['scheme'] . '://' . str_replace('www.', '', $strHost) . $arUrlParse['path']);
        }
        else{ // В противном случае, нужно запросить версию с ней
            $strTmpDomain = \Core\Page::Root($arUrlParse['scheme'] . '://www.' . $strHost . $arUrlParse['path']);
        }

        $clCURL = new \Core\CURL($strTmpDomain, true);

        $arTrace = $clCURL->GetTrace();

        if(count($arTrace) > 0){
            $arLastUrl  = array_pop($arTrace);
            $strLastUrl = $arLastUrl['URL'];

            if($strLastUrl === $strDomain){ // То всё ок.
                $arManifest['WARNINGS']['WWW'] = 'N';
            }
            else{

            }
        } // В противном случае - если нас не редиректнуло, значит 100% ошибка.
    }

    $arManifest['DOMAIN'] = $strHost;
    $arManifest['ROOT']   = $strDomain;

    $_REQUEST['URL'] = $arManifest['URL'];

    $strDirPath  = md5($_REQUEST['URL']);
    $strFullPath = $_SERVER['DOCUMENT_ROOT'] . '/process/' . $strDirPath;

    if($_REQUEST['CLEAR_START'] === 'Y'){
        if(is_dir($strFullPath)) {

            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($strFullPath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $clPath) {
                $clPath->isDir() && !$clPath->isLink() ? rmdir($clPath->getPathname()) : unlink($clPath->getPathname());
            }

            rmdir($strFullPath);
        }
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
        mkdir($strFullPath . '/hash');

        $objFile = @fopen($strFullPath . '/manifest.json', 'a+');

        fwrite($objFile, json_encode($arManifest));

        fclose($objFile);

        $objFile = @fopen($strFullPath . '/to-check/priority/' . md5($_REQUEST['URL']) . '.json', 'a+');

        fwrite($objFile, json_encode(array(
            'CLASS' => 'URL',
            'URL'   => $arManifest['ROOT_PAGE'],
            'OUR'   => 'Y'
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