<?

namespace Core;

class FILE extends \Core\Item{
    const FILE = 0x200;

    protected function Prepare(){
        $this->arStatus['TYPE'] = \Core\FILE::FILE;

        $arPreparedUrl = \Core\Item::PrepareUrl($this->arJSON, $this->arManifest['ROOT']);

        $this->strUrl  = $arPreparedUrl['URL'];
        $this->strPath = $arPreparedUrl['PATH'];

        $this->BlockContent();
        $this->Request();
    }
}