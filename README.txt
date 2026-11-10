HOSTEL FINDER - quick start

1. Install XAMPP and start MySQL in the XAMPP Control Panel.
2. Import database.sql (phpMyAdmin > Import). Do this once.
3. Open config.php and check the database name/user/password.
4. In a terminal, inside THIS folder (the one containing index.php), run:
     C:\xampp\php\php.exe -S localhost:8000
5. Public site:  http://localhost:8000/
   Admin login:  http://localhost:8000/admin/   (first visit creates the admin account)

While testing reviews, set REVIEW_COOLDOWN_HOURS to 0 in config.php.
