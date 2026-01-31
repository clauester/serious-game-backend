<?php

require_once __DIR__ . '/../Config/database.php';

class AuthRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = (new Database())->connect();
    }

    public function getByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("CALL sp_get_user_by_email(:p_email)");
        $stmt->bindParam(":p_email", $email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        while ($stmt->nextRowset()) {
        }
        $stmt->closeCursor();

        return $user ?: null;
    }

    public function registerBasic(string $name, string $email, string $passwordHash): ?array
    {
        $stmt = $this->pdo->prepare("CALL sp_register_user_basic(:p_name, :p_email, :p_password)");
        $stmt->bindParam(":p_name", $name, PDO::PARAM_STR);
        $stmt->bindParam(":p_email", $email, PDO::PARAM_STR);
        $stmt->bindParam(":p_password", $passwordHash, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        while ($stmt->nextRowset()) {
        }
        $stmt->closeCursor();

        return $row ?: null;
    }
}
