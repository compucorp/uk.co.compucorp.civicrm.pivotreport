#!/usr/bin/env php
<?php

# Fetch package files

$packages = array (
  'd3' => array (
    'd3.min.js',
  ),
  'c3' => array (
    'c3.min.js',
    'c3.min.css',
  ),
  'pivottable' => array (
    'c3_renderers.min.js',
    'd3_renderers.min.js',
    'export_renderers.min.js',
    'pivot.min.js',
    'pivot.min.css',
  ),
);

$package_dir = "packages";
if (!is_dir($package_dir)) {
  mkdir($package_dir, 0775);
}

foreach ($packages as $key => $files) {
  $dir = "$package_dir/$key";
  $version_file = "$dir/version";
  if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
  }
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

  file_put_contents($version_file, $json);
  print "Downloading $key $version\n";
  foreach ($files as $file) {
    file_put_contents("$dir/$file", fopen("https://cdnjs.cloudflare.com/ajax/libs/$key/$version/$file", 'r'));
  }
}
