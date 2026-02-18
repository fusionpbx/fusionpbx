<?php
/**
 * FusionPBX - Domain Resource for Filament
 * 
 * Filament resource for managing domains/tenants.
 * 
 * @package    FusionPBX
 * @subpackage Filament\Resources
 */

namespace FusionPBX\Filament\Resources;

use FusionPBX\Models\Domain;
use FusionPBX\Filament\Resources\DomainResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DomainResource extends Resource
{
    protected static ?string $model = Domain::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    
    protected static ?string $navigationGroup = 'System';
    
    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'domain_name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Domain Information')
                    ->schema([
                        Forms\Components\TextInput::make('domain_name')
                            ->label('Domain Name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('The domain name (e.g., example.com)'),
                        
                        Forms\Components\Toggle::make('domain_enabled')
                            ->label('Enabled')
                            ->default(true)
                            ->helperText('Enable or disable this domain'),
                        
                        Forms\Components\Select::make('domain_parent_uuid')
                            ->label('Parent Domain')
                            ->relationship('parent', 'domain_name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Optional parent domain for hierarchical organization'),
                        
                        Forms\Components\Textarea::make('domain_description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Optional description of this domain'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\TextInput::make('insert_user')
                            ->label('Created By')
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\DateTimePicker::make('insert_date')
                            ->label('Created At')
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\TextInput::make('update_user')
                            ->label('Updated By')
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\DateTimePicker::make('update_date')
                            ->label('Updated At')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('domain_name')
                    ->label('Domain Name')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->description(fn (Domain $record): string => $record->domain_description ?? ''),
                
                Tables\Columns\IconColumn::make('domain_enabled')
                    ->label('Enabled')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('parent.domain_name')
                    ->label('Parent Domain')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('None'),
                
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('extensions_count')
                    ->label('Extensions')
                    ->counts('extensions')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('insert_date')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('update_date')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('domain_enabled')
                    ->label('Enabled')
                    ->boolean()
                    ->trueLabel('Enabled only')
                    ->falseLabel('Disabled only')
                    ->native(false),
                
                Tables\Filters\SelectFilter::make('domain_parent_uuid')
                    ->label('Parent Domain')
                    ->relationship('parent', 'domain_name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('domain_name', 'asc');
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
            'index' => Pages\ListDomains::route('/'),
            'create' => Pages\CreateDomain::route('/create'),
            'view' => Pages\ViewDomain::route('/{record}'),
            'edit' => Pages\EditDomain::route('/{record}/edit'),
        ];
    }
}
