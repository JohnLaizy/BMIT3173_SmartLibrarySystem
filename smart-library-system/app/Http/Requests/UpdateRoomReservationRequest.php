<?php

namespace App\Http\Requests;

use App\Models\RoomReservation;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        $reservation = $this->route('reservation');

        return $user instanceof User
            && $reservation instanceof RoomReservation
            && $user->can(
                'update',
                $reservation
            );
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isLibrarian =
            $this->user()?->isLibrarian() === true;

        return [
            'room_id' => [
                'required',
                'integer',
                Rule::exists('rooms', 'id'),
            ],

            'user_id' => [
                Rule::requiredIf($isLibrarian),
                Rule::prohibitedIf(! $isLibrarian),
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where(
                        'role',
                        User::ROLE_STUDENT
                    ),
            ],

            'purpose' => [
                'required',
                'string',
                'max:255',
            ],

            'starts_at' => [
                'required',
                'date',
                'after:now',
            ],

            'ends_at' => [
                'required',
                'date',
                'after:starts_at',
            ],
        ];
    }

    /**
     * @return array{
     *     room_id: int,
     *     user_id: int|null,
     *     purpose: string,
     *     starts_at: string,
     *     ends_at: string
     * }
     */
    public function validatedData(): array
    {
        return [
            'room_id' => $this->integer('room_id'),

            'user_id' => $this->filled('user_id')
                ? $this->integer('user_id')
                : null,

            'purpose' => $this
                ->string('purpose')
                ->toString(),

            'starts_at' => $this
                ->string('starts_at')
                ->toString(),

            'ends_at' => $this
                ->string('ends_at')
                ->toString(),
        ];
    }
}