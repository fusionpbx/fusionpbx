<?php
/**
 * FusionPBX - User Resource for Filament
 * 
 * Filament resource for managing users.
 * 
 * @package    FusionPBX
 * @subpackage Filament\Resources
 */

namespace FusionPBX\Filament\Resources;

use FusionPBX\Models\User;
use FusionPBX\Filament\Resources\UserResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'System';
    
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'username';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\Select::make('domain_uuid')
                            ->label('Domain')
                            ->relationship('domain', 'domain_name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\TextInput::make('username')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Unique username for login'),
                        
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => $state ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->helperText('Leave blank to keep current password'),
                        
                        Forms\Components\Toggle::make('user_enabled')
                            ->label('Enabled')
                            ->default(true),
                        
                        Forms\Components\Select::make('user_status')
                            ->label('Status')
                            ->options([
                                'Available' => 'Available',
                                'On Break' => 'On Break',
                                'Do Not Disturb' => 'Do Not Disturb',
                                'Logged Out' => 'Logged Out',
                            ])
                            ->default('Available')
                            ->native(false),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Profile Settings')
                    ->schema([
                        Forms\Components\Select::make('contact_uuid')
                            ->label('Contact')
                            ->relationship('contact', 'contact_name_given')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        
                        Forms\Components\TextInput::make('user_language')
                            ->label('Language')
                            ->default('en-us')
                            ->maxLength(10),
                        
                        Forms\Components\TextInput::make('user_time_zone')
                            ->label('Time Zone')
                            ->default('America/New_York')
                            ->maxLength(50),
                        
                        Forms\Components\TextInput::make('user_email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsed(),
                
                Forms\Components\Section::make('Permissions')
                    ->schema([
                        Forms\Components\Toggle::make('user_edit_own_extension')
                            ->label('Can Edit Own Extension')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('user_edit_own_device')
                            ->label('Can Edit Own Device')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('user_edit_own_voicemail')
                            ->label('Can Edit Own Voicemail')
                            ->default(true),
                        
                        Forms\Components\TextInput::make('api_key')
                            ->label('API Key')
                            ->maxLength(255)
                            ->helperText('Optional API key for programmatic access'),
                    ])
                    ->columns(2)
                    ->collapsed(),
                
                Forms\Components\Section::make('Groups')
                    ->schema([
                        Forms\Components\Select::make('groups')
                            ->label('User Groups')
                            ->relationship('groups', 'group_name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Assign user to groups for permission management'),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('username')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('domain.domain_name')
                    ->label('Domain')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\IconColumn::make('user_enabled')
                    ->label('Enabled')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('user_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Available' => 'success',
                        'On Break' => 'warning',
                        'Do Not Disturb' => 'danger',
                        'Logged Out' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('contact.contact_name_given')
                    ->label('Contact')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('None'),
                
                Tables\Columns\TextColumn::make('user_email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('groups.group_name')
                    ->label('Groups')
                    ->badge()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('add_date')
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
                
                Tables\Filters\TernaryFilter::make('user_enabled')
                    ->label('Enabled')
                    ->boolean()
                    ->trueLabel('Enabled only')
                    ->falseLabel('Disabled only')
                    ->native(false),
                
                Tables\Filters\SelectFilter::make('user_status')
                    ->label('Status')
                    ->options([
                        'Available' => 'Available',
                        'On Break' => 'On Break',
                        'Do Not Disturb' => 'Do Not Disturb',
                        'Logged Out' => 'Logged Out',
                    ])
                    ->multiple(),
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
            ->defaultSort('username', 'asc');
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
