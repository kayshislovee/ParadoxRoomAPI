<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AchievementResource\Pages;
use App\Filament\Forms\Components\ImagePathUpload;
use App\Models\Achievement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters;

class AchievementResource extends Resource
{
    protected static ?string $model = Achievement::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationGroup = 'Konten Game';

    protected static ?string $navigationLabel = 'Achievements';

    protected static ?string $modelLabel = 'Achievement';

    protected static ?string $pluralModelLabel = 'Achievements';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Level & Identitas')
                    ->description('Ke mana achievement ini menempel dan kode uniknya.')
                    ->schema([
                        Forms\Components\Select::make('level_id')
                            ->label('Level')
                            ->relationship('level', 'title')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record ? "{$record->title} ({$record->level_key})" : '')
                            ->required(),
                        Forms\Components\TextInput::make('achievement_key')
                            ->label('Achievement Key')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Kode unik yang dipakai game untuk unlock, mis. ach_lab_treasure.'),
                    ])->columns(2),

                Forms\Components\Section::make('Konten')
                    ->description('Teks yang tampil di halaman achievements & saat unlock.')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Judul')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Aset & Tipe')
                    ->description('Icon (asset gambar) dan cara achievement ini didapat.')
                    ->schema([
                        ImagePathUpload::make('icon')
                            ->label('Icon / Asset Gambar')
                            ->directory('achievements')
                            ->placeholder('/achievements/lab.png atau uploads/icon-baru.png')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('type')
                            ->label('Tipe')
                            ->options([
                                'time' => 'Time — selesaikan dalam waktu tertentu',
                                'treasure' => 'Treasure — temukan semua treasure',
                            ])
                            ->required()
                            ->default('time'),
                        Forms\Components\Toggle::make('is_golden')
                            ->label('Golden (versi emas)')
                            ->helperText('Centang untuk varian golden.'),
                        Forms\Components\TextInput::make('time_threshold')
                            ->label('Batas Waktu (detik)')
                            ->numeric()
                            ->minValue(0)
                            ->default(null)
                            ->helperText('Hanya diisi untuk tipe Time. Kosongkan untuk tipe Treasure.'),
                        Forms\Components\TextInput::make('order')
                            ->label('Urutan')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('level.title')
                    ->label('Level')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\ImageColumn::make('icon')
                    ->label('Icon')
                    ->state(fn ($record) => self::publicUrl($record->icon))
                    ->checkFileExistence(false)
                    ->height(40)
                    ->width(40)
                    ->extraImgAttributes(['loading' => 'lazy']),
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->colors([
                        'warning' => 'time',
                        'success' => 'treasure',
                    ]),
                Tables\Columns\IconColumn::make('is_golden')
                    ->label('Golden')
                    ->boolean(),
                Tables\Columns\TextColumn::make('time_threshold')
                    ->label('Batas Waktu (s)')
                    ->numeric()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('order')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('level_id')
            ->filters([
                Filters\SelectFilter::make('level_id')
                    ->label('Level')
                    ->relationship('level', 'title')
                    ->searchable()
                    ->preload(),
                Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'time' => 'Time',
                        'treasure' => 'Treasure',
                    ]),
                Filters\TernaryFilter::make('is_golden')
                    ->label('Golden'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Ubah path aset menjadi URL absolut yang dipakai untuk preview di tabel.
     *  - URL penuh (http://...) / data: URI → apa adanya
     *  - path absolut ("/avatars/x.png") → url() host ini
     *  - path relatif ("achievements/x.png") → storage publik Laravel
     */
    public static function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $p = trim(str_replace('\\', '/', $path));

        if ($p === '') {
            return null;
        }

        if (preg_match('#^(https?:)?//#i', $p) || str_starts_with($p, 'data:')) {
            return $p;
        }

        if (str_starts_with($p, '/')) {
            return url($p);
        }

        return asset('storage/' . ltrim($p, '/'));
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAchievements::route('/'),
            'create' => Pages\CreateAchievement::route('/create'),
            'edit' => Pages\EditAchievement::route('/{record}/edit'),
        ];
    }
}
