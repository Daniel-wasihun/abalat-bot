# Senbet School Feedback System

A comprehensive school feedback management system featuring a Laravel backend, Vue.js/Tailwind CSS frontend dashboard, and a Telegram Bot interface for real-time interaction.

## Features

- **Admin Dashboard**: A modern, responsive Vue.js frontend for managing users, feedback, and system configuration.
- **Telegram Bot Integration**: Powered by Nutgram, allowing users to submit feedback directly via Telegram.
- **Role-Based Access Control**: Granular permissions system for managing different levels of staff access.
- **Real-time Updates**: Integrated with Laravel Reverb for real-time WebSocket communication.
- **Background Processing**: Robust queue system for handling asynchronous tasks like notifications and data imports.

## Prerequisites

Before running the application, ensure you have the following installed:
- PHP 8.2 or higher
- Composer
- Node.js & npm
- PostgreSQL (or your preferred database)

## Setup & Installation

1. **Clone the repository and install dependencies**:
   Run the all-in-one setup command which will install PHP and Node dependencies, copy `.env.example` to `.env`, generate the app key, run database migrations, and build the frontend assets:
   ```bash
   composer setup
   ```

2. **Configure your Environment**:
   Update your `.env` file with your specific database credentials and Telegram Bot token:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=your_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password

   TELEGRAM_TOKEN=your_bot_token_here
   ```

## Running the Application

### Development Mode

To run the application locally for development, use the comprehensive dev command. This will concurrently start the Laravel backend, the Vite dev server, the Reverb WebSocket server, the queue listener, and the Telegram bot polling:

```bash
composer dev
```

You can then access the dashboard at: **http://localhost:8000** (or whichever port artisan serve binds to).

### Production Mode

For a production environment, use:

```bash
composer prod-start
```

This will run database migrations, optimize configuration, and concurrently start all required background services (serve, reverb, queue workers, and the bot listener).

## License

This project is licensed under the [MIT license](https://opensource.org/licenses/MIT).
