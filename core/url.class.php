<?

namespace Core;

class URL extends \Core\Item{
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

    protected function Prepare(){
        $this->arStatus['TYPE'] = \Core\URL::URL;

        if(strpos($this->arJSON['URL'], 'mailto:') === 0)
            $this->arStatus['TYPE'] = \Core\URL::MAIL;

        else if(strpos($this->arJSON['URL'], '#') === 0)
            $this->arStatus['TYPE'] = \Core\URL::HASH;

        else if(strpos($this->arJSON['URL'], 'tel:') === 0 OR strpos($this->arJSON['URL'], 'callto:') === 0)
            $this->arStatus['TYPE'] = \Core\URL::PHONE;

        else {
            $arPreparedUrl = \Core\Item::PrepareUrl($this->arJSON, $this->arManifest['ROOT']);

            $this->strUrl  = $arPreparedUrl['URL'];
            $this->strPath = $arPreparedUrl['PATH'];
        }

        // В зависимости от того, что у нас. Нам нужно, либо распарсить это. Либо проверить на корректность.

        if($this->arStatus['TYPE'] === \Core\URL::URL)
            $this->Request();
        else
            $this->Check();
    }

    protected function Parse(){
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

        @$clDom->loadHTML($this->strHTML);

        $arURLs = $clDom->getElementsByTagName('a');

        foreach($arURLs as $clUrl)
            $this->arList[] = array(
                'URL' => $clUrl->getAttribute('href'),
                '__I' => array(
                    'TARGET' => $clUrl->getAttribute('target'),
                    'REL'    => $clUrl->getAttribute('rel')
                )
            );

        $arIMGs = $clDom->getElementsByTagName('img');

        foreach($arIMGs as $clImg)
            $this->arList[] = array(
                'URL' => $clImg->getAttribute('src')
            );

        $arCSSs = $clDom->getElementsByTagName('link');

        foreach($arCSSs as $clCss)
            $this->arList[] = array(
                'URL' => $clCss->getAttribute('href'),
                '__I' => array(
                    'REL' => $clCss->getAttribute('rel')
                )
            );

        $arJSs = $clDom->getElementsByTagName('script');
        foreach($arJSs as $clJs)
            $this->arList[] = array(
                'URL' => $clJs->getAttribute('src'),
                '__I' => array(
                    'TYPE' => $clJs->getAttribute('type')
                )
            );

        $this->arList = \Core\Item::ParseList($this->arList, $this->GetPath(), $this->arManifest['ROOT']);
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
}