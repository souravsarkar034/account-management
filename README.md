# Account Management API

This is a backend-only API built using Laravel and MySQL. It provides features for user authentication, account management, transactions, and generating statements.

## Features

- User registration and login (Laravel Sanctum authentication)
- Create, read, update, and delete accounts
- Perform transactions (debit and credit)
- View transaction history
- Download statements as PDFs
- Transfer funds between accounts

## Installation

Follow these steps to set up the project:

1. Clone the repository:

   ```sh
   git clone https://github.com/souravsarkar034/account-management.git
   cd account-management
   ```

2. Copy the `.env.example` file and update database credentials:

   ```sh
   cp .env.example .env
   ```

3. Install dependencies:

   ```sh
   composer install
   ```

4. Generate an application key:

   ```sh
   php artisan key:generate
   ```

5. Run database migrations:

   ```sh
   php artisan migrate
   ```

6. Start the development server:

   ```sh
   php artisan serve
   ```

## API Endpoints

### Authentication

| Method | Endpoint        | Description          |
|--------|----------------|----------------------|
| POST   | `/api/register` | Register a new user |
| POST   | `/api/login`    | Login and get a token |
| POST   | `/api/logout`   | Logout the user (requires authentication) |

### Account Management

| Method  | Endpoint                          | Description                   |
|---------|-----------------------------------|-------------------------------|
| POST    | `/api/accounts`                   | Create a new account         |
| GET     | `/api/accounts/{account_number}`  | Get account details          |
| PUT     | `/api/accounts/{account_number}`  | Update account details       |
| DELETE  | `/api/accounts/{account_number}`  | Deactivate an account        |

### Transactions

| Method  | Endpoint               | Description                     |
|---------|------------------------|---------------------------------|
| GET     | `/api/transactions`    | List all transactions          |
| POST    | `/api/transactions`    | Perform a debit/credit transaction |
| POST    | `/api/transfer`        | Transfer funds between accounts |
| GET     | `/api/transactions/pdf` | Download PDF transaction statement |

## License

This project is open-source and available under the MIT License.
