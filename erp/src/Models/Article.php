<?php
/**
 * Article.php — Modeli i artikullit (tabela salt). Query me prepared statements.
 */
declare(strict_types=1);

class Article
{
    private PDO $conn;
    private string $table = 'salt';

    public int|string|null $saltcode = null;
    public int|string|null $stock = null;
    public int|string|null $priceperunit = null;
    public ?string $title = null;
    public ?string $unit = null;
    public ?string $producer = null;
    public ?string $currency = null;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function read(): PDOStatement
    {
        $sql = "SELECT saltcode, title, stock, unit, producer, priceperunit, currency
                FROM {$this->table} ORDER BY saltcode DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    public function read_single(): bool
    {
        $sql = "SELECT title, stock, unit, producer, priceperunit, currency
                FROM {$this->table} WHERE saltcode = :saltcode LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':saltcode', $this->saltcode);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        $this->title        = $row['title'];
        $this->stock        = $row['stock'];
        $this->unit         = $row['unit'];
        $this->producer     = $row['producer'];
        $this->priceperunit = $row['priceperunit'];
        $this->currency     = $row['currency'];
        return true;
    }

    public function create(): bool
    {
        $sql = "INSERT INTO {$this->table}
                    (saltcode, title, producer, stock, unit, priceperunit, currency)
                VALUES (:saltcode, :title, :producer, :stock, :unit, :priceperunit, :currency)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':saltcode', $this->saltcode);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':producer', $this->producer);
        $stmt->bindParam(':stock', $this->stock);
        $stmt->bindParam(':unit', $this->unit);
        $stmt->bindParam(':priceperunit', $this->priceperunit);
        $stmt->bindParam(':currency', $this->currency);
        return $stmt->execute();
    }

    public function update(): bool
    {
        $sql = "UPDATE {$this->table}
                SET title = :title, producer = :producer, stock = :stock,
                    unit = :unit, priceperunit = :priceperunit, currency = :currency
                WHERE saltcode = :saltcode";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':producer', $this->producer);
        $stmt->bindParam(':stock', $this->stock);
        $stmt->bindParam(':unit', $this->unit);
        $stmt->bindParam(':priceperunit', $this->priceperunit);
        $stmt->bindParam(':currency', $this->currency);
        $stmt->bindParam(':saltcode', $this->saltcode);
        return $stmt->execute();
    }

    public function delete(): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE saltcode = :saltcode";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':saltcode', $this->saltcode);
        return $stmt->execute();
    }
}
