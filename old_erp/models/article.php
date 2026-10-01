<?php 
  class Article {
    // DB stuff
    private $conn;
    private $table = 'salt';

    // Post Properties
    public $saltcode;       // updated
    public $stock;          // updated
    public $priceperunit;   // updated
    public $title;          // updated
    public $unit;           // updated
    public $producer;       // updated
    public $currency;       // updated

    // Constructor with DB
    public function __construct($db) {
      $this->conn = $db;
    }

    // Get Posts
    public function read() {
      // Create query
      $query = 'SELECT saltcode, title, stock, unit, producer, priceperunit, currency FROM ' . $this->table;
      
      // Prepare statement
      $stmt = $this->conn->prepare($query);

      // Execute query
      $stmt->execute();

      return $stmt;
    }

    // Get Single Post
    public function read_single() {
      // Create query
      $query = 'SELECT title, stock, unit, producer, priceperunit, currency FROM ' . $this->table.' WHERE saltcode = ? LIMIT 0,1';
              
          // Prepare statement
          $stmt = $this->conn->prepare($query);

          // Bind ID
          $stmt->bindParam(1, $this->saltcode);

          // Execute query
          $stmt->execute();

          $row = $stmt->fetch(PDO::FETCH_ASSOC);

          // Set properties
          $this->title = $row['title'];
          $this->stock = $row['stock'];
          $this->unit = $row['unit'];
          $this->producer = $row['producer'];
          $this->priceperunit = $row['priceperunit'];
          $this->currency = $row['currency'];
    }

    // Create Post
    public function create() {
          // Create query
          $query = 'INSERT INTO ' . $this->table . 
                  ' SET saltcode = :saltcode, title = :title, stock = :stock, unit = :unit, producer = :producer, priceperunit = :priceperunit, currency = :currency';

          // Prepare statement
          $stmt = $this->conn->prepare($query);

          // Clean data
          $this->saltcode = htmlspecialchars(strip_tags($this->saltcode));
          $this->title = htmlspecialchars(strip_tags($this->title));
          $this->stock = htmlspecialchars(strip_tags($this->stock));
          $this->unit = htmlspecialchars(strip_tags($this->unit));
          $this->producer = htmlspecialchars(strip_tags($this->producer));
          $this->priceperunit = htmlspecialchars(strip_tags($this->priceperunit));
          $this->currency = htmlspecialchars(strip_tags($this->currency));

          // Bind data
          $stmt->bindParam(':saltcode', $this->saltcode);
          $stmt->bindParam(':title', $this->title);
          $stmt->bindParam(':stock', $this->stock);
          $stmt->bindParam(':unit', $this->unit);
          $stmt->bindParam(':producer', $this->producer);
          $stmt->bindParam(':priceperunit', $this->priceperunit);
          $stmt->bindParam(':currency', $this->currency);

          // Execute query
          if($stmt->execute()) {
            return true;
      }

      // Print error if something goes wrong
      printf("Error: %s.\n", $stmt->error);

      return false;
    }

    // Update Post
    public function update() {
          // Create query
          $query = 'UPDATE ' . $this->table . 
                  ' SET title = :title, stock = :stock, unit = :unit, producer = :producer, priceperunit = :priceperunit, currency = :currency '
                  . ' WHERE saltcode = :saltcode';

          // Prepare statement
          $stmt = $this->conn->prepare($query);

          // Clean data
          
          $this->title = htmlspecialchars(strip_tags($this->title));
          $this->stock = htmlspecialchars(strip_tags($this->stock));
          $this->unit = htmlspecialchars(strip_tags($this->unit));
          $this->producer = htmlspecialchars(strip_tags($this->producer));
          $this->priceperunit = htmlspecialchars(strip_tags($this->priceperunit));
          $this->currency = htmlspecialchars(strip_tags($this->currency));
          $this->saltcode = htmlspecialchars(strip_tags($this->saltcode));

          // Bind data
          
          $stmt->bindParam(':title', $this->title);
          $stmt->bindParam(':stock', $this->stock);
          $stmt->bindParam(':unit', $this->unit);
          $stmt->bindParam(':producer', $this->producer);
          $stmt->bindParam(':priceperunit', $this->priceperunit);
          $stmt->bindParam(':currency', $this->currency);
          $stmt->bindParam(':saltcode', $this->saltcode);

          // Execute query
          if($stmt->execute()) {
            return true;
          }

          // Print error if something goes wrong
          printf("Error: %s.\n", $stmt->error);

          return false;
    }

    // Delete Post
    public function delete() {
          // Create query
          $query = 'DELETE FROM ' . $this->table . ' WHERE saltcode = :saltcode';

          // Prepare statement
          $stmt = $this->conn->prepare($query);

          // Clean data
          $this->saltcode = htmlspecialchars(strip_tags($this->saltcode));

          // Bind data
          $stmt->bindParam(':saltcode', $this->saltcode);

          // Execute query
          if($stmt->execute()) {
            return true;
          }

          // Print error if something goes wrong
          printf("Error: %s.\n", $stmt->error);

          return false;
    }
    
  }