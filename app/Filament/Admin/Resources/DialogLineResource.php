<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DialogLineResource\Pages;
use App\Filament\Forms\Components\ImagePathUpload;
use App\Models\DialogLine;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters;

class DialogLineResource extends Resource
{
    protected static ?string $model = DialogLine::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Konten Game';

    protected static ?string $navigationLabel = 'Dialog Lines';

    protected static ?string $modelLabel = 'Dialog Line';

    protected static ?string $pluralModelLabel = 'Dialog Lines';

    /**
     * Ubah nilai numerik string ("750", "-55") menjadi angka asli, supaya
     * style (bottom/left/right/height) valid saat dipakai React di game.
     */
    public static function numericValues(?array $state): ?array
    {
        if (! is_array($state)) {
            return $state;
        }

        $out = [];

        foreach ($state as $key => $value) {
            if (is_string($value) && is_numeric(trim($value))) {
                $trimmed = trim($value);
                $out[$key] = str_contains($trimmed, '.') ? (float) $trimmed : (int) $trimmed;
            } else {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Level & Tipe')
                    ->description('Dialog dipakai pada layar intro level, atau layar menang/kalah.')
                    ->schema([
                        Forms\Components\Select::make('level_id')
                            ->label('Level')
                            ->relationship('level', 'title')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record ? "{$record->title} ({$record->level_key})" : '')
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label('Tipe Dialog')
                            ->options([
                                'intro' => 'Intro — sebelum masuk game',
                                'win' => 'Win — setelah menang',
                                'lose' => 'Lose — setelah kalah',
                            ])
                            ->required()
                            ->default('intro'),
                        Forms\Components\TextInput::make('order')
                            ->label('Urutan')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Urutan tampil dalam satu tipe dialog.'),
                    ])->columns(3),

                Forms\Components\Section::make('Karakter & Avatar')
                    ->description('Siapa yang bicara dan gambar yang tampil. Isi "Avatar kiri/kanan" bila 2 karakter tampil bersamaan.')
                    ->schema([
                        Forms\Components\TextInput::make('character')
                            ->label('Nama Karakter')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Contoh: Kayshii, Zahra, Vania. Nama tampil di atas teks dialog.'),
                        Forms\Components\Select::make('side')
                            ->label('Sisi Bicara')
                            ->options([
                                'left' => 'Kiri',
                                'right' => 'Kanan',
                            ])
                            ->required()
                            ->default('left'),
                        Forms\Components\Toggle::make('is_system')
                            ->label('Baris Sistem')
                            ->helperText('Centang bila baris ini pesan sistem (bukan karakter).'),
                        ImagePathUpload::make('avatar')
                            ->label('Avatar (tunggal / fallback)')
                            ->directory('avatars')
                            ->placeholder('/avatars/lab2.png'),
                        ImagePathUpload::make('avatar_left')
                            ->label('Avatar Kiri')
                            ->directory('avatars')
                            ->placeholder('/avatars/lab3.png'),
                        ImagePathUpload::make('avatar_right')
                            ->label('Avatar Kanan')
                            ->directory('avatars')
                            ->placeholder('/avatars/villain1.png'),
                        Forms\Components\KeyValue::make('avatar_left_style')
                            ->label('Style Avatar Kiri')
                            ->keyLabel('Property')
                            ->valueLabel('Nilai')
                            ->addButtonLabel('Tambah property')
                            ->default(null)
                            ->dehydrateStateUsing(fn ($state) => self::numericValues($state))
                            ->helperText('Opsional. Key yang umum: bottom, left, height. Nilai angka, mis. bottom = -55, left = -40, height = 750.'),
                        Forms\Components\KeyValue::make('avatar_right_style')
                            ->label('Style Avatar Kanan')
                            ->keyLabel('Property')
                            ->valueLabel('Nilai')
                            ->addButtonLabel('Tambah property')
                            ->default(null)
                            ->dehydrateStateUsing(fn ($state) => self::numericValues($state))
                            ->helperText('Opsional. Key yang umum: bottom, right, height. Nilai angka, mis. bottom = 200, right = 350, height = 390.'),
                    ])->columns(2),

                Forms\Components\Section::make('Isi Dialog')
                    ->description('Teks yang diketikkan karakter di layar.')
                    ->schema([
                        Forms\Components\Textarea::make('text')
                            ->label('Isi Dialog')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
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
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->colors([
                        'info' => 'intro',
                        'success' => 'win',
                        'danger' => 'lose',
                    ]),
                Tables\Columns\TextColumn::make('order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('character')
                    ->label('Karakter')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('side')
                    ->label('Sisi')
                    ->badge(),
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->state(fn ($record) => AchievementResource::publicUrl($record->avatar))
                    ->checkFileExistence(false)
                    ->height(44)
                    ->width(44),
                Tables\Columns\IconColumn::make('is_system')
                    ->label('Sistem')
                    ->boolean(),
                Tables\Columns\TextColumn::make('text')
                    ->label('Isi Dialog')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->text),
            ])
            ->defaultSort('level_id')
            ->reorderable('order')
            ->filters([
                Filters\SelectFilter::make('level_id')
                    ->label('Level')
                    ->relationship('level', 'title')
                    ->searchable()
                    ->preload(),
                Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'intro' => 'Intro',
                        'win' => 'Win',
                        'lose' => 'Lose',
                    ]),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDialogLines::route('/'),
            'create' => Pages\CreateDialogLine::route('/create'),
            'edit' => Pages\EditDialogLine::route('/{record}/edit'),
        ];
    }
}
