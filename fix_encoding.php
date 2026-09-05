<?php
$admin_files = glob(__DIR__ . '/public/views/admin/*.html');
foreach($admin_files as $file) {
    $content = file_get_contents($file);
    $content = preg_replace('/<span>Qu\?n tr\? vi[^<]*<\/span>/', '<span>Quản trị viên</span>', $content);
    file_put_contents($file, $content);
}

$org_files = glob(__DIR__ . '/public/views/organizer/*.html');
foreach($org_files as $file) {
    $content = file_get_contents($file);
    $content = preg_replace('/<span>Qu\?n l[^<]*<\/span>/', '<span>Quản lý</span>', $content);
    file_put_contents($file, $content);
}

echo "Fixed encoding.";
