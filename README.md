## Recent fixes and local configuration

- Student Attendance now displays the actual course name beside the course code.
- Student Grades now has a working course filter and shows course names in grade records.
- Student notification polling now includes published assignments and can play a short optional sound when a new assignment is detected after the first load. Browser sound restrictions may require clicking the notification bell once.
- Copy `.env.example` to `.env` and fill in your local database/SMTP settings before running the project. Do not share `.env`.

# FEU Marikina LMS --- Beginner-Friendly Local Setup

This guide explains how to run the FEU LMS on a Windows computer using
XAMPP. You do not need advanced PHP knowledge to follow the setup, but
you should complete each step in order.

> **Important:** These instructions are for local development. Do not
> publish this project to the internet or use real student data until
> its security has been reviewed.

## 1. What you need

-   **XAMPP for Windows** --- includes Apache, PHP, MariaDB/MySQL, and
    phpMyAdmin. Download it from the [official Apache Friends
    website](https://www.apachefriends.org/).
-   A modern browser, such as Chrome, Edge, or Firefox.
-   The LMS project ZIP file.

The project ZIP includes the Composer `vendor/` folder. You normally do
not need to install Composer just to run this copy. If
`vendor/autoload.php` is missing, see [Composer autoload
error](#composer-autoload-error).

### PHP extensions

The project uses PHP, MySQLi, cURL, sessions, and Composer packages.
XAMPP usually includes the needed extensions. If you encounter an
extension error, check that `mysqli`, `curl`, `mbstring`, and `openssl`
are enabled in the PHP configuration.

## 2. Extract the project to the correct folder

1.  Install and open XAMPP.

2.  Extract the project ZIP.

3.  Put the project files in:

    ``` text
    C:\xampp\htdocs\lms
    ```

4.  Check that these paths exist:

    ``` text
    C:\xampp\htdocs\lms\config.php
    C:\xampp\htdocs\lms\.env
    C:\xampp\htdocs\lms\lms_setup_svc.sql
    C:\xampp\htdocs\lms\gateway\index.php
    C:\xampp\htdocs\lms\web\index.php
    C:\xampp\htdocs\lms\vendor\autoload.php
    ```

**Check the folder level:** If you see
`C:\xampp\htdocs\lms\lms\config.php`, the project may have been
extracted into an extra nested folder. Move the inner project contents
up one level so `config.php` is directly inside `C:\xampp\htdocs\lms`.

## 3. Start Apache and MySQL/MariaDB

1.  Open **XAMPP Control Panel**.
2.  Click **Start** next to **Apache**.
3.  Click **Start** next to **MySQL**.
4.  Confirm both services are running.

If one will not start, another application may already be using its
port. Apache commonly uses ports `80`/`443`; MySQL/MariaDB commonly uses
`3306`. Resolve the port conflict before continuing.

## 4. Import the database setup file

The project includes `lms_setup_svc.sql`, which creates the LMS
databases, tables, and sample records.

1.  Open <http://localhost/phpmyadmin>.

2.  Click the **Import** tab.

3.  Click **Choose File**.

4.  Select:

    ``` text
    C:\xampp\htdocs\lms\lms_setup_svc.sql
    ```

5.  Keep the format set to **SQL**.

6.  Click **Import** or **Go** and wait for the result.

7.  Confirm that LMS databases such as `lms_user_svc`, `lms_auth_svc`,
    `lms_course_svc`, and `lms_assignment_svc` appear.

> **Danger: the SQL file can delete existing LMS data.** It contains
> `DROP DATABASE IF EXISTS` statements. Importing it again can delete
> and recreate the databases, including records you have added. Before
> re-importing, export/backup any databases you need to keep. Do not run
> this setup file on a database server that contains important data.

### How to back up a database in phpMyAdmin

1.  Open <http://localhost/phpmyadmin>.
2.  Select the database you want to preserve.
3.  Click **Export**.
4.  Choose **Quick** and **SQL**, then click **Export**.
5.  Store the downloaded `.sql` backup somewhere safe.

Repeat for each LMS database you need to preserve.

## 5. Configure the `.env` file

Open this file in a text editor such as Notepad or VS Code:

``` text
C:\xampp\htdocs\lms\.env
```

For a typical local XAMPP installation, the settings may look like this:

``` dotenv
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USER=root
DB_PASS=
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=
SMTP_PASSWORD=
SMTP_FROM_EMAIL=
SMTP_FROM_NAME="FEU Marikina LMS"
FRONTEND_URL=http://localhost/lms/web
```

Use the database settings that match **your own** XAMPP installation:

-   `DB_HOST`: usually `127.0.0.1`.
-   `DB_PORT`: usually `3306`; change it if your database uses another
    port.
-   `DB_USER`: often `root` on a default local XAMPP setup.
-   `DB_PASS`: blank only if your local database user has no password.
-   `FRONTEND_URL`: should match the URL where you open the LMS.
-   `SMTP_*`: leave blank if you have not configured email sending.
    Email features such as password recovery may not work until SMTP is
    configured.

Do not put your normal email password in the SMTP settings. Use your
provider's supported app password or SMTP credentials.

### Keep secrets private

-   **Do not share or publish the real `.env` file.** It may contain
    database or email credentials.
-   For a project you distribute, provide a `.env.example` with
    placeholder or blank secret values instead of including your working
    `.env`.
-   The project's `.gitignore` helps keep `.env` out of new Git commits,
    but it does **not** remove a file that is already tracked and does
    not automatically exclude it from a ZIP you create.
-   The shared internal service key is configured in `config.php`. If
    the project will be shared outside your trusted development group,
    replace any real shared key with a newly generated secret and update
    all components that depend on it. Do not post the key in
    screenshots, chats, or repositories.
-   If credentials or keys have already been exposed, change/rotate
    them; deleting the exposed copy alone may not be enough.

## 6. Open the LMS

Open this URL in your browser:

**<http://localhost/lms/web/>**

If you installed the project under a different folder name, the URL may
be different. Make sure `FRONTEND_URL` in `.env` matches your local URL,
especially if the application generates links for emails.

## 7. Sign in

The SQL setup file inserts sample roles and LMS records, including a
sample professor account. The SQL file does not document a plain-text
password for that account, so do not guess a password from the email
address or the stored password hash.

Use a known development account and password supplied by the project
owner. If none is available, ask the project maintainer for the intended
safe account-creation or password-reset procedure. Do not replace a
password hash with plain text directly in the database.

## 8. First-run checklist

Use this checklist if you are new to PHP/XAMPP:

-   [ ] XAMPP is installed.
-   [ ] Apache is running.
-   [ ] MySQL is running.
-   [ ] The project is located at `C:\xampp\htdocs\lms`.
-   [ ] `config.php`, `.env`, `vendor/autoload.php`, and `web\index.php`
    exist.
-   [ ] The SQL setup file imported successfully.
-   [ ] The `.env` database settings match my local database.
-   [ ] The LMS opens at <http://localhost/lms/web/>.
-   [ ] I can sign in with a valid development account.
-   [ ] I have not shared `.env` or any real secret.

## 9. Troubleshooting

### The page says "Service unavailable" or API requests fail

1.  Check that Apache and MySQL are running in XAMPP.
2.  Confirm the databases and tables were imported.
3.  Recheck `DB_HOST`, `DB_PORT`, `DB_USER`, and `DB_PASS` in `.env`.
4.  Confirm the project is in the expected folder.
5.  Check the Apache/PHP error log for a detailed error.

Opening <http://localhost/lms/gateway/index.php> directly may show a
JSON error or a missing-parameter response; that does not necessarily
mean the gateway file is missing. A browser 404 usually means the URL or
folder path is wrong.

### Database connection error

-   Confirm MySQL is running in XAMPP.
-   Verify the database username and password.
-   Check whether another database server is using port `3306`.
-   If you changed the database port, update `DB_PORT` in `.env`.

### `mysqli` or cURL is missing

1.  In XAMPP Control Panel, click **Config** next to Apache.
2.  Open **PHP (php.ini)**.
3.  Find the extension settings for `mysqli`, `curl`, `mbstring`, and
    `openssl`.
4.  If an extension line starts with a semicolon (`;`), it may be
    disabled. Enable it only if the extension is installed and the
    configuration supports it.
5.  Save the file and restart Apache.

### Composer autoload error

Check that this file exists:

``` text
C:\xampp\htdocs\lms\vendor\autoload.php
```

If it is missing, install [Composer](https://getcomposer.org/), open
Command Prompt in `C:\xampp\htdocs\lms`, and run:

``` bash
composer install
```

### Blank page or PHP fatal error

1.  Check the Apache/PHP error log in the XAMPP installation.
2.  Read the full error message and note the file name and line number.
3.  Fix the underlying error rather than deleting project files or the
    MariaDB `data` directory.
4.  Do not enable public error display on a live website because error
    messages can reveal sensitive details.

### The LMS opens but login does not work

-   Confirm that the SQL import completed without errors.
-   Use credentials provided by the project owner.
-   Check the PHP error log and browser developer console for errors.
-   Ask the project maintainer how to create or reset a development
    account if no valid account was provided.

## 10. Project folder overview

``` text
lms/
├── .env                  # Local settings and secrets; keep private
├── config.php            # Shared application configuration
├── lms_setup_svc.sql     # Database setup and sample data
├── composer.json         # PHP dependency definitions
├── vendor/               # Composer dependencies included in this archive
├── gateway/              # API gateway
├── services/             # Backend PHP services
├── shared/               # Shared backend utilities
├── storage/              # Application storage
└── web/                  # Browser-facing pages and assets
```

## 11. Before sharing or deploying the project

This guide is for local development only. Before sharing the project or
putting it on a server:

-   Remove `.env` and real credentials from the distribution archive and
    repository history where applicable.
-   Provide a safe `.env.example` with placeholders.
-   Replace exposed database passwords, SMTP credentials, and shared
    internal keys.
-   Review authentication, authorization, file uploads, database
    queries, and error handling.
-   Disable development-only debugging output.
-   Back up the database before making schema changes.
-   Test with non-sensitive sample data.
-   Have the application reviewed for production security before
    exposing it to the internet.

## 12. Help when something goes wrong

When asking for help, include: - The exact error message. - Which step
you were following. - Whether Apache and MySQL are running. - The
relevant error-log lines, with passwords, tokens, keys, and personal
data removed.

Never send your real `.env` file or paste passwords/API keys into a
support chat.

## Latest UI/data fixes

- Dashboard announcement Like, Dislike, and Mark as read actions use AJAX so the page does not jump away from the announcement.
- Student assignment cards display the subject/course and the professor who published the assignment.
- Student Calendar supports previous/next month navigation and clicking a date to view that weekday's course schedule.
- Course cards query assigned professor names and current non-pending/non-dropped enrollment counts from the database; they no longer use a fake default count.
- Attendance shows course names/codes and the decorative sample month calendar has been removed.
- Inbox conversations are ordered by most recent message and show the actual last message/time instead of placeholder content.

These changes require the existing database schema and matching records. Do not re-import `lms_setup_svc.sql` just to install these code changes; the setup SQL includes database drop statements.
