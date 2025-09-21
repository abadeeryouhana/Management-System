Task Management System API
A robust and scalable RESTful API for managing tasks with role-based access control, task dependencies, and JWT authentication.

Features
- JWT Authentication

- Role-based Access Control (Manager/User)

- Task Management with Dependencies

- Filtering and Search Capabilities

- Docker Containerization Support

- Comprehensive API Documentation

- PHPUnit Testing Suite

Tech Stack:

    Backend: Laravel 10.x

    Authentication: JWT (tymon/jwt-auth)

    Database: MySQL 8.0

    Containerization: Docker & Docker Compose

    Testing: PHPUnit

Prerequisites:

    PHP 8.1 or higher

    Composer

    MySQL 8.0 or higher

    Node.js (optional, for frontend)

    Docker & Docker Compose (optional)

Quick Setup:

1. Clone and Install
    git clone <your-repository-url>
    cd task-management-system
    composer install
2. Environment Setup
    cp .env.example .env
    php artisan key:generate
    php artisan jwt:secret
3. Configure Database
    edit the .env file :
        DB_CONNECTION=mysql
        DB_HOST=127.0.0.1
        DB_PORT=3306
        DB_DATABASE=task_management
        DB_USERNAME=root
        DB_PASSWORD=your_password
4. Database Migration
    php artisan migrate --seed
5. Serve Application
    php artisan serve

Default Users
    After seeding, the system creates:

Manager Account
    Email: manager@example.com

    Password: password

    Permissions: Full access to all operations

User Accounts (5 users)
    Emails: user1@example.com to user5@example.com

    Password: password (for all)

    Permissions: Limited to assigned tasks

Role-Based Access Control
    Manager Permissions
        Create, read, update, delete all tasks

        Assign tasks to any user

        Manage task dependencies

        View all tasks in the system

    User Permissions
        View only assigned tasks

        Update status of assigned tasks

        Cannot create, delete, or assign tasks

        Cannot manage dependencies

    Task Dependencies
        Tasks can have dependencies on other tasks. A task cannot be marked as completed until all its dependencies are completed.

API Endpoints

Authentication

    Method	Endpoint	        Description
    POST	/api/auth/login	    User login
    POST	/api/auth/logout	User logout
    POST	/api/auth/refresh	Refresh JWT token
    POST	/api/auth/me	    Get current user

Tasks (Protected)

    Method	    Endpoint	        Description	Access
    GET 	/api/tasks	            List tasks (with filtering)	All
    POST	/api/tasks	            Create new task	Manager
    GET	    /api/tasks/{id}	        Get task details	Owner/Manager
    PUT	    /api/tasks/{id}	        Update task	Varies
    DELETE	/api/tasks/{id}	        Delete task	Manager

Task Dependencies (Manager only)

    Method	Endpoint	            Description
    POST	/api/dependencies	    Add dependency
    DELETE	/api/dependencies/{id}	Remove dependency


