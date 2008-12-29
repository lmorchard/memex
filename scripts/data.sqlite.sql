INSERT INTO logins (id, login_name, email, password) 
    VALUES (1, 'admin', 'l.m.orchard@pobox.com', 'admin');
INSERT INTO profiles (id, screen_name, full_name) 
    VALUES (1, 'admin', 'Admin User');
INSERT INTO logins_profiles (id, login_id, profile_id) 
    VALUES (1, 1, 1);

