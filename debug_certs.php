<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CourseCertificateOption;

$opt = CourseCertificateOption::first();
if ($opt) {
    echo "CERT ID: " . $opt->id . "\n";
    echo "CERT IMG1: " . var_export($opt->image_1_path, true) . "\n";
}

use App\Models\Teacher;
$t = Teacher::first();
if ($t) {
    echo "TEACHER ID: " . $t->id . "\n";
    echo "TEACHER IMG: " . var_export($t->image_path, true) . "\n";
}

