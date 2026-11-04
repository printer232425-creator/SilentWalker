
    SET @row := 0;
UPDATE assigned_teachers
    SET id = (@row := @row + 1)
    ORDER BY id;
    
    SET @row := 0;
UPDATE attendance_list
    SET id = (@row := @row + 1)
    ORDER BY id;
    
    SET @row := 0;
UPDATE class_updates
    SET id = (@row := @row + 1)
    ORDER BY id;
    
    SET @row := 0;
UPDATE enrolled_students
    SET id = (@row := @row + 1)
    ORDER BY id;
    
    SET @row := 0;
UPDATE schools
    SET id = (@row := @row + 1)
    ORDER BY id;
    
    SET @row := 0;
UPDATE students
    SET id = (@row := @row + 1)
    ORDER BY id;
    
    SET @row := 0;
UPDATE teachers
    SET id = (@row := @row + 1)
    ORDER BY id;
    
        SET @row := 0;
UPDATE submitted_activities
    SET id = (@row := @row + 1)
    ORDER BY id;