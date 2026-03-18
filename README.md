# Taskly

Taskly is a simple task management application built with Laravel, Inertia.js, and Vue.js. It allows users to create and manage tasks within teams.

## Installation

### Pre-requirements

- PHP >= 8.5
- Composer
- Node.js >= 24
- NPM >= 11.6.2

### Installation steps

1. Clone the project repository
2. Install dependencies and initialize the environment:
    ```
    composer run setup
    ```
    This script :
    - installs the PHP and JavaScript dependencies
    - configures the .env file
    - generates the Laravel application key
3. Start the development server :
    ```
    composer run dev
    ```
    You can access the application at : http://localhost:8000.

## Project Presentation

The application is a **team-based task manager**.\
It contains **3 teams and 6 users**. Each user belongs to a team.\
Test accounts are available :
| User | EEmail | Password |
| ----------- | ------------------- | ------------ |
| User 1 | test1@example.com | password |
| User 2 | test2@example.com | password |
| ... | test{N}@example.com | password |
| User 6 | test6@example.com | password |

You can also create your own account.
