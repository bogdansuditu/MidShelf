<?php
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

$dbPath = getenv('SQLITE_DB_PATH') ?: '/var/www/data/midshelf.db';

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

function showHelp() {
    echo "Usage:\n";
    echo "  php manage_users.php add <username> <password>\n";
    echo "  php manage_users.php delete <username>\n";
    echo "  php manage_users.php list\n";
    echo "  php manage_users.php change-password <username> <new-password>\n";
}

if ($argc < 2) {
    showHelp();
    exit(1);
}

$command = $argv[1];

switch ($command) {
    case 'add':
        if ($argc !== 4) {
            echo "Error: Username and password required\n";
            exit(1);
        }
        $username = $argv[2];
        $password = $argv[3];
        
        try {
            $stmt = $db->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            echo "User '$username' created successfully.\n";
        } catch (PDOException $e) {
            echo "Error creating user: " . $e->getMessage() . "\n";
        }
        break;

    case 'delete':
        if ($argc !== 3) {
            echo "Error: Username required\n";
            exit(1);
        }
        $username = $argv[2];
        
        try {
            $stmt = $db->prepare('DELETE FROM users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                echo "User '$username' deleted successfully.\n";
            } else {
                echo "User '$username' not found.\n";
            }
        } catch (PDOException $e) {
            echo "Error deleting user: " . $e->getMessage() . "\n";
        }
        break;

    case 'list':
        try {
            $stmt = $db->query('SELECT username, created_at, last_login FROM users');
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($users)) {
                echo "No users found.\n";
                break;
            }

            echo "\nUser List:\n";
            echo str_repeat('-', 80) . "\n";
            echo sprintf("%-20s %-30s %-30s\n", 'Username', 'Created At', 'Last Login');
            echo str_repeat('-', 80) . "\n";
            
            foreach ($users as $user) {
                echo sprintf("%-20s %-30s %-30s\n",
                    $user['username'],
                    $user['created_at'],
                    $user['last_login'] ?? 'Never'
                );
            }
        } catch (PDOException $e) {
            echo "Error listing users: " . $e->getMessage() . "\n";
        }
        break;

    case 'change-password':
        if ($argc !== 4) {
            echo "Error: Username and new password required\n";
            exit(1);
        }
        $username = $argv[2];
        $newPassword = $argv[3];
        
        try {
            $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE username = ?');
            $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $username]);
            if ($stmt->rowCount() > 0) {
                echo "Password changed successfully for user '$username'.\n";
            } else {
                echo "User '$username' not found.\n";
            }
        } catch (PDOException $e) {
            echo "Error changing password: " . $e->getMessage() . "\n";
        }
        break;

    default:
        showHelp();
        exit(1);
}
