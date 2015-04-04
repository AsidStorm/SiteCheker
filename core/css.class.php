<?

namespace Core;

class CSS{
    const CSS = 0x100;

    private $strUrl;
    private $arJSON = array();
    private $strPath;

    private $arManifest = array();
    private $intType;
    private $strOurPath;

    private $isOurUrl = true;

    private $arList = array(
        'IMG'  => array(),
        'FILE' => array()
    );

    private $arStatus = array(
        'CODE'     => '',
        'TRACE'    => array(),
        'REDIRECT' => array()
    );

    public function __construct($arJSON, $strPath, $arManifest){
        $this->strUrl  = $arJSON['URL'];
        $this->arJSON  = $arJSON;

        $this->strPath    = $strPath;
        $this->arManifest = $arManifest;
        $this->intType    = \Core\CSS::CSS;

        $this->Prepare();
    }

    private function Prepare(){
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

        $this->Request();
    }

    private function Request(){
        $strUrl = $this->strUrl;

        if($this->isOurUrl) {
            if(strpos($this->strOurPath, '/') === 0)
                $strUrl = rtrim($this->arManifest['ROOT'], '/') . '/' . (($this->strOurPath === '/') ? '' : (ltrim($this->strOurPath, '/')));
            else {
                $strTrace = $this->arJSON['TRACE'][(count($this->arJSON['TRACE']) - 1)];
                $strUrl = rtrim($strTrace, '/') . '/' . (($this->strOurPath === '/') ? '' : (ltrim($this->strOurPath, '/')));
            }
        }

        $this->strUrl = $strUrl;

        $clCURL = new \Core\CURL($strUrl);

        $this->arStatus['CODE']     = $clCURL->GetCode();
        $this->arStatus['REDIRECT'] = $clCURL->GetTrace();

        if($clCURL->GetCode() === 200){
            preg_match_all('/url\((.*?)\)/', $clCURL->GetHTML(), $arMatches);

            foreach($arMatches[1] as $strMatch){
                $strMatch = trim(trim($strMatch, '\''), '"');

                if($strMatch !== '')
                    $this->arList[\Core\URL::GetType($strMatch)][] = $strMatch;
            }
        }

        foreach($this->arList as $strListName => &$arList){ // TODO: Нужно будет убрать формирование ссылок из самих ссылок, т.к. там оно становится бессмысленной трайтой времени
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
        }

        $this->Finish();
    }

    private function Finish(){

    }

    public function GetUrl(){
        return $this->strUrl;
    }

    public function GetList(){
        return $this->arList;
    }

    public function GetStatus(){
        return $this->arStatus;
    }
}