<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\DialogLine;
use App\Models\Hotspot;
use App\Models\Level;
use App\Models\Scene;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'name' => 'Admin Test',
        ]);

        $this->level = Level::create([
            'level_key' => 'level_1',
            'title' => 'Lab',
            'description' => 'Test level',
            'thumbnail' => '/ui/placeholder.png',
            'time_limit' => 600,
            'star_thresholds' => [400, 200, 60],
            'is_locked' => false,
            'order' => 1,
            'scene_dialogs' => [
                'scene1_intro' => [
                    [
                        'character' => 'Kayshii',
                        'text' => 'Halo dari scene 1',
                        'side' => 'left',
                        'avatarLeft' => '/avatars/zkayshi1.png',
                    ],
                ],
            ],
        ]);

        $this->scene = Scene::create([
            'level_id' => $this->level->id,
            'scene_key' => 'scene_1',
            'label' => 'Ruang Lab',
            'order' => 1,
        ]);

        Achievement::create([
            'level_id' => $this->level->id,
            'achievement_key' => 'ach_test',
            'title' => 'Test Achievement',
            'description' => 'Desc',
            'icon' => '/achievements/lab.png',
            'type' => 'time',
            'is_golden' => false,
            'order' => 1,
        ]);

        DialogLine::create([
            'level_id' => $this->level->id,
            'type' => 'intro',
            'character' => 'Kayshii',
            'avatar' => '/avatars/lab3.png',
            'text' => 'Intro line',
            'side' => 'left',
            'is_system' => false,
            'order' => 1,
        ]);

        Hotspot::create([
            'scene_id' => $this->scene->id,
            'hotspot_key' => 'hs_test',
            'label' => 'Lever',
            'x' => 10,
            'y' => 20,
            'width' => 30,
            'height' => 40,
            'cursor' => 'pointer',
            'z_index' => 1,
            'action_type' => 'open_puzzle',
            'action_data' => ['type' => 'open_puzzle', 'puzzleId' => 'puzzle_lever'],
            'order' => 1,
        ]);
    }

    public function test_admin_pages_render(): void
    {
        $this->actingAs($this->admin);

        foreach ([
            '/admin/achievements',
            '/admin/achievements/create',
            '/admin/dialog-lines',
            '/admin/dialog-lines/create',
            '/admin/hotspots',
            '/admin/hotspots/create',
            '/admin/treasures',
            '/admin/scene-dialogs',
        ] as $url) {
            $response = $this->get($url);
            $response->assertOk();
        }
    }

    public function test_edit_pages_render(): void
    {
        $this->actingAs($this->admin);

        $achievement = Achievement::first();
        $dialog = DialogLine::first();
        $hotspot = Hotspot::first();

        $this->get("/admin/achievements/{$achievement->id}/edit")->assertOk();
        $this->get("/admin/dialog-lines/{$dialog->id}/edit")->assertOk();
        $this->get("/admin/hotspots/{$hotspot->id}/edit")->assertOk();
    }

    public function test_admin_upload_endpoint(): void
    {
        Storage::fake('public');

        // Guest ditolak 401 JSON
        $this->postJson('/admin/uploads', [])->assertStatus(401);

        $this->actingAs($this->admin);

        // Validasi tanpa file → 422 JSON
        $response = $this->postJson('/admin/uploads', []);
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());

        // Direktori di luar whitelist ditolak
        $response = $this->postJson('/admin/uploads', [
            'image' => UploadedFile::fake()->create('icon.png', 10, 'image/png'),
            'directory' => '../../etc',
        ]);
        $response->assertStatus(422);

        // Upload valid
        $response = $this->postJson('/admin/uploads', [
            'image' => UploadedFile::fake()->create('icon.png', 10, 'image/png'),
            'directory' => 'avatars',
        ]);

        $response->assertOk()->assertJsonStructure(['path', 'url']);
        Storage::disk('public')->assertExists($response->json('path'));
        $this->assertStringStartsWith('avatars/', $response->json('path'));
    }

    public function test_scene_dialogs_page_shows_groups_and_lines(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/scene-dialogs');

        $response->assertOk();
        $response->assertSee('scene1_intro', false);
        $response->assertSee('Kayshii', false);
        $response->assertSee('Halo dari scene 1', false);
    }
}
