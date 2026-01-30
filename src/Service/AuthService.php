<?php

require_once __DIR__ . '/../Repository/AuthRepository.php';

class AuthService
{
    private $repo;

    public function __construct()
    {
        $this->repo = new AuthRepository();
    }

    /**
     * Devuelve el usuario (sin password) si las credenciales son correctas.
     * Retorna null si las credenciales no coinciden
     * Lanza InvalidArgumentException si faltan datos o son inválidos.
     * Lanza RuntimeException si el usuario existe pero no está activo.
     */
    public function login(array $data): ?array
    {
        $email = strtolower(trim((string)($data["email"] ?? "")));
        $password = (string)($data["password"] ?? "");

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { // validar estructura email
            throw new InvalidArgumentException("El email no es válido");
        }
        if ($password === "") {
            throw new InvalidArgumentException("Contraseña requerida");
        }
        if (mb_strlen($password) < 8) {
            return null; // credenciales inválidas
        }

        $user = $this->repo->getByEmail($email);
        if (!$user) {
            return null; // no existe usuario con ese email, credenciales invalidas
        }

        // sp devuelve status por nombre
        if (isset($user["status"]) && $user["status"] !== "active") {
            throw new RuntimeException("El usuario no se encuentra activo");
        }

        $stored = (string)($user["password"] ?? "");

        // Soporta hash (bcrypt/argon2) y texto plano
        $looksHashed = str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2');
        $passOk = $looksHashed ? password_verify($password, $stored) : hash_equals($stored, $password);

        if (!$passOk) {
            return null; // si contraseña inválida
        }

        unset($user["password"]); // no exponer password
        return $user; // credenciales correctas, devuelve data user logeado
    }

    /**
     * Registro para nuevos usuarios: name, email, password.
     * Rol y status por defecto en el SP. (participant y active).
     * Devuelve el usuario creado (sin password) si el SP retorna filas.
     * Lanza InvalidArgumentException si los datos no cumplen reglas.
     */
    public function register(array $data): array
    {
        $name = trim((string)($data["name"] ?? ""));
        $email = strtolower(trim((string)($data["email"] ?? "")));
        $password = (string)($data["password"] ?? "");

        if ($name === "" || mb_strlen($name) < 2) {
            throw new InvalidArgumentException("El nombre debe tener al menos 2 caracteres");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Email inválido");
        }
        if (mb_strlen($password) < 8) {
            throw new InvalidArgumentException("La contraseña debe tener al menos 8 caracteres");
        }

        // validar si el email ya se encuentra registrado
        $existingUser = $this->repo->getByEmail($email);
        if ($existingUser) {
            throw new RuntimeException("El email ya está registrado");
        }

        // guardar contraseña hash
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $created = $this->repo->registerBasic($name, $email, $hash);

        // no password en el resultado
        if (is_array($created) && isset($created["password"])) {
            unset($created["password"]);
        }

        // Si retorna null, se devuelve array vacío
        return $created ?? [];
    }
}
