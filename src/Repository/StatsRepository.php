<?php

require_once __DIR__ . '/../config/database.php';

class StatsRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = (new Database())->connect();
    }

    public function getStatsByGroupId(string $groupId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM user_group_personal_stats WHERE group_id = :group_id ORDER BY score DESC, total_time ASC");
        $stmt->bindParam(':group_id', $groupId);
        $stmt->execute();
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $stats;
    }

    public function getUserGameReport(string $groupId, string $userId, string $gameId): ?array
    {
        // obtener resumen para reporte individual
        $stmt = $this->pdo->prepare("
        SELECT
            ugps.*,
            gg.name AS group_name,
            gg.code AS group_code,
            u.name  AS user_name,
            g.started_on  AS game_started_on,
            g.finished_on AS game_finished_on
        FROM user_group_personal_stats ugps
        INNER JOIN game g
            ON g.id      = :game_id_join
        AND g.user_id = :user_id_join
        AND g.group_id= :group_id_join
        INNER JOIN game_group gg ON gg.id = ugps.group_id
        INNER JOIN user u ON u.id = ugps.user_id
        WHERE ugps.group_id = :group_id_where
        AND ugps.user_id  = :user_id_where
        AND ugps.game_id  = :game_id_where
        LIMIT 1
    ");

        $stmt->execute([
            ':group_id_join'  => $groupId,
            ':user_id_join'   => $userId,
            ':game_id_join'   => $gameId,

            ':group_id_where' => $groupId,
            ':user_id_where'  => $userId,
            ':game_id_where'  => $gameId,
        ]);

        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!$summary) return null;

        // obtener detalle de las preguntas
        $stmt2 = $this->pdo->prepare("
        SELECT
            ua.id AS answer_id,
            ua.question_id,
            q.title,
            q.description,
            q.tip_note,
            q.feedback,
            ua.is_correct,
            ua.started_on,
            ua.finished_on,
            TIMESTAMPDIFF(SECOND, ua.started_on, ua.finished_on) AS time_spent_sec,

            ua.q_option_id AS selected_option_id,

            opt.id AS option_id,
            opt.text_option AS option_text,
            opt.is_correct  AS option_is_correct,

            corr.id AS correct_option_id,
            corr.text_option AS correct_option_text

        FROM user_answer ua
        INNER JOIN question q ON q.id = ua.question_id

        -- Incluir todas las opciones de la pregunta
        INNER JOIN question_option opt ON opt.question_id = ua.question_id

        -- Opción correcta (para mostrar si el usuario falló)
        LEFT JOIN question_option corr
               ON corr.question_id = ua.question_id
              AND corr.is_correct = 1

        WHERE ua.group_id = :group_id
          AND ua.user_id  = :user_id
          AND ua.game_id  = :game_id
          AND ua.finished_on IS NOT NULL
          AND ua.question_id IS NOT NULL
        ORDER BY ua.started_on ASC, opt.id ASC
    ");

        $stmt2->execute([
            ':group_id' => $groupId,
            ':user_id'  => $userId,
            ':game_id'  => $gameId,
        ]);

        $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        $stmt2->closeCursor();

        // Mapeo, agrupar opciones por answer_id
        $questionsByAnswer = [];

        foreach ($rows as $r) {
            $aid = $r['answer_id'];

            if (!isset($questionsByAnswer[$aid])) {
                $questionsByAnswer[$aid] = [
                    'answer_id'           => $r['answer_id'],
                    'question_id'         => $r['question_id'],
                    'title'               => $r['title'],
                    'description'         => $r['description'],
                    'tip_note'            => $r['tip_note'],
                    'feedback'            => $r['feedback'],
                    'is_correct'          => (int)$r['is_correct'],
                    'started_on'          => $r['started_on'],
                    'finished_on'         => $r['finished_on'],
                    'time_spent_sec'      => $r['time_spent_sec'] !== null ? (int)$r['time_spent_sec'] : null,
                    'selected_option_id'  => $r['selected_option_id'],
                    'correct_option_id'   => $r['correct_option_id'],
                    'correct_option_text' => $r['correct_option_text'],
                    'options'             => [],
                ];
            }

            $questionsByAnswer[$aid]['options'][] = [
                'id'         => $r['option_id'],
                'text'       => $r['option_text'],
                'is_correct' => (int)$r['option_is_correct'],
            ];
        }

        return [
            'summary'   => $summary,
            'questions' => array_values($questionsByAnswer),
        ];
    }
}
