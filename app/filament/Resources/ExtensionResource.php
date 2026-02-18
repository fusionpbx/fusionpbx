<?php
/**
 * FusionPBX - Extension Resource for Filament
 * 
 * Filament resource for managing SIP extensions.
 * 
 * @package    FusionPBX
 * @subpackage Filament\Resources
 */

namespace FusionPBX\Filament\Resources;

use FusionPBX\Models\Extension;
use FusionPBX\Filament\Resources\ExtensionResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class ExtensionResource extends Resource
{
    protected static ?string $model = Extension::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';
    
    protected static ?string $navigationGroup = 'Communication';
    
    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'extension';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('domain_uuid')
                            ->label('Domain')
                            ->relationship('domain', 'domain_name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\TextInput::make('extension')
                            ->required()
                            ->numeric()
                            ->maxLength(255)
                            ->helperText('The extension number (e.g., 1001)'),
                        
                        Forms\Components\TextInput::make('number_alias')
                            ->label('Number Alias')
                            ->maxLength(255)
                            ->helperText('Optional alternate number'),
                        
                        Forms\Components\TextInput::make('password')
                            ->required()
                            ->maxLength(255)
                            ->helperText('SIP password for device registration'),
                        
                        Forms\Components\Toggle::make('enabled')
                            ->label('Enabled')
                            ->default(true),
                        
                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Directory Settings')
                    ->schema([
                        Forms\Components\TextInput::make('directory_first_name')
                            ->label('First Name')
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('directory_last_name')
                            ->label('Last Name')
                            ->maxLength(255),
                        
                        Forms\Components\Toggle::make('directory_visible')
                            ->label('Visible in Directory')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('directory_exten_visible')
                            ->label('Extension Visible in Directory')
                            ->default(true),
                    ])
                    ->columns(2)
                    ->collapsed(),
                
                Forms\Components\Section::make('Caller ID')
                    ->schema([
                        Forms\Components\TextInput::make('effective_caller_id_name')
                            ->label('Effective Caller ID Name')
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('effective_caller_id_number')
                            ->label('Effective Caller ID Number')
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('outbound_caller_id_name')
                            ->label('Outbound Caller ID Name')
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('outbound_caller_id_number')
                            ->label('Outbound Caller ID Number')
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('emergency_caller_id_name')
                            ->label('Emergency Caller ID Name')
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('emergency_caller_id_number')
                            ->label('Emergency Caller ID Number')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsed(),
                
                Forms\Components\Section::make('Advanced Settings')
                    ->schema([
                        Forms\Components\TextInput::make('accountcode')
                            ->label('Account Code')
                            ->maxLength(80),
                        
                        Forms\Components\Select::make('user_context')
                            ->label('User Context')
                            ->options([
                                'default' => 'Default',
                                'public' => 'Public',
                                'local' => 'Local',
                            ])
                            ->default('default')
                            ->native(false),
                        
                        Forms\Components\TextInput::make('call_timeout')
                            ->label('Call Timeout (seconds)')
                            ->numeric()
                            ->default(30),
                        
                        Forms\Components\Select::make('call_group')
                            ->label('Call Group')
                            ->options([
                                'sales' => 'Sales',
                                'support' => 'Support',
                                'billing' => 'Billing',
                            ])
                            ->multiple()
                            ->searchable(),
                        
                        Forms\Components\Toggle::make('call_screen_enabled')
                            ->label('Call Screen Enabled')
                            ->default(false),
                        
                        Forms\Components\Toggle::make('do_not_disturb')
                            ->label('Do Not Disturb')
                            ->default(false),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('extension')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('directory_first_name')
                    ->label('First Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('directory_last_name')
                    ->label('Last Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('domain.domain_name')
                    ->label('Domain')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\IconColumn::make('enabled')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('effective_caller_id_number')
                    ->label('Caller ID')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('Not set'),
                
                Tables\Columns\TextColumn::make('accountcode')
                    ->label('Account Code')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('description')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                
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
                
                Tables\Filters\TernaryFilter::make('enabled')
                    ->label('Enabled')
                    ->boolean()
                    ->trueLabel('Enabled only')
                    ->falseLabel('Disabled only')
                    ->native(false),
                
                Tables\Filters\TernaryFilter::make('directory_visible')
                    ->label('Directory Visible')
                    ->boolean()
                    ->native(false),
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
            ->defaultSort('extension', 'asc');
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
            'index' => Pages\ListExtensions::route('/'),
            'create' => Pages\CreateExtension::route('/create'),
            'view' => Pages\ViewExtension::route('/{record}'),
            'edit' => Pages\EditExtension::route('/{record}/edit'),
        ];
    }
}
