-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 31-01-2026 a las 23:13:27
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

START TRANSACTION;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `serius_game_periodontitits`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE PROCEDURE `sp_create_game_group` (IN `p_name` VARCHAR(255), IN `p_description` VARCHAR(255), IN `p_code` CHAR(6), IN `p_created_by` CHAR(36))   BEGIN

    DECLARE v_id CHAR(36);



	-- Validar que el código no exista

    IF not EXISTS (SELECT 1 FROM user WHERE id = p_created_by) THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'El usuario no existe';

    END IF;



    -- Validar que el código no exista

    IF EXISTS (SELECT 1 FROM game_group WHERE code = p_code) THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'El código del grupo ya existe';

    END IF;

    

    



    -- Generar UUID para el grupo

    SET v_id = UUID();



    -- Insertar grupo

    INSERT INTO game_group (id, name, description, code, created_by)

    VALUES (v_id, p_name, p_description, p_code, p_created_by);



    -- Retornar el grupo creado

    SELECT * FROM game_group WHERE id = v_id;

END$$

CREATE PROCEDURE `sp_create_question` (IN `p_title` VARCHAR(100), IN `p_description` VARCHAR(255), IN `p_type_id` CHAR(36), IN `p_tip_note` TEXT, IN `p_ai_generated` TINYINT(1), IN `p_lang` CHAR(2), IN `p_feedback` TEXT)   BEGIN

    DECLARE v_id CHAR(36);

    DECLARE v_type_id CHAR(36);

    DECLARE v_type_count INT;



    -- ======================================

    -- NORMALIZACIONES

    -- ======================================

    SET p_type_id = NULLIF(TRIM(p_type_id), '');

    SET p_lang    = NULLIF(LOWER(TRIM(p_lang)), '');



    -- ======================================

    -- VALIDAR LANG (Luis)

    -- ======================================

    IF p_lang IS NULL OR p_lang NOT IN ('es','en') THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'lang no válido (es | en)';

    END IF;



    -- ======================================

    -- RESOLVER TYPE DESDE STRING (Miguel + Luis)

    -- ======================================

    IF p_type_id IS NOT NULL THEN



        SELECT COUNT(*)

        INTO v_type_count

        FROM question_type t

        WHERE t.type COLLATE utf8mb4_general_ci

              LIKE CONCAT('%', p_type_id, '%');



        IF v_type_count = 0 THEN

            SIGNAL SQLSTATE '45000'

                SET MESSAGE_TEXT = 'No existe un question_type que coincida con el valor enviado';

        END IF;



        IF v_type_count > 1 THEN

            SIGNAL SQLSTATE '45000'

                SET MESSAGE_TEXT = 'El valor enviado es ambiguo, coincide con más de un question_type';

        END IF;



        SELECT t.id

        INTO v_type_id

        FROM question_type t

        WHERE t.type COLLATE utf8mb4_general_ci

              LIKE CONCAT('%', p_type_id, '%')

        LIMIT 1;

    END IF;



    -- ======================================

    -- GENERAR ID QUESTION

    -- ======================================

    SET v_id = UUID();



    -- ======================================

    -- INSERTAR QUESTION (COMBINADO)

    -- ======================================

    INSERT INTO question (

        id,

        title,

        description,

        type_id,

        tip_note,

        ai_generated,

        lang,

        feedback

    ) VALUES (

        v_id,

        p_title,

        p_description,

        v_type_id,

        p_tip_note,

        p_ai_generated,

        p_lang,

        p_feedback

    );



    -- ======================================

    -- DEVOLVER REGISTRO CREADO

    -- ======================================

    SELECT

        id,

        title,

        description,

        type_id,

        tip_note,

        created_on,

        ai_generated,

        lang,

        feedback

    FROM question

    WHERE id = v_id;



end$$

CREATE PROCEDURE `sp_create_question_option` (IN `p_question_id` CHAR(36), IN `p_text_option` VARCHAR(255), IN `p_is_correct` TINYINT(1))   BEGIN

    DECLARE v_id CHAR(36);

    DECLARE v_option_count INT;

    DECLARE v_option_label CHAR(1);





    -- Validar que la pregunta exista

    IF NOT EXISTS (

        SELECT 1 FROM question WHERE id = p_question_id

    ) THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'El question_id no existe en question';

    END IF;

    

     -- Contar opciones EXISTENTES de ESA pregunta (bloqueo)

    SELECT COUNT(*)

    INTO v_option_count

    FROM question_option

    WHERE question_id = p_question_id

    FOR UPDATE;

     

       -- Máximo 4 opciones

    IF v_option_count >= 4 THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'La pregunta ya tiene el máximo de 4 opciones';

    END IF;

       

         -- Asignar literal

    SET v_option_label = CASE v_option_count

        WHEN 0 THEN 'A'

        WHEN 1 THEN 'B'

        WHEN 2 THEN 'C'

        WHEN 3 THEN 'D'

    END;

      /*   

         -- Validar solo una correcta

    IF p_is_correct = 1 AND EXISTS (

        SELECT 1 FROM question_option

        WHERE question_id = p_question_id

          AND is_correct = 1

    ) THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'La pregunta ya tiene una opción correcta';

    END IF;

*/

    -- Generar UUID para la opción

    SET v_id = UUID();



    -- Insertar opción

    INSERT INTO question_option (

        id,

        question_id,

        text_option,

        is_correct,

        option_label

    ) VALUES (

        v_id,

        p_question_id,

        p_text_option,

        p_is_correct,

        v_option_label

    );



    -- Devolver la opción creada

    SELECT 

        id,

        question_id,

        text_option,

        is_correct,

        created_on,

        option_label

    FROM question_option

    WHERE id = v_id;



END$$

CREATE PROCEDURE `sp_create_user` (IN `p_name` VARCHAR(100), IN `p_email` VARCHAR(100), IN `p_password` VARCHAR(255), IN `p_rol_id` CHAR(36), IN `p_status_id` CHAR(36))   BEGIN

    DECLARE v_id CHAR(36);



    -- Manejar error de email repetido (código 1062)

    DECLARE CONTINUE HANDLER FOR 1062

    BEGIN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'USR_DUPLICATE_EMAIL: El email ya existe';

    END;



    -- Validar rol

    IF NOT EXISTS (SELECT 1 FROM rol WHERE id = p_rol_id) THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'USR_INVALID_ROLE: El rol no existe';

    END IF;



    -- Validar status

    IF NOT EXISTS (SELECT 1 FROM status WHERE id = p_status_id) THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'USR_INVALID_STATUS: El estado no existe';

    END IF;



    -- Crear UUID

    SET v_id = UUID();



    -- Insertar usuario

    INSERT INTO user (id, name, email, password, rol_id, status_id, created_on)

    VALUES (v_id, p_name, p_email, p_password, p_rol_id, p_status_id, NOW());



    -- Devolver objeto completo del usuario creado

    SELECT 

        id,

        name,

        email,

        password,

        rol_id,

        status_id,

        created_on

    FROM user

    WHERE id = v_id;



END$$

CREATE PROCEDURE `sp_delete_group` (IN `p_group_id` CHAR(36))   begin

	

	 IF p_group_id IS NULL THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'group_id es requerido';

    END IF;

	 

	 IF NOT EXISTS (SELECT 1 FROM game_group WHERE id = p_group_id) THEN

            SIGNAL SQLSTATE '45000'

                SET MESSAGE_TEXT = 'USR_INVALID_STATUS: El grupo no existe';

        END IF;

	 

	 update game_group set status = 'inactive' where id = p_group_id;

	 

	 select 

	 	gp.id,

	 	gp.name

	 from game_group gp

	 where id = p_group_id;

	 

	

END$$

CREATE PROCEDURE `sp_delete_question` (IN `p_question_id` CHAR(36))   begin

	

	IF p_question_id IS NULL THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'id de pregunta es requerido';

    END IF;

	

	IF NOT EXISTS (SELECT 1 FROM question WHERE id = p_question_id) THEN

            SIGNAL SQLSTATE '45000'

                SET MESSAGE_TEXT = 'USR_INVALID_STATUS: La pregunta no existe';

        END IF;

	 

	update question set status = 'inactive' where id = p_question_id;

	 

	 select 

	 	q.id,

	 	q.description

	 from question q

	 where id = p_question_id;

	

END$$

CREATE PROCEDURE `sp_delete_user` (IN `p_id` CHAR(36))   BEGIN

    -- Validar que el usuario exista

    IF NOT EXISTS (SELECT 1 FROM user WHERE id = p_id) THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'USR_NOT_FOUND: El usuario no existe';

    END IF;



    -- Marcar como eliminado

    UPDATE user

    SET status_id = (SELECT id FROM status WHERE name = 'deleted')

    WHERE id = p_id;



    -- Devolver objeto final

    SELECT 

        id,

        name,

        email,

        password,

        rol_id,

        status_id,

        created_on

    FROM user

    WHERE id = p_id;



END$$

CREATE PROCEDURE `sp_game_crud` (IN `p_action` VARCHAR(10), IN `p_id` CHAR(36), IN `p_user_id` CHAR(36), IN `p_group_id` CHAR(36), IN `p_status` VARCHAR(100), IN `p_grade` INT, IN `p_started_on` DATETIME, IN `p_finished_on` DATETIME)   BEGIN
	
	declare p_gp_id char(36);
	DECLARE v_id CHAR(36);
    /* ==============================
       SELECT TODOS
       ============================== */
    IF p_action = 'SEL' THEN

        SELECT *
        FROM game
        WHERE status <> 'DELETED';

    /* ==============================
       SELECT POR ID
       ============================== */
    ELSEIF p_action = 'SEL_ID' THEN
     
        SELECT 
		    g.*,
		    gg.code,
		    COUNT(ua.id) AS total_answered,
		    (
		        SELECT COUNT(*)
		        FROM group_question gq
		        WHERE gq.group_id = g.group_id
		    ) AS total_questions
		    
		    
		FROM game g

	    left join game_group gg
	    	on gg.id = g.group_id
	    LEFT JOIN user_answer ua 
	       ON ua.game_id = g.id
	       AND ua.finished_on IS NOT null
	       and ua.is_correct = 1 	
		WHERE g.id = p_id
		  AND g.status <> 'DELETED'
		GROUP BY g.id;

    /* ==============================
       INSERT
       ============================== */
    ELSEIF p_action = 'INS' then
    	
    	 -- 1. Validar que exista
    IF NOT EXISTS (
        SELECT 1
        FROM group_question gq 
        join game_group gg on gq.group_id = gg.id
        WHERE gg.code = p_group_id
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'GM_NOT_FOUND: El grupo no tiene preguntas';
    END IF;
    
    
    	SET v_id = UUID();
    	
    	
    	
    	select gg.id  
    		into p_gp_id 
    		from game_group gg 
    		where  gg.code =  p_group_id and gg.status = 'active';
    	
    	    -- Si no se encontró el grupo
    IF p_gp_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'GP_NOT_FOUND: Grupo no encontrado o inactivo';
    END IF;
    	

        INSERT INTO game (
        	id,
            user_id,
            group_id,
            grade
            -- started_on,
            -- finished_on
        ) VALUES (
        	v_id,
            p_user_id,
            p_gp_id,
            p_grade
            -- p_started_on,
            -- p_finished_on
        );

        SELECT 
        	g.id,
            g.user_id,
            g.group_id,
            g.status,
            g.grade,
            g.created_on,
            g.started_on,
            g.finished_on
        FROM game g
        WHERE g.id = v_id;
    /* ==============================
       START
       ============================== */
        
        ELSEIF p_action = 'STR' THEN

    -- 1. Validar que exista
    IF NOT EXISTS (
        SELECT 1
        FROM game
        WHERE id = p_id
          AND user_id = p_user_id
          AND status <> 'DELETED'
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'GM_NOT_FOUND: El juego no existe';
    END IF;

    -- 2. Validar que no esté terminado
    IF EXISTS (
        SELECT 1
        FROM game
        WHERE id = p_id
          AND finished_on IS NOT NULL
          AND user_id = p_user_id
          AND status <> 'DELETED'
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'GM_FINISHED: El juego ya terminó';
    END IF;

    -- 3. SI YA ESTÁ INICIADO → SOLO SELECT
    IF EXISTS (
        SELECT 1
        FROM game
        WHERE id = p_id
          AND started_on IS NOT NULL
          AND user_id = p_user_id
          AND status <> 'DELETED'
    ) THEN

        SELECT
            g.id,
            g.user_id,
            g.group_id,
            g.status,
            g.grade,
            g.lifes,
            g.created_on,
            g.started_on,
            g.finished_on,
            gg.code
        FROM game g
       	left join game_group gg
	    	on gg.id = g.group_id
        WHERE g.id = p_id
          AND g.user_id = p_user_id;

    ELSE
        -- 4. NO iniciado → iniciar
        UPDATE game
        SET started_on = CURRENT_TIMESTAMP()
        WHERE id = p_id
          AND user_id = p_user_id
          AND status <> 'DELETED';

        SELECT
            g.id,
            g.user_id,
            g.group_id,
            g.status,
            g.grade,
            g.lifes,
            g.created_on,
            g.started_on,
            g.finished_on,
            gg.code
        FROM game g
        	    left join game_group gg
	    	on gg.id = g.group_id
        WHERE g.id = p_id
          AND g.user_id = p_user_id;
    END IF;

        /* ==============================
       FINISHED
       ============================== */
        
        ELSEIF p_action = 'FNS' then -- TERMINAR JUEGO
        
	    -- Verificar que exista el juego creado
	    IF NOT EXISTS (
	    	SELECT 1 FROM 
	    		game 
	    			WHERE user_id = p_user_id 
	    			and id = p_id 
	    			and started_on is not null 
	    			and created_on is not null 
	    			) THEN
		        SIGNAL SQLSTATE '45000'
		            SET MESSAGE_TEXT = 'USR_NOT_FOUND: Juego no existe o no creado';
	    END IF;
        

        UPDATE game
        SET
            finished_on  = CURRENT_TIMESTAMP(),
            status = 'abandoned' -- flag abandoned game (abort)

        WHERE id = p_id
          AND status <> 'DELETED';
        
        CALL sp_generate_user_group_personal_stats(p_id);

        SELECT 
            g.id,
            g.user_id,
            g.group_id,
            g.status,
            g.grade,
            g.created_on,
            g.started_on,
            g.finished_on,
            gg.code
        FROM game g
        	    left join game_group gg
	    	on gg.id = g.group_id
        WHERE g.id = p_id;




    /* ==============================
       UPDATE
       ============================== */
    ELSEIF p_action = 'UPD' THEN

        UPDATE game
        SET
            user_id     = p_user_id,
            group_id    = p_group_id,
            status      = p_status,
            grade       = p_grade,
            started_on  = p_started_on,
            finished_on = p_finished_on
        WHERE id = p_id
          AND status <> 'DELETED';

        SELECT ROW_COUNT() AS rows_affected;

    /* ==============================
       DELETE LÓGICO
       ============================== */
    ELSEIF p_action = 'DEL' THEN

        UPDATE game
        SET status = 'DELETED'
        WHERE id = p_id
          AND status <> 'DELETED';

        SELECT ROW_COUNT() AS rows_affected;
        
        
        ELSEIF p_action = 'IG' then -- iniciar juego

        UPDATE game
        set started_on  = NOW()

        WHERE id = p_id
          AND status <> 'DELETED';

        SELECT ROW_COUNT() AS rows_affected;


    /* ==============================
       ACCIÓN NO VÁLIDA
       ============================== */
    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acción no válida. Use SEL, SEL_ID, INS, UPD o DEL';
    END IF;

END$$

CREATE PROCEDURE `sp_generate_user_group_personal_stats` (IN `p_game_id` CHAR(36))   BEGIN

    DECLARE v_user_id CHAR(36);

    DECLARE v_group_id CHAR(36);

    DECLARE v_username VARCHAR(36);

    DECLARE v_question_quantity INT;

    DECLARE v_correct_answers INT;

    DECLARE v_wrong_answers INT;

    DECLARE v_lifes INT;

    DECLARE v_score INT;

    DECLARE v_total_seconds INT;

    DECLARE v_status VARCHAR(15);

    DECLARE v_exists INT DEFAULT 0;

	declare v_name varchar(100);

	DECLARE v_option_a INT DEFAULT 0;

	DECLARE v_option_b INT DEFAULT 0;

	DECLARE v_option_c INT DEFAULT 0;

	DECLARE v_option_d INT DEFAULT 0;
	
	DECLARE v_current_game_status VARCHAR(20); -- n



    /* ===========================

       VALIDAR JUEGO FINALIZADO

       =========================== */

    IF NOT EXISTS (

        SELECT 1

        FROM game

        WHERE id = p_game_id

          AND finished_on IS NOT NULL

    ) THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'El juego no ha finalizado';

    END IF;



    /* ===========================

       VALIDAR QUE NO EXISTA YA

       =========================== */

    SELECT COUNT(*)

    INTO v_exists

    FROM user_group_personal_stats ugps

    JOIN game g ON g.group_id = ugps.group_id

    WHERE ugps.game_id = p_game_id

      AND ugps.user_id = g.user_id;



    IF v_exists = 0 THEN



        /* ===========================

           DATOS BASE DEL JUEGO

           =========================== */

        SELECT 

            g.user_id,

            g.group_id,

            g.lifes,

            g.grade,

            TIMESTAMPDIFF(SECOND, g.started_on, g.finished_on),
            
            g.status

        INTO

            v_user_id,

            v_group_id,

            v_lifes,

            v_score,

            v_total_seconds,
            
            v_current_game_status -- estado previo del juego

        FROM game g

        WHERE g.id = p_game_id;



        /* Username */

      /*  SELECT username

        INTO v_username

        FROM user

        WHERE id = v_user_id;

*/

        SELECT name

        INTO v_name

        FROM user

        WHERE id = v_user_id;

        

        /* ===========================

           MÉTRICAS

           =========================== */

        SELECT COUNT(*)

        INTO v_question_quantity

        FROM group_question

        WHERE group_id = v_group_id;



        SELECT COUNT(*)

        INTO v_correct_answers

        FROM user_answer

        WHERE game_id = p_game_id

          AND is_correct = 1;



        SELECT COUNT(*)

        INTO v_wrong_answers

        FROM user_answer

        WHERE game_id = p_game_id

          AND is_correct = 0;



        /* ===========================

           ESTADO FINAL

           =========================== */
          
        IF v_current_game_status = 'abandoned' then
        
        	SET v_status = 'abandoned';

        ELSEIF v_lifes = 0 THEN

            SET v_status = 'failed';

        ELSE

            SET v_status = 'finished';

        END IF;

        

        SELECT

		    SUM(CASE WHEN qo.option_label = 'A' THEN 1 ELSE 0 END),

		    SUM(CASE WHEN qo.option_label = 'B' THEN 1 ELSE 0 END),

		    SUM(CASE WHEN qo.option_label = 'C' THEN 1 ELSE 0 END),

		    SUM(CASE WHEN qo.option_label = 'D' THEN 1 ELSE 0 END)

		INTO

		    v_option_a,

		    v_option_b,

		    v_option_c,

		    v_option_d

		FROM user_answer ua

		JOIN question_option qo ON qo.id = ua.q_option_id

		WHERE ua.game_id = p_game_id;





        /* ===========================

           INSERTAR ESTADÍSTICAS

           =========================== */

        INSERT INTO user_group_personal_stats (

            id,

            user_id,

           -- username,

            group_id,

            question_quantity,

            correct_answers,

            wrong_answers,

            lives_number,

            score,

            total_time,

            status,

            created_on,

            name,

            game_id

            /*option_a,

		    option_b,

		    option_c,

		    option_d*/

        ) VALUES (

            UUID(),

            v_user_id,

           -- v_username,

            v_group_id,

            v_question_quantity,

            v_correct_answers,

            v_wrong_answers,

            v_lifes,

            v_score,

            v_total_seconds,

            v_status,

            NOW(),

            v_name,

            p_game_id

           /* v_option_a,

		    v_option_b,

		    v_option_c,

		    v_option_d */

        );



    END IF;



END$$

CREATE PROCEDURE `sp_get_all_group_questions` (IN `p_id` CHAR(36))   begin

	

	select

		q.id,

        q.title,

        q.description,

        qt.type,

        q.tip_note,

        qo.id as option_id,

        qo.text_option,

        qo.is_correct, 

        q.created_on,

        q.feedback,

        q.lang

		

	from group_question gq

	join question q

	on q.id = gq.question_id

	JOIN question_option qo 

        ON q.id = qo.question_id 

    JOIN question_type qt 

        ON q.type_id = qt.id

	where gq.group_id = p_id;

end$$

CREATE PROCEDURE `sp_get_all_questions` ()   BEGIN

    SELECT 

        q.id,

        q.title,

        q.description,

        qt.type,

        q.tip_note,

        qo.text_option,

        qo.is_correct, 

        q.created_on,
        q.ai_generated,
        q.lang,
        q.feedback,
        q.status

    FROM question q

    JOIN question_option qo 

        ON q.id = qo.question_id 

    JOIN question_type qt 

        ON q.type_id = qt.id
	where q.status = 'active'
	order by q.created_on desc ;

END$$

CREATE PROCEDURE `sp_get_all_roles` ()   begin

	SELECT 

        id,

        name,

        description

    FROM rol;

		

END$$

CREATE PROCEDURE `sp_get_all_status` ()   begin

	SELECT 

        id,

        name,

        description

    FROM status;

END$$

CREATE PROCEDURE `sp_get_group_by_code` (IN `p_group_code` CHAR(10))   begin

	

	-- Validar que exista el usuario

    IF NOT EXISTS (SELECT 1 FROM game_group WHERE code = p_group_code ) THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'GRP_NOT_FOUND: El grupo no existe';

    END IF;

	

	-- Validar que exista el usuario

    IF EXISTS (SELECT 1 FROM game_group WHERE code = p_group_code and status = 'inactive' ) THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'GRP_NOT_AVAILABLE: El grupo fue inavilitado';

    END IF;

	

	-- Validar que exista el usuario

    IF EXISTS (SELECT 1 FROM game_group WHERE code = p_group_code and status = 'deleted' ) THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'GRP_NOT_AVAILABLE: El grupo ya no esta disponible';

    END IF;

	

	select 

	gg.id, 

	gg.name,

	gg.description,

	gg.created_on,

	gg.code,

	gg.status,

	gg.created_by

	from game_group gg 

	where gg.code = p_group_code

		and gg.status <> 'deleted';

	

END$$

CREATE PROCEDURE `sp_get_group_questions` (IN `p_id` CHAR(36))   begin

	select



	gq.created_on as question_added_on,



	q.id as question_id,



	q.description as question,

	

	q.ai_generated



		



	from group_question gq



	join question q



	on q.id = gq.question_id



	where gq.group_id = p_id;





END$$

CREATE PROCEDURE `sp_get_group_question_stats` (IN `p_group_id` CHAR(36))   BEGIN

    SELECT 

        gqs.id,

        gqs.group_id,

        gqs.question_id,

        gqs.total_answers,

        gqs.correct_answers,

        gqs.incorrect_answers,

        gqs.avg_response_time,

        gqs.total_time,

        gqs.accuracy,

        gqs.created_on,

        gqs.updated_on, 

        gqs.opt_a,

        gqs.opt_b,

        gqs.opt_c,

        gqs.opt_d,
        
        
        (
            SELECT qo.option_label
            FROM question_option qo
            WHERE qo.question_id = gqs.question_id
              AND qo.is_correct = 1
            LIMIT 1
        ) AS correct_option_letter
-- letra correspondiente a la opción correcta de la pregunta

    FROM group_question_stats gqs

    WHERE (p_group_id IS NULL OR gqs.group_id = p_group_id);

    -- ORDER BY gqs.created_on DESC;

END$$

CREATE PROCEDURE `sp_get_question_by_id` (IN `p_question_id` CHAR(36))   BEGIN

    IF p_question_id IS NULL OR TRIM(p_question_id) = '' THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'question_id es requerido';

    END IF;



    IF NOT EXISTS (SELECT 1 FROM question WHERE id = p_question_id AND status = 'active') THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'Pregunta no existe';

    END IF;



    SELECT

        q.id,

        q.title,

        q.description,

        q.tip_note,

        q.lang,

        q.feedback,

        qo.id AS option_id,

        qo.text_option,

        qo.is_correct

    FROM question q

    JOIN question_option qo ON qo.question_id = q.id

    WHERE q.id = p_question_id

      AND q.status = 'active'

    ORDER BY qo.created_on ASC;

END$$

CREATE PROCEDURE `sp_get_users` (IN `p_q` VARCHAR(100), IN `p_status_name` CHAR(36))   BEGIN

      SELECT 



        u.id,



        u.name,



        u.email,



        u.password,



        rl.name as rol,



        s.name as status,



        u.created_on



        



    FROM user u



    join status s



    on s.id = u.status_id



    join rol rl on rl.id = u.rol_id



    WHERE



    (

            p_q IS NULL OR p_q = '' OR

            u.name  LIKE CONCAT('%', p_q, '%') OR

            u.email LIKE CONCAT('%', p_q, '%')

    )



AND



    (p_status_name IS NULL OR p_status_name = '' OR s.name = p_status_name);





END$$

CREATE PROCEDURE `sp_get_user_by_email` (IN `p_email` VARCHAR(100))   BEGIN

  SELECT

    u.id,

    u.name,

    u.email,

    u.password,

    rl.name AS rol,

    s.name  AS status

  FROM user u

  JOIN rol rl   ON rl.id = u.rol_id

  JOIN status s ON s.id  = u.status_id

  WHERE u.email = p_email

  LIMIT 1;

END$$

CREATE PROCEDURE `sp_get_user_by_id` (IN `p_id` CHAR(36))   BEGIN

    -- Validar que exista el usuario

    IF NOT EXISTS (SELECT 1 FROM user WHERE id = p_id) THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'USR_NOT_FOUND: El usuario no existe';

    END IF;



    SELECT 

        u.id,

        u.name,

        u.email,

        u.password,

        u.rol_id,

        u.status_id,

        u.created_on

    FROM user u

    join rol r

    on r.id = u.rol_id

    join status s

    on s.id = u.status_id

    WHERE u.id = p_id;

END$$

CREATE PROCEDURE `sp_get_user_profile` (IN `p_user_id` CHAR(36))   BEGIN

  SELECT

    u.id,

    u.name,

    u.email,

    r.name AS rol,

    s.name AS status

  FROM user u

  LEFT JOIN rol r ON r.id = u.rol_id

  LEFT JOIN status s ON s.id = u.status_id

  WHERE u.id = p_user_id

  LIMIT 1;

END$$

CREATE PROCEDURE `sp_list_game_groups` ()   BEGIN

    SELECT 

        id,
        name,

        code,

        description,

        created_on,
        status

    FROM game_group

    where status <> 'deleted'

    ORDER BY created_on DESC;

END$$

CREATE PROCEDURE `sp_question_group_accion` (IN `accion_name` VARCHAR(20), IN `p_group_id` CHAR(36), IN `p_question_id` CHAR(36))   BEGIN



    -- Acción: OBTENER

	

    IF accion_name = 'OF' then  -- obtener faltantes - preguntas que faltan 

        

        SELECT q.*

        FROM question q

        where q.status = 'active'



        and NOT EXISTS (

            SELECT 1

            FROM group_question gq

            WHERE gq.question_id = q.id

              AND gq.group_id = p_group_id

              

        )

        order by q.created_on desc ;

        

         -- Acción: ACTUALIZAR

    /*ELSEIF accion_name = 'OR' then --obtener registrados 

        

        select q.*

        from group_question gq

        join question q

        on gq.question_id = q.id

        where gq.group_id = p_group_id

*/

    ELSE

        SIGNAL SQLSTATE '45000'

        SET MESSAGE_TEXT = 'Acción no válida';

    END IF;



END$$

CREATE PROCEDURE `sp_reactivate_question` (IN `p_question_id` CHAR(36))   BEGIN

    IF p_question_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'id de pregunta requerido';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM question WHERE id = p_question_id) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'INVALID_STATUS: La pregunta no existe';
    END IF;

    -- si ya está activa, no permite reactivar
    IF EXISTS (SELECT 1 FROM question WHERE id = p_question_id AND status = 'active') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'El estado actual de la pregunta es activa';
    END IF;

    UPDATE question
    SET status = 'active'
    WHERE id = p_question_id;

    SELECT
        q.id,
        q.description
    FROM question q
    WHERE q.id = p_question_id;

END$$

CREATE PROCEDURE `sp_register_group_question` (IN `p_group_id` CHAR(36), IN `p_question_id` CHAR(36))   BEGIN

    -- Validación: group_id requerido

    IF p_group_id IS NULL THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'group_id es requerido';

    END IF;



    -- Validación: question_id requerido

    IF p_question_id IS NULL THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'question_id es requerido';

    END IF;



    -- Validar que el grupo exista

    IF NOT EXISTS (SELECT 1 FROM game_group WHERE id = p_group_id) THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'El grupo no existe';

    END IF;



    -- Validar que la pregunta exista

    IF NOT EXISTS (SELECT 1 FROM question WHERE id = p_question_id) THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'La pregunta no existe';

    END IF;



    -- Validar que la relación no exista (llave compuesta)

    IF EXISTS (

        SELECT 1

        FROM group_question

        WHERE group_id = p_group_id

          AND question_id = p_question_id

    ) THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'Esta pregunta ya está asignada a este grupo';

    END IF;



    -- Insertar relación

    INSERT INTO group_question (

        group_id, question_id, created_on

    ) VALUES (

        p_group_id, p_question_id, NOW()

    );



    -- Devolver la fila insertada

    SELECT *

    FROM group_question

    WHERE group_id = p_group_id

      AND question_id = p_question_id;

END$$

CREATE PROCEDURE `sp_register_group_questions_bulk` (IN `p_group_id` CHAR(36), IN `p_question_ids` TEXT, IN `p_delete_ids` TEXT)   BEGIN

    DECLARE v_question_id CHAR(36);

    DECLARE v_delete_id CHAR(36);

    DECLARE v_error_msg VARCHAR(255);



    /* ================= VALIDACIONES ================= */



    IF p_group_id IS NULL THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'group_id es requerido';

    END IF;



    IF NOT EXISTS (SELECT 1 FROM game_group WHERE id = p_group_id) THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'El grupo no existe';

    END IF;



    START TRANSACTION;



    /* ================= ELIMINAR IDS ================= */



    IF p_delete_ids IS NOT NULL AND p_delete_ids <> '' THEN



        delete_loop: LOOP



            SET v_delete_id = SUBSTRING_INDEX(p_delete_ids, ',', 1);



            SET p_delete_ids = IF(

                LOCATE(',', p_delete_ids) > 0,

                SUBSTRING(p_delete_ids, LOCATE(',', p_delete_ids) + 1),

                NULL

            );



            DELETE FROM group_question

            WHERE group_id = p_group_id

              AND question_id = v_delete_id;



            IF p_delete_ids IS NULL THEN

                LEAVE delete_loop;

            END IF;



        END LOOP;



    END IF;



    /* ================= INSERTAR IDS ================= */



    IF p_question_ids IS NOT NULL AND p_question_ids <> '' THEN



        insert_loop: LOOP



            SET v_question_id = SUBSTRING_INDEX(p_question_ids, ',', 1);



            SET p_question_ids = IF(

                LOCATE(',', p_question_ids) > 0,

                SUBSTRING(p_question_ids, LOCATE(',', p_question_ids) + 1),

                NULL

            );



            IF NOT EXISTS (SELECT 1 FROM question WHERE id = v_question_id) THEN

                SET v_error_msg = CONCAT('La pregunta no existe: ', v_question_id);

                SIGNAL SQLSTATE '45000'

                    SET MESSAGE_TEXT = v_error_msg;

            END IF;



            IF EXISTS (

                SELECT 1

                FROM group_question

                WHERE group_id = p_group_id

                  AND question_id = v_question_id

            ) THEN

                SET v_error_msg = 'Esta pregunta ya está asignada al grupo';

                SIGNAL SQLSTATE '45000'

                    SET MESSAGE_TEXT = v_error_msg;

            END IF;



            INSERT INTO group_question (

                group_id, question_id, created_on

            ) VALUES (

                p_group_id, v_question_id, NOW()

            );



            IF p_question_ids IS NULL THEN

                LEAVE insert_loop;

            END IF;



        END LOOP;



    END IF;



    COMMIT;



    /* ================= RESULTADO ================= */



    SELECT *

    FROM group_question

    WHERE group_id = p_group_id;



END$$

CREATE PROCEDURE `sp_register_user_answer` (IN `p_answer_id` CHAR(36), IN `p_group_id` CHAR(36), IN `p_user_id` CHAR(36), IN `p_question_id` CHAR(36), IN `p_q_option_id` CHAR(36), IN `p_game_id` CHAR(36))   BEGIN

    DECLARE v_id CHAR(36);

    DECLARE v_is_correct TINYINT(1);

    DECLARE v_finished DATETIME;

    DECLARE v_elapsed_seconds INT;

    DECLARE v_question_answered INT;

    DECLARE v_question_quantity INT;

    DECLARE v_lifes INT;



    /* ===========================

       VALIDACIONES GENERALES

       =========================== */

    IF p_group_id IS NULL THEN

        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'group id es requerido';

    END IF;



    IF p_user_id IS NULL THEN

        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'user id es requerido';

    END IF;



    IF p_question_id IS NULL THEN

        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'question id es requerido';

    END IF;



    IF NOT EXISTS (SELECT 1 FROM `user` WHERE id = p_user_id) THEN

        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuario no encontrado';

    END IF;



    IF NOT EXISTS (SELECT 1 FROM question WHERE id = p_question_id) THEN

        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pregunta no encontrada';

    END IF;



    IF EXISTS (

        SELECT 1

        FROM game

        WHERE id = p_game_id

          AND finished_on IS NOT NULL

    ) THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'Este juego ya fue finalizado';

    END IF;



    /* ==================================================

       FLUJO PRINCIPAL

       ================================================== */

    IF p_answer_id IS NULL THEN



        /* ----------------------------------------------

           BUSCAR RESPUESTA ACTIVA

           ---------------------------------------------- */

    	SET v_id = NULL;

    

        SELECT id

		INTO v_id

		FROM user_answer

		WHERE user_id = p_user_id

		  AND question_id = p_question_id

		  AND group_id = p_group_id

		  AND game_id = p_game_id

		  AND finished_on IS NULL

		LIMIT 1

		FOR UPDATE;



        IF v_id IS NOT NULL THEN



            /* Ya existe una respuesta sin finalizar */

            SELECT * FROM user_answer WHERE id = v_id;



        ELSE



            /* Si ya existe respuesta finalizada → error */

            IF EXISTS (

                SELECT 1

                FROM user_answer

                WHERE user_id = p_user_id

                  AND question_id = p_question_id

                  AND group_id = p_group_id

                  AND game_id = p_game_id

                  AND finished_on IS NOT NULL

            ) THEN

                SIGNAL SQLSTATE '45000'

                    SET MESSAGE_TEXT = 'El usuario ya respondió esta pregunta';

            END IF;



            /* Crear nueva respuesta */

            SET v_id = UUID();



            INSERT INTO user_answer (

                id, group_id, user_id, question_id, started_on, game_id

            ) VALUES (

                v_id, p_group_id, p_user_id, p_question_id, NOW(), p_game_id

            );



            SELECT * FROM user_answer WHERE id = v_id;



        END IF;



    ELSE

    /* ==================================================

       FINALIZAR RESPUESTA

       ================================================== */



        /* Validar propiedad y coherencia */

        IF NOT EXISTS (

            SELECT 1

            FROM user_answer

            WHERE id = p_answer_id

              AND user_id = p_user_id

              AND question_id = p_question_id

        ) THEN

            SIGNAL SQLSTATE '45000'

                SET MESSAGE_TEXT = 'Respuesta no válida o no autorizada';

        END IF;



        IF p_q_option_id IS NULL THEN

            SIGNAL SQLSTATE '45000'

                SET MESSAGE_TEXT = 'Debe seleccionar una opción';

        END IF;



        IF NOT EXISTS (

            SELECT 1

            FROM question_option

            WHERE id = p_q_option_id

              AND question_id = p_question_id

        ) THEN

            SIGNAL SQLSTATE '45000'

                SET MESSAGE_TEXT = 'La opción no pertenece a la pregunta';

        END IF;



        /* Evaluar respuesta */

        SELECT CAST(is_correct AS UNSIGNED)

        INTO v_is_correct

        FROM question_option

        WHERE id = p_q_option_id;



        SET v_finished = NOW();



        SELECT TIMESTAMPDIFF(

            SECOND,

            started_on,

            v_finished

        )

        INTO v_elapsed_seconds

        FROM user_answer

        WHERE id = p_answer_id;



        /* Actualizar juego */

        IF v_is_correct = 1 then

        

            IF v_elapsed_seconds < 7 THEN

	        UPDATE game

	        SET grade = grade + 150

	        WHERE id = p_game_id;

	

		    ELSEIF v_elapsed_seconds BETWEEN 7 AND 12 THEN

		        UPDATE game

		        SET grade = grade + 100

		        WHERE id = p_game_id;

		

		    ELSE

		        UPDATE game

		        SET grade = grade + 80

		        WHERE id = p_game_id;

		    END IF;

            

            

        ELSE

            SELECT lifes

            INTO v_lifes

            FROM game

            WHERE id = p_game_id;



            IF v_lifes <= 1 THEN

                UPDATE game

                SET lifes = 0,

                    finished_on = NOW()

                WHERE id = p_game_id;

            ELSE

                UPDATE game

                SET lifes = lifes - 1

                WHERE id = p_game_id;

            END IF;

        END IF;



        /* Guardar respuesta */

        UPDATE user_answer

        SET 

            q_option_id = p_q_option_id,

            is_correct = v_is_correct,

            finished_on = v_finished

        WHERE id = p_answer_id;



        /* Estadísticas */

        CALL sp_update_group_question_stats(

            p_group_id,

            p_question_id,

            v_is_correct,

            v_elapsed_seconds,

            p_q_option_id

        );



        CALL sp_update_group_question_option_stats(

            p_group_id,

            p_question_id,

            p_q_option_id

        );



        /* Finalizar juego si ya respondió todo */

        SELECT COUNT(*)

        INTO v_question_quantity

        FROM game g

        JOIN group_question gq ON gq.group_id = g.group_id

        WHERE g.id = p_game_id;



        SELECT COUNT(*)

        INTO v_question_answered

        FROM user_answer

        WHERE game_id = p_game_id

          AND finished_on IS NOT NULL;



        IF v_question_answered >= v_question_quantity THEN

            UPDATE game

            SET finished_on = NOW()

            WHERE id = p_game_id

              AND finished_on IS NULL;

        

        	CALL sp_generate_user_group_personal_stats(p_game_id);

        END IF;



        SELECT * FROM user_answer WHERE id = p_answer_id;



    END IF;



END$$

CREATE PROCEDURE `sp_register_user_basic` (IN `p_name` VARCHAR(100), IN `p_email` VARCHAR(100), IN `p_password` VARCHAR(255))   BEGIN

  DECLARE v_id CHAR(36);

  DECLARE v_role_id CHAR(36);

  DECLARE v_status_id CHAR(36);



  -- email duplicado

  DECLARE CONTINUE HANDLER FOR 1062

  BEGIN

    SIGNAL SQLSTATE '45000'

      SET MESSAGE_TEXT = 'USR_DUPLICATE_EMAIL: La dirección de correo ya se encuentra en uso';

  END;



  SELECT id INTO v_role_id FROM rol WHERE name = 'participant' LIMIT 1;

  IF v_role_id IS NULL THEN

    SIGNAL SQLSTATE '45000'

      SET MESSAGE_TEXT = 'USR_INVALID_ROLE: Rol participant no existe';

  END IF;



  SELECT id INTO v_status_id FROM status WHERE name = 'active' LIMIT 1;

  IF v_status_id IS NULL THEN

    SIGNAL SQLSTATE '45000'

      SET MESSAGE_TEXT = 'USR_INVALID_STATUS: Status active no existe';

  END IF;



  SET v_id = UUID();



  INSERT INTO user (id, name, email, password, rol_id, status_id, created_on)

  VALUES (v_id, p_name, p_email, p_password, v_role_id, v_status_id, NOW());



  -- no devuelve password

  SELECT id, name, email, v_role_id AS rol_id, v_status_id AS status_id, created_on

  FROM user

  WHERE id = v_id;

END$$

CREATE PROCEDURE `sp_update_game_group` (IN `p_id` CHAR(36), IN `p_name` VARCHAR(255), IN `p_description` VARCHAR(255), IN `p_code` CHAR(6), IN `p_status` VARCHAR(100))   BEGIN

    UPDATE game_group

    SET

        name        = COALESCE(p_name, name),

        description = COALESCE(p_description, description),

        code        = COALESCE(p_code, code),

        status      = COALESCE(p_status, status)

    WHERE id = p_id;

end$$

CREATE PROCEDURE `sp_update_group_question_option_stats` (IN `p_group_id` CHAR(36), IN `p_question_id` CHAR(36), IN `p_q_option_id` CHAR(36))   BEGIN

    -- crear registro si no existe

    INSERT INTO group_question_option_stats (

        id, group_id, question_id, q_option_id, is_correct_option

    )

    SELECT 

        UUID(), 

        p_group_id, 

        p_question_id, 

        p_q_option_id,

        (SELECT is_correct FROM question_option WHERE id = p_q_option_id)

    WHERE NOT EXISTS (

        SELECT 1 FROM group_question_option_stats

        WHERE group_id = p_group_id 

          AND question_id = p_question_id

          AND q_option_id = p_q_option_id

    );



    -- incrementar contador

    UPDATE group_question_option_stats

    SET total_selected = total_selected + 1

    WHERE group_id = p_group_id

      AND question_id = p_question_id

      AND q_option_id = p_q_option_id;

END$$

CREATE PROCEDURE `sp_update_group_question_stats` (IN `p_group_id` CHAR(36), IN `p_question_id` CHAR(36), IN `p_is_correct` TINYINT(1), IN `p_elapsed_seconds` DECIMAL(10,2), IN `p_q_option_id` CHAR(36))   begin

	DECLARE v_option_label CHAR(1);



    -- si no existe el registro, crearlo

    INSERT INTO group_question_stats (

        id, group_id, question_id

    ) SELECT UUID(), p_group_id, p_question_id

      WHERE NOT EXISTS (

        SELECT 1 FROM group_question_stats

        WHERE group_id = p_group_id AND question_id = p_question_id

      );

    

    SELECT option_label

INTO v_option_label

FROM question_option

WHERE id = p_q_option_id;





    -- actualizar estadísticas

    UPDATE group_question_stats

    SET 

        total_answers = total_answers + 1,

        correct_answers = correct_answers + IF(p_is_correct = 1, 1, 0),

        incorrect_answers = incorrect_answers + IF(p_is_correct = 0, 1, 0),

        total_time = total_time + p_elapsed_seconds,

        avg_response_time = total_time / total_answers,

        

        

        opt_a = opt_a + IF(v_option_label = 'A', 1, 0),

	    opt_b = opt_b + IF(v_option_label = 'B', 1, 0),

	    opt_c = opt_c + IF(v_option_label = 'C', 1, 0),

	    opt_d = opt_d + IF(v_option_label = 'D', 1, 0)

        

    WHERE group_id = p_group_id

      AND question_id = p_question_id;

END$$

CREATE PROCEDURE `sp_update_question` (IN `p_id` CHAR(36), IN `p_title` VARCHAR(100), IN `p_description` VARCHAR(255), IN `p_type_id` CHAR(36), IN `p_tip_note` TEXT, IN `p_lang` CHAR(2), IN `p_feedback` TEXT)   BEGIN

    DECLARE v_type_id CHAR(36);

    DECLARE v_type_count INT;



    IF p_id IS NULL OR TRIM(p_id) = '' THEN

        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'p_id es requerido';

    END IF;



    IF NOT EXISTS (SELECT 1 FROM question WHERE id = p_id AND status = 'active') THEN

        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pregunta no encontrada';

    END IF;



    SET p_type_id = NULLIF(TRIM(p_type_id), '');

    SET p_lang = NULLIF(LOWER(TRIM(p_lang)), '');



    IF p_lang IS NULL OR p_lang NOT IN ('es','en') THEN

        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'lang no valido (es | en)';

    END IF;



    IF p_type_id IS NOT NULL THEN

        SELECT COUNT(*)

        INTO v_type_count

        FROM question_type t

        WHERE t.type COLLATE utf8mb4_general_ci

              LIKE CONCAT('%', p_type_id, '%');



        IF v_type_count = 0 THEN

            SIGNAL SQLSTATE '45000'

                SET MESSAGE_TEXT = 'No existe un question_type que coincida con el valor enviado';

        END IF;



        IF v_type_count > 1 THEN

            SIGNAL SQLSTATE '45000'

                SET MESSAGE_TEXT = 'El valor enviado es ambiguo, coincide con mas de un question_type';

        END IF;



        SELECT t.id

        INTO v_type_id

        FROM question_type t

        WHERE t.type COLLATE utf8mb4_general_ci

              LIKE CONCAT('%', p_type_id, '%')

        LIMIT 1;

    END IF;



    UPDATE question

    SET

        title = p_title,

        description = p_description,

        type_id = v_type_id,

        tip_note = p_tip_note,

        lang = p_lang,

        feedback = p_feedback

    WHERE id = p_id;



    SELECT

        id, title, description, type_id, tip_note, created_on, ai_generated, lang, feedback

    FROM question

    WHERE id = p_id;

END$$

CREATE PROCEDURE `sp_update_question_option` (IN `p_id` CHAR(36), IN `p_text_option` VARCHAR(255), IN `p_is_correct` TINYINT(1))   BEGIN

  -- Validar ID

  IF p_id IS NULL OR TRIM(p_id) = '' THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'p_id es requerido';

  END IF;



  -- Validar existencia

  IF NOT EXISTS (SELECT 1 FROM question_option WHERE id = p_id) THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Opción no encontrada';

  END IF;



  -- Validar is_correct

  IF p_is_correct IS NULL OR p_is_correct NOT IN (0, 1) THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'p_is_correct debe ser 0 o 1';

  END IF;



  UPDATE question_option

  SET

    text_option = p_text_option,

    is_correct = p_is_correct

  WHERE id = p_id;



  -- Confirmar actualización efectiva





  SELECT id, question_id, text_option, is_correct

  FROM question_option

  WHERE id = p_id;

END$$

CREATE PROCEDURE `sp_update_user` (IN `p_id` CHAR(36), IN `p_name` VARCHAR(100), IN `p_email` VARCHAR(100), IN `p_password` VARCHAR(255), IN `p_rol_id` CHAR(36), IN `p_status_id` CHAR(36))   BEGIN

    -- Verificar que exista el usuario

    IF NOT EXISTS (SELECT 1 FROM user WHERE id = p_id) THEN

        SIGNAL SQLSTATE '45000'

            SET MESSAGE_TEXT = 'USR_NOT_FOUND: El usuario no existe';

    END IF;





    -- Validación de email (si se envía)

    IF p_email IS NOT NULL THEN

        IF EXISTS(

            SELECT 1 

            FROM user 

            WHERE email = p_email AND id <> p_id

        ) THEN

            SIGNAL SQLSTATE '45000'

                SET MESSAGE_TEXT = 'USR_DUPLICATE_EMAIL: El email ya existe';

        END IF;

    END IF;





    -- Validar rol solo si se envía

    IF p_rol_id IS NOT NULL THEN

        IF NOT EXISTS (SELECT 1 FROM rol WHERE id = p_rol_id) THEN

            SIGNAL SQLSTATE '45000'

                SET MESSAGE_TEXT = 'USR_INVALID_ROLE: El rol no existe';

        END IF;

    END IF;



    -- Validar status solo si se envía

    IF p_status_id IS NOT NULL THEN

        IF NOT EXISTS (SELECT 1 FROM status WHERE id = p_status_id) THEN

            SIGNAL SQLSTATE '45000'

                SET MESSAGE_TEXT = 'USR_INVALID_STATUS: El estado no existe';

        END IF;

    END IF;





    -- Actualizar dinámicamente solo lo enviado

    UPDATE user

    SET

        name = COALESCE(p_name, name),

        email = COALESCE(p_email, email),

        password = COALESCE(p_password, password),

        rol_id = COALESCE(p_rol_id, rol_id),

        status_id = COALESCE(p_status_id, status_id)

    WHERE id = p_id;





    -- Devolver objeto actualizado

    SELECT 

        id,

        name,

        email,

        password,

        rol_id,

        status_id,

        created_on

    FROM user

    WHERE id = p_id;



END$$

CREATE PROCEDURE `sp_update_user_password` (IN `p_id` CHAR(36), IN `p_password` VARCHAR(255))   BEGIN

  IF p_id IS NULL OR p_id = '' THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'USER_ID_REQUIRED';

  END IF;



  IF p_password IS NULL OR p_password = '' THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PASSWORD_REQUIRED';

  END IF;



  IF NOT EXISTS (SELECT 1 FROM user WHERE id = p_id) THEN

    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'USR_NOT_FOUND';

  END IF;



  UPDATE user SET password = p_password WHERE id = p_id;



  SELECT ROW_COUNT() AS affected;



END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `game`
--

CREATE TABLE `game` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `user_id` char(36) DEFAULT NULL,
  `group_id` char(36) NOT NULL,
  `status` varchar(100) NOT NULL DEFAULT 'active',
  `grade` int(11) DEFAULT NULL,
  `created_on` datetime NOT NULL DEFAULT current_timestamp(),
  `started_on` datetime DEFAULT NULL,
  `finished_on` datetime DEFAULT NULL,
  `lifes` int(2) DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Estructura de tabla para la tabla `game_group`
--

CREATE TABLE `game_group` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_on` datetime NOT NULL DEFAULT current_timestamp(),
  `code` char(6) NOT NULL,
  `status` varchar(100) DEFAULT 'active',
  `created_by` char(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Estructura de tabla para la tabla `group_question`
--

CREATE TABLE `group_question` (
  `group_id` char(36) NOT NULL,
  `question_id` char(36) NOT NULL,
  `created_on` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Estructura de tabla para la tabla `group_question_option_stats`
--

CREATE TABLE `group_question_option_stats` (
  `id` char(36) NOT NULL,
  `group_id` char(36) NOT NULL,
  `question_id` char(36) NOT NULL,
  `q_option_id` char(36) NOT NULL,
  `total_selected` int(11) DEFAULT 0,
  `is_correct_option` tinyint(1) NOT NULL,
  `created_on` datetime DEFAULT current_timestamp(),
  `updated_on` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Estructura de tabla para la tabla `group_question_stats`
--

CREATE TABLE `group_question_stats` (
  `id` char(36) NOT NULL,
  `group_id` char(36) NOT NULL,
  `question_id` char(36) NOT NULL,
  `total_answers` int(11) DEFAULT 0,
  `correct_answers` int(11) DEFAULT 0,
  `incorrect_answers` int(11) DEFAULT 0,
  `avg_response_time` decimal(10,2) DEFAULT 0.00,
  `total_time` decimal(10,2) DEFAULT 0.00,
  `accuracy` decimal(5,2) GENERATED ALWAYS AS (`correct_answers` / nullif(`total_answers`,0) * 100) STORED,
  `created_on` datetime DEFAULT current_timestamp(),
  `updated_on` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `opt_a` int(3) DEFAULT 0,
  `opt_b` int(3) DEFAULT 0,
  `opt_c` int(3) DEFAULT 0,
  `opt_d` int(3) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Estructura de tabla para la tabla `question`
--

CREATE TABLE `question` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `title` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `type_id` char(36) DEFAULT NULL,
  `tip_note` text DEFAULT NULL,
  `created_on` datetime NOT NULL DEFAULT current_timestamp(),
  `status` varchar(100) NOT NULL DEFAULT 'active',
  `ai_generated` tinyint(1) NOT NULL DEFAULT 0,
  `feedback` text DEFAULT NULL,
  `lang` char(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Estructura de tabla para la tabla `question_option`
--

CREATE TABLE `question_option` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `question_id` varchar(100) NOT NULL,
  `text_option` varchar(255) DEFAULT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `created_on` datetime NOT NULL DEFAULT current_timestamp(),
  `retroalimentacion` varchar(255) DEFAULT NULL,
  `option_label` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Estructura de tabla para la tabla `question_type`
--

CREATE TABLE `question_type` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `type` varchar(100) NOT NULL,
  `created_on` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `question_type`
--

INSERT INTO `question_type` (`id`, `type`, `created_on`) VALUES
('67b7cedb-c30c-11f0-9013-88a4c233c11d', 'multiple_option', '2025-11-16 11:50:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`id`, `name`, `description`) VALUES
('45374501-c270-11f0-9e66-88a4c233c11d', 'admin', 'Administrador del sistema'),
('45375124-c270-11f0-9e66-88a4c233c11d', 'participant', 'Usuario participante'),
('453751a8-c270-11f0-9e66-88a4c233c11d', 'dev', 'Usuario desarrollador'),
('453751ca-c270-11f0-9e66-88a4c233c11d', 'superadmin', 'Acceso total al sistema');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `status`
--

CREATE TABLE `status` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `status`
--

INSERT INTO `status` (`id`, `name`, `description`) VALUES
('09587991-c270-11f0-9e66-88a4c233c11d', 'active', 'Registro activo'),
('09588275-c270-11f0-9e66-88a4c233c11d', 'inactive', 'Registro inactivo'),
('0958830a-c270-11f0-9e66-88a4c233c11d', 'pending', 'En espera de aprobación'),
('0958832d-c270-11f0-9e66-88a4c233c11d', 'deleted', 'Eliminado lógicamente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user`
--

CREATE TABLE `user` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol_id` char(36) NOT NULL,
  `status_id` char(36) NOT NULL,
  `created_on` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `user`
--

INSERT INTO `user` (`id`, `name`, `email`, `password`, `rol_id`, `status_id`, `created_on`) VALUES
('0e558da9-fe05-11f0-aaaa-745d22b52004', 'SG Administrator', 'admin@sg.com', '$2y$10$oTiZVNcfFVWxThyXMuBNbOa6QRR2UoVbvX94RdaRb0EyEGg8G3.5y', '453751ca-c270-11f0-9e66-88a4c233c11d', '09587991-c270-11f0-9e66-88a4c233c11d', '2026-01-30 12:56:48'),
('fc47e711-db09-11f0-a527-88a4c233c11d', 'test user', 'testemail2@gmail.com', '$2y$10$tx74m5p8BXFN1JqyImPMGeMa7MKF.vwe9/9AMcPMk3uhH94FyNEhK', '45375124-c270-11f0-9e66-88a4c233c11d', '09587991-c270-11f0-9e66-88a4c233c11d', '2025-12-17 00:33:58'),
('9c1835c4-309e-11f1-bdfc-745d22b52004', 'Participante Demo', 'participanteGUM01@gmail.com', '$2y$10$Y1mV1ib4Y2zhEUvjXHhv6uwt7PzrHJXJbl7vm7lAYs4WdRkZSk.Wm', '45375124-c270-11f0-9e66-88a4c233c11d', '09587991-c270-11f0-9e66-88a4c233c11d', '2026-02-10 22:22:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_answer`
--

CREATE TABLE `user_answer` (
  `id` char(36) NOT NULL,
  `group_id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `question_id` char(36) DEFAULT NULL,
  `q_option_id` char(36) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `started_on` datetime NOT NULL DEFAULT current_timestamp(),
  `finished_on` datetime DEFAULT NULL,
  `game_id` char(36) DEFAULT NULL,
  `is_active` tinyint(1) GENERATED ALWAYS AS (`finished_on` is null) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Estructura de tabla para la tabla `user_group_personal_stats`
--

CREATE TABLE `user_group_personal_stats` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `username` varchar(36) DEFAULT NULL,
  `group_id` char(36) NOT NULL,
  `question_quantity` int(11) NOT NULL,
  `correct_answers` int(11) NOT NULL,
  `wrong_answers` int(11) NOT NULL,
  `lives_number` int(1) NOT NULL,
  `score` int(5) NOT NULL,
  `total_time` int(100) DEFAULT NULL,
  `status` varchar(15) DEFAULT 'pending',
  `created_on` datetime DEFAULT current_timestamp(),
  `updated_on` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_on` datetime DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `game_id` char(36) NOT NULL,
  `option_a` int(3) DEFAULT NULL,
  `option_b` int(3) DEFAULT NULL,
  `option_c` int(3) DEFAULT NULL,
  `option_d` int(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indices de la tabla `game`
--
ALTER TABLE `game`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indices de la tabla `game_group`
--
ALTER TABLE `game_group`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indices de la tabla `group_question`
--
ALTER TABLE `group_question`
  ADD PRIMARY KEY (`group_id`,`question_id`),
  ADD KEY `fk_gq_question` (`question_id`);

--
-- Indices de la tabla `group_question_option_stats`
--
ALTER TABLE `group_question_option_stats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `q_option_id` (`q_option_id`);

--
-- Indices de la tabla `group_question_stats`
--
ALTER TABLE `group_question_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_group_question` (`group_id`,`question_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indices de la tabla `question`
--
ALTER TABLE `question`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_QUESTION_TYPE_ID` (`type_id`);

--
-- Indices de la tabla `question_option`
--
ALTER TABLE `question_option`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_QUESTION_OPTION_ID` (`question_id`);

--
-- Indices de la tabla `question_type`
--
ALTER TABLE `question_type`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type` (`type`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `rol_id` (`rol_id`),
  ADD KEY `status_id` (`status_id`);

--
-- Indices de la tabla `user_answer`
--
ALTER TABLE `user_answer`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_user_answer_active` (`user_id`,`question_id`,`group_id`,`game_id`,`is_active`),
  ADD KEY `fk_user_answer_question` (`question_id`),
  ADD KEY `fk_user_answer_option` (`q_option_id`),
  ADD KEY `fk_user_answer_group` (`group_id`);

--
-- Indices de la tabla `user_group_personal_stats`
--
ALTER TABLE `user_group_personal_stats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `game`
--
ALTER TABLE `game`
  ADD CONSTRAINT `game_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `game_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `game_group` (`id`);

--
-- Filtros para la tabla `group_question`
--
ALTER TABLE `group_question`
  ADD CONSTRAINT `fk_gq_group` FOREIGN KEY (`group_id`) REFERENCES `game_group` (`id`),
  ADD CONSTRAINT `fk_gq_question` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`);

--
-- Filtros para la tabla `group_question_option_stats`
--
ALTER TABLE `group_question_option_stats`
  ADD CONSTRAINT `group_question_option_stats_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `game_group` (`id`),
  ADD CONSTRAINT `group_question_option_stats_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`),
  ADD CONSTRAINT `group_question_option_stats_ibfk_3` FOREIGN KEY (`q_option_id`) REFERENCES `question_option` (`id`);

--
-- Filtros para la tabla `group_question_stats`
--
ALTER TABLE `group_question_stats`
  ADD CONSTRAINT `group_question_stats_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `game_group` (`id`),
  ADD CONSTRAINT `group_question_stats_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`);

--
-- Filtros para la tabla `question`
--
ALTER TABLE `question`
  ADD CONSTRAINT `FK_QUESTION_TYPE_ID` FOREIGN KEY (`type_id`) REFERENCES `question_type` (`id`);

--
-- Filtros para la tabla `question_option`
--
ALTER TABLE `question_option`
  ADD CONSTRAINT `FK_QUESTION_OPTION_ID` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`);

--
-- Filtros para la tabla `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`),
  ADD CONSTRAINT `user_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`);

--
-- Filtros para la tabla `user_answer`
--
ALTER TABLE `user_answer`
  ADD CONSTRAINT `fk_user_answer_group` FOREIGN KEY (`group_id`) REFERENCES `game_group` (`id`),
  ADD CONSTRAINT `fk_user_answer_option` FOREIGN KEY (`q_option_id`) REFERENCES `question_option` (`id`),
  ADD CONSTRAINT `fk_user_answer_question` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`),
  ADD CONSTRAINT `fk_user_answer_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Filtros para la tabla `user_group_personal_stats`
--
ALTER TABLE `user_group_personal_stats`
  ADD CONSTRAINT `group_question_personal_stats_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `game_group` (`id`),
  ADD CONSTRAINT `group_question_personal_stats_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
