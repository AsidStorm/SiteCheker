<pre>
<?
error_reporting(0);
include($_SERVER['DOCUMENT_ROOT'] . '/core/include.php');

$strPage = '/dir/';

$arLinks = array(

);

// Get TITLE

/*$strHTML = file_get_contents('http://cheker.com/');

preg_match_all('/title\>(.*?)<\/title\>/', $strHTML, $arMatches);

print_r($arMatches);*/

/*$clDom = new DOMDocument;

$clDom->loadHTML(file_get_contents('http://cheker.com/'));

$arTitles = $clDom->getElementsByTagName('title');

foreach($arTitles as $clTitle){
    print htmlentities($clTitle->nodeValue);
}*/

// Get FROM HTML

/*$strHTML = file_get_contents('http://www.skoda-avto.ru/models/new-octavia-rs/overview-page');

preg_match_all('/url\((.*?)\)/', $strHTML, $arMatches);

print_r($arMatches); // $arMatches[1] - Contains URLs

print "End";*/

// Get from CSS

/*$strHTML = file_get_contents('http://master.vw-dealer.ru/bitrix/templates/vw4d/css/style.css');

preg_match_all('/url\((.*?)\)/', $strHTML, $arMatches);

print_r($arMatches); // $arMatches[1] - Contains URLs

foreach($arMatches[1] as $strMatch){
    print trim(trim($strMatch, '\''), '"') . "<br />";
}*/

/*$clDom = new DOMDocument;

$clDom->loadHTML(file_get_contents('http://master.vw-dealer.ru/'));

$arMeta = $clDom->getElementsByTagName('meta');

foreach($arMeta as $clMeta){
    $strType = strtolower($clMeta->getAttribute('name'));

    if($strType === 'keywords' OR $strType === 'description')
        print $clMeta->getAttribute('content');
}*/

/*print_r(parse_url(\Core\Page::Merge('/test/abc/../mail/test.htm')));
print "End";

var_dump(pathinfo('/core/abc/', PATHINFO_EXTENSION));
var_dump(pathinfo('/core/abs/test.htm', PATHINFO_EXTENSION));*/