<?php

namespace App\Filament\Resources\Teacher\GradePromotionResource\Pages;

use Filament\Actions;
use App\Helpers\Helper;
use Filament\Forms\Get;
use Filament\Actions\Action;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Select;
use App\Models\ClassSchool;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\CheckboxList;
use App\Models\MemberClassSchool;
use App\Filament\Resources\Teacher\GradePromotionResource;
use Filament\Forms\Components\Hidden;

class ListGradePromotions extends ListRecords
{
    protected static string $resource = GradePromotionResource::class;

    protected function getHeaderActions(): array
    {
        $lastGrade = ClassSchool::where('academic_year_id', Helper::getActiveAcademicYearId())->max('level_id');

        return [
            Action::make('Select Student')
                ->form([
                    Select::make('class_school_id')
                        ->relationship('classSchool', 'name', function ($query) {
                            if (auth()->user()->hasRole('super_admin')) {
                                return $query->whereNotIn('level_id', [1, 2, 3])->where('academic_year_id', Helper::getActiveAcademicYearId())->orderBy('level_id');
                            } else {
                                $user = auth()->user();
                                if ($user && $user->employee && $user->employee->teacher) {
                                    $teacherId = $user->employee->teacher->id;
                                    return $query->whereNotIn('level_id', [1, 2, 3])->where('academic_year_id', Helper::getActiveAcademicYearId())->orderBy('level_id')->where('teacher_id', $teacherId);
                                }
                                return $query->with('subject')->whereHas('classSchool', function (Builder $query) {
                                    $query->where('academic_year_id', Helper::getActiveAcademicYearId());
                                });
                            }
                        })
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                        ->required()
                        ->searchable()
                        ->preload()
                        ->label('Class School')
                        ->reactive()
                        ->live(),
                    CheckboxList::make('member_class_school_id')
                        ->label('Students')
                        ->rules(function ($get) {
                            $classSchoolId = $get('class_school_id');

                            return [
                                Rule::unique('grade_promotions', 'member_class_school_id')->where(function ($query) use ($classSchoolId) {
                                    return $query->where('class_school_id', $classSchoolId);
                                }),
                            ];
                        })
                        ->options(function (Get $get) {
                            $selectedClassSchool = ClassSchool::find($get('class_school_id'));

                            if ($selectedClassSchool) {
                                if ($selectedClassSchool->memberClassSchools) {
                                    $memberClassSchool = $selectedClassSchool->memberClassSchools->pluck('id')
                                        ->toArray();

                                    return MemberClassSchool::whereIn('id', $memberClassSchool)->get()->pluck('student.fullname', 'id');
                                }
                            }

                            return collect(); // Return an empty collection if the checks fail
                        })
                        ->searchable()
                        ->bulkToggleable()
                        ->columns(3),
                    Hidden::make('decision')->default(function (Get $get) use ($lastGrade) {
                        $classSchoolId = $get('class_school_id');

                        if ($classSchoolId) {
                            $classSchool = \App\Models\ClassSchool::find($classSchoolId); // Adjust the model namespace as needed

                            if ($classSchool && $classSchool->level) {
                                $currentLevelId = $classSchool->level_id;

                                if ($currentLevelId != $lastGrade) {
                                    return '1';
                                } else {
                                    return '3';
                                }
                            }
                        }

                        return '1';
                    }),
                ])
                ->action(function (array $data): void {
                    $dataArray = [];
                    $getMemberClassSchoolId = $data['member_class_school_id'];

                    if (!count($getMemberClassSchoolId)) {
                        Notification::make()->warning()->title('Whopps, cant do that :(')->body('No student selected')->send();
                    } else {
                        for ($i = 0; $i < count($getMemberClassSchoolId); $i++) {
                            $dataArray = [
                                'class_school_id' => $data['class_school_id'],
                                'decision' => $data['decision'],
                                'member_class_school_id' => $getMemberClassSchoolId[$i],
                            ];

                            $exists = DB::table('member_class_schools')
                                ->where('id', $getMemberClassSchoolId[$i])
                                ->exists();

                            if (!$exists) {
                                Notification::make()
                                    ->warning()
                                    ->title('Warning')
                                    ->body('Member Class ID ' . $getMemberClassSchoolId[$i] . ' does not exist.')
                                    ->send();

                                Log::warning('Member Class ID does not exist', [
                                    'member_class_school_id' => $getMemberClassSchoolId[$i],
                                ]);

                                continue;
                            }

                            try {
                                DB::table('grade_promotions')->insert($dataArray);

                                // Send success notification
                                Notification::make()->success()->title('Yeayy, success!')->body('Successfully added data')->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('An error occurred: ' . $e->getMessage())
                                    ->send();

                                // Log the error for debugging
                                Log::error('Error inserting student attendance: ' . $e->getMessage(), [
                                    'dataArray' => $dataArray,
                                ]);
                            }
                        }
                    }
                }),
        ];
    }
}
