<?

namespace Core;

class FILE{
    const FILE = 0x200;

    private $strUrl;
    private $arJSON = array();
    private $strPath;

    private $arManifest = array();
    private $intType;
    private $strOurPath;

    private $isOurUrl = true;

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
        $this->intType    = \Core\FILE::FILE;

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

        $clCURL = new \Core\CURL($strUrl, true);

        $this->arStatus['CODE']     = $clCURL->GetCode();
        $this->arStatus['REDIRECT'] = $clCURL->GetTrace();

        $this->Finish();
    }

    private function Finish(){

    }

    public function GetUrl(){
        return $this->strUrl;
    }

    public function GetList(){
        return array();
    }

    public function GetStatus(){
        return $this->arStatus;
    }
}