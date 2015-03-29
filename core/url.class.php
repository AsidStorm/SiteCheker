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

    private $arList = array(
        'JS'   => array(),
        'CSS'  => array(),
        'IMG'  => array(),
        'URL'  => array(),
        'FILE' => array(),

        'PRIORITY' => array()
    );

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
            'PDF', 'XLS', 'XLSX', 'SWF', 'ZIP', 'RAR'
        ),
        'IMG'  => array(
            'PNG', 'JPG', 'JPEG', 'ICO', 'GIF', 'BMP', 'SVG'
        )
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
        $clDom = new \DOMDocument;

        $clDom->loadHTML($this->strHTML);

        $arURLs = $clDom->getElementsByTagName('a');

        foreach($arURLs as $clUrl){
            $strHref = $clUrl->getAttribute('href');

            if($strHref !== '' && $strHref !== 'javascript:;')
                $this->arList[\Core\URL::GetType($strHref)][] = $strHref;
        }

        $arIMGs = $clDom->getElementsByTagName('img');

        foreach($arIMGs as $clImg){
            $strSrc = $clImg->getAttribute('src');

            if($strSrc !== '')
                $this->arList[\Core\URL::GetType($strSrc)][] = $strSrc;
        }

        $arCSSs = $clDom->getElementsByTagName('link');

        foreach($arCSSs as $clCss){
            $strHref = $clCss->getAttribute('href');

            if($strHref !== ''){
                if(strrpos($strHref, 'x/cache/') !== false){
                    $strHref = array_shift(explode('?', $strHref)); // Убираем ?time()

                    $this->arList['PRIORITY'][] = $strHref;
                }
                else
                    $this->arList[\Core\URL::GetType($strHref)][] = $strHref;
            }
        }

        $arJSs = $clDom->getElementsByTagName('script');
        foreach($arJSs as $clJs){
            $strSrc = $clJs->getAttribute('src');

            if($strSrc !== ''){
                if(strrpos($strSrc, 'x/cache/') !== false){
                    $strSrc = array_shift(explode('?', $strSrc)); // Убираем ?time()

                    $this->arList['PRIORITY'][] = $strSrc;
                }
                else
                    $this->arList[\Core\URL::GetType($strSrc)][] = $strSrc;
            }
        }
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