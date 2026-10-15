GREY REPAIRS - LOCAL INSTALLATION

1. Copy the electronics-repair folder to C:\xampp\htdocs\.
2. Start Apache and MySQL in XAMPP.
3. Open http://localhost/phpmyadmin/ and create/import the electronics_repair database.
   If this is a new database, import electronics_repair.sql. Do NOT import over an existing database without first backing it up.
4. Check config/database.php. The included root/blank-password settings are for a typical local XAMPP installation only.
5. Open http://localhost/electronics-repair/ in your browser.
6. Register a customer and test the booking flow. Register a technician using technician/register.php.
7. To create an administrator locally, register a normal account, then run in phpMyAdmin (replace the email):
   UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';
   Do not expose the admin account credentials.

IMPORTANT BEFORE PUBLIC DEPLOYMENT
- Remove test-db.php.
- Set production DB credentials using a dedicated least-privilege database user; never use the XAMPP root account online.
- Configure HTTPS/TLS and secure session cookies on the production host.
- Review every POST action for CSRF validation and every page for role/ownership checks.
- Configure display_errors=Off, log_errors=On, and protect error logs.
- Test all flows and back up the database. This package has not been penetration-tested or verified on your hosting provider.

This SQL file is schema-only and contains no user or booking data.
