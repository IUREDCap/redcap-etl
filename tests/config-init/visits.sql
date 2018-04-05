ALTER TABLE VisitResults ADD COLUMN sleep_minutes FLOAT;
UPDATE VisitResults SET sleep_minutes = sleep_hours * 60;
