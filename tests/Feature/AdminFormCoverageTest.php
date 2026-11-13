<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\LevelResource;
use App\Filament\Admin\Resources\SceneResource;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Tests\TestCase;

/**
 * Admin panel harus bisa MENYUNTING semua kolom yang dipakai frontend.
 *
 * Sebelumnya kolom JSON (characters, scene_comics, scene_hints, scene_dialogs,
 * char*_layer, char*_sub_views, comic_pages, intro_dialog) tidak punya field
 * di form admin, sehingga isinya tidak bisa diubah sama sekali.
 */
class AdminFormCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function makeForm(): Form
    {
        $livewire = new class extends Component implements HasForms
        {
            use InteractsWithForms;

            public array $data = [];
        };

        return Form::make($livewire)->statePath('data');
    }

    public function test_level_form_exposes_content_columns(): void
    {
        $form = $this->makeForm();
        (new LevelResource())->form($form);

        $names = collect($form->getComponents())
            ->flatMap(function ($c) {
                $out = [];
                if (method_exists($c, 'getName') && $c->getName()) $out[] = $c->getName();
                if (method_exists($c, 'getChildComponents')) {
                    foreach ($c->getChildComponents() as $child) {
                        if (method_exists($child, 'getName') && $child->getName()) $out[] = $child->getName();
                    }
                }
                return $out;
            })
            ->unique()
            ->values();

        foreach ([
            'level_key', 'title', 'description', 'thumbnail', 'time_limit',
            'star_thresholds', 'music', 'dialog_music', 'is_locked', 'order',
            'characters', 'scene_comics', 'scene_hints', 'scene_dialogs',
            'comic_src', 'exit_door_points',
            'character_avatar', 'character_name',
        ] as $field) {
            $this->assertContains($field, $names, "Field admin '$field' tidak tersedia.");
        }
    }

    public function test_scene_form_exposes_content_columns(): void
    {
        $form = $this->makeForm();
        (new SceneResource())->form($form);

        $names = collect($form->getComponents())
            ->flatMap(function ($c) {
                $out = [];
                if (method_exists($c, 'getName') && $c->getName()) $out[] = $c->getName();
                if (method_exists($c, 'getChildComponents')) {
                    foreach ($c->getChildComponents() as $child) {
                        if (method_exists($child, 'getName') && $child->getName()) $out[] = $child->getName();
                    }
                }
                return $out;
            })
            ->unique()
            ->values();

        foreach ([
            'scene_key', 'label', 'background_image', 'exit_puzzle_id', 'order',
            'char1_layer', 'char2_layer', 'char3_layer',
            'char1_sub_views', 'char2_sub_views', 'char3_sub_views',
            'scene_comic', 'comic_pages', 'intro_dialog', 'highlight_hotspots',
            'auto_switch_char',
        ] as $field) {
            $this->assertContains($field, $names, "Field admin '$field' tidak tersedia.");
        }
    }
}
