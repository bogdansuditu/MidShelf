# MidShelf

MidShelf is a personal inventory management system that helps you organize and track your items by categories and locations. It provides a clean, modern interface for managing your collection with features like tagging, rating, and filtering.

![MidShelf Screenshot](https://via.placeholder.com/800x450.png?text=MidShelf+Inventory+Management)

## Features

- **Item Management**: Add, edit, and delete items in your inventory
- **Categorization**: Organize items by customizable categories
- **Location Tracking**: Track where your items are stored
- **Tagging System**: Add multiple tags to items for flexible organization
- **Rating System**: Rate your items on a 5-star scale
- **Responsive Design**: Works on desktop and mobile devices
- **User Authentication**: Secure login system
- **Dark Theme**: Easy on the eyes with a modern dark interface

## Technologies Used

### Backend
- **PHP 8.x**: Core programming language
- **SQLite**: Lightweight database for storing inventory data
- **PDO**: Database abstraction layer for secure database operations

### Frontend
- **HTML5/CSS3**: Modern markup and styling
- **JavaScript (ES6+)**: Enhanced user interactions
- **Font Awesome**: Icon library
- **Inter Font**: Clean, modern typography

### Infrastructure
- **Docker**: Containerization for easy deployment
- **Docker Compose**: Multi-container orchestration

## Application Structure

```
MidShelf/
├── data/                  # Database storage
├── docker/                # Docker configuration
│   └── Dockerfile         # PHP container definition
├── docker-compose.yml     # Docker Compose configuration
├── scripts/               # Utility scripts
│   └── init_db.php        # Database initialization
└── src/                   # Application source code
    ├── api/               # API endpoints
    ├── assets/            # Static assets (CSS, JS, images)
    ├── auth/              # Authentication logic
    ├── components/        # Reusable UI components
    ├── config/            # Application configuration
    ├── models/            # Data models
    └── ...                # Main PHP files
```

## Installation

### Prerequisites
- Docker and Docker Compose
- Git (optional)

### Setup Instructions

1. **Clone the repository** (or download and extract)
   ```bash
   git clone https://github.com/yourusername/midshelf.git
   cd midshelf
   ```

2. **Start the Docker container**
   ```bash
   docker-compose up -d
   ```

3. **Initialize the database**
   ```bash
   docker-compose exec web php /scripts/init_db.php
   ```

4. **Access the application**
   Open your browser and navigate to [http://localhost:8080](http://localhost:8080)

5. **Create a user account**
   User accounts are managed through the command-line script:
   ```bash
   docker-compose exec web php /scripts/manage_users.php add <username> <password>
   ```

## Usage Guide

### User Management

All user management is handled via the command-line script:

```bash
# Add a new user
docker-compose exec web php /scripts/manage_users.php add <username> <password>

# List all users
docker-compose exec web php /scripts/manage_users.php list

# Change a user's password
docker-compose exec web php /scripts/manage_users.php change-password <username> <new-password>

# Delete a user
docker-compose exec web php /scripts/manage_users.php delete <username>
```

### Managing Items

1. **Adding Items**
   - Click the "Add Item" button in the top-right corner
   - Fill in the item details (name, description, category, location, etc.)
   - Add tags by typing and pressing Enter
   - Set a rating (1-5 stars)
   - Click "Add Item" to save

2. **Viewing Items**
   - All items are displayed on the home page
   - Use the sidebar to filter by category
   - Click on an item to view its details

3. **Editing/Deleting Items**
   - Use the edit (pencil) icon to modify an item
   - Use the delete (trash) icon to remove an item

### Managing Categories and Locations

1. **Categories**
   - Navigate to the Categories page from the sidebar
   - Add new categories with custom icons and colors
   - Edit or delete existing categories

2. **Locations**
   - Navigate to the Locations page from the sidebar
   - Add new locations with descriptions
   - Edit or delete existing locations

### Interface Navigation

- **Sidebar**: Toggle the sidebar using the hamburger menu icon
- **Home**: View all categories, locations, and items
- **All Items**: View and manage all items in a table format
- **Categories**: Filter items by specific categories
- **Settings**: Access category and location management

## Development

### Local Development Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/midshelf.git
   cd midshelf
   ```

2. **Start the development environment**
   ```bash
   docker-compose up -d
   ```

3. **Make changes to the code**
   - Edit files in the `src` directory
   - Changes will be immediately reflected in the running application

### Database Structure

- **users**: User accounts and authentication
- **categories**: Item categories with customizable icons and colors
- **locations**: Physical or virtual locations where items are stored
- **items**: The main inventory items with their properties
- **tags**: Flexible tagging system
- **items_tags**: Many-to-many relationship between items and tags

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- Font Awesome for the icon library
- Inter font by Rasmus Andersson
- All contributors to the project

---

Built with ❤️ for organizing your stuff.
