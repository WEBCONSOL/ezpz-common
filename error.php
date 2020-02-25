<?php
if (isset($_GET['d']))
{
    $d = $_GET['d'];
    $file = __DIR__ . '/static/json/' . $d . '.json';

    if (file_exists($file))
    {
        $output = file_get_contents($file);
    }
    else
    {
        $output = file_get_contents(__DIR__ . '/static/json/500.json');
    }

    header('Content-Type: application/json');
    echo $output;
}