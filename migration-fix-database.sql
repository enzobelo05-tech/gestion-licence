-- =====================================================
-- Migration : remise en conformité du schéma vs diagramme
-- À EXÉCUTER UNE SEULE FOIS via phpMyAdmin ou la CLI mysql
-- Recommandé : faire un dump avant (mysqldump projet_php > backup.sql)
-- =====================================================

START TRANSACTION;

-- 1. Nettoyage des données parasites (SIUUU, course_instructor orphelin)
DELETE FROM course_instructor WHERE course_id = 0;
DELETE FROM course             WHERE title = 'SIUUU';

-- 2. Réassignation des IDs : module
SET @i = 0;
UPDATE module       SET id = (@i := @i + 1) ORDER BY name;

-- 3. Réassignation des IDs : intervention_type
SET @i = 0;
UPDATE intervention_type SET id = (@i := @i + 1) ORDER BY name;

-- 4. Réassignation des IDs : course (selon start_date — matche course_instructor)
SET @i = 0;
UPDATE course       SET id = (@i := @i + 1) ORDER BY start_date;

-- 5. Ajout des clés primaires + auto_increment
ALTER TABLE module
    MODIFY hours_count INT NOT NULL,
    ADD PRIMARY KEY (id),
    MODIFY id INT(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE intervention_type
    ADD PRIMARY KEY (id),
    MODIFY id INT(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE course
    MODIFY title VARCHAR(255) NULL,
    ADD PRIMARY KEY (id),
    MODIFY id INT(11) NOT NULL AUTO_INCREMENT;

-- 6. Clés primaires composées sur les tables de liaison
ALTER TABLE instructor_module
    ADD PRIMARY KEY (instructor_id, module_id);

ALTER TABLE course_instructor
    ADD PRIMARY KEY (course_id, instructor_id);

-- 7. Contraintes de clés étrangères (préviennent les corruptions futures)
ALTER TABLE instructor
    ADD CONSTRAINT fk_instructor_user
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE;

ALTER TABLE instructor_module
    ADD CONSTRAINT fk_im_instructor
        FOREIGN KEY (instructor_id) REFERENCES instructor(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_im_module
        FOREIGN KEY (module_id) REFERENCES module(id) ON DELETE CASCADE;

ALTER TABLE course
    ADD CONSTRAINT fk_course_module
        FOREIGN KEY (module_id) REFERENCES module(id),
    ADD CONSTRAINT fk_course_type
        FOREIGN KEY (intervention_type_id) REFERENCES intervention_type(id);

ALTER TABLE course_instructor
    ADD CONSTRAINT fk_ci_course
        FOREIGN KEY (course_id) REFERENCES course(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_ci_instructor
        FOREIGN KEY (instructor_id) REFERENCES instructor(id) ON DELETE CASCADE;

-- 8. Self-référence sur module.parent_id
ALTER TABLE module
    ADD CONSTRAINT fk_module_parent
        FOREIGN KEY (parent_id) REFERENCES module(id) ON DELETE SET NULL;

COMMIT;
