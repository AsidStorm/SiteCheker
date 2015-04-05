<?

namespace Core;

class Item{
    protected $arList   = array();
    protected $arStatus = array(
        'TYPE'  => 0,
        'VALID' => 'Y',
        'CODE'  => 0,

        'REDIRECT' => array()
    );

    protected $arJSON     = array();
    protected $arManifest = array();

    protected $strUrl;
    protected $strPath;

    protected $intType;
    protected $strHTML;

    protected $boolFull = true;

    public static $arTypes = array( // Список расширений для файлов, и куда мы их поместим.
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
        'data:image/png;base64',
        'data:image/gif;base64',
        'javascript:;'
    );

    public function __construct($arJSON, $arManifest){
        $this->arJSON     = $arJSON;
        $this->arManifest = $arManifest;

        $this->Prepare();
    }

    protected function Prepare(){

    }

    protected function Request(){

        $clCURL = new \Core\CURL($this->strUrl, !$this->boolFull);

        $this->arStatus['CODE']     = $clCURL->GetCode();
        $this->arStatus['REDIRECT'] = $clCURL->GetTrace();

        if($this->arJSON['OUR'] === 'Y' && $clCURL->GetCode() === 200){
            $this->strHTML = $clCURL->GetHTML();
            $this->Parse();
        }
    }

    protected function Parse(){

    }

    public function GetList(){
        return $this->arList;
    }

    public function GetPath(){
        return $this->strPath;
    }

    public function GetUrl(){
        return $this->strUrl;
    }

    public function GetStatus(){
        return $this->arStatus;
    }

    protected function BlockContent(){
        $this->boolFull = false;
    }

    /* Далее идут методы, которые могут использоваться где-то ещё */

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
        $arViewed = array();

        $arResult = array(
            'CSS'      => array(),
            'JS'       => array(),
            'PRIORITY' => array(),
            'URL'      => array(),
            'IMG'      => array(),
            'FILE'     => array(),

            'HASH'     => array()
        );

        foreach($arList as $arUrl){
            $arItem = array(
                'URL'  => '',
                'TRIM' => 'N',
                'TYPE' => 'URL',
                'OUR'  => 'Y',
                '__I'  => array()
            );

            $strUrl = $arUrl['URL'];
            $arItem = array_merge_recursive($arItem, $arUrl);

            // Шаг 0. Определяем тип ресурса.

            $arItem['TYPE'] = self::GetType($strUrl);

            // Шаг 1. Обрезаем ? часть. И # часть.

            if($arItem['TYPE'] === 'CSS' OR $arItem['TYPE'] === 'JS'){ // Нам необходимо распарсиваться конструкции вида ?v=XXX ?time(), и не индексировать их дважды
                $arTmpStrUrl = explode('?', $strUrl);

                if(count($arTmpStrUrl) === 2){
                    if( is_numeric($arTmpStrUrl[1]) ) // Вырезает timestamp
                        $strUrl = array_shift($arTmpStrUrl);
                }
            }

            $arTmpStrUrl = explode("#", $strUrl);

            if(count($arTmpStrUrl) === 2){
                $arResult['HASH'][] = $strUrl;

                $strUrl = array_shift($arTmpStrUrl);
                // Помещаем часть в список ХЭШЭЙ. И ставим сразу же checked. А страницу без хэша, пихаем в URL.
            }
            else{
                if(count($arTmpStrUrl) > 1)
                    continue; // TODO: Это тоже нужно логировать
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
                    $strTmpDomain = parse_url($strDomain, PHP_URL_HOST);

                    if ($mxdUrlHost !== $strTmpDomain)
                        $isOurURL = false; // 1. Проверка не внешняя ли это ссылка. Если внешняя - ничего не меняем.
                    else { // Если это наша ссылка, но она указана через полный путь. Исправим это для проверки.
                        $strOurPath = parse_url($strUrl, PHP_URL_PATH);
                        $strUrl = ($strOurPath !== NULL) ? $strOurPath : '/';
                    }
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

                if(in_array($arItem['URL'], $arViewed)) // Режем ссылки для обработки с файлами на этапе прочёсывания страницы
                    continue;

                $arViewed[] = $arItem['URL'];

                if(strrpos($strUrl, 'cache') !== false)
                    $arResult['PRIORITY'][] = $arItem;
                else
                    $arResult[$arItem['TYPE']][] = $arItem;
            }
        }

        $arResult['HASH'] = array_unique($arResult['HASH']);

        return $arResult;
    }

    public static function PrepareUrl($arJSON, $strDomain){
        $strPath = parse_url($arJSON['URL'], PHP_URL_PATH);

        $strUrl  = $arJSON['URL'];

        if($arJSON['OUR'] === 'Y')
            $strUrl = $strDomain . ltrim($strPath, '/');

        return array(
            'URL'  => $strUrl,
            'PATH' => $strPath
        );
    }
}