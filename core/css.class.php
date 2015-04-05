<?

namespace Core;

class CSS extends \Core\Item{
    const CSS = 0x100;

    protected function Prepare(){
        $this->arStatus['TYPE'] = \Core\CSS::CSS;

        $arPreparedUrl = \Core\Item::PrepareUrl($this->arJSON, $this->arManifest['ROOT']);

        $this->strUrl  = $arPreparedUrl['URL'];
        $this->strPath = $arPreparedUrl['PATH'];

        $this->Request();
    }

    protected function Parse(){
        preg_match_all('/url\((.*?)\)/', $this->strHTML, $arMatches);

        foreach($arMatches[1] as $strMatch)
            $this->arList[] = array(
                'URL' => trim(trim($strMatch, '\''), '"')
            );

        $this->arList = \Core\Item::ParseList($this->arList, $this->GetPath(), $this->arManifest['ROOT']);
    }
}