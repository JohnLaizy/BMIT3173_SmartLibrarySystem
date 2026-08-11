<?php

namespace App\Http\Requests;

use App\Models\Room;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            Room::class
        ) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $roomNumber = $this->input('room_number');
        $name = $this->input('name');
        $type = $this->input('type');
        $location = $this->input('location');
        $status = $this->input('status');
        $description = $this->input('description');
        $facilities = $this->input('facilities', []);

        $this->merge([
            'room_number' => is_string($roomNumber)
                ? strtoupper(trim($roomNumber))
                : $roomNumber,

            'name' => is_string($name)
                ? trim($name)
                : $name,

            'type' => is_string($type)
                ? strtolower(trim($type))
                : $type,

            'location' => is_string($location)
                ? trim($location)
                : $location,

            'status' => is_string($status)
                ? strtolower(trim($status))
                : $status,

            'description' => is_string($description)
                ? trim($description)
                : $description,

            'facilities' => $facilities,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'room_number' => [
                'bail',
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('rooms', 'room_number'),
            ],

            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'type' => [
                'required',
                'string',
                Rule::in(Room::ALLOWED_TYPES),
            ],

            'capacity' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],

            'location' => [
                'required',
                'string',
                'max:100',
            ],

            'status' => [
                'required',
                'string',
                Rule::in(Room::ALLOWED_STATUSES),
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'facilities' => [
                'array',
                'max:5',
            ],

            'facilities.*' => [
                'string',
                'distinct',
                Rule::in(Room::ALLOWED_FACILITIES),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'room_number.required' => 'Room number is required.',
            'room_number.regex' => 'Room number may contain only letters, numbers and hyphens.',
            'room_number.unique' => 'This room number already exists.',

            'name.required' => 'Room name is required.',

            'type.required' => 'Room type is required.',
            'type.in' => 'The selected room type is invalid.',

            'capacity.required' => 'Room capacity is required.',
            'capacity.integer' => 'Room capacity must be a whole number.',
            'capacity.min' => 'Room capacity must be at least 1.',
            'capacity.max' => 'Room capacity cannot exceed 100.',

            'location.required' => 'Room location is required.',

            'status.required' => 'Room status is required.',
            'status.in' => 'The selected room status is invalid.',

            'description.max' => 'The description cannot exceed 1000 characters.',

            'facilities.array' => 'Facilities must be submitted as a list.',

            'facilities.max' => 'You may select up to five facilities.',

            'facilities.*.distinct' => 'The same facility cannot be selected more than once.',

            'facilities.*.in' => 'One or more selected facilities are invalid.',
        ];
    }
}
