<?php
  // Create some random text-encoded data for a line chart.
  header('content-type: image/png');
  $url = 'http://chart.apis.google.com/chart';
  $chd = 't:';
  for ($i = 0; $i < 150; ++$i) {
    $data = rand(0, 100000);
    $chd .= $data . ',';
  }
  $chd = substr($chd, 0, -1);

  // Add data, chart type, chart size, and scale to params.
  $chart = array(
    'cht' => 'lc',
    'chs' => '600x200',
    'chds' => '0,100000',
    'chd' => $chd);

  // Send the request, and print out the returned bytes.
  $context = stream_context_create(
    array('http' => array(
      'method' => 'POST',
      'content' => http_build_query($chart))));
  fpassthru(@fopen($url, 'r', false, $context));
