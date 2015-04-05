<?

namespace Core;

class JS extends \Core\Item{
    const JS = 0x300;

    protected function Prepare(){
        $this->arStatus['TYPE'] = \Core\JS::JS;

        $arPreparedUrl = \Core\Item::PrepareUrl($this->arJSON, $this->arManifest['ROOT']);

        $this->strUrl  = $arPreparedUrl['URL'];
        $this->strPath = $arPreparedUrl['PATH'];

        $this->BlockContent();
        $this->Request();
    }
}