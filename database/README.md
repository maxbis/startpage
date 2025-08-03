# Database Files

This directory contains all database-related files for the StartPage application.

## Files

### Setup Files
- `setup.sql` - Main database setup script (creates tables for pages, categories, bookmarks)
- `auth_setup.sql` - Authentication tables setup (users, remember_tokens)

### Migrations
- `migrations/migrate_to_multi_user.sql` - Adds user_id to all tables for multi-user support
- `migrations/migrate_add_user_agent.sql` - Adds user_agent and ip_address to remember_tokens
- `migrations/backup_before_migration.sql` - Backup script before running migrations
- `migrations/rollback_migration.sql` - Rollback script for multi-user migration
- `migrations/verify_migration.sql` - Verification script for multi-user migration

## Usage

1. **Initial Setup**: Run `setup.sql` and `auth_setup.sql` in order
2. **Multi-User Migration**: Run `migrate_to_multi_user.sql` to add user support
3. **User Agent Tracking**: Run `migrate_add_user_agent.sql` to add device tracking

## Migration Order

```bash
# 1. Initial setup
mysql -u username -p database_name < setup.sql
mysql -u username -p database_name < auth_setup.sql

# 2. Multi-user migration (optional)
mysql -u username -p database_name < migrations/migrate_to_multi_user.sql

# 3. User agent tracking (optional)
mysql -u username -p database_name < migrations/migrate_add_user_agent.sql
``` 