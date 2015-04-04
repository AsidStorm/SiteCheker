<pre>
<?
error_reporting(0);
include($_SERVER['DOCUMENT_ROOT'] . '/core/include.php');

//error_reporting(E_ALL);

preg_match_all('/url\((.*?)\)/', file_get_contents('http://mst-dc1.ford-dws.ru/models/ranger/comps/xl/'), $arMatches);

foreach($arMatches[1] as $strMatch)
    $arList[] = array(
        'URL' => trim(trim($strMatch, '\''), '"')
    );

unset($arMatches);

preg_match_all('/title\>(.*?)<\/title\>/', file_get_contents('http://mst-dc1.ford-dws.ru/models/ranger/comps/xl/'), $arMatches);

if(isset($arMatches[1]) && isset($arMatches[1][0])){
    print 'Title: ' . $arMatches[1][0];
}//    $this->arStatus['TITLE'] = $arMatches[1][0];

unset($arMatches);

$clDom = new \DOMDocument;

$clDom->loadHTML(file_get_contents('http://mst-dc1.ford-dws.ru/models/ranger/comps/xl/'));

$arMeta = $clDom->getElementsByTagName('meta');

foreach($arMeta as $clMeta){
    $strType = strtolower($clMeta->getAttribute('name'));

    if(strtoupper($strType) === 'KEYWORDS' OR strtoupper($strType) === 'DESCRIPTION'){
        print "<br />";
        print $strType . ': ' . $clMeta->getAttribute('content');
    }

}

print "<br />";

$arURLs = $clDom->getElementsByTagName('a');

foreach($arURLs as $clUrl)
    $arList[] = array(
        'URL' => $clUrl->getAttribute('href')
    );

$arIMGs = $clDom->getElementsByTagName('img');

foreach($arIMGs as $clImg)
    $arList[] = array(
        'URL' => $clImg->getAttribute('src')
    );

$arCSSs = $clDom->getElementsByTagName('link');

foreach($arCSSs as $clCss)
    $arList[] = array(
        'URL' => $clCss->getAttribute('href')
    );

$arJSs = $clDom->getElementsByTagName('script');
foreach($arJSs as $clJs)
    $arList[] = array(
        'URL' => $clJs->getAttribute('src')
    );

$arList = \Core\Url::ParseList($arList, '/models/ranger/comps/xl/', 'http://mst-dc1.ford-dws.ru/');

print_r($arList);

/*$strPage = '/dir/test/';

$arLinks = array(
    'testdir/',
    'testfile.png',
    'testfile.pdf',
    '/about/test/',
    '/style.css',
    '../style.css',
    'http://yandex.ru/',
    '//code.jquery.com/jquery.js',
    './test.gif',
    '/cache/script.js?v=2.3'
);

$arResult = array(
    'CSS' => array(),
    'JS'  => array(),
    'PRIORITY' => array(),
    'URL' => array(),
    'IMG' => array(),
    'FILE' => array()
);

function GetSType($strUrl){
    $strRealExt = pathinfo(parse_url($strUrl, PHP_URL_PATH), PATHINFO_EXTENSION);

    $arTypes = array(
        'CSS'  => array(
            'CSS'
        ),
        'JS'   => array(
            'JS'
        ),
        'FILE' => array(
            'PDF', 'XLS', 'XLSX', 'SWF', 'ZIP', 'RAR',
            'EOT', 'WOFF', 'TTF', 'SVG' // Fonts
        ),
        'IMG'  => array(
            'PNG', 'JPG', 'JPEG', 'ICO', 'GIF', 'BMP', 'SVG'
        )
    );


    foreach($arTypes as $strType => $arTypes){
        foreach($arTypes as $strExt){
            if( strtoupper($strRealExt) === strtoupper($strExt) )
                return $strType;
        }
    }

    return 'URL';
}

$arSkipped = array( // Список игнорируемых файлов
    'image/png;base64',
    'image/gif;base64'
);

$strDomain = 'http://yandex.ru';

foreach($arLinks as $strUrl){
    $arItem = array(
        'URL'  => '',
        'TRIM' => 'N',
        'TYPE' => 'URL',
        'OUR'  => 'Y'
    );

    // Шаг 1. Обрезаем ? часть.
    $strUrl = array_shift(explode('?', $strUrl));

    // Шаг 2. Удаляем лишние пробелы. Если ссылка изменилась, помечаем это в журнал.
    $strTmpUrl = trim($strUrl);

    if($strTmpUrl !== $strUrl){
        $arItem['TRIM'] = 'Y';
        $strUrl         = $strTmpUrl;
    }

    if($strUrl === '') // Пропускаем пустую ссылку
        continue;

    // Шаг 3. не проверяем определённого типа ссылки (Например - image/gif)
    $boolSkip = false;
    foreach($arSkipped as $strSkipped){
        if(strpos($strUrl, $strSkipped) === 0){
            $boolSkip = true;
            break;
        }
    }

    if(!$boolSkip) {
        $mxdUrlHost = parse_url($strUrl, PHP_URL_HOST);
        $isOurURL   = true;

        // Шаг 4. Проверяем привязку к URL'y

        if ($mxdUrlHost !== NULL) {
            if ($mxdUrlHost !== $strDomain)
                $isOurURL = false; // 1. Проверка не внешняя ли это ссылка. Если внешняя - ничего не меняем.
        }

        $arItem['URL'] = $strUrl;

        // Шаг 5. Определяем тип

        $arItem['TYPE'] = GetSType($strUrl);

        if(strpos($strUrl, 'mailto:') !== 0 && strpos($strUrl, '#') !== 0 && strpos($strUrl, 'tel:') !== 0 && strpos($strUrl, 'callto:') !== 0) {

            if ($isOurURL) {
                $strOurPath = parse_url($strUrl, PHP_URL_PATH);

                if(strpos($strOurPath, '/') !== 0){ // Если у нас не ссылка вида /NAME
                    if(strrpos($strPage, '/') === (strlen($strPage)-1)) { // Если у нас последний символ в строке - /
                    }
                    else{ // В противном случае. Нам нужно определить - является ли страница вида /page.EXT или у нас просто страница.
                        if(pathinfo($strPage, PATHINFO_EXTENSION) === ''){ // Мы находимся на странице
                            $strPage .= '/'; // Необходимо добавить обратный слеш для корректной обработки страницы.
                        }
                        else{ // В случае, если у нас страница - то нужно её распарсить по-другому
                            $arTmpStrPage = explode('/', $strPage);
                            array_pop($arTmpStrPage);
                            $strPage = '/' . implode('/', $arTmpStrPage) . '/';
                        }
                    }

                    $arItem['URL'] = \Core\Page::Merge($strPage . $strUrl);
                }
            }
            else
                $arItem['OUR'] = 'N';
        }

        if(strrpos($strUrl, 'cache') !== false)
            $arResult['PRIORITY'][] = $arItem;
        else
            $arResult[$arItem['TYPE']][] = $arItem;

        //print $strUrl . "<br />";
    }
}

print_r($arResult);*/

// Get TITLE

/*$strHTML = file_get_contents('http://cheker.com/');

preg_match_all('/title\>(.*?)<\/title\>/', $strHTML, $arMatches);

print_r($arMatches);*/

/*$clDom = new DOMDocument;

$clDom->loadHTML(file_get_contents('http://cheker.com/'));

$arTitles = $clDom->getElementsByTagName('title');

foreach($arTitles as $clTitle){
    print htmlentities($clTitle->nodeValue);
}*/

// Get FROM HTML

/*$strHTML = file_get_contents('http://www.skoda-avto.ru/models/new-octavia-rs/overview-page');

preg_match_all('/url\((.*?)\)/', $strHTML, $arMatches);

print_r($arMatches); // $arMatches[1] - Contains URLs

print "End";*/

// Get from CSS

/*$strHTML = file_get_contents('http://master.vw-dealer.ru/bitrix/templates/vw4d/css/style.css');

preg_match_all('/url\((.*?)\)/', $strHTML, $arMatches);

print_r($arMatches); // $arMatches[1] - Contains URLs

foreach($arMatches[1] as $strMatch){
    print trim(trim($strMatch, '\''), '"') . "<br />";
}*/

/*$clDom = new DOMDocument;

$clDom->loadHTML(file_get_contents('http://master.vw-dealer.ru/'));

$arMeta = $clDom->getElementsByTagName('meta');

foreach($arMeta as $clMeta){
    $strType = strtolower($clMeta->getAttribute('name'));

    if($strType === 'keywords' OR $strType === 'description')
        print $clMeta->getAttribute('content');
}*/

/*print_r(parse_url(\Core\Page::Merge('/test/abc/../mail/test.htm')));
print "End";

var_dump(pathinfo('/core/abc/', PATHINFO_EXTENSION));
var_dump(pathinfo('/core/abs/test.htm', PATHINFO_EXTENSION));*/