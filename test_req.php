<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/?store_id=', 'GET');
$response = $kernel->handle($request);
echo "store_id: ";
var_dump($request->query('store_id'));
