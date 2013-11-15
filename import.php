<?php
$GLOBALS['csvSeparator'] = ';';
$GLOBALS['config']['cnFormat'] = 'MB_CASE_TITLE';
$GLOBALS['config']['lFormat'] = 'MB_CASE_TITLE';
$GLOBALS['config']['accountSeparator'] = '.';
$GLOBALS['config']['userAccountControl'] = 66048;
require_once 'config/example.php';
require_once 'csvExtract.php';
require_once 'activeDirectory.php';

## Traitement CSV
$csv = new csv();
$csv->importCsv('csv/' . $GLOBALS['config']['csvName']);
# Formatage CSV to AD
$nbFields = count($csv->fields);
foreach ($csv->datas as $baseKey => $array) {
    foreach ($array as $key => $data) {
        $info[$baseKey][$csv->fields[$key]] = $data;
    }
}

if ($_POST['import'] !== 'OK') {
    $csvPreview  = '<table>' . PHP_EOL;
    $csvPreview .= '<tr>' . PHP_EOL;
    foreach ($csv->fields as $fKey => $fValue) {
        $csvPreview .= '<th>' . $fKey . '</th>';
    }
    $csvPreview .= '</tr>';
    $csvPreview .= '<tr>' . PHP_EOL;
    foreach ($csv->fields as $value) {
        $csvPreview .= '<td nowrap="nowrap">' . $value . '</td>';
    }
    $csvPreview .= '</tr>';
    foreach ($csv->datas as $datas) {
        $csvPreview .= '<tr>';
        foreach ($datas as $data) {
            $csvPreview .= '<td nowrap="nowrap">' . $data . '</td>';
        }
        $csvPreview .= '</tr>';
    }
    $csvPreview .= '</table>';
}
elseif ($_POST['import'] === 'OK') {
    # Traitement AD
    $ad = new activeDirectory();
    $ad->constructDn($GLOBALS['config']['dc'][0]);
    $ad->constructDn($GLOBALS['config']['dc'][1]);
    $ad->constructDn($GLOBALS['config']['ou']);
    $ad->connect($GLOBALS['config']['ldapAddress'], $GLOBALS['config']['ldapUser'], $GLOBALS['config']['ldapPassword']);
    $users = $ad->prepare($info);
    $ad->record();

    if (is_array($ad->resource)) {
        $passwordList  = '<table class="passwordList">' . PHP_EOL;
        $passwordList .= '<tr>
            <th>Utilisateur</th><th>Mot de passe</th>
        </tr>';
        foreach ($ad->resource as $key => $value) {
            $passwordList .= '<tr>' .PHP_EOL;
            $passwordList .= '<td>' . $value['sAMAccountName'] . '</td>' . PHP_EOL;
            $passwordList .= '<td>' . mb_substr($value['unicodePwd'], 1, -2) . '</td>' . PHP_EOL;
            $passwordList .= '</tr>' . PHP_EOL;
        }
        $passwordList .= '</table>';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Importation AD</title>
    <style>
        table {
            border-collapse: collapse;
            font-size: 10px;
            font-family: monospace;
        }
        table, td {
            border: 1px solid;
        }
        .passwordList th {
            font-size: 12px;
            padding: 2px;
            width: 200px;
        }
        .passwordList td {
            font-size: 16px;
            padding: 5px;
            text-align: center;
        }

    </style>
</head>
<body>
<div class='csvPreview'>
    <?php echo $csvPreview; ?>
    <?php if ($_POST['import'] !== 'OK') { ?>
    <form name="import" method="POST">
        <input type="hidden" name="import" value="OK">
        <input type="submit" name="post" value="Import datas">
    </form>
    <?php } ?>
</div>
<div class="passwordList">
    <?php echo $passwordList; ?>
    <?php if ($_POST['import'] === 'OK') { ?>
    <form name="import" method="POST">
        <input type="hidden" name="import" value="OK">
        <input type="submit" name="post" value="re-import">
    </form>
    <?php } ?>
</div>
</body>
</html>