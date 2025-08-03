# Database Seeders Documentation

## Overview
Database seeders telah dibuat untuk mengisi database dengan data awal yang diperlukan agar aplikasi dapat berjalan tanpa error. Semua data telah dikonfigurasi untuk mendukung sistem multi-store dengan role-based access control.

## Available Seeders

### 1. RoleSeeder
Membuat role dan permission untuk sistem:
- **Roles**: `super_admin`, `kasir`, `admin`
- **Permissions**: Berbagai permission untuk mengelola stores, products, orders, categories, users, dan cashier shifts

### 2. StoreSeeder
Membuat data toko contoh:
- **Toko A** (TOKO-A) - Jakarta
- **Toko B** (TOKO-B) - Bandung

### 3. UserSeeder
Membuat user untuk testing:
- **Super Admin**: `superadmin@example.com` / `password` (akses semua toko)
- **Kasir Toko A**: `kasir.tokoa@example.com` / `password`
- **Kasir Toko B**: `kasir.tokob@example.com` / `password`
- **Admin Toko A**: `admin.tokoa@example.com` / `password`

### 4. PaymentMethodSeeder
Membuat metode pembayaran untuk setiap toko:
- **Cash** (tunai)
- **Bank Transfer**: BCA, Mandiri
- **E-wallet**: GoPay, OVO, DANA

### 5. ProductGroupSeeder
Membuat grup produk untuk setiap toko:
- Makanan & Minuman
- Elektronik
- Fashion & Aksesoris
- Kesehatan & Kecantikan
- ATK & Perlengkapan

### 6. CategorySeeder
Membuat kategori produk untuk setiap toko:
- Makanan, Minuman, Snack
- Elektronik, Fashion
- Kesehatan, Kecantikan, ATK
- Pulsa & Paket Data, Token Listrik

### 7. ProductSeeder
Membuat produk contoh untuk setiap toko:
- Nasi Gudeg (Rp 15.000 / modal Rp 10.000)
- Es Teh Manis (Rp 5.000 / modal Rp 2.000)
- Charger Samsung (Rp 35.000 / modal Rp 25.000)
- Kopi Hitam (Rp 8.000 / modal Rp 4.000)
- Headset Bluetooth (Rp 150.000 / modal Rp 100.000)

### 8. CashierShiftPermissionsSeeder
Membuat permission khusus untuk cashier shift management.

## Custom Artisan Commands

### Database Management Commands

#### 1. Fresh Database with Seeds
```bash
# Reset database dan run seeders (dengan konfirmasi)
php artisan db:fresh-seed

# Force reset tanpa konfirmasi (untuk development)
php artisan db:fresh-seed --force
```

#### 2. Database Status Check
```bash
# Lihat status dan statistik database
php artisan db:status
```

#### 3. Database Backup
```bash
# Backup database lengkap
php artisan db:backup

# Backup dengan nama file custom
php artisan db:backup --filename=my-backup.sql

# Backup hanya struktur tabel
php artisan db:backup --only-structure
```

## Manual Seeding Commands

### Run All Seeders
```bash
php artisan db:seed
```

### Run Specific Seeder
```bash
php artisan db:seed --class=StoreSeeder
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=PaymentMethodSeeder
```

### Complete Reset Options
```bash
# Drop tables, migrate, dan seed
php artisan migrate:fresh --seed

# Reset hanya dengan seeders
php artisan migrate:fresh && php artisan db:seed
```

## Database Structure After Seeding

### Data Summary
- **👥 Users**: 4 (1 Super Admin, 2 Kasir, 1 Admin)
- **🏪 Stores**: 2 toko dengan data lengkap
- **💳 Payment Methods**: 12 metode pembayaran (6 per toko)
- **📦 Product Groups**: 10 grup produk (5 per toko)
- **🏷️ Categories**: 20 kategori (10 per toko)
- **🛍️ Products**: 10 produk contoh (5 per toko)
- **🔐 Roles**: 3 roles dengan permission lengkap
- **⚡ Permissions**: 19+ permission untuk various features

### Multi-Store Architecture
```
Super Admin (access: ALL STORES)
├── Toko A (TOKO-A)
│   ├── Kasir Toko A
│   ├── Admin Toko A
│   ├── 6 Payment Methods
│   ├── 5 Product Groups
│   ├── 10 Categories
│   └── 5 Products
└── Toko B (TOKO-B)
    ├── Kasir Toko B
    ├── 6 Payment Methods
    ├── 5 Product Groups
    ├── 10 Categories
    └── 5 Products
```

## Login Credentials

### Default Test Accounts
| Role | Email | Password | Store Access |
|------|-------|----------|--------------|
| Super Admin | `superadmin@example.com` | `password` | All Stores |
| Kasir | `kasir.tokoa@example.com` | `password` | Toko A Only |
| Kasir | `kasir.tokob@example.com` | `password` | Toko B Only |
| Admin | `admin.tokoa@example.com` | `password` | Toko A Only |

## Features Ready for Testing

### ✅ Fully Functional Features
- Multi-store management
- Role-based access control (RBAC)
- User management with store assignment
- Payment method management per store
- Product categorization and grouping
- Inventory management with cost tracking
- Cashier shift system
- Permission-based UI/UX

### 🔧 Available Management Tools
- Database status monitoring
- Automated backup system
- One-command database reset
- Data validation and health checks

## Database Health Monitoring

The `php artisan db:status` command performs automatic health checks:

- ✅ All users have roles assigned
- ✅ All stores have payment methods
- ✅ All stores have categories
- ✅ All products have categories assigned
- ✅ All products have cost price defined

## Backup & Restore

### Creating Backups
```bash
# Full backup (recommended)
php artisan db:backup

# Structure only backup
php artisan db:backup --only-structure --filename=structure-backup.sql
```

### Backup Location
Backups are stored in: `storage/app/backups/`

### Backup Naming Convention
- Auto-generated: `backup-YYYY-MM-DD_HH-mm-ss.sql`
- Custom: Use `--filename` option

## Troubleshooting

### Common Issues & Solutions

#### 1. Migration Errors
```bash
# Issue: Foreign key constraint errors
# Solution: Check migration order
php artisan migrate:fresh-seed --force
```

#### 2. Duplicate Data Errors
```bash
# Issue: Unique constraint violations
# Solution: Seeders use firstOrCreate() to prevent duplicates
php artisan db:fresh-seed --force
```

#### 3. Permission Errors
```bash
# Issue: Role/permission not working
# Solution: Clear cache and re-seed
php artisan permission:cache-reset
php artisan db:fresh-seed --force
```

#### 4. Empty Database
```bash
# Quick fix: Reset everything
php artisan db:fresh-seed --force
```

### Emergency Reset
If anything goes wrong, use the nuclear option:
```bash
php artisan db:fresh-seed --force
```

## Production Deployment Notes

### 🚨 Important Security Considerations

1. **Change Default Passwords**: Update all default passwords before production
2. **Environment Configuration**: Set proper database credentials
3. **Backup Strategy**: Implement regular automated backups
4. **Permission Review**: Audit all roles and permissions
5. **User Audit**: Remove/update test accounts

### Production Seeding
```bash
# For production, review and customize seeders first
# Then run with caution:
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=StoreSeeder
# ... etc (selective seeding recommended)
```

## Development Workflow

### Daily Development
```bash
# Quick reset for testing
php artisan db:fresh-seed --force

# Check data after changes
php artisan db:status
```

### Before Deployment
```bash
# Create backup
php artisan db:backup --filename=pre-deployment-backup.sql

# Verify data integrity
php artisan db:status
```

---

**📝 Note**: This seeding strategy ensures your POS system has a complete, functional dataset for immediate testing and development. All relationships are properly configured and the multi-store architecture is fully supported.
