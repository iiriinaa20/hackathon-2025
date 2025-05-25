# Budget Tracker – Evozon PHP Internship Hackathon 2025

## Starting from the skeleton

Prerequisites:

- PHP >= 8.1 with the usual extension installed, including PDO.
- [Composer](https://getcomposer.org/download)
- Sqlite3 (or another database tool that allows handling SQLite databases)
- Git
- A good PHP editor: PHPStorm or something similar

About the skeleton:

- The skeleton is built on Slim (`slim/slim : ^4.0`)
- The templating engine of choice is Twig (`slim/twig-view`)
- The dependency injection container of choice is `php-di/php-di`
- The database access layer of choice is plain PDO
- The configuration should be provided in a .env file (`vlucas/phpdotenv`)
- There is logging support by using `monolog/monolog`
- Input validation should be simply done using `webmozart/assert` and throwing Slim dedicated HTTP exceptions

## Step-by-step set-up

Install dependencies:

```
composer install
```

Set up the database:

```
cd database
./apply_migrations.sh
```

Note: be aware that, if you are using WSL2 (Windows Subsystem for Linux), you'll have trouble opening SQLite databases
with a DB management app (PHPStorm, for example) in Windows **when they are stored within the virtualized WSL2 drive**.
The solution is to store the `db.sqlite` file on the Windows drive (`/mnt/c`) and configure the path to the file in the
application config (`.env`):

```
cd database
./apply_migrations.sh /mnt/c/Users/<user>/AppData/Local/Temp/db.sqlite
```

Copy `.env.example` to `.env` and configure as necessary:

```
cp .env.example .env
```

Run the built-in server on http://localhost:8000

```
composer start
```

## Features

## Tasks

### Before you start coding

Make sure you inspect the skeleton and identify the important parts:

- `public/index.php` - the web entry point
- `app/Kernel.php` - DI container and application setup
- classes under `app` - this is where most of your code will go
- templates under `templates` are almost complete, at least in terms of static mark-up; all you need is to make use of
  the Twig syntax to make them dynamic.

### Main tasks — for having a functional application

Start coding: search for `// TODO: ...` and fill in the necessary logic. Don't limit yourself to that; you can do
whatever you want, design it the way you see fit. The TODOs are a starting point that you may choose to use.

### Extra tasks — for extra points

Solve extra requirements for extra points. Some of them you can implement from the start, others we prefer you to attack
after you have a fully functional application, should you have time left. More instructions on this in the assignment.

### Deliver well designed quality code

Before delivering your solution, make sure to:

- format every file and make sure there is no commented code left, and code looks spotless

- run static analysis tools to check for code issues:

```
composer analyze
```

- run unit tests (in case you added any):

```
composer test
```

A solution with passing analysis and unit tests will receive extra points.

## Delivery details

Participant:
- Full name: Irina Mihaela Calota
- Email address: mihaelairina48@yahoo.com

Features fully implemented:
- Register functionality with CSRF and validation (with password hash)
- Implemented password retype check on register (frontend & backend)
- Login functionality with CSRF and validation  (with password check)
- Logout functionality
- Prevention on Session fixation attacks 
- Basic CRUD functionalities for expenses
- Advanced queries for expenses
- Used pagination and filtering on Expenses Resource
- Used prepared statements
- Applied all necessary formating on UI level
- Used Flash Session for short hand notification messages
- Used generated Old Session vector for storing user input on validation errors
- Added overspending allerts and all dashboard functionality
- Note (on expenses Add the expected value for amount is in decimal unit currency : eg -> 10.50 => amount cents 1050)
- added option for csv imports

- added categories and budgets to .env file, updated .env.example for clarity
- created migration file with optimizations regarding indexes
- Passed composer analyze results

Where I used AI:
- on csv import feature 
  why? : got composer analyze issue regarding cyclomatic complexity 
       : the time was to short

What I have not done yet:
- Unit tests (not enough time)
- Soft Delete flag (not enough time)
- Evolve the Expense entity (not enough time)

How would I had done these features:
- Unit tests (not enough time)
  **  install PhpUnit and start writing basic tests for services and repositories ** 
- Soft Delete flag (not enough time)
  **  add a new migration for adding a nullable column deleted_at (timestamp)** 
  **  modify the entity expense and query to retrieve only not deleted_at entries ** 
  **  on delete mark the entry as deleted at current timestamp ** 
  **  implement short undo feature for undoing missclicked deletes, add js confrimation on delete click ** 
- Evolve the Expense entity (not enough time)
  **  add a new migration for adding the necessary column ** 
  **  Note: migration could also select all the amount cents and convert it to amount at SQL LEVEL (define procedure) ** 
  **  update the entity, update, add relevant queries** 
  **  Note: since my implementation already works with amount at ui level , remove the conversing from backend eg: remove ammount_cents = amount * 100 ** 


Other instructions about setting up the application (if any): None
