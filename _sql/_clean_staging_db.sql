UPDATE item_values SET value = CONCAT('isaac+', REPLACE(REPLACE(value, '@', '_'), '.', '_'), '@example.com')
    WHERE value LIKE '%@%.%' AND value NOT LIKE 'isaac+%mm.st';
UPDATE users SET email = CONCAT('isaac+', REPLACE(REPLACE(email, '@', '_'), '.', '_'), '@example.com')
    WHERE email NOT LIKE 'isaac+%mm.st';
UPDATE accounts SET name = CONCAT('isaac+', REPLACE(REPLACE(name, '@', '_'), '.', '_'), '@example.com')
    WHERE name NOT LIKE 'isaac+%mm.st';
UPDATE users SET password = "$2y$13$NqAcKZ7NQiwg4z7gQ0QKpuGwgd.8Zxah5hN0d4BdpqHffirPk38fO";
