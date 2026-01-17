# 🚀 PANDUAN IMPORT KE PRODUCTION

## Files SQL yang Perlu Diimport (Urutan!)

```
database/sql/
├── 00_create_table.sql      # Create tabel satuan_pendidikan
├── import_satpen_part1.sql  # Data SMP Negeri (23 sekolah)
├── import_satpen_part2.sql  # Data SMP Swasta Part 1 (52 sekolah)
├── import_satpen_part3.sql  # Data SMP Swasta Part 2 (52 sekolah)
└── import_users_from_satpen.sql # Create users dari satuan_pendidikan
```

---

## Langkah Import via phpMyAdmin

### 1. Buka phpMyAdmin di hosting
### 2. Pilih database SIMAK
### 3. Klik tab "Import"
### 4. Import file secara berurutan:
   1. `00_create_table.sql`
   2. `import_satpen_part1.sql`
   3. `import_satpen_part2.sql`
   4. `import_satpen_part3.sql`
   5. `import_users_from_satpen.sql`

### 5. Update password via SSH/Artisan:

```bash
php artisan tinker
```

```php
$users = \App\Models\User::where('role', 'Sekolah')->get();
foreach($users as $u) { 
    $u->password = bcrypt($u->npsn . 'Fa*'); 
    $u->save(); 
    echo "Updated: " . $u->npsn . "\n";
}
```

---

## Files PHP yang Perlu Di-copy ke Production

Copy folder/file berikut ke production:

```
app/
├── Models/
│   └── SatuanPendidikan.php          # NEW
├── Observers/
│   └── SatuanPendidikanObserver.php  # NEW
└── Http/Controllers/
    └── SatuanPendidikanController.php # NEW

routes/
└── web.php                            # UPDATED

database/
├── migrations/
│   └── 2024_01_01_000004_create_satuan_pendidikan_table.php # NEW
└── seeders/
    ├── DatabaseSeeder.php             # UPDATED
    └── SatuanPendidikanSeeder.php     # NEW
```

---

## Test Login

| Sekolah | Username | Password |
|---------|----------|----------|
| SMP NEGERI 1 BOGOR | 20220385 | 20220385Fa* |
| SMP NEGERI 13 BOGOR | 20220389 | 20220389Fa* |
| SMP AL AZHAR PLUS BOGOR | 20220270 | 20220270Fa* |

---

## Total Data

- **Satuan Pendidikan**: 127 sekolah (23 Negeri + 104 Swasta)
- **Users (Role: Sekolah)**: 127 akun