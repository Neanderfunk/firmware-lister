<?php

if (PHP_SAPI === 'cli-server' && preg_match('/\.(?:png|jpg|jpeg|gif|svg|css|js|ico)(\?\S+)?$/', $_SERVER["REQUEST_URI"])) {
    return false; // Liefere die angefragte Ressource direkt aus
}

$blacklist = [
  'list.php',
  'css',
  'fonts',
  'img',
  'js',
];

function formatBytes($bytes, $precision = 1) {
  if($bytes === '-') return $bytes;
  $units = array('B', 'KB', 'MB', 'GB', 'TB');

  $bytes = max($bytes, 0);
  $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
  $pow = min($pow, count($units) - 1);

  // Uncomment one of the following alternatives
  // $bytes /= pow(1024, $pow);
  $bytes /= (1 << (10 * $pow));

  return round($bytes, $precision) . ' ' . $units[$pow];
}


$ruri = $_SERVER['REQUEST_URI'];

// echo $ruri . '<br><br>';
//
// echo __DIR__ . '<br><br>';

$itemliste = scandir(__DIR__ . $ruri);

$pfadteile = explode('/', $ruri);

if(1 !== preg_match('/[a-zA-Z0-9]*\/$/', $ruri)) {
    $ruri = $ruri . '/';
}

?>
<!doctype html>

<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta http-equiv="Content-Security-Policy" content="font-src 'self'">

  <title>Freifunk Neanderfunk Firmware Seite</title>
  <meta name="description" content="Firmware Freifunk Neanderfunk">
  <meta name="author" content="Freifunk">

  <link rel="stylesheet" href="/css/bootstrap.min.css?v=4.5">
  <link rel="stylesheet" href="/css/datatables.min.css?v=1.10.21"/>
  <!-- minified mit https://cssminifier.com/ -->
  <link rel="stylesheet" href="/css/style.min.css?v=1.0">

</head>
<body>
<div class="container">

<nav class="mt-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/">
      <img src="/img/house-fill.svg" alt="Root" width="16" height="16" title="Root">
    </a></li>
    <?php
    $anzahl = count($pfadteile) -2; // Ziehe 2 schon mal ab ( Index 0 ist ein leerer String, da explode den erzeugt; der letzte Eintrag ist ja aktiv und muss abgezogen werden)
    foreach($pfadteile as $key => $pfad) {
      if(empty($pfad)) continue;
      $activ = '';
      $ariacurrent = '';
      $url = dirname($ruri, (($anzahl >= 1) ? $anzahl : 1));
      $link = '<a href="' . $url . '">' . $pfad . '</a>';
      $anzahl--;
      if($key === array_key_last($pfadteile)) {
        $activ = ' active';
        $ariacurrent = ' aria-current="page"';
        $link = $pfad;
      }
    ?>
    <li class="breadcrumb-item<?= $activ ?>"<?= $ariacurrent ?>><?= $link ?></li>
    <?php
      }
    ?>
  </ol>
</nav>

<!-- <div class="row">

<div class="col"> -->

<table id="filelist" class="display table table-striped table-hover table-sm" style="width: 100%;">
    <thead>
        <tr>
            <th class="d-none"></th>
            <th>Name</th>
            <th>Letzte Bearbeitung</th>
            <th>Größe</th>
        </tr>
    </thead>
    <tbody>

<?php if($ruri != '/'): ?>
<tr>
    <td class="d-none">1</td>
    <td>
      <img src="/img/arrow-return-left.svg" alt="zurueck" width="16" height="16" title="zurück">
      <a href="<?= (dirname($ruri) == '/') ? '/' : dirname($ruri).'/' ?>">../</a>
    </td>
    <td data-sort=""></td>
    <td data-sort="">-</td>
</tr>
<?php
endif;


foreach($itemliste as $item) {

if(in_array($item, $blacklist)) continue;

if($item == '.' || $item == '..' || 1 == preg_match('/^\.[a-z]+/', $item)) continue;

$stats = stat(__DIR__ . $ruri . $item);

$size = $stats['size'];
$mtime = $stats['mtime'];

?>


<tr>
    <td class="d-none"></td>
    <td>
      <?php if(is_file(__DIR__ . $ruri . $item)): ?>
        <img src="/img/file-earmark-binary.svg" alt="Datei" width="16" height="16" title="Datei">
      <?php else: ?>
        <img src="/img/folder.svg" alt="Ordner" width="16" height="16" title="Ordner">
      <?php $size = '-'; ?>
      <?php endif; ?>
      <a href="<?= $ruri . $item ?>"><?= $item ?></a>
    </td>
    <td data-sort="<?= $mtime ?>"><?= date('d.m.Y H:i:s', $mtime) ?></td>
    <td data-sort="<?= $size ?>"><?= formatBytes($size) ?></td>
</tr>

<?php
}
?>

</tbody>
</table>

<!-- </div>
</div> -->

</div>

  <script src="/js/jquery-3.5.1.slim.min.js"></script>
  <script src="/js/bootstrap.min.js?v=4.5"></script>
  <script src="/js/datatables.min.js?v=1.10.21"></script>

  <script>


    $(document).ready( function () {
      $('#filelist').DataTable({
        paging: false,
        responsive: true,
        processing: true,
        "autoWidth": true,
        language: {
           'decimal': ',',
           'thousands': '.',
           'search': 'Suche:',
           'info': '_TOTAL_ Einträge',
           'infoEmpty': '',
           'aria': {
               'sortAscending':  ': aufsteigend sortieren',
               'sortDescending': ': absteigend sortieren'
           },
           'lengthMenu': 'Zeige _MENU_ Zeilen',
           'emptyTable': 'Keine Einträge vorhanden',
           'infoFiltered': '(von _MAX_ Einträgen gesamt)'
        },
        "columnDefs": [
            {
                "targets": [ 0 ],
                "visible": false,
                "searchable": false,
            },
            {
                "targets": [ 2, 3 ],
                "searchable": false,
            },
        ],
        'orderFixed': [0, 'desc'],

      });
    } );
    $('#filelist').resize()
  </script>
</body>
</html>
