# Backend Schema Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the full v1 PostgreSQL schema (migrations 007–026 from the spec), their Eloquent models, and the development seeders, so every later backend feature (checkout, capacity reservation, Filament admin) has a working, constraint-enforcing data layer to build on.

**Architecture:** One migration file + one Eloquent model per table, added in the exact dependency order the spec lists (`md-file/2026-07-31-ninadough-final-project-spec.md` §9). Each migration enforces the spec's stated constraints (uniqueness, FK delete behaviour, non-negative/positive numeric checks, partial unique indexes) directly in PostgreSQL via `DB::statement` check/partial-unique constraints, not just app-level validation — the spec's capacity-safety design (§8) depends on the database itself refusing bad states, not just Laravel remembering to check. Each task's test is a Feature test that persists a real row and asserts the specific constraint the spec calls out for that table.

**Tech Stack:** Laravel 13, PostgreSQL 16 (via the `postgres` service container already in CI), PHPUnit (`artisan test`), Eloquent `HasUlids`.

## Global Constraints

- PHP `^8.4`, Laravel `^13.8` (backend/composer.json).
- Business timezone `Asia/Kuala_Lumpur`; all timestamps stored as UTC `timestamptz` — use `timestampTz()` / `timestampsTz()`, never `timestamps()`.
- Internal keys are `bigint` auto-increment `id`. Any table the spec marks "public records get a ulid" must also get a unique `char(26)` `ulid` column — never expose `id` externally.
- Money is integer **sen** in `bigint` columns, never floats.
- Statuses are `varchar` + Laravel-side enum validation, not native Postgres `enum` types.
- `softDeletesTz()` only on: `products`, `customers`, `customer_addresses`, `delivery_zones` (per spec §9 schema rules).
- Every numeric constraint the spec states in prose (`>= 0`, `> 0`, `<= capacity_limit`) must be a real Postgres `CHECK` constraint added via `DB::statement(...)` inside the migration's `up()` — Postgres has no `unsigned` integer type, so `unsignedBigInteger()` alone does **not** enforce non-negativity there.
- Run `php artisan test` and `php artisan migrate:fresh --force` locally against the same `postgres:16-alpine` container CI uses (see `.github/workflows/ci.yml` for the exact service config) before every commit in this plan.

---

## File Structure

```
backend/
  app/Models/
    Concerns/HasUlid.php              (Task 1)
    User.php                          (Task 1, modified)
    BusinessSetting.php                (Task 2)
    Product.php                        (Task 3)
    ProductImage.php                   (Task 3)
    ProductOptionGroup.php             (Task 4)
    ProductOptionValue.php             (Task 4)
    ProductVariant.php                 (Task 5)
    PreorderDate.php                   (Task 6)
    DeliveryZone.php                   (Task 7)
    DeliveryZonePostcode.php           (Task 7)
    Customer.php                       (Task 8)
    CustomerAddress.php                (Task 8)
    Order.php                          (Task 9)
    OrderItem.php                      (Task 10)
    OrderItemOptionValue.php           (Task 10)
    OrderStatusEvent.php               (Task 11)
    Payment.php                        (Task 12)
    PaymentProof.php                   (Task 12)
    NotificationLog.php                (Task 13)
    ActivityLog.php                    (Task 13)
  database/
    migrations/
      0001_01_01_000000_create_users_table.php          (Task 1, modified)
      2026_07_31_100000_create_business_settings_table.php   (Task 2)
      2026_07_31_100100_create_products_table.php             (Task 3)
      2026_07_31_100101_create_product_images_table.php       (Task 3)
      2026_07_31_100200_create_product_option_groups_table.php (Task 4)
      2026_07_31_100201_create_product_option_values_table.php (Task 4)
      2026_07_31_100300_create_product_variants_table.php      (Task 5)
      2026_07_31_100301_create_product_variant_option_values_table.php (Task 5)
      2026_07_31_100400_create_preorder_dates_table.php        (Task 6)
      2026_07_31_100500_create_delivery_zones_table.php        (Task 7)
      2026_07_31_100501_create_delivery_zone_postcodes_table.php (Task 7)
      2026_07_31_100600_create_customers_table.php             (Task 8)
      2026_07_31_100601_create_customer_addresses_table.php    (Task 8)
      2026_07_31_100700_create_orders_table.php                (Task 9)
      2026_07_31_100800_create_order_items_table.php            (Task 10)
      2026_07_31_100801_create_order_item_option_values_table.php (Task 10)
      2026_07_31_100900_create_order_status_events_table.php    (Task 11)
      2026_07_31_101000_create_payments_table.php               (Task 12)
      2026_07_31_101001_create_payment_proofs_table.php         (Task 12)
      2026_07_31_101100_create_notification_logs_table.php      (Task 13)
      2026_07_31_101101_create_activity_logs_table.php          (Task 13)
    factories/
      BusinessSettingFactory.php, ProductFactory.php, ProductImageFactory.php,
      ProductOptionGroupFactory.php, ProductOptionValueFactory.php,
      ProductVariantFactory.php, PreorderDateFactory.php, DeliveryZoneFactory.php,
      DeliveryZonePostcodeFactory.php, CustomerFactory.php, CustomerAddressFactory.php,
      OrderFactory.php, OrderItemFactory.php, OrderItemOptionValueFactory.php,
      OrderStatusEventFactory.php, PaymentFactory.php, PaymentProofFactory.php,
      NotificationLogFactory.php, ActivityLogFactory.php     (created alongside each task's model)
    seeders/
      OwnerUserSeeder.php, BusinessSettingsSeeder.php, ProductSeeder.php,
      PreorderDateSeeder.php, DeliveryZoneSeeder.php, OrderDemoSeeder.php  (Task 14)
  tests/Feature/Schema/
    UsersTableTest.php                 (Task 1)
    BusinessSettingsTableTest.php      (Task 2)
    ProductsTableTest.php              (Task 3)
    ProductOptionsTableTest.php        (Task 4)
    ProductVariantsTableTest.php       (Task 5)
    PreorderDatesTableTest.php         (Task 6)
    DeliveryZonesTableTest.php         (Task 7)
    CustomersTableTest.php            (Task 8)
    OrdersTableTest.php               (Task 9)
    OrderItemsTableTest.php           (Task 10)
    OrderStatusEventsTableTest.php    (Task 11)
    PaymentsTableTest.php             (Task 12)
    LogsTableTest.php                 (Task 13)
  tests/Feature/DatabaseSeedersTest.php (Task 14)
  tests/Feature/MigrationVerificationTest.php (Task 15)
```

Out of scope for this plan (later plans): checkout/reservation business logic (`lockForUpdate()` capacity flow, §8), Filament admin panel, public API endpoints (§10), WhatsApp expiry scheduler. This plan only builds the data layer those features will sit on. The API health endpoint the spec asks for in Phase 1 is **already satisfied** — Laravel 13's default `bootstrap/app.php` already registers `health: '/up'` — no task needed for it.

---

### Task 1: ULID trait + `users` table brought up to spec

**Files:**
- Create: `backend/app/Models/Concerns/HasUlid.php`
- Modify: `backend/database/migrations/0001_01_01_000000_create_users_table.php`
- Modify: `backend/app/Models/User.php`
- Test: `backend/tests/Feature/Schema/UsersTableTest.php`

**Interfaces:**
- Produces: `App\Models\Concerns\HasUlid` trait — any model using it gets an auto-generated `ulid` column (26-char) on create, via `uniqueIds(): array { return ['ulid']; }`, while keeping `id` as the normal auto-increment primary key. Every later task's model (`Product`, `Customer`, `Order`, `Payment`, `User`) uses this trait.

- [ ] **Step 1: Write the failing test**

```php
<?php
// backend/tests/Feature/Schema/UsersTableTest.php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates a unique 26-character ulid and defaults role/is_active', function () {
    $user = User::factory()->create();

    expect($user->ulid)->toHaveLength(26);
    expect($user->role)->toBe('owner');
    expect($user->is_active)->toBeTrue();
});

it('rejects a second user with a duplicate ulid', function () {
    $first = User::factory()->create();

    expect(fn () => User::factory()->create(['ulid' => $first->ulid]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd backend && php artisan test --filter=UsersTableTest`
Expected: FAIL — `ulid` column / attribute does not exist on `users`.

- [ ] **Step 3: Modify the users migration**

```php
<?php
// backend/database/migrations/0001_01_01_000000_create_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role', 30)->default('owner');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();

            $table->index(['is_active', 'role']);
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('owner', 'staff'))");

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
```

- [ ] **Step 4: Create the HasUlid trait**

```php
<?php
// backend/app/Models/Concerns/HasUlid.php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

trait HasUlid
{
    use HasUlids;

    public function uniqueIds(): array
    {
        return ['ulid'];
    }
}
```

- [ ] **Step 5: Update the User model**

```php
<?php
// backend/app/Models/User.php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUlid, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
```

- [ ] **Step 6: Run migrations and test to verify it passes**

Run: `cd backend && php artisan migrate:fresh --force && php artisan test --filter=UsersTableTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add backend/app/Models/Concerns/HasUlid.php backend/app/Models/User.php \
        backend/database/migrations/0001_01_01_000000_create_users_table.php \
        backend/tests/Feature/Schema/UsersTableTest.php
git commit -m "feat(schema): add ulid/role/is_active to users table"
```

---

### Task 2: `business_settings` table

**Files:**
- Create: `backend/database/migrations/2026_07_31_100000_create_business_settings_table.php`
- Create: `backend/app/Models/BusinessSetting.php`
- Create: `backend/database/factories/BusinessSettingFactory.php`
- Test: `backend/tests/Feature/Schema/BusinessSettingsTableTest.php`

**Interfaces:**
- Consumes: `App\Models\User` (Task 1) for `updated_by_user_id`.
- Produces: `App\Models\BusinessSetting` with `key` (string, unique), `value` (array cast), `is_public` (bool). Later Filament settings screens and any config-reading code read `BusinessSetting::where('key', ...)->first()?->value`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// backend/tests/Feature/Schema/BusinessSettingsTableTest.php

use App\Models\BusinessSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores a jsonb value and casts it back to an array', function () {
    $setting = BusinessSetting::factory()->create([
        'key' => 'business_name',
        'value' => ['name' => 'ninadough'],
    ]);

    expect($setting->fresh()->value)->toBe(['name' => 'ninadough']);
});

it('rejects a duplicate key', function () {
    BusinessSetting::factory()->create(['key' => 'timezone']);

    expect(fn () => BusinessSetting::factory()->create(['key' => 'timezone']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd backend && php artisan test --filter=BusinessSettingsTableTest`
Expected: FAIL — table `business_settings` does not exist.

- [ ] **Step 3: Write the migration**

```php
<?php
// backend/database/migrations/2026_07_31_100000_create_business_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->jsonb('value')->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_settings');
    }
};
```

- [ ] **Step 4: Write the model**

```php
<?php
// backend/app/Models/BusinessSetting.php

namespace App\Models;

use Database\Factories\BusinessSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'is_public', 'updated_by_user_id'])]
class BusinessSetting extends Model
{
    /** @use HasFactory<BusinessSettingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'is_public' => 'boolean',
        ];
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
```

- [ ] **Step 5: Write the factory**

```php
<?php
// backend/database/factories/BusinessSettingFactory.php

namespace Database\Factories;

use App\Models\BusinessSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessSettingFactory extends Factory
{
    protected $model = BusinessSetting::class;

    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->slug(2),
            'value' => ['sample' => $this->faker->word()],
            'is_public' => false,
        ];
    }
}
```

- [ ] **Step 6: Run migrations and test to verify it passes**

Run: `cd backend && php artisan migrate:fresh --force && php artisan test --filter=BusinessSettingsTableTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add backend/database/migrations/2026_07_31_100000_create_business_settings_table.php \
        backend/app/Models/BusinessSetting.php \
        backend/database/factories/BusinessSettingFactory.php \
        backend/tests/Feature/Schema/BusinessSettingsTableTest.php
git commit -m "feat(schema): add business_settings table"
```

---

### Task 3: `products` + `product_images` tables

**Files:**
- Create: `backend/database/migrations/2026_07_31_100100_create_products_table.php`
- Create: `backend/database/migrations/2026_07_31_100101_create_product_images_table.php`
- Create: `backend/app/Models/Product.php`, `backend/app/Models/ProductImage.php`
- Create: `backend/database/factories/ProductFactory.php`, `backend/database/factories/ProductImageFactory.php`
- Test: `backend/tests/Feature/Schema/ProductsTableTest.php`

**Interfaces:**
- Consumes: `App\Models\Concerns\HasUlid` (Task 1).
- Produces: `App\Models\Product` (`ulid`, `slug`, `base_price_sen`, `is_active`, soft-deletable) with `images()` relation; `App\Models\ProductImage` with `product()` relation and `is_primary` uniqueness per product. Task 4/5 (option groups, variants) both `belongsTo(Product::class)`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// backend/tests/Feature/Schema/ProductsTableTest.php

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a product with a ulid and soft-deletes it', function () {
    $product = Product::factory()->create();

    expect($product->ulid)->toHaveLength(26);

    $product->delete();

    expect(Product::find($product->id))->toBeNull();
    expect(Product::withTrashed()->find($product->id))->not->toBeNull();
});

it('rejects a negative base_price_sen', function () {
    expect(fn () => Product::factory()->create(['base_price_sen' => -100]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('only allows one primary image per product', function () {
    $product = Product::factory()->create();
    ProductImage::factory()->for($product)->create(['is_primary' => true]);

    expect(fn () => ProductImage::factory()->for($product)->create(['is_primary' => true]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('deletes product images when the product is force-deleted', function () {
    $product = Product::factory()->create();
    $image = ProductImage::factory()->for($product)->create();

    $product->forceDelete();

    expect(ProductImage::find($image->id))->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd backend && php artisan test --filter=ProductsTableTest`
Expected: FAIL — table `products` does not exist.

- [ ] **Step 3: Write the products migration**

```php
<?php
// backend/database/migrations/2026_07_31_100100_create_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('short_description', 500)->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('base_price_sen');
            $table->integer('default_capacity_units')->default(1);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->text('allergen_information')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['is_active', 'sort_order']);
            $table->index(['is_featured', 'is_active']);
        });

        DB::statement('ALTER TABLE products ADD CONSTRAINT products_base_price_sen_check CHECK (base_price_sen >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_default_capacity_units_check CHECK (default_capacity_units > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

- [ ] **Step 4: Write the product_images migration**

```php
<?php
// backend/database/migrations/2026_07_31_100101_create_product_images_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('storage_disk', 50);
            $table->string('object_key', 500);
            $table->string('public_url', 1000)->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestampsTz();

            $table->index(['product_id', 'sort_order']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX product_images_one_primary_per_product ' .
            'ON product_images (product_id) WHERE is_primary = true'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
```

- [ ] **Step 5: Write the models**

```php
<?php
// backend/app/Models/Product.php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'slug', 'short_description', 'description', 'base_price_sen',
    'default_capacity_units', 'is_active', 'is_featured', 'sort_order',
    'allergen_information',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasUlid, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function optionGroups()
    {
        return $this->hasMany(ProductOptionGroup::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
```

```php
<?php
// backend/app/Models/ProductImage.php

namespace App\Models;

use Database\Factories\ProductImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['product_id', 'storage_disk', 'object_key', 'public_url', 'alt_text', 'sort_order', 'is_primary'])]
class ProductImage extends Model
{
    /** @use HasFactory<ProductImageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
```

- [ ] **Step 6: Write the factories**

```php
<?php
// backend/database/factories/ProductFactory.php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'name' => ucfirst($name),
            'slug' => str($name)->slug(),
            'base_price_sen' => $this->faker->numberBetween(500, 15000),
            'default_capacity_units' => 1,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }
}
```

```php
<?php
// backend/database/factories/ProductImageFactory.php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'storage_disk' => 'spaces',
            'object_key' => 'products/' . $this->faker->uuid() . '.jpg',
            'sort_order' => 0,
            'is_primary' => false,
        ];
    }
}
```

- [ ] **Step 7: Run migrations and test to verify it passes**

Run: `cd backend && php artisan migrate:fresh --force && php artisan test --filter=ProductsTableTest`
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add backend/database/migrations/2026_07_31_100100_create_products_table.php \
        backend/database/migrations/2026_07_31_100101_create_product_images_table.php \
        backend/app/Models/Product.php backend/app/Models/ProductImage.php \
        backend/database/factories/ProductFactory.php backend/database/factories/ProductImageFactory.php \
        backend/tests/Feature/Schema/ProductsTableTest.php
git commit -m "feat(schema): add products and product_images tables"
```

---

### Task 4: `product_option_groups` + `product_option_values` tables

**Files:**
- Create: `backend/database/migrations/2026_07_31_100200_create_product_option_groups_table.php`
- Create: `backend/database/migrations/2026_07_31_100201_create_product_option_values_table.php`
- Create: `backend/app/Models/ProductOptionGroup.php`, `backend/app/Models/ProductOptionValue.php`
- Create: `backend/database/factories/ProductOptionGroupFactory.php`, `backend/database/factories/ProductOptionValueFactory.php`
- Test: `backend/tests/Feature/Schema/ProductOptionsTableTest.php`

**Interfaces:**
- Consumes: `App\Models\Product` (Task 3).
- Produces: `App\Models\ProductOptionGroup` (`belongsTo Product`, `hasMany ProductOptionValue`), `App\Models\ProductOptionValue` (`belongsTo ProductOptionGroup`). Task 5's `product_variant_option_values` pivot references `product_option_values.id`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// backend/tests/Feature/Schema/ProductOptionsTableTest.php

use App\Models\Product;
use App\Models\ProductOptionGroup;
use App\Models\ProductOptionValue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects two option groups with the same name on the same product', function () {
    $product = Product::factory()->create();
    ProductOptionGroup::factory()->for($product)->create(['name' => 'Size']);

    expect(fn () => ProductOptionGroup::factory()->for($product)->create(['name' => 'Size']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('rejects two option values with the same value_code in the same group', function () {
    $group = ProductOptionGroup::factory()->create();
    ProductOptionValue::factory()->for($group, 'productOptionGroup')->create(['value_code' => 'small']);

    expect(fn () => ProductOptionValue::factory()->for($group, 'productOptionGroup')->create(['value_code' => 'small']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd backend && php artisan test --filter=ProductOptionsTableTest`
Expected: FAIL — table `product_option_groups` does not exist.

- [ ] **Step 3: Write the migrations**

```php
<?php
// backend/database/migrations/2026_07_31_100200_create_product_option_groups_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_option_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('selection_type', 20)->default('single');
            $table->boolean('is_required')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();

            $table->unique(['product_id', 'name']);
            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_option_groups');
    }
};
```

```php
<?php
// backend/database/migrations/2026_07_31_100201_create_product_option_values_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_group_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('value_code', 80);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();

            $table->unique(['product_option_group_id', 'value_code']);
            $table->index(['product_option_group_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_option_values');
    }
};
```

- [ ] **Step 4: Write the models**

```php
<?php
// backend/app/Models/ProductOptionGroup.php

namespace App\Models;

use Database\Factories\ProductOptionGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['product_id', 'name', 'selection_type', 'is_required', 'sort_order'])]
class ProductOptionGroup extends Model
{
    /** @use HasFactory<ProductOptionGroupFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_required' => 'boolean'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function values()
    {
        return $this->hasMany(ProductOptionValue::class);
    }
}
```

```php
<?php
// backend/app/Models/ProductOptionValue.php

namespace App\Models;

use Database\Factories\ProductOptionValueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['product_option_group_id', 'name', 'value_code', 'is_active', 'sort_order'])]
class ProductOptionValue extends Model
{
    /** @use HasFactory<ProductOptionValueFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function productOptionGroup()
    {
        return $this->belongsTo(ProductOptionGroup::class);
    }
}
```

- [ ] **Step 5: Write the factories**

```php
<?php
// backend/database/factories/ProductOptionGroupFactory.php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductOptionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductOptionGroupFactory extends Factory
{
    protected $model = ProductOptionGroup::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => $this->faker->unique()->randomElement(['Size', 'Flavour', 'Topping']),
            'selection_type' => 'single',
            'is_required' => true,
            'sort_order' => 0,
        ];
    }
}
```

```php
<?php
// backend/database/factories/ProductOptionValueFactory.php

namespace Database\Factories;

use App\Models\ProductOptionGroup;
use App\Models\ProductOptionValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductOptionValueFactory extends Factory
{
    protected $model = ProductOptionValue::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'product_option_group_id' => ProductOptionGroup::factory(),
            'name' => ucfirst($name),
            'value_code' => str($name)->slug(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
```

- [ ] **Step 6: Run migrations and test to verify it passes**

Run: `cd backend && php artisan migrate:fresh --force && php artisan test --filter=ProductOptionsTableTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add backend/database/migrations/2026_07_31_100200_create_product_option_groups_table.php \
        backend/database/migrations/2026_07_31_100201_create_product_option_values_table.php \
        backend/app/Models/ProductOptionGroup.php backend/app/Models/ProductOptionValue.php \
        backend/database/factories/ProductOptionGroupFactory.php backend/database/factories/ProductOptionValueFactory.php \
        backend/tests/Feature/Schema/ProductOptionsTableTest.php
git commit -m "feat(schema): add product_option_groups and product_option_values tables"
```

---

### Task 5: `product_variants` + `product_variant_option_values` tables

**Files:**
- Create: `backend/database/migrations/2026_07_31_100300_create_product_variants_table.php`
- Create: `backend/database/migrations/2026_07_31_100301_create_product_variant_option_values_table.php`
- Create: `backend/app/Models/ProductVariant.php`
- Create: `backend/database/factories/ProductVariantFactory.php`
- Test: `backend/tests/Feature/Schema/ProductVariantsTableTest.php`

**Interfaces:**
- Consumes: `App\Models\Product` (Task 3), `App\Models\ProductOptionValue` (Task 4).
- Produces: `App\Models\ProductVariant` with `optionValues()` (`belongsToMany ProductOptionValue` through `product_variant_option_values`). Task 10 (`order_items`) references `product_variants.id`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// backend/tests/Feature/Schema/ProductVariantsTableTest.php

use App\Models\Product;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects a capacity_units value that is not positive', function () {
    $product = Product::factory()->create();

    expect(fn () => ProductVariant::factory()->for($product)->create(['capacity_units' => 0]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('attaches option values to a variant through the pivot table', function () {
    $variant = ProductVariant::factory()->create();
    $value = ProductOptionValue::factory()->create();

    $variant->optionValues()->attach($value);

    expect($variant->fresh()->optionValues)->toHaveCount(1);
});

it('rejects attaching the same option value twice to a variant', function () {
    $variant = ProductVariant::factory()->create();
    $value = ProductOptionValue::factory()->create();
    $variant->optionValues()->attach($value);

    expect(fn () => $variant->optionValues()->attach($value))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd backend && php artisan test --filter=ProductVariantsTableTest`
Expected: FAIL — table `product_variants` does not exist.

- [ ] **Step 3: Write the migrations**

```php
<?php
// backend/database/migrations/2026_07_31_100300_create_product_variants_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 100)->nullable()->unique();
            $table->string('name', 180);
            $table->bigInteger('price_adjustment_sen')->default(0);
            $table->integer('capacity_units')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();

            $table->index(['product_id', 'is_active', 'sort_order']);
        });

        DB::statement(
            'ALTER TABLE product_variants ADD CONSTRAINT product_variants_capacity_units_check ' .
            'CHECK (capacity_units IS NULL OR capacity_units > 0)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
```

```php
<?php
// backend/database/migrations/2026_07_31_100301_create_product_variant_option_values_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_option_values', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_option_value_id')->constrained()->restrictOnDelete();
            $table->timestampsTz();

            $table->primary(['product_variant_id', 'product_option_value_id']);
            $table->index(['product_option_value_id', 'product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_option_values');
    }
};
```

- [ ] **Step 4: Write the model**

```php
<?php
// backend/app/Models/ProductVariant.php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['product_id', 'sku', 'name', 'price_adjustment_sen', 'capacity_units', 'is_active', 'sort_order'])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function optionValues()
    {
        return $this->belongsToMany(ProductOptionValue::class, 'product_variant_option_values')
            ->withTimestamps();
    }
}
```

- [ ] **Step 5: Write the factory**

```php
<?php
// backend/database/factories/ProductVariantFactory.php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => $this->faker->words(2, true),
            'price_adjustment_sen' => 0,
            'capacity_units' => 1,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
```

- [ ] **Step 6: Run migrations and test to verify it passes**

Run: `cd backend && php artisan migrate:fresh --force && php artisan test --filter=ProductVariantsTableTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add backend/database/migrations/2026_07_31_100300_create_product_variants_table.php \
        backend/database/migrations/2026_07_31_100301_create_product_variant_option_values_table.php \
        backend/app/Models/ProductVariant.php backend/database/factories/ProductVariantFactory.php \
        backend/tests/Feature/Schema/ProductVariantsTableTest.php
git commit -m "feat(schema): add product_variants and product_variant_option_values tables"
```

---

### Task 6: `preorder_dates` table

**Files:**
- Create: `backend/database/migrations/2026_07_31_100400_create_preorder_dates_table.php`
- Create: `backend/app/Models/PreorderDate.php`
- Create: `backend/database/factories/PreorderDateFactory.php`
- Test: `backend/tests/Feature/Schema/PreorderDatesTableTest.php`

**Interfaces:**
- Consumes: `App\Models\User` (Task 1) for `created_by_user_id`.
- Produces: `App\Models\PreorderDate` — the row every future checkout-reservation task (§8) will `lockForUpdate()` on. Task 9 (`orders`) references `preorder_dates.id`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// backend/tests/Feature/Schema/PreorderDatesTableTest.php

use App\Models\PreorderDate;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects a second preorder_date row for the same order_date', function () {
    PreorderDate::factory()->create(['order_date' => '2026-08-15']);

    expect(fn () => PreorderDate::factory()->create(['order_date' => '2026-08-15']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('rejects reserved_capacity greater than capacity_limit', function () {
    expect(fn () => PreorderDate::factory()->create([
        'capacity_limit' => 10,
        'reserved_capacity' => 11,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('rejects a negative reserved_capacity', function () {
    expect(fn () => PreorderDate::factory()->create(['reserved_capacity' => -1]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd backend && php artisan test --filter=PreorderDatesTableTest`
Expected: FAIL — table `preorder_dates` does not exist.

- [ ] **Step 3: Write the migration**

```php
<?php
// backend/database/migrations/2026_07_31_100400_create_preorder_dates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preorder_dates', function (Blueprint $table) {
            $table->id();
            $table->date('order_date')->unique();
            $table->timestampTz('cutoff_at');
            $table->integer('capacity_limit');
            $table->integer('reserved_capacity')->default(0);
            $table->boolean('pickup_enabled')->default(true);
            $table->boolean('delivery_enabled')->default(true);
            $table->string('status', 20)->default('open');
            $table->string('note_internal', 500)->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['status', 'order_date']);
        });

        DB::statement("ALTER TABLE preorder_dates ADD CONSTRAINT preorder_dates_status_check CHECK (status IN ('open', 'closed', 'full'))");
        DB::statement('ALTER TABLE preorder_dates ADD CONSTRAINT preorder_dates_capacity_limit_check CHECK (capacity_limit > 0)');
        DB::statement('ALTER TABLE preorder_dates ADD CONSTRAINT preorder_dates_reserved_capacity_check CHECK (reserved_capacity >= 0)');
        DB::statement('ALTER TABLE preorder_dates ADD CONSTRAINT preorder_dates_reserved_within_limit_check CHECK (reserved_capacity <= capacity_limit)');
    }

    public function down(): void
    {
        Schema::dropIfExists('preorder_dates');
    }
};
```

- [ ] **Step 4: Write the model**

```php
<?php
// backend/app/Models/PreorderDate.php

namespace App\Models;

use Database\Factories\PreorderDateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'order_date', 'cutoff_at', 'capacity_limit', 'reserved_capacity',
    'pickup_enabled', 'delivery_enabled', 'status', 'note_internal', 'created_by_user_id',
])]
class PreorderDate extends Model
{
    /** @use HasFactory<PreorderDateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'cutoff_at' => 'datetime',
            'pickup_enabled' => 'boolean',
            'delivery_enabled' => 'boolean',
        ];
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
```

- [ ] **Step 5: Write the factory**

```php
<?php
// backend/database/factories/PreorderDateFactory.php

namespace Database\Factories;

use App\Models\PreorderDate;
use Illuminate\Database\Eloquent\Factories\Factory;

class PreorderDateFactory extends Factory
{
    protected $model = PreorderDate::class;

    public function definition(): array
    {
        $orderDate = $this->faker->unique()->dateTimeBetween('+1 day', '+60 days');

        return [
            'order_date' => $orderDate->format('Y-m-d'),
            'cutoff_at' => (clone $orderDate)->modify('-1 day 18:00'),
            'capacity_limit' => 20,
            'reserved_capacity' => 0,
            'pickup_enabled' => true,
            'delivery_enabled' => true,
            'status' => 'open',
        ];
    }
}
```

- [ ] **Step 6: Run migrations and test to verify it passes**

Run: `cd backend && php artisan migrate:fresh --force && php artisan test --filter=PreorderDatesTableTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add backend/database/migrations/2026_07_31_100400_create_preorder_dates_table.php \
        backend/app/Models/PreorderDate.php backend/database/factories/PreorderDateFactory.php \
        backend/tests/Feature/Schema/PreorderDatesTableTest.php
git commit -m "feat(schema): add preorder_dates table"
```

---

### Task 7: `delivery_zones` + `delivery_zone_postcodes` tables

**Files:**
- Create: `backend/database/migrations/2026_07_31_100500_create_delivery_zones_table.php`
- Create: `backend/database/migrations/2026_07_31_100501_create_delivery_zone_postcodes_table.php`
- Create: `backend/app/Models/DeliveryZone.php`, `backend/app/Models/DeliveryZonePostcode.php`
- Create: `backend/database/factories/DeliveryZoneFactory.php`, `backend/database/factories/DeliveryZonePostcodeFactory.php`
- Test: `backend/tests/Feature/Schema/DeliveryZonesTableTest.php`

**Interfaces:**
- Produces: `App\Models\DeliveryZone` (soft-deletable), `App\Models\DeliveryZonePostcode`. Task 8 (`customer_addresses`) and Task 9 (`orders`) both reference `delivery_zones.id`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// backend/tests/Feature/Schema/DeliveryZonesTableTest.php

use App\Models\DeliveryZone;
use App\Models\DeliveryZonePostcode;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects a negative delivery_fee_sen', function () {
    expect(fn () => DeliveryZone::factory()->create(['delivery_fee_sen' => -1]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('rejects the same postcode assigned to two zones', function () {
    DeliveryZonePostcode::factory()->create(['postcode' => '43000']);

    expect(fn () => DeliveryZonePostcode::factory()->create(['postcode' => '43000']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd backend && php artisan test --filter=DeliveryZonesTableTest`
Expected: FAIL — table `delivery_zones` does not exist.

- [ ] **Step 3: Write the migrations**

```php
<?php
// backend/database/migrations/2026_07_31_100500_create_delivery_zones_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('description', 500)->nullable();
            $table->bigInteger('delivery_fee_sen');
            $table->bigInteger('minimum_order_sen')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['is_active', 'sort_order']);
        });

        DB::statement('ALTER TABLE delivery_zones ADD CONSTRAINT delivery_zones_fee_check CHECK (delivery_fee_sen >= 0)');
        DB::statement('ALTER TABLE delivery_zones ADD CONSTRAINT delivery_zones_min_order_check CHECK (minimum_order_sen IS NULL OR minimum_order_sen >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
```

```php
<?php
// backend/database/migrations/2026_07_31_100501_create_delivery_zone_postcodes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zone_postcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_zone_id')->constrained()->cascadeOnDelete();
            $table->string('postcode', 20)->unique();
            $table->timestampsTz();

            $table->index(['delivery_zone_id', 'postcode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_zone_postcodes');
    }
};
```

- [ ] **Step 4: Write the models**

```php
<?php
// backend/app/Models/DeliveryZone.php

namespace App\Models;

use Database\Factories\DeliveryZoneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'description', 'delivery_fee_sen', 'minimum_order_sen', 'is_active', 'sort_order'])]
class DeliveryZone extends Model
{
    /** @use HasFactory<DeliveryZoneFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function postcodes()
    {
        return $this->hasMany(DeliveryZonePostcode::class);
    }
}
```

```php
<?php
// backend/app/Models/DeliveryZonePostcode.php

namespace App\Models;

use Database\Factories\DeliveryZonePostcodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['delivery_zone_id', 'postcode'])]
class DeliveryZonePostcode extends Model
{
    /** @use HasFactory<DeliveryZonePostcodeFactory> */
    use HasFactory;

    public function deliveryZone()
    {
        return $this->belongsTo(DeliveryZone::class);
    }
}
```

- [ ] **Step 5: Write the factories**

```php
<?php
// backend/database/factories/DeliveryZoneFactory.php

namespace Database\Factories;

use App\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryZoneFactory extends Factory
{
    protected $model = DeliveryZone::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->city(),
            'delivery_fee_sen' => 800,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
```

```php
<?php
// backend/database/factories/DeliveryZonePostcodeFactory.php

namespace Database\Factories;

use App\Models\DeliveryZone;
use App\Models\DeliveryZonePostcode;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryZonePostcodeFactory extends Factory
{
    protected $model = DeliveryZonePostcode::class;

    public function definition(): array
    {
        return [
            'delivery_zone_id' => DeliveryZone::factory(),
            'postcode' => $this->faker->unique()->numerify('#####'),
        ];
    }
}
```

- [ ] **Step 6: Run migrations and test to verify it passes**

Run: `cd backend && php artisan migrate:fresh --force && php artisan test --filter=DeliveryZonesTableTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add backend/database/migrations/2026_07_31_100500_create_delivery_zones_table.php \
        backend/database/migrations/2026_07_31_100501_create_delivery_zone_postcodes_table.php \
        backend/app/Models/DeliveryZone.php backend/app/Models/DeliveryZonePostcode.php \
        backend/database/factories/DeliveryZoneFactory.php backend/database/factories/DeliveryZonePostcodeFactory.php \
        backend/tests/Feature/Schema/DeliveryZonesTableTest.php
git commit -m "feat(schema): add delivery_zones and delivery_zone_postcodes tables"
```

---

### Task 8: `customers` + `customer_addresses` tables

**Files:**
- Create: `backend/database/migrations/2026_07_31_100600_create_customers_table.php`
- Create: `backend/database/migrations/2026_07_31_100601_create_customer_addresses_table.php`
- Create: `backend/app/Models/Customer.php`, `backend/app/Models/CustomerAddress.php`
- Create: `backend/database/factories/CustomerFactory.php`, `backend/database/factories/CustomerAddressFactory.php`
- Test: `backend/tests/Feature/Schema/CustomersTableTest.php`

**Interfaces:**
- Consumes: `App\Models\Concerns\HasUlid` (Task 1), `App\Models\DeliveryZone` (Task 7).
- Produces: `App\Models\Customer` (`ulid`, unique `phone_e164`, soft-deletable), `App\Models\CustomerAddress`. Task 9 (`orders`) references `customers.id`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// backend/tests/Feature/Schema/CustomersTableTest.php

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects two customers with the same phone_e164', function () {
    Customer::factory()->create(['phone_e164' => '+60123456789']);

    expect(fn () => Customer::factory()->create(['phone_e164' => '+60123456789']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('only allows one default address per customer', function () {
    $customer = Customer::factory()->create();
    CustomerAddress::factory()->for($customer)->create(['is_default' => true]);

    expect(fn () => CustomerAddress::factory()->for($customer)->create(['is_default' => true]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd backend && php artisan test --filter=CustomersTableTest`
Expected: FAIL — table `customers` does not exist.

- [ ] **Step 3: Write the migrations**

```php
<?php
// backend/database/migrations/2026_07_31_100600_create_customers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('name', 160);
            $table->string('phone_e164', 20)->unique();
            $table->string('email', 255)->nullable();
            $table->timestampTz('marketing_consent_at')->nullable();
            $table->timestampTz('last_order_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('last_order_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
```

```php
<?php
// backend/database/migrations/2026_07_31_100601_create_customer_addresses_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label', 60)->nullable();
            $table->string('recipient_name', 160);
            $table->string('recipient_phone_e164', 20);
            $table->string('line_1', 255);
            $table->string('line_2', 255)->nullable();
            $table->string('city', 120);
            $table->string('state', 120);
            $table->string('postcode', 20);
            $table->char('country_code', 2)->default('MY');
            $table->foreignId('delivery_zone_id')->nullable()->constrained()->restrictOnDelete();
            $table->boolean('is_default')->default(false);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['customer_id', 'is_default']);
            $table->index('postcode');
        });

        DB::statement(
            'CREATE UNIQUE INDEX customer_addresses_one_default_per_customer ' .
            'ON customer_addresses (customer_id) WHERE is_default = true'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
```

- [ ] **Step 4: Write the models**

```php
<?php
// backend/app/Models/Customer.php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'phone_e164', 'email', 'marketing_consent_at', 'last_order_at'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, HasUlid, SoftDeletes;

    protected function casts(): array
    {
        return [
            'marketing_consent_at' => 'datetime',
            'last_order_at' => 'datetime',
        ];
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
```

```php
<?php
// backend/app/Models/CustomerAddress.php

namespace App\Models;

use Database\Factories\CustomerAddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'customer_id', 'label', 'recipient_name', 'recipient_phone_e164', 'line_1', 'line_2',
    'city', 'state', 'postcode', 'country_code', 'delivery_zone_id', 'is_default',
])]
class CustomerAddress extends Model
{
    /** @use HasFactory<CustomerAddressFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function deliveryZone()
    {
        return $this->belongsTo(DeliveryZone::class);
    }
}
```

- [ ] **Step 5: Write the factories**

```php
<?php
// backend/database/factories/CustomerFactory.php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'phone_e164' => '+601' . $this->faker->unique()->numerify('########'),
        ];
    }
}
```

```php
<?php
// backend/database/factories/CustomerAddressFactory.php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerAddressFactory extends Factory
{
    protected $model = CustomerAddress::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'recipient_name' => $this->faker->name(),
            'recipient_phone_e164' => '+601' . $this->faker->numerify('########'),
            'line_1' => $this->faker->streetAddress(),
            'city' => 'Kuala Lumpur',
            'state' => 'Wilayah Persekutuan',
            'postcode' => $this->faker->numerify('#####'),
            'country_code' => 'MY',
            'is_default' => false,
        ];
    }
}
```

- [ ] **Step 6: Run migrations and test to verify it passes**

Run: `cd backend && php artisan migrate:fresh --force && php artisan test --filter=CustomersTableTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add backend/database/migrations/2026_07_31_100600_create_customers_table.php \
        backend/database/migrations/2026_07_31_100601_create_customer_addresses_table.php \
        backend/app/Models/Customer.php backend/app/Models/CustomerAddress.php \
        backend/database/factories/CustomerFactory.php backend/database/factories/CustomerAddressFactory.php \
        backend/tests/Feature/Schema/CustomersTableTest.php
git commit -m "feat(schema): add customers and customer_addresses tables"
```

---

### Task 9: `orders` table

**Files:**
- Create: `backend/database/migrations/2026_07_31_100700_create_orders_table.php`
- Create: `backend/app/Models/Order.php`
- Create: `backend/database/factories/OrderFactory.php`
- Test: `backend/tests/Feature/Schema/OrdersTableTest.php`

**Interfaces:**
- Consumes: `App\Models\Customer` (Task 8), `App\Models\PreorderDate` (Task 6), `App\Models\DeliveryZone` (Task 7).
- Produces: `App\Models\Order`. Task 10 (`order_items`), Task 11 (`order_status_events`), Task 12 (`payments`) all `belongsTo(Order::class)` via `order_id`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// backend/tests/Feature/Schema/OrdersTableTest.php

use App\Models\Customer;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\PreorderDate;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeOrderAttributes(array $overrides = []): array
{
    return array_merge([
        'ulid' => (string) \Illuminate\Support\Str::ulid(),
        'order_number' => 'ND-' . fake()->unique()->numerify('######'),
        'customer_id' => Customer::factory(),
        'preorder_date_id' => PreorderDate::factory(),
        'checkout_channel' => 'website',
        'fulfilment_method' => 'pickup',
        'customer_name_snapshot' => 'Test Customer',
        'customer_phone_snapshot' => '+60123456789',
        'subtotal_sen' => 3600,
        'delivery_fee_sen' => 0,
        'discount_sen' => 0,
        'total_sen' => 3600,
        'total_capacity_units' => 1,
        'status' => 'awaiting_payment',
        'payment_status' => 'awaiting_payment',
        'idempotency_key' => \Illuminate\Support\Str::uuid()->toString(),
    ], $overrides);
}

it('creates a valid pickup order', function () {
    $order = Order::create(makeOrderAttributes());

    expect($order->exists)->toBeTrue();
});

it('rejects a pickup order that has a delivery_address', function () {
    expect(fn () => Order::create(makeOrderAttributes([
        'delivery_address' => ['line_1' => 'x'],
    ])))->toThrow(\Illuminate\Database\QueryException::class);
});

it('rejects a delivery order missing delivery_address and delivery_zone_id', function () {
    expect(fn () => Order::create(makeOrderAttributes([
        'fulfilment_method' => 'delivery',
    ])))->toThrow(\Illuminate\Database\QueryException::class);
});

it('accepts a delivery order with address and zone', function () {
    $zone = DeliveryZone::factory()->create();

    $order = Order::create(makeOrderAttributes([
        'fulfilment_method' => 'delivery',
        'delivery_zone_id' => $zone->id,
        'delivery_address' => ['line_1' => 'Jalan Test', 'postcode' => '43000'],
    ]));

    expect($order->exists)->toBeTrue();
});

it('rejects a whatsapp_pending order with no expires_at', function () {
    expect(fn () => Order::create(makeOrderAttributes([
        'checkout_channel' => 'whatsapp',
        'status' => 'whatsapp_pending',
    ])))->toThrow(\Illuminate\Database\QueryException::class);
});

it('rejects a duplicate idempotency_key', function () {
    $attrs = makeOrderAttributes();
    Order::create($attrs);

    $attrs['ulid'] = (string) \Illuminate\Support\Str::ulid();
    $attrs['order_number'] = 'ND-' . fake()->unique()->numerify('######');

    expect(fn () => Order::create($attrs))->toThrow(\Illuminate\Database\QueryException::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd backend && php artisan test --filter=OrdersTableTest`
Expected: FAIL — table `orders` does not exist.

- [ ] **Step 3: Write the migration**

```php
<?php
// backend/database/migrations/2026_07_31_100700_create_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('order_number', 40)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('preorder_date_id')->constrained()->restrictOnDelete();
            $table->string('checkout_channel', 20);
            $table->string('fulfilment_method', 20);
            $table->foreignId('delivery_zone_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('customer_name_snapshot', 160);
            $table->string('customer_phone_snapshot', 20);
            $table->string('customer_email_snapshot', 255)->nullable();
            $table->jsonb('delivery_address')->nullable();
            $table->text('pickup_instruction_snapshot')->nullable();
            $table->bigInteger('subtotal_sen');
            $table->bigInteger('delivery_fee_sen');
            $table->bigInteger('discount_sen')->default(0);
            $table->bigInteger('total_sen');
            $table->integer('total_capacity_units');
            $table->string('status', 30);
            $table->string('payment_status', 30);
            $table->string('payment_method', 30)->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('capacity_released_at')->nullable();
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->uuid('idempotency_key')->unique();
            $table->jsonb('source_metadata')->nullable();
            $table->timestampsTz();

            $table->index(['preorder_date_id', 'status']);
            $table->index(['preorder_date_id', 'fulfilment_method', 'status']);
            $table->index(['customer_id', 'created_at']);
            $table->index(['status', 'expires_at']);
            $table->index(['payment_status', 'created_at']);
            $table->index(['checkout_channel', 'created_at']);
        });

        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_checkout_channel_check CHECK (checkout_channel IN ('website', 'whatsapp'))");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_fulfilment_method_check CHECK (fulfilment_method IN ('pickup', 'delivery'))");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status IN (
            'whatsapp_pending', 'awaiting_payment', 'payment_submitted', 'payment_confirmed',
            'preparing', 'ready_for_pickup', 'out_for_delivery', 'completed', 'cancelled', 'rejected', 'expired'
        ))");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_status_check CHECK (payment_status IN (
            'not_required', 'awaiting_payment', 'submitted', 'paid', 'failed', 'refunded'
        ))");
        DB::statement(
            'ALTER TABLE orders ADD CONSTRAINT orders_fulfilment_fields_check CHECK (' .
            "(fulfilment_method = 'delivery' AND delivery_address IS NOT NULL AND delivery_zone_id IS NOT NULL) OR " .
            "(fulfilment_method = 'pickup' AND delivery_address IS NULL AND delivery_zone_id IS NULL)" .
            ')'
        );
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_whatsapp_pending_expiry_check CHECK (status <> 'whatsapp_pending' OR expires_at IS NOT NULL)");
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_subtotal_check CHECK (subtotal_sen >= 0)');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_delivery_fee_check CHECK (delivery_fee_sen >= 0)');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_discount_check CHECK (discount_sen >= 0)');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_total_check CHECK (total_sen >= 0)');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_total_capacity_units_check CHECK (total_capacity_units > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
```

- [ ] **Step 4: Write the model**

```php
<?php
// backend/app/Models/Order.php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ulid', 'order_number', 'customer_id', 'preorder_date_id', 'checkout_channel',
    'fulfilment_method', 'delivery_zone_id', 'customer_name_snapshot', 'customer_phone_snapshot',
    'customer_email_snapshot', 'delivery_address', 'pickup_instruction_snapshot', 'subtotal_sen',
    'delivery_fee_sen', 'discount_sen', 'total_sen', 'total_capacity_units', 'status',
    'payment_status', 'payment_method', 'expires_at', 'capacity_released_at', 'confirmed_at',
    'paid_at', 'completed_at', 'idempotency_key', 'source_metadata',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, HasUlid;

    protected function casts(): array
    {
        return [
            'delivery_address' => 'array',
            'source_metadata' => 'array',
            'expires_at' => 'datetime',
            'capacity_released_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function preorderDate()
    {
        return $this->belongsTo(PreorderDate::class);
    }

    public function deliveryZone()
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusEvents()
    {
        return $this->hasMany(OrderStatusEvent::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
```

- [ ] **Step 5: Write the factory**

```php
<?php
// backend/database/factories/OrderFactory.php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\PreorderDate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'order_number' => 'ND-' . $this->faker->unique()->numerify('######'),
            'customer_id' => Customer::factory(),
            'preorder_date_id' => PreorderDate::factory(),
            'checkout_channel' => 'website',
            'fulfilment_method' => 'pickup',
            'customer_name_snapshot' => $this->faker->name(),
            'customer_phone_snapshot' => '+60123456789',
            'subtotal_sen' => 3600,
            'delivery_fee_sen' => 0,
            'discount_sen' => 0,
            'total_sen' => 3600,
            'total_capacity_units' => 1,
            'status' => 'awaiting_payment',
            'payment_status' => 'awaiting_payment',
            'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
```

- [ ] **Step 6: Run migrations and test to verify it passes**

Run: `cd backend && php artisan migrate:fresh --force && php artisan test --filter=OrdersTableTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add backend/database/migrations/2026_07_31_100700_create_orders_table.php \
        backend/app/Models/Order.php backend/database/factories/OrderFactory.php \
        backend/tests/Feature/Schema/OrdersTableTest.php
git commit -m "feat(schema): add orders table with fulfilment and status check constraints"
```

---

### Task 10: `order_items` + `order_item_option_values` tables

**Files:**
- Create: `backend/database/migrations/2026_07_31_100800_create_order_items_table.php`
- Create: `backend/database/migrations/2026_07_31_100801_create_order_item_option_values_table.php`
- Create: `backend/app/Models/OrderItem.php`, `backend/app/Models/OrderItemOptionValue.php`
- Create: `backend/database/factories/OrderItemFactory.php`, `backend/database/factories/OrderItemOptionValueFactory.php`
- Test: `backend/tests/Feature/Schema/OrderItemsTableTest.php`

**Interfaces:**
- Consumes: `App\Models\Order` (Task 9), `App\Models\Product` (Task 3), `App\Models\ProductVariant` (Task 5).
- Produces: `App\Models\OrderItem`, `App\Models\OrderItemOptionValue`. These are the immutable snapshot rows the checkout logic (future plan) will write inside the capacity-lock transaction.

- [ ] **Step 1: Write the failing test**

```php
<?php
// backend/tests/Feature/Schema/OrderItemsTableTest.php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOptionValue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps the order_item row when the source product is deleted', function () {
    $item = OrderItem::factory()->create();
    $productId = $item->product_id;

    \App\Models\Product::find($productId)->forceDelete();

    expect($item->fresh()->product_id)->toBeNull();
    expect($item->fresh()->product_name_snapshot)->not->toBeNull();
});

it('rejects a quantity of zero', function () {
    expect(fn () => OrderItem::factory()->create(['quantity' => 0]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('deletes option value snapshots when the order item is deleted', function () {
    $item = OrderItem::factory()->create();
    $optionValue = OrderItemOptionValue::factory()->for($item, 'orderItem')->create();

    $item->delete();

    expect(OrderItemOptionValue::find($optionValue->id))->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd backend && php artisan test --filter=OrderItemsTableTest`
Expected: FAIL — table `order_items` does not exist.

- [ ] **Step 3: Write the migrations**

```php
<?php
// backend/database/migrations/2026_07_31_100800_create_order_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name_snapshot', 160);
            $table->string('variant_name_snapshot', 180)->nullable();
            $table->string('sku_snapshot', 100)->nullable();
            $table->bigInteger('unit_price_sen');
            $table->integer('quantity');
            $table->integer('capacity_units_each');
            $table->bigInteger('line_total_sen');
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();

            $table->index(['order_id', 'sort_order']);
        });

        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_unit_price_check CHECK (unit_price_sen >= 0)');
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_quantity_check CHECK (quantity > 0)');
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_capacity_units_each_check CHECK (capacity_units_each > 0)');
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_line_total_check CHECK (line_total_sen >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
```

```php
<?php
// backend/database/migrations/2026_07_31_100801_create_order_item_option_values_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->string('option_group_name_snapshot', 80);
            $table->string('option_value_name_snapshot', 120);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();

            $table->index(['order_item_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_option_values');
    }
};
```

- [ ] **Step 4: Write the models**

```php
<?php
// backend/app/Models/OrderItem.php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'order_id', 'product_id', 'product_variant_id', 'product_name_snapshot',
    'variant_name_snapshot', 'sku_snapshot', 'unit_price_sen', 'quantity',
    'capacity_units_each', 'line_total_sen', 'sort_order',
])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function optionValues()
    {
        return $this->hasMany(OrderItemOptionValue::class);
    }
}
```

```php
<?php
// backend/app/Models/OrderItemOptionValue.php

namespace App\Models;

use Database\Factories\OrderItemOptionValueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['order_item_id', 'option_group_name_snapshot', 'option_value_name_snapshot', 'sort_order'])]
class OrderItemOptionValue extends Model
{
    /** @use HasFactory<OrderItemOptionValueFactory> */
    use HasFactory;

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
```

- [ ] **Step 5: Write the factories**

```php
<?php
// backend/database/factories/OrderItemFactory.php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_name_snapshot' => $this->faker->words(2, true),
            'unit_price_sen' => 3600,
            'quantity' => 1,
            'capacity_units_each' => 1,
            'line_total_sen' => 3600,
            'sort_order' => 0,
        ];
    }
}
```

```php
<?php
// backend/database/factories/OrderItemOptionValueFactory.php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\OrderItemOptionValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemOptionValueFactory extends Factory
{
    protected $model = OrderItemOptionValue::class;

    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'option_group_name_snapshot' => 'Size',
            'option_value_name_snapshot' => 'Medium',
            'sort_order' => 0,
        ];
    }
}
```

- [ ] **Step 6: Run migrations and test to verify it passes**

Run: `cd backend && php artisan migrate:fresh --force && php artisan test --filter=OrderItemsTableTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add backend/database/migrations/2026_07_31_100800_create_order_items_table.php \
        backend/database/migrations/2026_07_31_100801_create_order_item_option_values_table.php \
        backend/app/Models/OrderItem.php backend/app/Models/OrderItemOptionValue.php \
        backend/database/factories/OrderItemFactory.php backend/database/factories/OrderItemOptionValueFactory.php \
        backend/tests/Feature/Schema/OrderItemsTableTest.php
git commit -m "feat(schema): add order_items and order_item_option_values tables"
```

---

### Task 11: `order_status_events` table

**Files:**
- Create: `backend/database/migrations/2026_07_31_100900_create_order_status_events_table.php`
- Create: `backend/app/Models/OrderStatusEvent.php`
- Create: `backend/database/factories/OrderStatusEventFactory.php`
- Test: `backend/tests/Feature/Schema/OrderStatusEventsTableTest.php`

**Interfaces:**
- Consumes: `App\Models\Order` (Task 9), `App\Models\User` (Task 1).
- Produces: `App\Models\OrderStatusEvent` — append-only, no `updated_at`. Future checkout/status-transition logic writes one row per status change here.

- [ ] **Step 1: Write the failing test**

```php
<?php
// backend/tests/Feature/Schema/OrderStatusEventsTableTest.php

use App\Models\OrderStatusEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('records an event with no updated_at column', function () {
    $event = OrderStatusEvent::factory()->create();

    expect(Schema::hasColumn('order_status_events', 'updated_at'))->toBeFalse();
    expect($event->created_at)->not->toBeNull();
});

it('rejects an actor_type outside user/system', function () {
    expect(fn () => OrderStatusEvent::factory()->create(['actor_type' => 'robot']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd backend && php artisan test --filter=OrderStatusEventsTableTest`
Expected: FAIL — table `order_status_events` does not exist.

- [ ] **Step 3: Write the migration**

```php
<?php
// backend/database/migrations/2026_07_31_100900_create_order_status_events_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('actor_type', 20);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note_internal')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['order_id', 'created_at']);
            $table->index(['to_status', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
        });

        DB::statement("ALTER TABLE order_status_events ADD CONSTRAINT order_status_events_actor_type_check CHECK (actor_type IN ('user', 'system'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_events');
    }
};
```

- [ ] **Step 4: Write the model**

```php
<?php
// backend/app/Models/OrderStatusEvent.php

namespace App\Models;

use Database\Factories\OrderStatusEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['order_id', 'from_status', 'to_status', 'actor_type', 'actor_user_id', 'note_internal', 'metadata'])]
class OrderStatusEvent extends Model
{
    /** @use HasFactory<OrderStatusEventFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
```

- [ ] **Step 5: Write the factory**

```php
<?php
// backend/database/factories/OrderStatusEventFactory.php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderStatusEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderStatusEventFactory extends Factory
{
    protected $model = OrderStatusEvent::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'to_status' => 'awaiting_payment',
            'actor_type' => 'system',
        ];
    }
}
```

- [ ] **Step 6: Run migrations and test to verify it passes**

Run: `cd backend && php artisan migrate:fresh --force && php artisan test --filter=OrderStatusEventsTableTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add backend/database/migrations/2026_07_31_100900_create_order_status_events_table.php \
        backend/app/Models/OrderStatusEvent.php backend/database/factories/OrderStatusEventFactory.php \
        backend/tests/Feature/Schema/OrderStatusEventsTableTest.php
git commit -m "feat(schema): add append-only order_status_events table"
```

---

### Task 12: `payments` + `payment_proofs` tables

**Files:**
- Create: `backend/database/migrations/2026_07_31_101000_create_payments_table.php`
- Create: `backend/database/migrations/2026_07_31_101001_create_payment_proofs_table.php`
- Create: `backend/app/Models/Payment.php`, `backend/app/Models/PaymentProof.php`
- Create: `backend/database/factories/PaymentFactory.php`, `backend/database/factories/PaymentProofFactory.php`
- Test: `backend/tests/Feature/Schema/PaymentsTableTest.php`

**Interfaces:**
- Consumes: `App\Models\Order` (Task 9), `App\Models\User` (Task 1).
- Produces: `App\Models\Payment`, `App\Models\PaymentProof`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// backend/tests/Feature/Schema/PaymentsTableTest.php

use App\Models\Payment;
use App\Models\PaymentProof;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects a zero or negative amount_sen', function () {
    expect(fn () => Payment::factory()->create(['amount_sen' => 0]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('cascades payment_proofs when the payment is deleted', function () {
    $payment = Payment::factory()->create();
    $proof = PaymentProof::factory()->for($payment)->create();

    $payment->delete();

    expect(PaymentProof::find($proof->id))->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd backend && php artisan test --filter=PaymentsTableTest`
Expected: FAIL — table `payments` does not exist.

- [ ] **Step 3: Write the migrations**

```php
<?php
// backend/database/migrations/2026_07_31_101000_create_payments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('method', 30);
            $table->string('provider', 60)->nullable();
            $table->string('provider_reference', 120)->nullable();
            $table->bigInteger('amount_sen');
            $table->char('currency', 3)->default('MYR');
            $table->string('status', 20);
            $table->timestampTz('paid_at')->nullable();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->jsonb('gateway_payload')->nullable();
            $table->timestampsTz();

            $table->index(['order_id', 'created_at']);
            $table->index(['provider', 'provider_reference']);
            $table->index(['status', 'created_at']);
        });

        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_amount_check CHECK (amount_sen > 0)');
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status IN ('pending', 'submitted', 'confirmed', 'failed', 'refunded'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
```

```php
<?php
// backend/database/migrations/2026_07_31_101001_create_payment_proofs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('storage_disk', 50);
            $table->string('object_key', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->timestampTz('uploaded_at');
            $table->timestampTz('reviewed_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note_internal')->nullable();
            $table->timestampsTz();

            $table->index(['payment_id', 'uploaded_at']);
            $table->index('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_proofs');
    }
};
```

- [ ] **Step 4: Write the models**

```php
<?php
// backend/app/Models/Payment.php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'order_id', 'method', 'provider', 'provider_reference', 'amount_sen', 'currency',
    'status', 'paid_at', 'confirmed_by_user_id', 'gateway_payload',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, HasUlid;

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'gateway_payload' => 'array',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function proofs()
    {
        return $this->hasMany(PaymentProof::class);
    }
}
```

```php
<?php
// backend/app/Models/PaymentProof.php

namespace App\Models;

use Database\Factories\PaymentProofFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'payment_id', 'storage_disk', 'object_key', 'mime_type', 'size_bytes',
    'uploaded_at', 'reviewed_at', 'reviewed_by_user_id', 'review_note_internal',
])]
class PaymentProof extends Model
{
    /** @use HasFactory<PaymentProofFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
```

- [ ] **Step 5: Write the factories**

```php
<?php
// backend/database/factories/PaymentFactory.php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'method' => 'bank_transfer',
            'amount_sen' => 3600,
            'currency' => 'MYR',
            'status' => 'pending',
        ];
    }
}
```

```php
<?php
// backend/database/factories/PaymentProofFactory.php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PaymentProof;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentProofFactory extends Factory
{
    protected $model = PaymentProof::class;

    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'storage_disk' => 'spaces',
            'object_key' => 'payment-proofs/' . $this->faker->uuid() . '.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 204800,
            'uploaded_at' => now(),
        ];
    }
}
```

- [ ] **Step 6: Run migrations and test to verify it passes**

Run: `cd backend && php artisan migrate:fresh --force && php artisan test --filter=PaymentsTableTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add backend/database/migrations/2026_07_31_101000_create_payments_table.php \
        backend/database/migrations/2026_07_31_101001_create_payment_proofs_table.php \
        backend/app/Models/Payment.php backend/app/Models/PaymentProof.php \
        backend/database/factories/PaymentFactory.php backend/database/factories/PaymentProofFactory.php \
        backend/tests/Feature/Schema/PaymentsTableTest.php
git commit -m "feat(schema): add payments and payment_proofs tables"
```

---

### Task 13: `notification_logs` + `activity_logs` tables

**Files:**
- Create: `backend/database/migrations/2026_07_31_101100_create_notification_logs_table.php`
- Create: `backend/database/migrations/2026_07_31_101101_create_activity_logs_table.php`
- Create: `backend/app/Models/NotificationLog.php`, `backend/app/Models/ActivityLog.php`
- Create: `backend/database/factories/NotificationLogFactory.php`, `backend/database/factories/ActivityLogFactory.php`
- Test: `backend/tests/Feature/Schema/LogsTableTest.php`

**Interfaces:**
- Consumes: `App\Models\Order` (Task 9), `App\Models\Customer` (Task 8), `App\Models\User` (Task 1).
- Produces: `App\Models\NotificationLog`, `App\Models\ActivityLog` (both append-only-friendly; `ActivityLog` has no `updated_at`).

- [ ] **Step 1: Write the failing test**

```php
<?php
// backend/tests/Feature/Schema/LogsTableTest.php

use App\Models\ActivityLog;
use App\Models\NotificationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps a notification_log row when its order is deleted', function () {
    $log = NotificationLog::factory()->create();
    $log->order->delete();

    expect($log->fresh()->order_id)->toBeNull();
});

it('stores before/after jsonb snapshots on an activity log with no updated_at', function () {
    $log = ActivityLog::factory()->create([
        'before' => ['is_active' => true],
        'after' => ['is_active' => false],
    ]);

    expect(Schema::hasColumn('activity_logs', 'updated_at'))->toBeFalse();
    expect($log->fresh()->before)->toBe(['is_active' => true]);
    expect($log->fresh()->after)->toBe(['is_active' => false]);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd backend && php artisan test --filter=LogsTableTest`
Expected: FAIL — table `notification_logs` does not exist.

- [ ] **Step 3: Write the migrations**

```php
<?php
// backend/database/migrations/2026_07_31_101100_create_notification_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20);
            $table->string('template_key', 100);
            $table->string('recipient', 255);
            $table->string('status', 20);
            $table->string('provider_message_id', 120)->nullable();
            $table->text('error_message')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampsTz();

            $table->index(['order_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['channel', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
```

```php
<?php
// backend/database/migrations/2026_07_31_101101_create_activity_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 120);
            $table->string('subject_type', 120);
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->jsonb('before')->nullable();
            $table->jsonb('after')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
```

- [ ] **Step 4: Write the models**

```php
<?php
// backend/app/Models/NotificationLog.php

namespace App\Models;

use Database\Factories\NotificationLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'order_id', 'customer_id', 'channel', 'template_key', 'recipient', 'status',
    'provider_message_id', 'error_message', 'sent_at',
])]
class NotificationLog extends Model
{
    /** @use HasFactory<NotificationLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
```

```php
<?php
// backend/app/Models/ActivityLog.php

namespace App\Models;

use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['actor_user_id', 'event', 'subject_type', 'subject_id', 'before', 'after', 'ip_address'])]
class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
        ];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
```

- [ ] **Step 5: Write the factories**

```php
<?php
// backend/database/factories/NotificationLogFactory.php

namespace Database\Factories;

use App\Models\NotificationLog;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'channel' => 'whatsapp_link',
            'template_key' => 'order_confirmed',
            'recipient' => '+60123456789',
            'status' => 'queued',
        ];
    }
}
```

```php
<?php
// backend/database/factories/ActivityLogFactory.php

namespace Database\Factories;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'event' => 'product.updated',
            'subject_type' => 'App\\Models\\Product',
            'subject_id' => 1,
        ];
    }
}
```

- [ ] **Step 6: Run migrations and test to verify it passes**

Run: `cd backend && php artisan migrate:fresh --force && php artisan test --filter=LogsTableTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add backend/database/migrations/2026_07_31_101100_create_notification_logs_table.php \
        backend/database/migrations/2026_07_31_101101_create_activity_logs_table.php \
        backend/app/Models/NotificationLog.php backend/app/Models/ActivityLog.php \
        backend/database/factories/NotificationLogFactory.php backend/database/factories/ActivityLogFactory.php \
        backend/tests/Feature/Schema/LogsTableTest.php
git commit -m "feat(schema): add notification_logs and activity_logs tables"
```

---

### Task 14: Development seeders

**Files:**
- Create: `backend/database/seeders/OwnerUserSeeder.php`
- Create: `backend/database/seeders/BusinessSettingsSeeder.php`
- Create: `backend/database/seeders/ProductSeeder.php`
- Create: `backend/database/seeders/PreorderDateSeeder.php`
- Create: `backend/database/seeders/DeliveryZoneSeeder.php`
- Create: `backend/database/seeders/OrderDemoSeeder.php`
- Modify: `backend/database/seeders/DatabaseSeeder.php`
- Test: `backend/tests/Feature/DatabaseSeedersTest.php`

**Interfaces:**
- Consumes: every model from Tasks 1–13.
- Produces: a runnable `php artisan migrate:fresh --seed` for local/staging that matches spec §9 "Required development seeders" exactly.

- [ ] **Step 1: Write the failing test**

```php
<?php
// backend/tests/Feature/DatabaseSeedersTest.php

use App\Models\BusinessSetting;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\PreorderDate;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('seeds an owner user, business settings, products, dates, zones, and demo orders', function () {
    Artisan::call('db:seed');

    expect(User::where('role', 'owner')->exists())->toBeTrue();
    expect(BusinessSetting::where('key', 'business_name')->exists())->toBeTrue();
    expect(Product::count())->toBeGreaterThan(0);
    expect(PreorderDate::count())->toBeGreaterThan(0);
    expect(DeliveryZone::count())->toBeGreaterThan(0);
    expect(Order::count())->toBeGreaterThan(0);
});

it('seeds orders covering every lifecycle status', function () {
    Artisan::call('db:seed');

    $seededStatuses = Order::pluck('status')->unique()->all();

    foreach (['awaiting_payment', 'payment_confirmed', 'completed', 'cancelled'] as $status) {
        expect($seededStatuses)->toContain($status);
    }
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd backend && php artisan test --filter=DatabaseSeedersTest`
Expected: FAIL — seeders don't exist yet / `DatabaseSeeder` is empty.

- [ ] **Step 3: Write `OwnerUserSeeder`**

```php
<?php
// backend/database/seeders/OwnerUserSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class OwnerUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'owner@ninadough.test'],
            [
                'name' => 'ninadough Owner',
                'password' => 'password',
                'role' => 'owner',
                'is_active' => true,
            ]
        );
    }
}
```

- [ ] **Step 4: Write `BusinessSettingsSeeder`**

```php
<?php
// backend/database/seeders/BusinessSettingsSeeder.php

namespace Database\Seeders;

use App\Models\BusinessSetting;
use Illuminate\Database\Seeder;

class BusinessSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'business_name' => 'ninadough',
            'timezone' => 'Asia/Kuala_Lumpur',
            'whatsapp_number' => '+60123456789',
            'default_whatsapp_reservation_minutes' => 20,
            'checkout_mode' => 'hybrid',
            'bank_transfer_instructions' => 'Transfer to Maybank 1234567890, then send proof via WhatsApp.',
            'pickup_instructions' => 'Pickup at ninadough kitchen, 10am-6pm.',
            'privacy_contact_email' => 'privacy@ninadough.test',
        ];

        foreach ($settings as $key => $value) {
            BusinessSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
```

- [ ] **Step 5: Write `ProductSeeder`**

```php
<?php
// backend/database/seeders/ProductSeeder.php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductOptionGroup;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::factory()->create([
            'name' => 'Chocolate Fudge Cake',
            'slug' => 'chocolate-fudge-cake',
            'base_price_sen' => 8000,
        ]);

        ProductImage::factory()->for($product)->create(['is_primary' => true]);

        $sizeGroup = ProductOptionGroup::factory()->for($product)->create(['name' => 'Size']);
        $small = ProductOptionValue::factory()->for($sizeGroup, 'productOptionGroup')->create([
            'name' => 'Small (6")', 'value_code' => 'small',
        ]);
        $large = ProductOptionValue::factory()->for($sizeGroup, 'productOptionGroup')->create([
            'name' => 'Large (9")', 'value_code' => 'large',
        ]);

        $smallVariant = ProductVariant::factory()->for($product)->create([
            'name' => 'Chocolate Fudge Cake - Small', 'price_adjustment_sen' => 0, 'capacity_units' => 1,
        ]);
        $smallVariant->optionValues()->attach($small);

        $largeVariant = ProductVariant::factory()->for($product)->create([
            'name' => 'Chocolate Fudge Cake - Large', 'price_adjustment_sen' => 3000, 'capacity_units' => 2,
        ]);
        $largeVariant->optionValues()->attach($large);
    }
}
```

- [ ] **Step 6: Write `PreorderDateSeeder`**

```php
<?php
// backend/database/seeders/PreorderDateSeeder.php

namespace Database\Seeders;

use App\Models\PreorderDate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PreorderDateSeeder extends Seeder
{
    public function run(): void
    {
        PreorderDate::factory()->create([
            'order_date' => Carbon::now('Asia/Kuala_Lumpur')->addDays(2)->toDateString(),
            'status' => 'open',
            'capacity_limit' => 20,
            'reserved_capacity' => 5,
        ]);

        PreorderDate::factory()->create([
            'order_date' => Carbon::now('Asia/Kuala_Lumpur')->addDays(3)->toDateString(),
            'status' => 'full',
            'capacity_limit' => 10,
            'reserved_capacity' => 10,
        ]);

        PreorderDate::factory()->create([
            'order_date' => Carbon::now('Asia/Kuala_Lumpur')->addDays(4)->toDateString(),
            'status' => 'closed',
            'capacity_limit' => 15,
            'reserved_capacity' => 0,
        ]);
    }
}
```

- [ ] **Step 7: Write `DeliveryZoneSeeder`**

```php
<?php
// backend/database/seeders/DeliveryZoneSeeder.php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use App\Models\DeliveryZonePostcode;
use Illuminate\Database\Seeder;

class DeliveryZoneSeeder extends Seeder
{
    public function run(): void
    {
        $zone = DeliveryZone::factory()->create([
            'name' => 'Klang Valley',
            'delivery_fee_sen' => 800,
        ]);

        foreach (['40000', '40100', '43000'] as $postcode) {
            DeliveryZonePostcode::factory()->for($zone)->create(['postcode' => $postcode]);
        }
    }
}
```

- [ ] **Step 8: Write `OrderDemoSeeder`**

```php
<?php
// backend/database/seeders/OrderDemoSeeder.php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PreorderDate;
use Illuminate\Database\Seeder;

class OrderDemoSeeder extends Seeder
{
    public function run(): void
    {
        $preorderDate = PreorderDate::first() ?? PreorderDate::factory()->create();

        $statuses = [
            'whatsapp_pending', 'awaiting_payment', 'payment_submitted', 'payment_confirmed',
            'preparing', 'ready_for_pickup', 'out_for_delivery', 'completed', 'cancelled',
            'rejected', 'expired',
        ];

        foreach ($statuses as $status) {
            $order = Order::factory()->create([
                'preorder_date_id' => $preorderDate->id,
                'status' => $status,
                'payment_status' => $status === 'completed' ? 'paid' : 'awaiting_payment',
                'expires_at' => in_array($status, ['whatsapp_pending', 'expired'], true) ? now()->addMinutes(20) : null,
            ]);

            OrderItem::factory()->for($order)->create();
        }
    }
}
```

- [ ] **Step 9: Wire up `DatabaseSeeder`**

```php
<?php
// backend/database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OwnerUserSeeder::class,
            BusinessSettingsSeeder::class,
            ProductSeeder::class,
            PreorderDateSeeder::class,
            DeliveryZoneSeeder::class,
            OrderDemoSeeder::class,
        ]);
    }
}
```

- [ ] **Step 10: Run migrations and test to verify it passes**

Run: `cd backend && php artisan migrate:fresh --force && php artisan test --filter=DatabaseSeedersTest`
Expected: PASS

- [ ] **Step 11: Commit**

```bash
git add backend/database/seeders backend/tests/Feature/DatabaseSeedersTest.php
git commit -m "feat(schema): add development seeders for all v1 tables"
```

---

### Task 15: Full migration verification test

**Files:**
- Test: `backend/tests/Feature/MigrationVerificationTest.php`

**Interfaces:**
- Consumes: every model and migration from Tasks 1–14.
- Produces: nothing new — this is the spec's §9 "Migration verification checklist" items 1–4 encoded as an automated regression test, run by CI's `migrate:fresh --force` + `artisan test` steps already in `.github/workflows/ci.yml`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// backend/tests/Feature/MigrationVerificationTest.php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('has all 26 v1 tables after a fresh migration', function () {
    $expectedTables = [
        'users', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks',
        'jobs', 'job_batches', 'failed_jobs', 'personal_access_tokens',
        'business_settings', 'products', 'product_images', 'product_option_groups',
        'product_option_values', 'product_variants', 'product_variant_option_values',
        'preorder_dates', 'delivery_zones', 'delivery_zone_postcodes', 'customers',
        'customer_addresses', 'orders', 'order_items', 'order_item_option_values',
        'order_status_events', 'payments', 'payment_proofs', 'notification_logs',
        'activity_logs',
    ];

    foreach ($expectedTables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Missing table: {$table}");
    }
});

it('enforces unique public identifiers across the schema', function () {
    $product = \App\Models\Product::factory()->create();
    $customer = \App\Models\Customer::factory()->create();
    $order = \App\Models\Order::factory()->create();

    expect($product->ulid)->toHaveLength(26);
    expect($customer->ulid)->toHaveLength(26);
    expect($order->ulid)->toHaveLength(26);
    expect($order->idempotency_key)->not->toBeNull();
});

it('retains order snapshots after the source product is force-deleted', function () {
    $item = \App\Models\OrderItem::factory()->create();
    $snapshotName = $item->product_name_snapshot;

    \App\Models\Product::find($item->product_id)->forceDelete();

    expect($item->fresh()->product_name_snapshot)->toBe($snapshotName);
    expect($item->fresh()->product_id)->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails (or passes if earlier tasks are complete)**

Run: `cd backend && php artisan migrate:fresh --force && php artisan test --filter=MigrationVerificationTest`
Expected: If Tasks 1–14 are all done, this should already PASS — it is a regression guard, not new functionality. If any table is missing, it fails with a clear "Missing table: X" message pointing at the incomplete task.

- [ ] **Step 3: Commit**

```bash
git add backend/tests/Feature/MigrationVerificationTest.php
git commit -m "test(schema): add full-schema migration verification regression test"
```

---

## Self-Review Notes

- **Spec coverage:** every table in spec §9's migration-order table (007–026) has a task; §9's "Required development seeders" list (6 seeders) is Task 14; §9's "Migration verification checklist" items 1–4 are Task 15 (items 5–7 — concurrent checkout, WhatsApp expiry, backup/restore — belong to the future checkout-logic and infra plans, not this schema-only plan, and are explicitly called out as out of scope above).
- **Placeholder scan:** no TBD/TODO; every step has runnable code.
- **Type consistency:** `HasUlid::uniqueIds()` (Task 1) is reused identically by `User`, `Product`, `Customer`, `Order`, `Payment` — same trait, same method name throughout. `belongsTo`/`hasMany` relation method names match the FK column names used in every later task's migration.
- **Scope:** intentionally schema-only. Checkout/reservation logic (§8), Filament admin (§4/§11), and public API routes (§10) are separate future plans that will build on top of the models this plan produces.
