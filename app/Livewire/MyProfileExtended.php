<?php

namespace App\Livewire;

use Exception;
use Filament\Forms\Form;
use Filament\Facades\Filament;
use Filament\Forms\Components\Grid;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use function Filament\Support\is_app_url;
use Filament\Support\Facades\FilamentView;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Contracts\Auth\Authenticatable;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;
use App\Filament\Resources\UserResource\RelationManagers\EmployeeRelationManager;

class MyProfileExtended extends MyProfileComponent
{
    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public $user;

    public function mount(): void
    {
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $data = $this->getUser()->attributesToArray();

        $this->form->fill($data);
    }

    public function getUser(): Authenticatable & Model
    {
        $user = Filament::auth()->user();

        if (!$user instanceof Model) {
            throw new Exception('The authenticated user object must be an Eloquent model to allow the profile page to update it.');
        }

        return $user;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                SpatieMediaLibraryFileUpload::make('media')->label('Avatar')
                    ->collection('avatars')
                    ->avatar()
                    ->required(),
                Grid::make()->schema([
                    TextInput::make('username')
                        ->disabled()
                        ->required(),
                    TextInput::make('email')
                        ->disabled()
                        ->required(),
                ]),
                ToggleButtons::make('status')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Non active',
                    ]),
            ])
            ->operation('edit')
            ->model($this->getUser())
            ->statePath('data');
    }

    public function submit()
    {
        try {
            $data = $this->form->getState();

            $this->handleRecordUpdate($this->getUser(), $data);

            Notification::make()
                ->title('Profile updated')
                ->success()
                ->send();

            $this->redirect('my-profile', navigate: FilamentView::hasSpaMode() && is_app_url('my-profile'));
        } catch (\Throwable $th) {
            Notification::make()
                ->title('Failed to update.')
                ->danger()
                ->send();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        return $record;
    }

    public function render(): View
    {
        return view("livewire.my-profile-extended");
    }
}
