<?

namespace Core;

class URL{
    const PHONE = 0x1;
    const HASH  = 0x2;
    const MAIL  = 0x3;

    const URL   = 0x9;

    /*
     * Задача данного класса. Проверить:
     * 1. Является ли ссылка битой. И дополнить [TRACE]
     * 2. Выделить из ссылки все файлы
     * 3. Записать все редиректы
     */

    private $strUrl;
    private $arJSON;
    private $strPath;

    private $isOurUrl = true;
    private $intType;
    private $strOurPath;

    private $strHTML;

    private $arList = array();

    private $arStatus = array(
        'CODE'     => false,
        'TRACE'    => array(),
        'REDIRECT' => array(),
        'EXTERNAL' => 'N'
    );

    public static $arTypes = array(
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

    public static $arSkipped = array( // Список игнорируемых файлов
        'image/png;base64',
        'image/gif;base64',
        'javascript:;'
    );

    private $arManifest;

    public function __construct($arJSON, $strPath, $arManifest){
        $this->strUrl  = $arJSON['URL'];
        $this->arJSON  = $arJSON;

        $this->strPath    = $strPath;
        $this->arManifest = $arManifest;
        $this->intType    = \Core\URL::URL;

        $this->Prepare();
    }

    private function Prepare(){
        if(strrpos($this->strUrl, 'mailto:') === 0)
            $this->intType = \Core\URL::MAIL;
        else if(strrpos($this->strUrl, '#') === 0)
            $this->intType = \Core\URL::HASH;
        else if(strrpos($this->strUrl, 'tel:') === 0 OR strrpos($this->strUrl, 'callto:') === 0)
            $this->intType = \Core\URL::PHONE;
        else {
            $mxdUrlHost = parse_url($this->strUrl, PHP_URL_HOST);

            if ($mxdUrlHost !== NULL) {
                if ($mxdUrlHost !== $this->arManifest['DOMAIN']) {
                    $this->isOurUrl             = false;
                    $this->arStatus['EXTERNAL'] = 'Y';
                }
                else
                    $this->strOurPath = parse_url($this->strUrl, PHP_URL_PATH);
            }
            else
                $this->strOurPath = parse_url($this->strUrl, PHP_URL_PATH);
        }

        $this->arStatus['TYPE'] = $this->intType;

        if($this->intType === \Core\URL::URL)
            $this->Request();
        else
            $this->Check();

        $this->Finish();
    }

    private function Request(){
        $strUrl = $this->strUrl;

        if($this->isOurUrl) {
            if(strpos($this->strOurPath, '/') === 0)
                $strUrl = rtrim($this->arManifest['ROOT'], '/') . '/' . (($this->strOurPath === '/') ? '' : (rtrim(ltrim($this->strOurPath, '/'), '/') . '/'));
            else {
                $strTrace = $this->arJSON['TRACE'][(count($this->arJSON['TRACE']) - 1)];
                $strUrl = rtrim($strTrace, '/') . '/' . (($this->strOurPath === '/') ? '' : (rtrim(ltrim($this->strOurPath, '/'), '/') . '/'));
            }
        }

        $this->strUrl = $strUrl;

        $clCURL = new \Core\CURL($strUrl);

        $this->arStatus['CODE']     = $clCURL->GetCode();
        $this->arStatus['REDIRECT'] = $clCURL->GetTrace();

        if($this->isOurUrl){
            if($clCURL->GetCode() === 200) {
                $this->strHTML = $clCURL->GetHTML();

                $this->Parse();
            }
        }
    }

    private function Parse(){
        preg_match_all('/url\((.*?)\)/', $this->strHTML, $arMatches);

        foreach($arMatches[1] as $strMatch)
            $this->arList[] = array(
                'URL' => trim(trim($strMatch, '\''), '"')
            );

        unset($arMatches);

        preg_match_all('/title\>(.*?)<\/title\>/', $this->strHTML, $arMatches);

        if(isset($arMatches[1]) && isset($arMatches[1][0]))
            $this->arStatus['TITLE'] = $arMatches[1][0];

        unset($arMatches);

        $clDom = new \DOMDocument;

        $clDom->loadHTML($this->strHTML);

        $arURLs = $clDom->getElementsByTagName('a');

        foreach($arURLs as $clUrl)
            $this->arList[] = array(
                'URL' => $clUrl->getAttribute('href')
            );

        $arIMGs = $clDom->getElementsByTagName('img');

        foreach($arIMGs as $clImg)
            $this->arList[] = array(
                'URL' => $clImg->getAttribute('src')
            );

        $arCSSs = $clDom->getElementsByTagName('link');

        foreach($arCSSs as $clCss)
            $this->arList[] = array(
                'URL' => $clCss->getAttribute('href')
            );

        $arJSs = $clDom->getElementsByTagName('script');
        foreach($arJSs as $clJs)
            $this->arList[] = array(
                'URL' => $clJs->getAttribute('src')
            );

        $this->arList = self::ParseList($this->arList, $this->strOurPath, $this->arManifest['DOMAIN']);

        /*foreach($this->arList as $strListName => &$arList){ // TODO: Нужно будет убрать формирование ссылок из самих ссылок, т.к. там оно становится бессмысленной трайтой времени
            // TODO: Вынести это в отдельную функция по формированию URL'a
            foreach($arList as $intKey => &$strUrl){
                $strUrl = trim($strUrl);

                if($strUrl === ''){
                    unset($arList[$intKey]);
                    continue;
                }


                // 2. Смотри, начинается ли ссылка с / - если да, то опять же ничего не делаем.
                // 3. Если начало с ../ или просто name/ - то вызывает Page::Merge($this->strUrl, $strUrl);

                if(strrpos($strUrl, 'mailto:') !== 0 && strrpos($strUrl, '#') !== 0 && strrpos($strUrl, 'tel:') !== 0 && strrpos($strUrl, 'callto:') !== 0){
                    $mxdUrlHost = parse_url($strUrl, PHP_URL_HOST);
                    $isOurURL   = true;


                    if ($mxdUrlHost !== NULL) {
                        if ($mxdUrlHost !== $this->arManifest['DOMAIN'])
                            $isOurURL = false; // 1. Проверка не внешняя ли это ссылка. Если внешняя - ничего не меняем.
                    }

                    if($isOurURL){
                        $strOurPath = parse_url($strUrl, PHP_URL_PATH);

                        if(strpos($strOurPath, '/') !== 0) { // Значит у нас крутой путь. Ничего не будем менять.

                            if(pathinfo($this->strOurPath, PATHINFO_EXTENSION) === '')
                                $strUrl = \Core\Page::Merge($this->strOurPath . $strOurPath);
                            else {
                                $arTmpOurPath  = explode('/', $this->strOurPath);
                                array_pop($arTmpOurPath);
                                $strTmpOurPath = '/' . implode('/', $arTmpOurPath) . '/';

                                $strUrl = \Core\Page::Merge($strTmpOurPath . $strOurPath);
                            }
                        }
                    }
                }
            }
        }*/
    }

    public static function GetType($strUrl){
        $strRealExt = pathinfo(parse_url($strUrl, PHP_URL_PATH), PATHINFO_EXTENSION);

        foreach(self::$arTypes as $strType => $arTypes){
            foreach($arTypes as $strExt){
                if( strtoupper($strRealExt) === strtoupper($strExt) )
                    return $strType;
            }
        }

        return 'URL';
    }

    public static function ParseList($arList, $strCurPath, $strDomain){
        $arResult = array(
            'CSS'      => array(),
            'JS'       => array(),
            'PRIORITY' => array(),
            'URL'      => array(),
            'IMG'      => array(),
            'FILE'     => array()
        );

        foreach($arList as $arUrl){
            $arItem = array(
                'URL'  => '',
                'TRIM' => 'N',
                'TYPE' => 'URL',
                'OUR'  => 'Y'
            );

            $strUrl = $arUrl['URL'];
            $arItem = array_merge($arItem, $arUrl);

            // Шаг 0. Определяем тип ресурса.

            $arItem['TYPE'] = self::GetType($strUrl);

            // Шаг 1. Обрезаем ? часть. И # часть.

            if($arItem['TYPE'] === 'CSS' OR $arItem['TYPE'] === 'JS'){ // Нам необходимо распарсиваться конструкции вида ?v=XXX ?time(), и не индексировать их дважды

            }

            //$strUrl = array_shift(explode('?', $strUrl));
            //$strUrl = array_shift(explode("#", $strUrl));

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
            foreach(self::$arSkipped as $strSkipped){
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


                if(strpos($strUrl, 'mailto:') !== 0 && strpos($strUrl, '#') !== 0 && strpos($strUrl, 'tel:') !== 0 && strpos($strUrl, 'callto:') !== 0) {

                    if ($isOurURL) {
                        $strOurPath = parse_url($strUrl, PHP_URL_PATH);

                        if(strpos($strOurPath, '/') !== 0){ // Если у нас не ссылка вида /NAME
                            if(strrpos($strCurPath, '/') === (strlen($strCurPath)-1)) { // Если у нас последний символ в строке - /
                            }
                            else{ // В противном случае. Нам нужно определить - является ли страница вида /page.EXT или у нас просто страница.
                                if(pathinfo($strCurPath, PATHINFO_EXTENSION) === ''){ // Мы находимся на странице
                                    $strCurPath .= '/'; // Необходимо добавить обратный слеш для корректной обработки страницы.
                                }
                                else{ // В случае, если у нас страница - то нужно её распарсить по-другому
                                    $arTmpStrPage = explode('/', $strCurPath);
                                    array_pop($arTmpStrPage);
                                    $strCurPath = '/' . implode('/', $arTmpStrPage) . '/';
                                }
                            }

                            $arItem['URL'] = \Core\Page::Merge($strCurPath . $strUrl);
                        }
                    }
                    else
                        $arItem['OUR'] = 'N';
                }

                if(strrpos($strUrl, 'cache') !== false)
                    $arResult['PRIORITY'][] = $arItem;
                else
                    $arResult[$arItem['TYPE']][] = $arItem;
            }
        }

        return $arResult;
    }

    private function Finish(){

    }

    private function Check(){
        $this->arStatus['VALID'] = 'N';

        if($this->intType === \Core\URL::MAIL){
            $arMail = explode("@", $this->strUrl);

            if(count($arMail) === 2){
                getmxrr($arMail[1], $arMXRecords, $arMXWeight);

                if($arMXRecords !== NULL)
                    $this->arStatus['VALID'] = 'Y';
            }
        }
        if($this->intType === \Core\URL::HASH)
            $this->arStatus['VALID'] = 'Y';

        if($this->intType === \Core\URL::PHONE){
            if(strlen($this->strUrl) > 3){
				$strTmpUrl = array_pop(explode(':', $this->strUrl));
				
				
                if(preg_match('/[\+7|8]{1}[\d]+/', $strTmpUrl, $arMatches)) {
                    if ($arMatches[0] == $strTmpUrl)
                        $this->arStatus['VALID'] = 'Y';
                }
            }
        }
    }

    public function GetUrl(){
        return $this->strUrl;
    }

    public function GetStatus(){
        return $this->arStatus;
    }

    public function GetList(){
        return $this->arList;
    }
}