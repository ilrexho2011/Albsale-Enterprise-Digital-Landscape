<?php 
  // Headers
  header('Access-Control-Allow-Origin: *');
  header('Content-Type: application/json');

  include_once '../../config/Database.php';
  include_once '../../models/article.php';

  // Instantiate DB & connect
  $database = new Database();
  $db = $database->connect();

  // Instantiate blog post object
  $post = new Article($db);

  // Get ID
  $post->saltcode = isset($_GET['saltcode']) ? $_GET['saltcode'] : die();

  // Get post
  $post->read_single();

  // Create array
  $post_arr = array(
    'saltcode' => $post->saltcode,
       'title' => $post->title,
       'stock' => $post->stock,
        'unit' => $post->unit,
    'producer' => $post->producer,
'priceperunit' => $post->priceperunit,
    'currency' => $post->currency
  );

  // Make JSON
  print_r(json_encode($post_arr));