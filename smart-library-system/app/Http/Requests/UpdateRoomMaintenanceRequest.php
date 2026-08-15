<?php

namespace App\Http\Requests;

use App\Models\RoomMaintenance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $maintenance =
            $this->route('maintenance');

        return $maintenance instanceof RoomMaintenance
            && $this->user()?->can(
                'update',
                $maintenance
            ) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'room_id' => [
                'required',
                'integer',
                Rule::exists('rooms', 'id'),
            ],

            'title' => [
                'required',
                'string',
                'max:120',
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'starts_at' => [
                'required',
                'date',
            ],

            'ends_at' => [
                'required',
                'date',
                'after:starts_at',
            ],

            'status' => [
                'required',
                'string',
                Rule::in(
                    RoomMaintenance::ALLOWED_STATUSES
                ),
            ],
        ];
    }

    /**
     * @return array{
     *     room_id: int,
     *     title: string,
     *     description: string|null,
     *     starts_at: string,
     *     ends_at: string,
     *     status: string
     * }
     */
    public function validatedData(): array
    {
        $description =
            $this->input('description');

        return [
            'room_id' => $this->integer('room_id'),

            'title' => $this->string('title')->toString(),

            'description' => is_string($description)
                    ? $description
                    : null,

            'starts_at' => $this->string('starts_at')->toString(),

            'ends_at' => $this->string('ends_at')->toString(),

            'status' => $this->string('status')->toString(),
        ];
    }
}
