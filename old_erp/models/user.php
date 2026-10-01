<?php
  class User {
    // DB Stuff
    private $conn;
    private $table = 'user';

    // Properties
    public $id;
    public $name;
    public $surname;
    public $ZINN;
    public $email;
    public $tel;

    // Constructor with DB
    public function __construct($db) {
      $this->conn = $db;
    }

    // Get users
    public function read() {
      // Create query
      $query = 'SELECT id, name, surname,
        ZINN,
        email,
        tel
      FROM
        ' . $this->table . '
      ORDER BY
        id DESC';

      // Prepare statement
      $stmt = $this->conn->prepare($query);

      // Execute query
      $stmt->execute();

      return $stmt;
    }

  // Get Single User
  public function read_single(){
    // Create query
    $query = ' SELECT id, name, surname, ZINN, email, tel FROM ' . $this->table . ' WHERE id = ? LIMIT 0,1 ';

      //Prepare statement
      $stmt = $this->conn->prepare($query);

      // Bind ID
      $stmt->bindParam(1, $this->id);

      // Execute query
      $stmt->execute();

      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      // set properties
      $this->id = $row['id'];
      $this->name = $row['name'];
      $this->surname = $row['surname'];
      $this->ZINN = $row['ZINN'];
      $this->email = $row['email'];
      $this->tel = $row['tel'];
  }

  // Create User
  public function create() {
    // Create Query
    $query = 'INSERT INTO ' . $this->table . 
            ' SET id = :id, name = :name, surname = :surname, ZINN = :ZINN, email = :email, tel = :tel';

  // Prepare Statement
  $stmt = $this->conn->prepare($query);

  // Clean data
  $this->id = htmlspecialchars(strip_tags($this->id));
  $this->name = htmlspecialchars(strip_tags($this->name));
  $this->surname = htmlspecialchars(strip_tags($this->surname));
  $this->ZINN = htmlspecialchars(strip_tags($this->ZINN));
  $this->email = htmlspecialchars(strip_tags($this->email));
  $this->tel = htmlspecialchars(strip_tags($this->tel));

  // Bind data
  $stmt-> bindParam(':id', $this->id);
  $stmt-> bindParam(':name', $this->name);
  $stmt-> bindParam(':surname', $this->surname);
  $stmt-> bindParam(':ZINN', $this->ZINN);
  $stmt-> bindParam(':email', $this->email);
  $stmt-> bindParam(':tel', $this->tel);

  // Execute query
  if($stmt->execute()) {
    return true;
  }

  // Print error if something goes wrong
  printf("Error: $s.\n", $stmt->error);

  return false;
  }

  // Update User
  public function update() {
    // Create Query
    $query = 'UPDATE ' . $this->table . 
            ' SET name = :name, surname = :surname, ZINN = :ZINN, email = :email, tel = :tel ' 
            . ' WHERE id = :id';

  // Prepare Statement
  $stmt = $this->conn->prepare($query);

  // Clean data
  $this->name = htmlspecialchars(strip_tags($this->name));
  $this->surname = htmlspecialchars(strip_tags($this->surname));
  $this->ZINN = htmlspecialchars(strip_tags($this->ZINN));
  $this->email = htmlspecialchars(strip_tags($this->email));
  $this->tel = htmlspecialchars(strip_tags($this->tel));
  $this->id = htmlspecialchars(strip_tags($this->id));

  // Bind data
  $stmt-> bindParam(':name', $this->name);
  $stmt-> bindParam(':surname', $this->surname);
  $stmt-> bindParam(':ZINN', $this->ZINN);
  $stmt-> bindParam(':email', $this->email);
  $stmt-> bindParam(':tel', $this->tel);
  $stmt-> bindParam(':id', $this->id);

  // Execute query
  if($stmt->execute()) {
    return true;
  }

  // Print error if something goes wrong
  printf("Error: $s.\n", $stmt->error);

  return false;
  }

  // Delete User
  public function delete() {
    // Create query
    $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';

    // Prepare Statement
    $stmt = $this->conn->prepare($query);

    // clean data
    $this->id = htmlspecialchars(strip_tags($this->id));

    // Bind Data
    $stmt-> bindParam(':id', $this->id);

    // Execute query
    if($stmt->execute()) {
      return true;
    }

    // Print error if something goes wrong
    printf("Error: $s.\n", $stmt->error);

    return false;
    }
  }
