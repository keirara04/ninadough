<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminProductImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_upload_product_image(): void
    {
        Storage::fake('spaces');
        $owner = User::factory()->create(['role' => 'owner']);
        $product = Product::factory()->create();
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/products/{$product->id}/images", [
            'image' => UploadedFile::fake()->image('cake.jpg'),
        ]);

        $response->assertCreated();
        $this->assertSame(1, ProductImage::count());
        $this->assertTrue(ProductImage::first()->is_primary);
    }

    public function test_staff_blocked_from_uploading_images(): void
    {
        Storage::fake('spaces');
        $staff = User::factory()->create(['role' => 'staff']);
        $product = Product::factory()->create();
        Sanctum::actingAs($staff);

        $response = $this->postJson("/api/v1/admin/products/{$product->id}/images", [
            'image' => UploadedFile::fake()->image('cake.jpg'),
        ]);

        $response->assertStatus(403);
    }

    public function test_rejects_non_whitelisted_mime(): void
    {
        Storage::fake('spaces');
        $owner = User::factory()->create(['role' => 'owner']);
        $product = Product::factory()->create();
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/products/{$product->id}/images", [
            'image' => UploadedFile::fake()->create('cake.svg', 10, 'image/svg+xml'),
        ]);

        $response->assertStatus(422);
    }

    public function test_rejects_upload_past_max_image_count(): void
    {
        Storage::fake('spaces');
        $owner = User::factory()->create(['role' => 'owner']);
        $product = Product::factory()->create();
        ProductImage::factory()->for($product)->count(6)->create();
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/products/{$product->id}/images", [
            'image' => UploadedFile::fake()->image('cake.jpg'),
        ]);

        $response->assertStatus(422);
    }

    public function test_setting_primary_unsets_other_images(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $product = Product::factory()->create();
        $first = ProductImage::factory()->for($product)->create(['is_primary' => true]);
        $second = ProductImage::factory()->for($product)->create(['is_primary' => false]);
        Sanctum::actingAs($owner);

        $response = $this->patchJson("/api/v1/admin/products/{$product->id}/images/{$second->id}/primary");

        $response->assertOk();
        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);
    }

    public function test_delete_removes_file_and_row(): void
    {
        Storage::fake('spaces');
        $owner = User::factory()->create(['role' => 'owner']);
        $product = Product::factory()->create();
        $path = 'products/test.jpg';
        Storage::disk('spaces')->put($path, 'fake-contents');
        $image = ProductImage::factory()->for($product)->create(['storage_disk' => 'spaces', 'object_key' => $path]);
        Sanctum::actingAs($owner);

        $response = $this->deleteJson("/api/v1/admin/products/{$product->id}/images/{$image->id}");

        $response->assertOk();
        $this->assertSame(0, ProductImage::count());
        Storage::disk('spaces')->assertMissing($path);
    }
}
