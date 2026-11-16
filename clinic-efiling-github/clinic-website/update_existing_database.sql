USE clinic_db;
-- Real password hashes (Password123) so the demo backdoor can be removed
UPDATE staff SET password='$2y$10$hH01gAi/1UxxVuL7cTDsaOum2zvQjngQEY3gbI0kgVMhHEhUqpcGe';
-- Valid SA ID numbers for the sample patients
UPDATE patients SET id_number='9001015801086' WHERE id_number='9001011234085';
UPDATE patients SET id_number='8805050342082' WHERE id_number='8805055678083';
UPDATE patients SET id_number='9503035127087' WHERE id_number='9503036789082';
UPDATE patients SET id_number='0204240615086' WHERE id_number='0204240987087';

-- Demo follow-ups due soon (so the dashboard card has data)
UPDATE consultations SET next_visit_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) WHERE consultation_id = 2;
UPDATE consultations SET next_visit_date = DATE_ADD(CURDATE(), INTERVAL 3 DAY) WHERE consultation_id = 3;
UPDATE consultations SET next_visit_date = DATE_ADD(CURDATE(), INTERVAL 6 DAY) WHERE consultation_id = 4;
