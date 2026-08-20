<?php 
  // Headers
  header('Access-Control-Allow-Origin: *');
  header('Content-Type: application/json');
  header('Access-Control-Allow-Methods: PUT');
  header('Access-Control-Allow-Headers: Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Methods, Authorization, X-Requested-With');

  include_once '../../config/Database.php';
  include_once '../../models/article.php';

  // Instantiate DB & connect
  $database = new Database();
  $db = $database->connect();

  // Instantiate blog post object
  $post = new Article($db);

  // Get raw posted data
  $data = json_decode(file_get_contents("php://input"));

  // Set ID to update
  $post->saltcode = $data->saltcode;

  $post->title = $data->title;
  $post->stock = $data->stock;
  $post->unit = $data->unit;
  $post->producer = $data->producer;
  $post->priceperunit = $data->priceperunit;
  $post->currency = $data->currency;

  // Update post
  if($post->update()) {
    echo json_encode(
      array('message' => 'Article Updated')
    );
  } else {
    echo json_encode(
      array('message' => 'Article Not Updated')
    );
  }

