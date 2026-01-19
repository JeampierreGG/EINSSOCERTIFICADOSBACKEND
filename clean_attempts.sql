-- Limpiar attempts completados de la evaluación 1
DELETE FROM evaluation_answers WHERE evaluation_attemps_id IN (SELECT id FROM evaluation_attempts WHERE evaluation_id = 1);
DELETE FROM evaluation_attempts WHERE evaluation_id = 1;
