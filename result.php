<html>
<head>
    <title>Cool Site Cheker</title>
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.6.0/bootstrap-table.min.css" />

    <script src="http://code.jquery.com/jquery-2.1.3.js" type="text/javascript"></script>
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js" type="text/javascript"></script>
</head>
<body>
<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/core/include.php');

$arFiles = scandir($_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/checked/');
$arManifest = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/manifest.json'), true);

$arTitles = array();

$strHTML = '';

foreach($arFiles as $strFileName){
    if($strFileName === '.' OR $strFileName === '..') continue;

    $strHash = array_shift(explode('.', $strFileName));

    $arJSON = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/process/' . $_REQUEST['DIR'] . '/checked/' . $strFileName), true);

    if($arJSON['TYPE'] === \Core\URL::HASH OR $arJSON['TYPE'] === \Core\URL::PHONE OR $arJSON['TYPE'] === \Core\URL::MAIL) {
        if(!$arJSON['VALID'] OR ($arJSON['VALID'] && $arJSON['VALID'] === 'Y'))
            $arJSON['CODE'] = 200;
    }

    if($arJSON['TYPE'] === \Core\URL::URL && $arJSON['EXTERNAL'] === 'N' && ( !$arJSON['TITLE'] OR ($arJSON['TITLE'] && trim($arJSON['TITLE']) === '')) )
        $arJSON['CODE'] = -1;

    if(count($arJSON['TRACE']) === 0)
        continue;

    if($arJSON['TITLE'])
        $arTitles[$arJSON['TITLE']][] = $arJSON['URL'];

    if($arJSON['CODE'] === 200)
        continue;

    $arJSON['FROM'] = array_unique($arJSON['FROM']);
    $strHTML .= "<tr data-status='" . (((int) $arJSON['CODE'] !== 200) ? 'danger' : 'success') . "' data-code='" . $arJSON['CODE'] . "'>";
    $strHTML .= '<td><div style="max-width: 400px; overflow: auto;">' . $arJSON['URL'] . "</div></td>";
    $strHTML .= "<td>";

    if( (int) $arJSON['CODE'] !== 200 )
        $strHTML .= '<span class="glyphicon glyphicon-exclamation-sign text-danger"></span>&nbsp;';
    else
        $strHTML .= '<span class="glyphicon glyphicon-ok text-success"></span>&nbsp;';

    $strHTML .= $arJSON['CODE'] . "</td>";
    $strHTML .= "<td>" . count($arJSON['FROM']) . '&nbsp;<span class="glyphicon glyphicon-option-horizontal" data-toggle="modal" data-target="#_' . $strHash . '_From"></span></td>';
    $strHTML .= "<td>" . $arJSON['TRACE'][(count($arJSON['TRACE']) - 1)] . '&nbsp;<span class="glyphicon glyphicon-option-horizontal"  data-toggle="modal" data-target="#_' . $strHash . '_Way"></span></td>';

    if(count($arJSON['REDIRECT']) > 0){
        $strHTML .= '<td>Редиректы&nbsp;<span class="glyphicon glyphicon-option-horizontal" data-toggle="modal" data-target="#_' . $strHash . '_Redirect"></span></td>';

        $strHTML .= '<!-- Modal -->
<div class="modal fade" id="_' . $strHash . '_Redirect" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Карта редиректов</h4>
      </div>
      <div class="modal-body">';
        $arTrace = array();
        foreach($arJSON['REDIRECT'] as $key => $arRedirect){
            $arTrace[] = "<a href='" . $arRedirect['URL'] . "' target='_blank'>" . $arRedirect['URL'] . "</a> (" . $arRedirect['CODE'] . ")";
        }
        $strHTML .= implode(' &rArr; ', $arTrace);

        $strHTML .= '
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
      </div>
    </div>
  </div>
</div>';
    }
    else {
        $strHTML .= "<td>&nbsp;</td>";
    }
    $strHTML .= "</tr>";

    $strHTML .= '<!-- Modal -->
<div class="modal fade" id="_' . $strHash . '_From" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">На эту страницу ссылаются</h4>
      </div>
      <div class="modal-body">';
    foreach($arJSON['FROM'] as $strUrl){
        $strHTML .= "<a href='" . $strUrl . "' target='_blank'>" . $strUrl . "</a><br />";
    }
    $strHTML .= '
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
      </div>
    </div>
  </div>
</div>';

    $strHTML .= '<!-- Modal -->
<div class="modal fade" id="_' . $strHash . '_Way" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Путь к странице</h4>
      </div>
      <div class="modal-body">';
    $arTrace = array();
    foreach($arJSON['TRACE'] as $key => $strUrl){
        $arTrace[] = "<a href='" . $strUrl . "' target='_blank'>" . $strUrl . "</a>";
    }
    $strHTML .= implode(' &rArr; ', $arTrace);

    $strHTML .= '
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
      </div>
    </div>
  </div>
</div>';
}
?>
<div class="container-fluid">
    <table class="table table-stripped">
        <thead>
            <tr>
                <th>Запрашиваемый адрес</th>
                <th>Статус</th>
                <th>Ссылаются</th>
                <th>Путь</th>
                <th>Остальное</th>
            </tr>
            <tr>
                <th colspan="5" class="filter">
                    <span class="glyphicon glyphicon-exclamation-sign text-danger"></span>
                    <span class="glyphicon glyphicon-ok text-success"></span>

                    <input type="text" class="form-control" id="_Code" />
                </th>
            </tr>
        </thead>
        <tbody id="_Full">
            <?=$strHTML?>
            <tr>
                <th colspan="5" class="text-center">
                    Заголовки
                </th>
            </tr>
            <?foreach($arTitles as $strTitle => $arUrls){?>
                <?if(count($arUrls) === 1) continue;?>
                <?++$intKey;?>

                <tr>
                    <td colspan="4">
                        <?=$strTitle?>
                    </td>
                    <td>
                        Используется (<?=count($arUrls)?>) <span class="glyphicon glyphicon-option-horizontal"  data-toggle="modal" data-target="#_<?=$intKey?>_Titles"></span>
                    </td>
                </tr>

                <!-- Modal -->
                <div class="modal fade" id="_<?=$intKey?>_Titles" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title" id="myModalLabel">Одинаковый заголовок у страниц</h4>
                            </div>
                            <div class="modal-body">
                                <?foreach($arUrls as $strUrl){?>
                                    <?if(strrpos($strUrl, $arManifest['DOMAIN']) === false){?>
                                    <a href='<?=$arManifest['ROOT']?><?=ltrim($strUrl, '/')?>' target='_blank'><?=$strUrl?></a><br />
                                    <?} else {?>
                                    <a href='<?=$strUrl?>' target='_blank'><?=$strUrl?></a><br />
                                    <?}?>
                                <?}?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?}?>
        </tbody>
    </table>
    </div>
<script type="text/javascript">
    var _curStatus = '';
    $(document).ready(function(){
        $('body').on("click", ".filter .glyphicon", function(){
            var _setStatus = ($(this).hasClass("text-danger")) ? 'danger' : 'success';

            if(_setStatus === _curStatus){
                _curStatus = '';
                $("#_Full tr").show();
            }
            else{
                $("#_Full tr[data-status='" + _setStatus + "']").show();
                $("#_Full tr[data-status!='" + _setStatus + "']").hide();

                _curStatus = _setStatus;
            }
        });
    });
</script>
</body>
</html>