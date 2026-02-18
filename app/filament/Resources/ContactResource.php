<?php
/**
 * FusionPBX - Contact Resource for Filament
 * 
 * Filament resource for managing contacts.
 * 
 * @package    FusionPBX
 * @subpackage Filament\Resources
 */

namespace FusionPBX\Filament\Resources;

use FusionPBX\Models\Contact;
use FusionPBX\Filament\Resources\ContactResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    
    protected static ?string $navigationGroup = 'Contacts & CRM';
    
    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'contact_name_given';

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
                        
                        Forms\Components\Select::make('contact_type')
                            ->label('Contact Type')
                            ->options([
                                'person' => 'Person',
                                'company' => 'Company',
                                'lead' => 'Lead',
                                'customer' => 'Customer',
                                'vendor' => 'Vendor',
                            ])
                            ->default('person')
                            ->required()
                            ->native(false),
                        
                        Forms\Components\TextInput::make('contact_name_given')
                            ->label('First Name')
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('contact_name_family')
                            ->label('Last Name')
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('contact_organization')
                            ->label('Organization')
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('contact_title')
                            ->label('Title')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('contact_email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('contact_url')
                            ->label('Website')
                            ->url()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('contact_nickname')
                            ->label('Nickname')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsed(),
                
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('contact_note')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contact_name_given')
                    ->label('First Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('contact_name_family')
                    ->label('Last Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('contact_organization')
                    ->label('Organization')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('contact_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'person' => 'gray',
                        'company' => 'success',
                        'lead' => 'warning',
                        'customer' => 'info',
                        'vendor' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('contact_email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('contact_title')
                    ->label('Title')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('domain.domain_name')
                    ->label('Domain')
                    ->searchable()
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
                
                Tables\Filters\SelectFilter::make('contact_type')
                    ->label('Type')
                    ->options([
                        'person' => 'Person',
                        'company' => 'Company',
                        'lead' => 'Lead',
                        'customer' => 'Customer',
                        'vendor' => 'Vendor',
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
            ->defaultSort('contact_name_family', 'asc');
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
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'view' => Pages\ViewContact::route('/{record}'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
