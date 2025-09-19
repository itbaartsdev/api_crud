# Backup System Documentation

## Overview
The system has been updated to handle duplicate tables, folders, and files by automatically creating backups with "_backup" suffix.

## How it works

### Database Tables
When creating a new table:
1. If table name "barang" already exists
2. The existing table will be renamed to "barang_backup"
3. If "barang_backup" also exists, it will be renamed to "barang_backup2"
4. And so on: barang_backup3, barang_backup4, etc.

### Folders
When creating new folders (Panel and images directories):
1. If folder "Barang" already exists in Panel/
2. The existing folder will be renamed to "Barang_backup"
3. If "Barang_backup" also exists, it will be renamed to "Barang_backup2"
4. Same logic applies for images/ folders

### Files
When creating laporan files:
1. If file "barang.php" already exists in laporan/
2. The existing file will be renamed to "barang_backup.php"
3. If "barang_backup.php" also exists, it will be renamed to "barang_backup2.php"

## Functions Added

### getUniqueTableName($koneksi, $tableName)
- Checks if table exists in database
- Renames existing table to backup name
- Returns original table name for new table creation

### getUniqueFolderName($basePath, $folderName)
- Checks if folder exists at specified path
- Renames existing folder to backup name
- Returns original folder name for new folder creation

### getUniqueFileName($basePath, $fileName, $extension)
- Checks if file exists at specified path
- Renames existing file to backup name
- Returns original file name for new file creation

## Benefits
- No more conflicts when creating tables/folders with same names
- Old data is preserved with backup naming
- Automatic incremental backup numbering
- No manual intervention required