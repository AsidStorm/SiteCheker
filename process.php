<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/core/include.php');

$arPriority = array(
    'priority', 'url', 'css', 'js', 'file', 'img'
);

foreach($arPriority as $strPath){
    $strFullPath = $_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/to-check/' . $strPath;
    $arFiles     = scandir($strFullPath);
    $boolBreak   = false;

    foreach($arFiles as $strFileName){
        if($strFileName === '.' OR $strFileName === '..') continue;

        $boolBreak = true;
    }

    if($boolBreak)
        break;
}

if($boolBreak){
    $strJSON      = file_get_contents($strFullPath . '/' . $strFileName);
    $arJSON       = json_decode($strJSON, true);

    if(!$arJSON['CLASS'] OR !$arJSON['URL']){
        print json_encode(array(
            'STATUS' => 'success',
                'MORE'   => 'Y'
        ));

        unlink($strFullPath . '/' . $strFileName);
    }

    $strClassName = '\Core\\' . $arJSON['CLASS'];

    $clProcess = new $strClassName($arJSON, json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/manifest.json'), true));

    $arList = $clProcess->GetList();

    foreach($arPriority as $strRealFolder){
        if(isset($arList[strtoupper($strRealFolder)]) && count($arList[strtoupper($strRealFolder)]) > 0) {
            foreach($arList[strtoupper($strRealFolder)] as $arUrl) {
                $strUrl = $arUrl['URL'];

                $strSubFileName = md5($strUrl) . '.json';
                $boolFileFound = false;

                foreach ($arPriority as $strFolder) {
                    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/to-check/' . $strFolder . '/' . $strSubFileName)) {
                        $strSubJSON = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/to-check/' . $strFolder . '/' . $strSubFileName);

                        $arSubJSON = json_decode($strSubJSON, true);

                        // Шаг 1. Устанавливаем дополнительный FROM

                        if (array_key_exists('FROM', $arSubJSON)) {
                            $arSubJSON['FROM'][] = $clProcess->GetUrl();

                            $arSubJSON['FROM'] = array_unique($arSubJSON['FROM']);
                        }
                        else
                            $arSubJSON['FROM'] = array($clProcess->GetUrl());

                        // Шаг 2. Обновляем файл

                        unlink($_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/to-check/' . $strFolder . '/' . $strSubFileName);

                        $objFile = @fopen($_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/to-check/' . $strFolder . '/' . $strSubFileName, 'a+');
                        fwrite($objFile, json_encode($arSubJSON));
                        fclose($objFile);

                        $boolFileFound = true;

                        break;
                    }
                }

                if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/checked/' . $strSubFileName)) {
                    $strSubJSON = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/checked/' . $strSubFileName);
                    $arSubJSON = json_decode($strSubJSON, true);

                    // Шаг 1. Устанавливаем дополнительный FROM

                    if (array_key_exists('FROM', $arSubJSON)) {
                        $arSubJSON['FROM'][] = $clProcess->GetUrl();

                        $arSubJSON['FROM'] = array_unique($arSubJSON['FROM']);
                    }
                    else
                        $arSubJSON['FROM'] = array($clProcess->GetUrl());

                    // Шаг 2. Обновляем файл

                    unlink($_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/checked/' . $strSubFileName);

                    $objFile = @fopen($_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/checked/' . $strSubFileName, 'a+');
                    fwrite($objFile, json_encode($arSubJSON));
                    fclose($objFile);

                    $boolFileFound = true;
                }

                if (!$boolFileFound) {
                    // Если файл не найден, то его нужно создать.
                    $objFile = @fopen($_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/to-check/' . strtolower($strRealFolder) . '/' . $strSubFileName, 'a+');

                    $arTrace = (is_array($arJSON['TRACE'])) ? $arJSON['TRACE'] : array();
                    $arTrace[] = $clProcess->GetUrl();

                    fwrite($objFile, json_encode(array(
                        'CLASS' => strtoupper($arUrl['TYPE']),
                        'FROM'  => array(
                            $clProcess->GetUrl()
                        ),
                        'URL'   => $strUrl,
                        'TRACE' => $arTrace,
                        'TRIM'  => $arUrl['TRIM'],
                        'OUR'   => $arUrl['OUR']
                    )));

                    fclose($objFile);
                }
            }
        }
    }

    foreach($arList['HASH'] as $strUrl){
        $strSubFileName = md5($strUrl) . '.json';
        $boolFileFound  = false;

        if(file_exists($_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/hash/' . $strSubFileName)){
            $strSubJSON = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/hash/' . $strSubFileName);
            $arSubJSON = json_decode($strSubJSON, true);

            // Шаг 1. Устанавливаем дополнительный FROM

            if (array_key_exists('FROM', $arSubJSON)) {
                $arSubJSON['FROM'][] = $clProcess->GetUrl();

                $arSubJSON['FROM'] = array_unique($arSubJSON['FROM']);
            }
            else
                $arSubJSON['FROM'] = array($clProcess->GetUrl());

            // Шаг 2. Обновляем файл

            unlink($_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/hash/' . $strSubFileName);

            $objFile = @fopen($_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/hash/' . $strSubFileName, 'a+');
            fwrite($objFile, json_encode($arSubJSON));
            fclose($objFile);
        }
        else{
            $objFile = @fopen($_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/hash/' . $strSubFileName, 'a+');

            $arTrace = (is_array($arJSON['TRACE'])) ? $arJSON['TRACE'] : array();
            $arTrace[] = $clProcess->GetUrl();

            fwrite($objFile, json_encode(array(
                'CLASS' => strtoupper($arUrl['TYPE']),
                'FROM'  => array(
                    $clProcess->GetUrl()
                ),
                'URL'   => $strUrl,
                'TRACE' => $arTrace,
            )));

            fclose($objFile);
        }
    }

    $arStatus = $clProcess->GetStatus();

    unlink($strFullPath . '/' . $strFileName);

    $objFile = @fopen($_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/checked/' . $strFileName, 'a+');

    fwrite($objFile, json_encode(array_merge_recursive($arJSON, $arStatus)));
    fclose($objFile);

    $arResult = array(
        'STATUS' => 'success',
        'MORE'   => 'Y',
        'SCANNED' => $arJSON['URL'],
        'TRACE' => implode("&rArr;", $arJSON['TRACE'])
    );
}
else
    $arResult = array(
        'STATUS' => 'success',
        'MORE'   => 'N'
    );

$arChartNames = array(
	'priority' => 'Приоритет',
	'img'      => 'Картинки',
	'js'       => 'JavaScript',
	'css'      => 'CSS',
	'url'      => 'Ссылки',
	'file'     => 'Файлы'
);
	
foreach($arPriority as $strPath){
    $strFullPath = $_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/to-check/' . $strPath;
	$arFiles     = scandir($strFullPath);
	
		$arResult['CHART'][] = array(
			$arChartNames[$strPath], (count($arFiles)-2)
		);
}

$strFullPath = $_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/checked';
$arFiles     = scandir($strFullPath);

	$arResult['CHART'][] = array(
		'Проверено', (count($arFiles) - 2)
	);

print json_encode($arResult);