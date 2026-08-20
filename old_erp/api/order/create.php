<?php 
  // Headers
  header('Access-Control-Allow-Origin: *');
  header('Content-Type: application/json');
  header('Access-Control-Allow-Methods: POST');
  header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

  include_once '../../config/Database.php';
  include_once '../../models/order.php';

  // Instantiate DB & connect
  $database = new Database();
  $db = $database->connect();

  // Instantiate blog post object
  $post = new Order($db);

  // Get raw posted data
  $data = json_decode(file_get_contents("php://input"));

  $post->idso = $data->idso;
  $post->ZINN = $data->ZINN;
  $post->saltcode = $data->saltcode;
  $post->title = $data->title;
  $post->quantity = $data->quantity;
  $post->unit = $data->unit;
  $post->value = $data->value;
  $post->currency = $data->currency;

  // Create post
  if($post->create()) {
    echo json_encode(
      array('message' => 'Sales Order Created')
    );
  } else {
    echo json_encode(
      array('message' => 'Sales Order Not Created')
    );
  }

