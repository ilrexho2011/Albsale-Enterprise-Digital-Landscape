<?php
/**
 * Order.php — Modeli i porosisë (salesorder). Query me prepared statements.
 */
declare(strict_types=1);

class Order
{
    private PDO $conn;
    private string $table = 'salesorder';

    public int|string|null $idso = null;
    public ?string $ZINN = null;
    public int|string|null $saltcode = null;
    public ?string $title = null;
    public int|string|null $quantity = null;
    public ?string $unit = null;
    public int|string|null $value = null;
    public ?string $currency = null;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function read(): PDOStatement
    {
        $sql = "SELECT idso, ZINN, saltcode, title, quantity, unit, value, currency
                FROM {$this->table} ORDER BY idso DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    public function read_single(): bool
    {
        $sql = "SELECT ZINN, saltcode, title, quantity, unit, value, currency
                FROM {$this->table} WHERE idso = :idso LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':idso', $this->idso);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        $this->ZINN     = $row['ZINN'];
        $this->saltcode = $row['saltcode'];
        $this->title    = $row['title'];
        $this->quantity = $row['quantity'];
        $this->unit     = $row['unit'];
        $this->value    = $row['value'];
        $this->currency = $row['currency'];
        return true;
    }

    /** Porositë e një klienti (self-service, filtruar me ZINN). */
    public function readByCustomer(string $zinn): array
    {
        $sql = "SELECT idso, s4_order_id, ZINN, saltcode, title, quantity, unit,
                       value, currency, order_status, confirmed_qty, delivery_no,
                       invoice_no, created, updated
                FROM {$this->table} WHERE ZINN = :z ORDER BY created DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':z', $zinn);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(): bool
    {
        $sql = "INSERT INTO {$this->table}
                    (idso, ZINN, saltcode, title, quantity, unit, value, currency)
                VALUES (:idso, :ZINN, :saltcode, :title, :quantity, :unit, :value, :currency)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':idso', $this->idso);
        $stmt->bindParam(':ZINN', $this->ZINN);
        $stmt->bindParam(':saltcode', $this->saltcode);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':quantity', $this->quantity);
        $stmt->bindParam(':unit', $this->unit);
        $stmt->bindParam(':value', $this->value);
        $stmt->bindParam(':currency', $this->currency);
        return $stmt->execute();
    }

    public function update(): bool
    {
        $sql = "UPDATE {$this->table}
                SET ZINN = :ZINN, saltcode = :saltcode, title = :title,
                    quantity = :quantity, unit = :unit, value = :value, currency = :currency
                WHERE idso = :idso";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':ZINN', $this->ZINN);
        $stmt->bindParam(':saltcode', $this->saltcode);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':quantity', $this->quantity);
        $stmt->bindParam(':unit', $this->unit);
        $stmt->bindParam(':value', $this->value);
        $stmt->bindParam(':currency', $this->currency);
        $stmt->bindParam(':idso', $this->idso);
        return $stmt->execute();
    }

    public function delete(): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE idso = :idso";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':idso', $this->idso);
        return $stmt->execute();
    }
}
