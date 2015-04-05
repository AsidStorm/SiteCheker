<?

namespace Core;

class IMG extends \Core\Item{
    const IMG = 0x50;

    protected function Prepare(){
        $this->arStatus['TYPE'] = \Core\IMG::IMG;

        $arPreparedUrl = \Core\Item::PrepareUrl($this->arJSON, $this->arManifest['ROOT']);

        $this->strUrl  = $arPreparedUrl['URL'];
        $this->strPath = $arPreparedUrl['PATH'];

        $this->BlockContent();
        $this->Request();
    }
}