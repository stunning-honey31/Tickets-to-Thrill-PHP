# Tickets-to-Thrill-PHP
**Project: Tickets to Thrill - Theme Park Ticket Booking System using PHP/MySQL**

**About**

Tickets to Thrill is a PHP/MySQL project that lets visitors browse, book, and pay for theme park tickets entirely online, and lets park staff manage all of it from an admin panel. Visitors can register an account, add different ticket types to a cart, check out through a simulated payment step, and instantly receive a PDF e-ticket - both as a download and as an email attachment. Every order is tied to the customer's account, so they can review past and upcoming bookings, cancel an upcoming one, or finish paying for one they abandoned. The administrator has full control over the system: managing every registered user, viewing and updating the status of every booked ticket, and reading contact-form messages sent in by visitors. The system separates a public customer-facing site from a completely separate admin panel, and every form (registration, login, contact, checkout, profile edits) is validated on the server, not just in the browser.

**Features**

Home / Public Pages

The public-facing pages (Home, Tickets, About, Contact) where visitors can learn about the park, browse the ticket catalog, and send a message through the contact form.

Register Page

The page where a new visitor creates an account with their name, email, phone, and password before they can check out.

Login Page

The page where returning users and the admin sign in. Failed logins are throttled (5 attempts locks further tries for 30 seconds), and admins are redirected straight into the admin panel on success.

Ticket Catalog Page

The page where all available ticket types (entrance passes, combo passes, add-ons, etc.) are listed with their pricing, so visitors can add the ones they want to their cart.

Cart Page

The page where a visitor reviews the tickets they've added, adjusts quantities, applies a promo code, and starts checkout. Checkout requires an active login.

Payment Page

A simulated payment step for a pending order - it validates that the card details look real (card number, expiry, CVV) but does not contact an actual bank, since this is a demo gateway.

Order Confirmation Page

The page shown right after a successful payment, confirming the order and linking straight to the downloadable PDF ticket.

My Account Page

The customer's own panel: a summary of their bookings, the ability to cancel an upcoming ticket or finish paying for a pending one, edit their profile, and change their password.

Admin Dashboard Page

The default page admin users land on after logging in, showing total users, total tickets booked, revenue, unread messages, and the most recent tickets and messages at a glance.

Users Page

The page where the admin views every registered user, creates one directly, edits their details, promotes or demotes their role, views their full booking history, or deletes their account. Only visible to admin users.

Tickets Page

The page where every booked ticket across every customer is listed and can be filtered by status, with the admin able to mark a ticket as upcoming, completed, or cancelled. Only visible to admin users.

Messages Page

The page where every contact-form submission from visitors is listed, with the ability to mark a message read/unread or delete it. Only visible to admin users.


The Tickets to Thrill system was developed using HTML, PHP (PDO/MySQLi), MySQL, CSS, and vanilla JavaScript, with a hand-built PDF generator (no external library) for the e-tickets and PHP's built-in mail() function for sending the confirmation email. The source code is fully functional and easy to modify or extend. Follow the instructions below to run it yourself.


**How to Run**

Download the source code and extract the zip file.

Download or set up any local web server that runs PHP (7.4+) with the pdo_mysql extension enabled.

Make sure a MySQL (or MariaDB) server is running.

Either let the app set itself up automatically - just point config.php (or the DB_HOST / DB_PORT / DB_NAME / DB_USER / DB_PASS environment variables) at your MySQL server and it will create the database, tables, and seeded admin account on first request - or import database/schema.sql yourself (e.g. via phpMyAdmin) if you'd rather set it up by hand.

Copy the source code to the location your local web server serves from, or simply run PHP's built-in server from the project folder: php -S localhost:8000

Open a web browser and browse the project. E.g. http://localhost:8000


**Default Admin Access**

Email: admin@ticketstothrill.example

Password: Admin@123


Change this password (or delete the seeded account) before deploying anywhere real - from Admin -> Users you can promote your own account to admin and then remove the seeded one.

Enjoy!
I hope this Tickets to Thrill system helps with what you're looking for - feel free to explore, modify, and build on the source code.

Enjoy!
