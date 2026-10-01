<?php 
  class Order {
    // DB stuff
    private $conn;
    private $table = 'salesorder';

    // Post Properties
    public $idso;        // updated
    public $ZINN;        // updated
    public $saltcode;    // updated
    public $title;       // updated
    public $quantity;    // updated
    public $unit;        // updated
    public $value;       // updated
    public $currency;    // updated

    // Constructor with DB
    public function __construct($db) {
      $this->conn = $db;
    }

    // Get Posts
    public function read() {
      // Create query
      $query = ' SELECT idso, ZINN, saltcode, title, quantity, unit, value, currency FROM ' . $this->table;
      
      // Prepare statement
      $stmt = $this->conn->prepare($query);

      // Execute query
      $stmt->execute();

      return $stmt;
    }

    // Get Single Post
    public function read_single() {
      // Create query
      $query = ' SELECT ZINN, saltcode, title, quantity, unit, value, currency FROM ' . $this->table.' WHERE idso = ? LIMIT 0,1';
              
          // Prepare statement
          $stmt = $this->conn->prepare($query);

          // Bind ID
          $stmt->bindParam(1, $this->idso);

          // Execute query
          $stmt->execute();

          $row = $stmt->fetch(PDO::FETCH_ASSOC);

          // Set properties
          $this->ZINN = $row['ZINN'];
          $this->saltcode = $row['saltcode'];
          $this->title = $row['title'];
          $this->quantity = $row['quantity'];
          $this->unit = $row['unit'];
          $this->value = $row['value'];
          $this->currency = $row['currency'];
    }

    // Create Post
    public function create() {
          // Create query
          $query = 'INSERT INTO ' . $this->table . 
                  ' SET idso = :idso, ZINN = :ZINN, saltcode = :saltcode, title = :title, quantity = :quantity, unit = :unit, value = :value, currency = :currency';

          // Prepare statement
          $stmt = $this->conn->prepare($query);

          // Clean data
          $this->idso = htmlspecialchars(strip_tags($this->idso));
          $this->ZINN = htmlspecialchars(strip_tags($this->ZINN));
          $this->saltcode = htmlspecialchars(strip_tags($this->saltcode));
          $this->title = htmlspecialchars(strip_tags($this->title));
          $this->quantity = htmlspecialchars(strip_tags($this->quantity));
          $this->unit = htmlspecialchars(strip_tags($this->unit));
          $this->value = htmlspecialchars(strip_tags($this->value));
          $this->currency = htmlspecialchars(strip_tags($this->currency));

          // Bind data
          $stmt->bindParam(':idso', $this->idso);
          $stmt->bindParam(':ZINN', $this->ZINN);
          $stmt->bindParam(':saltcode', $this->saltcode);
          $stmt->bindParam(':title', $this->title);
          $stmt->bindParam(':quantity', $this->quantity);
          $stmt->bindParam(':unit', $this->unit);
          $stmt->bindParam(':value', $this->value);
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
                  ' SET ZINN = :ZINN, saltcode = :saltcode, title = :title, quantity = :quantity, unit = :unit, value = :value, currency = :currency '
                  . ' WHERE idso = :idso';

          // Prepare statement
          $stmt = $this->conn->prepare($query);

          // Clean data
          $this->ZINN = htmlspecialchars(strip_tags($this->ZINN));
          $this->saltcode = htmlspecialchars(strip_tags($this->saltcode));
          $this->title = htmlspecialchars(strip_tags($this->title));
          $this->quantity = htmlspecialchars(strip_tags($this->quantity));
          $this->unit = htmlspecialchars(strip_tags($this->unit));
          $this->value = htmlspecialchars(strip_tags($this->value));
          $this->currency = htmlspecialchars(strip_tags($this->currency));
          $this->idso = htmlspecialchars(strip_tags($this->idso));
          

          // Bind data
          
          $stmt->bindParam(':ZINN', $this->ZINN);
          $stmt->bindParam(':saltcode', $this->saltcode);
          $stmt->bindParam(':title', $this->title);
          $stmt->bindParam(':quantity', $this->quantity);
          $stmt->bindParam(':unit', $this->unit);
          $stmt->bindParam(':value', $this->value);
          $stmt->bindParam(':currency', $this->currency);
          $stmt->bindParam(':idso', $this->idso);

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
          $query = 'DELETE FROM ' . $this->table . ' WHERE idso = :idso';

          // Prepare statement
          $stmt = $this->conn->prepare($query);

          // Clean data
          $this->idso = htmlspecialchars(strip_tags($this->idso));

          // Bind data
          $stmt->bindParam(':idso', $this->idso);

          // Execute query
          if($stmt->execute()) {
            return true;
          }

          // Print error if something goes wrong
          printf("Error: %s.\n", $stmt->error);

          return false;
    }
    
  }