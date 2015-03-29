<?

namespace Core;

class Page{
    public static function Root($url){
        $arParse = parse_url($url);

        $strScheme = ($arParse['scheme']) ? $arParse['scheme'] : 'http';

        if($arParse['host'])
            $strHost = $arParse['host'];
        else{
            $arPath  = explode('/', $arParse['path']);
            $strHost = array_shift($arPath);
        }
        $strUrl = $strScheme . '://' . $strHost . '/';

        return $strUrl;
    }
}