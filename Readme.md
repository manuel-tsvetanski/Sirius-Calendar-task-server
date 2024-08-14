# Sirius Calendar Task - Server

## Overview

Sirius Calendar Task is a web-based application designed to manage appointments for a dental clinic. This application allows users to view available time slots on a calendar, book appointments, and manage existing appointments. The backend server is built using Symfony, providing a robust framework for handling data and business logic.

## Features

- **Calendar View:** Displays available and booked time slots.
- **Appointment Booking:** Users can book appointments using a simple form.
- **AJAX-Based Submissions:** Forms are submitted asynchronously for a seamless experience.
- **Appointment Management:** Includes functionality for deleting existing appointments.
- **Data Persistence:** Uses Doctrine ORM and MySQL for data storage.

## Installation

Follow these steps to set up the project on your local machine:

### Prerequisites

Ensure that the following software is installed:

- PHP 8.0 or higher
- Composer
- Node.js and npm
- MySQL (or another supported database)
- Git

### Step 1: Clone the Repository

Clone the repository to your local machine:

```bash
git clone https://github.com/manuel-tsvetanski/Sirius-Calendar-task-server.git
cd Sirius-Calendar-task-server

Step 2: Install PHP Dependencies
Install the required PHP dependencies using Composer:

composer install

Step 3: Install JavaScript Dependencies
Install the JavaScript dependencies using npm:

npm install

cp .env .env.local

Configure the following in the .env.local file:

DATABASE_URL: Set this to your database connection string.

Step 5: Set Up the Database
Create the database and run the migrations:

php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

Step 6: Build Frontend Assets
Build the frontend assets using Webpack Encore:

npm run build

For development, use:

npm run watch

Step 7: Start the Development Server
symfony server:start

he application should now be accessible at http://127.0.0.1:8000.

Usage
Once the server is running, you can access the application in your web browser. The calendar will display the available and booked time slots. You can book new appointments by clicking on available slots and filling out the booking form.

Contributing
If you'd like to contribute to this project, please fork the repository and submit a pull request with your changes.

License
This project is licensed under the MIT License.

Contact
For any inquiries, please reach out to Manuel Tsvetanski.


You can copy this text into a `README.md` file for your project. This file provides detailed instructions and information for developers who want to set up and contribute to your project.
