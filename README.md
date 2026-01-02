# Cha-Ching Chronicles - Budget Tracker

A Laravel Livewire budget tracking application with multi-currency support.

## Features

- **Dashboard**: View total balance in RSD/EUR, monthly savings percentage, income/expense chart
- **Transactions**: Add, edit, delete transactions with support for RSD, EUR, USD currencies
- **Categories**: Categorize expenses (Bills, Food, Rest)
- **Savings**: Yearly and monthly savings breakdown
- **Profile**: Set starting balance
- **Export**: Export transactions to Excel
- **Exchange Rates**: Automatic currency conversion using Fixer.io API

## Requirements

- PHP 8.2+
- Composer
- Node.js & npm
- SQLite/MySQL/PostgreSQL

## Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```

3. Copy the environment file:
   ```bash
   cp .env.example .env
   ```

4. Generate application key:
   ```bash
   php artisan key:generate
   ```

5. Configure your database in `.env`

6. Run migrations:
   ```bash
   php artisan migrate
   ```

7. Build assets:
   ```bash
   npm run build
   ```

8. Start the development server:
   ```bash
   php artisan serve
   ```

## Environment Variables

Add these to your `.env` file:

```env
# Fixer.io API for exchange rates (optional, falls back to defaults)
FIXER_API_KEY=your_fixer_api_key

# API authentication for external access
BUDGET_API_KEY=your_api_key
BUDGET_API_USER_ID=user_id_for_api_access
```

## API Endpoints

### Exchange Rates
- `GET /api/exchange-rates` - Get current exchange rates
- `POST /api/exchange-rates/refresh` - Force refresh rates from API

### Expenses by Category
- `GET /api/expenses-by-category?year=2024&month=1` - Get expenses grouped by category

**Authentication:**
- Session-based for logged-in users
- API Key: Pass `X-API-Key` header for external access

### Export Transactions
- `GET /api/export-transactions` - Download Excel file (requires authentication)

## Development

Run the development server with all services:

```bash
composer dev
```

Or run individually:

```bash
php artisan serve
npm run dev
```

## Testing

```bash
php artisan test
```

## Tech Stack

- **Laravel 12** - PHP Framework
- **Livewire 3 / Volt** - Frontend interactivity
- **Flux UI** - UI Components
- **Tailwind CSS** - Styling
- **Chart.js** - Charts
- **PhpSpreadsheet** - Excel export
- **Laravel Fortify** - Authentication

## License

MIT
