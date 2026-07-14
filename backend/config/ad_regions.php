<?php

$syFile = __DIR__.'/ad_regions_sy.php';
$trFile = __DIR__.'/ad_regions_tr.php';

return [
    'SY' => is_file($syFile) ? require $syFile : [],
    'TR' => is_file($trFile) ? require $trFile : [],
];
