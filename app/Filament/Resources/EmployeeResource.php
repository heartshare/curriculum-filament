<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Employee;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use RelationManagers\TreatmentsRelationManager;
use App\Filament\Resources\EmployeeResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Filament\Resources\EmployeeResource\RelationManagers\UsersRelationManager;

function generateUniqueEmployeeCode(): string
{
    $code = Str::random(6); // Menghasilkan kode acak
    while (Employee::where('kode_karyawan', $code)->exists()) {
        $code = Str::random(6); // Ulangi jika kode sudah ada di database
    }
    return strtoupper($code);
}

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        // Personal Information
                        Forms\Components\Tabs\Tab::make('Personal Information')
                            ->schema([
                                Forms\Components\TextInput::make('nama_lengkap')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('kode_karyawan')
                                            ->label('Employee Code')
                                            ->maxLength(25)
                                            ->nullable()
                                            ->disabled()
                                            ->default(fn () => generateUniqueEmployeeCode()),
                                        Forms\Components\TextInput::make('nik')
                                            ->maxLength(16)
                                            ->nullable(),
                                    ]),
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\Select::make('jenis_kelamin')
                                            ->label('Gender')
                                            ->options([
                                                '1' => 'Male',
                                                '2' => 'Female',
                                            ])
                                            ->required()
                                            ->searchable(),
                                        Forms\Components\Select::make('agama')
                                            ->label('Religion')
                                            ->options([
                                                '1' => 'Islam',
                                                '2' => 'Kristen',
                                                '3' => 'Katolik',
                                                '4' => 'Hindu',
                                                '5' => 'Budha',
                                                '6' => 'Konghucu',
                                                '7' => 'Lainnya',
                                            ])
                                            ->required()
                                            ->searchable(),
                                    ]),
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('tempat_lahir')
                                            ->label('Place of Birth')
                                            ->maxLength(50)
                                            ->nullable(),
                                        Forms\Components\DatePicker::make('tanggal_lahir')
                                            ->label('Date of Birth')
                                            ->native(false)
                                            ->nullable(),
                                    ]),
                                Forms\Components\TextInput::make('warga_negara')
                                    ->label('Nationality')
                                    ->maxLength(255)
                                    ->nullable(),
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\Select::make('status_pernikahan')
                                            ->label('Marital Status')
                                            ->options([
                                                '1' => 'Merried',
                                                '2' => 'Single',
                                                '3' => 'Widow',
                                                '4' => 'Widower',
                                            ])
                                            ->searchable()
                                            ->nullable(),
                                        Forms\Components\TextInput::make('nama_pasangan')
                                            ->label('Spouse Name')
                                            ->maxLength(255)
                                            ->nullable(),
                                    ]),
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('jumlah_anak')
                                            ->label('Number of Children')
                                            ->maxLength(255)
                                            ->nullable(),
                                        Forms\Components\TextInput::make('keterangan')
                                            ->label('Notes')
                                            ->maxLength(255)
                                            ->nullable(),
                                    ]),
                            ]),

                        // Employment Information
                        Forms\Components\Tabs\Tab::make('Employment Information')
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\Select::make('employee_status_id')
                                            ->label('Employee Status')
                                            ->relationship(name: 'employeeStatus', titleAttribute: 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                        Forms\Components\Select::make('employee_unit_id')
                                            ->label('Employee Unit')
                                            ->relationship(name: 'employeeUnit', titleAttribute: 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(),

                                    ]),
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\Select::make('employee_position_id')
                                            ->label('Employee Position')
                                            ->relationship(name: 'employeePosition', titleAttribute: 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                        Forms\Components\DatePicker::make('join_date')
                                            ->nullable(),
                                    ]),
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\DatePicker::make('resign_date')
                                            ->label('Resignation Date')
                                            ->native(false)
                                            ->nullable(),
                                        Forms\Components\DatePicker::make('permanent_date')
                                            ->label('Permanent Date')
                                            ->native(false)
                                            ->nullable(),
                                    ]),
                            ]),
                        // Contact Information
                        Forms\Components\Tabs\Tab::make('Contact Information')
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('nomor_phone')
                                            ->tel()
                                            ->maxLength(255)
                                            ->nullable(),
                                        Forms\Components\TextInput::make('nomor_hp')
                                            ->tel()
                                            ->maxLength(255)
                                            ->nullable(),
                                    ]),
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->maxLength(255)
                                            ->nullable(),
                                        Forms\Components\TextInput::make('email_sekolah')
                                            ->email()
                                            ->maxLength(255)
                                            ->nullable(),
                                    ]),
                                Forms\Components\TextInput::make('alamat')
                                    ->maxLength(255)
                                    ->nullable(),
                                Forms\Components\TextInput::make('alamat_sekarang')
                                    ->maxLength(255)
                                    ->nullable(),
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('kota')
                                            ->maxLength(255)
                                            ->nullable(),
                                        Forms\Components\TextInput::make('kode_pos')
                                            ->maxLength(255)
                                            ->nullable(),
                                    ]),
                            ]),

                        // Tax and Insurance Information
                        Forms\Components\Tabs\Tab::make('Tax and Insurance Information')
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('nomor_taxpayer')
                                            ->maxLength(255)
                                            ->nullable(),
                                        Forms\Components\TextInput::make('nama_taxpayer')
                                            ->maxLength(255)
                                            ->nullable(),
                                    ]),
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('nomor_bpjs_ketenagakerjaan')
                                            ->maxLength(255)
                                            ->nullable(),
                                        Forms\Components\TextInput::make('iuran_bpjs_ketenagakerjaan')
                                            ->maxLength(255)
                                            ->nullable(),
                                    ]),
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('nomor_bpjs_yayasan')
                                            ->maxLength(255)
                                            ->nullable(),
                                        Forms\Components\TextInput::make('nomor_bpjs_pribadi')
                                            ->maxLength(255)
                                            ->nullable(),
                                    ]),
                            ]),

                        // Documents
                        Forms\Components\Tabs\Tab::make('Documents')
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\SpatieMediaLibraryFileUpload::make('pas_photo')
                                            ->directory('employee')
                                            ->collection('pas_photo')
                                            ->nullable(),
                                        Forms\Components\SpatieMediaLibraryFileUpload::make('ttd')
                                            ->directory('employee/ttd')
                                            ->collection('ttd')
                                            ->nullable(),
                                    ]),
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\SpatieMediaLibraryFileUpload::make('photo_kartu_identitas')
                                            ->directory('employee/photo_kartu_identitas')
                                            ->collection('photo_kartu_identitas')
                                            ->nullable(),
                                        Forms\Components\SpatieMediaLibraryFileUpload::make('photo_taxpayer')
                                            ->directory('employee')
                                            ->collection('photo_taxpayer')
                                            ->nullable(),
                                    ]),
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\SpatieMediaLibraryFileUpload::make('photo_kk')
                                            ->directory('employee/photo_kk')
                                            ->collection('photo_kk')
                                            ->nullable(),
                                        Forms\Components\SpatieMediaLibraryFileUpload::make('other_document')
                                            ->collection('other_document')
                                            ->preserveFilenames()
                                            ->nullable(),
                                    ]),
                            ]),
                    ])->columnSpan('full')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.username')
                    ->label('Username')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->searchable(),
                Tables\Columns\TextColumn::make('employeeStatus.name')
                    ->label('Status')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employeeUnit.name')
                    ->label('Unit')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employeePosition.name')
                    ->label('Position')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kode_karyawan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
