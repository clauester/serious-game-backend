<?php

require_once __DIR__ . '/../config/database.php';

class StatsRepository {
    private $pdo;

    public function __construct() {
        $this->pdo = (new Database())->connect();
    }

    public function getStatsByGroupId(string $groupId) {
        $stmt = $this->pdo->prepare("SELECT * FROM user_group_personal_stats WHERE group_id = :group_id ORDER BY score DESC, total_time ASC");
        $stmt->bindParam(':group_id', $groupId);
        $stmt->execute();
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $stats;
    }
}
