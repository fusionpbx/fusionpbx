<?php
/**
 * FusionPBX - Group Resource for Filament
 * 
 * Filament resource for managing groups.
 * 
 * @package    FusionPBX
 * @subpackage Filament\Resources
 */

namespace FusionPBX\Filament\Resources;

use FusionPBX\Models\Group;
use FusionPBX\Filament\Resources\GroupResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationGroup = 'System';
    
    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'group_name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Group Information')
                    ->schema([
                        Forms\Components\Select::make('domain_uuid')
                            ->label('Domain')
                            ->relationship('domain', 'domain_name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\TextInput::make('group_name')
                            ->label('Group Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Unique name for this group'),
                        
                        Forms\Components\Toggle::make('group_protected')
                            ->label('Protected')
                            ->default(false)
                            ->helperText('Protected groups cannot be deleted'),
                        
                        Forms\Components\Textarea::make('group_description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Group Level')
                    ->schema([
                        Forms\Components\TextInput::make('group_level')
                            ->label('Group Level')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('Higher levels have more privileges (0-100)'),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group_name')
                    ->label('Group Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Group $record): string => $record->group_description ?? ''),
                
                Tables\Columns\TextColumn::make('domain.domain_name')
                    ->label('Domain')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('group_level')
                    ->label('Level')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 80 => 'success',
                        $state >= 50 => 'warning',
                        default => 'gray',
                    }),
                
                Tables\Columns\IconColumn::make('group_protected')
                    ->label('Protected')
                    ->boolean()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('insert_date')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('domain_uuid')
                    ->label('Domain')
                    ->relationship('domain', 'domain_name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\TernaryFilter::make('group_protected')
                    ->label('Protected')
                    ->boolean()
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->disabled(fn (Group $record): bool => $record->group_protected === 'true'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('group_name', 'asc');
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
            'index' => Pages\ListGroups::route('/'),
            'create' => Pages\CreateGroup::route('/create'),
            'view' => Pages\ViewGroup::route('/{record}'),
            'edit' => Pages\EditGroup::route('/{record}/edit'),
        ];
    }
}
