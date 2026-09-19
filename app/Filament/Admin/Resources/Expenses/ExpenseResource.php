<?php

namespace App\Filament\Admin\Resources\Expenses;

use App\Enums\ExpenseCategory;
use App\Filament\Admin\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Admin\Resources\Expenses\Pages\EditExpense;
use App\Filament\Admin\Resources\Expenses\Pages\ListExpenses;
use App\Models\Expense;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Pengeluaran';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Pengeluaran';

    protected static ?string $pluralModelLabel = 'Pengeluaran';

    protected static ?string $recordTitleAttribute = 'description';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Pengeluaran')
                ->description('Catat detail transaksi, lalu lampirkan bukti untuk dokumentasi.')
                ->columnSpanFull()
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ])
                ->schema([
                    Select::make('category')
                        ->label('Kategori')
                        ->options(collect(ExpenseCategory::cases())->mapWithKeys(
                            fn (ExpenseCategory $category): array => [$category->value => $category->label()],
                        ))
                        ->required(),
                    DatePicker::make('expense_date')
                        ->label('Tanggal Pengeluaran')
                        ->required()
                        ->default(today())
                        ->maxDate(today()),
                    TextInput::make('amount')
                        ->label('Jumlah')
                        ->prefix('Rp')
                        ->numeric()
                        ->minValue(1)
                        ->required(),
                    Textarea::make('description')
                        ->label('Keterangan')
                        ->required()
                        ->maxLength(1000)
                        ->columnSpanFull(),
                    FileUpload::make('proof')
                        ->label('Bukti Pengeluaran')
                        ->disk('local')
                        ->directory('expenses/proofs')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                        ->maxSize(5120)
                        ->getUploadedFileNameForStorageUsing(
                            fn (UploadedFile $file): string => Str::uuid().'.'.$file->guessExtension(),
                        )
                        ->downloadable()
                        ->openable()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('expense_date', 'desc')
            ->columns([
                TextColumn::make('expense_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (ExpenseCategory $state): string => $state->label())
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable()
                    ->limit(50)
                    ->wrap(),
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Dicatat Oleh')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->stackedOnMobile()
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->options(collect(ExpenseCategory::cases())->mapWithKeys(
                        fn (ExpenseCategory $category): array => [$category->value => $category->label()],
                    )),
                Filter::make('expense_date')
                    ->label('Rentang Tanggal')
                    ->schema([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('expense_date', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('expense_date', '<=', $date));
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpenses::route('/'),
            'create' => CreateExpense::route('/create'),
            'edit' => EditExpense::route('/{record}/edit'),
        ];
    }
}
