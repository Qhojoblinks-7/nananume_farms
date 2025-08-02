<?php
// Script to apply database migrations
require_once '../config/database.php';




try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if migrations table exists, create if not
    $checkTable = "SELECT name FROM sqlite_master WHERE type='table' AND name='migrations'";
    $stmt = $db->prepare($checkTable);
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        $createMigrationsTable = "CREATE TABLE migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration_name VARCHAR(255) NOT NULL,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $db->exec($createMigrationsTable);
    }
    
    // Get list of applied migrations
    $appliedMigrations = [];
    $getApplied = "SELECT migration_name FROM migrations ORDER BY id";
    $stmt = $db->prepare($getApplied);
    $stmt->execute();
    while ($row = $stmt->fetch()) {
        $appliedMigrations[] = $row['migration_name'];
    }
    
    // Get migration files
    $migrationDir = __DIR__ . '/migrations';
    $migrationFiles = glob($migrationDir . '/*.sql');
    
    // Apply new migrations
    foreach ($migrationFiles as $migrationFile) {
        $migrationName = basename($migrationFile);
        
        if (!in_array($migrationName, $appliedMigrations)) {
            echo "Applying migration: $migrationName\n";
            
            // Read and execute migration SQL
            $sql = file_get_contents($migrationFile);
            $db->exec($sql);
            
            // Record migration as applied
            $insertMigration = "INSERT INTO migrations (migration_name) VALUES (?)";
            $stmt = $db->prepare($insertMigration);
            $stmt->execute([$migrationName]);
            
            echo "Migration $migrationName applied successfully.\n";
        } else {
            echo "Migration $migrationName already applied.\n";
        }
    }
    
    echo "All migrations completed.\n";
    
} catch (Exception $e) {
    echo "Error applying migrations: " . $e->getMessage() . "\n";
}
?>
