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

    public static function Merge($strPath, $strSeparator = '/'){
        $strPath = str_replace(array('/', '\\'), $strSeparator, $strPath);

        $parts     = array_filter(explode($strSeparator, $strPath), 'strlen');
        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part) continue;
            if ('..' == $part)
                array_pop($absolutes);
            else
                $absolutes[] = $part;
        }

        return '/' . implode($strSeparator, $absolutes);
    }
}