<?php
/**
 * User.php — Modeli i përdoruesit/klientit. Të gjitha query-t me prepared statements.
 * Fjalëkalimet ruhen VETËM me hash (shih src/Security/auth.php).
 */
declare(strict_types=1);

class User
{
    private PDO $conn;
    private string $table = 'user';

    public int|string|null $id = null;
    public ?string $name = null;
    public ?string $surname = null;
    public ?string $username = null;
    public ?string $password = null;   // hash, jo tekst i thjeshtë
    public ?string $ZINN = null;
    public ?string $email = null;
    public ?string $tel = null;
    public ?string $created = null;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /** Lista e përdoruesve (pa fusha sensitive si password). */
    public function read(): PDOStatement
    {
        $sql = "SELECT id, name, surname, ZINN, email, tel
                FROM {$this->table} ORDER BY id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    public function read_single(): bool
    {
        $sql = "SELECT id, name, surname, ZINN, email, tel
                FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        $this->name    = $row['name'];
        $this->surname = $row['surname'];
        $this->ZINN    = $row['ZINN'];
        $this->email   = $row['email'];
        $this->tel     = $row['tel'];
        return true;
    }

    /** Gjen përdoruesin me username (për login). */
    public function findByUsername(string $username): ?array
    {
        $sql = "SELECT id, name, surname, username, password, ZINN, email
                FROM {$this->table} WHERE username = :u LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':u', $username);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function usernameExists(string $username): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE username = :u LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':u', $username);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    /** Krijim klienti (pa kredenciale login-i). */
    public function create(): bool
    {
        $sql = "INSERT INTO {$this->table} (id, name, surname, ZINN, email, tel)
                VALUES (:id, :name, :surname, :ZINN, :email, :tel)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':surname', $this->surname);
        $stmt->bindParam(':ZINN', $this->ZINN);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':tel', $this->tel);
        return $stmt->execute();
    }

    /** Regjistrim me kredenciale (password duhet të jetë tashmë i hash-uar). */
    public function signup(): bool
    {
        if ($this->username === null || $this->usernameExists($this->username)) {
            return false;
        }
        $sql = "INSERT INTO {$this->table}
                    (name, surname, username, password, ZINN, email, tel, created)
                VALUES (:name, :surname, :username, :password, :ZINN, :email, :tel, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':surname', $this->surname);
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':ZINN', $this->ZINN);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':tel', $this->tel);
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function update(): bool
    {
        $sql = "UPDATE {$this->table}
                SET name = :name, surname = :surname, ZINN = :ZINN,
                    email = :email, tel = :tel
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':surname', $this->surname);
        $stmt->bindParam(':ZINN', $this->ZINN);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':tel', $this->tel);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    public function delete(): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
}
