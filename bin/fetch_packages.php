#!/usr/bin/env php
<?php

# Fetch package files

$packages = array (
  'd3' => array(
    'files' => array (
      'd3.min.js',
    ),
    'version' => '3.5.17'
  ),
  'c3' => array (
    'files' => array(
      'c3.min.js',
      'c3.min.css',
    ),
    'version' => 'latest'
  ),
  'pivottable' => array (
    'files' => array(
      'c3_renderers.min.js',
      'd3_renderers.min.js',
      'export_renderers.min.js',
      'pivot.min.js',
      'pivot.min.css',
    ),
    'version' => 'latest'
  ),
  'moment.js' => array(
    'files' => array(
      'moment.min.js',
    ),
    'version' => 'latest'
  ),
  'bootstrap-sweetalert' => array(
    'files' => array(
      'sweetalert.min.js',
      'sweetalert.min.css',
    ),
    'version' => 'latest',
  ),
);

$package_dir = "packages";
if (!is_dir($package_dir)) {
  mkdir($package_dir, 0775);
}

foreach ($packages as $key => $component) {
  $dir = "$package_dir/$key";
  $version_file = "$dir/version";
  if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
  }

  if ($component['version'] == 'latest') {
    $json = file_get_contents("http://api.cdnjs.com/libraries/$key?fields=version,name,filename");
    $info = json_decode($json, true);
    $version = $info['version'];

    if (is_file($version_file) AND is_readable($version_file)) {
      $old_json = file_get_contents($version_file);
      if ($old_json == $json) {
        print "$key is already at latest version: $version\n";
        continue;
      }
    }
  } else {
    $json = '{"version":"' . $component['version'] . '","name":"' . $key . '","filename":"' . $component['files'][0] . '"}';
    $version = $component['version'];
  }

  file_put_contents($version_file, $json);
  print "Downloading $key $version\n";
  foreach ($component['files'] as $file) {
    file_put_contents("$dir/$file", fopen("https://cdnjs.cloudflare.com/ajax/libs/$key/$version/$file", 'r'));
  }
}
