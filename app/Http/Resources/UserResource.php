<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'avatar' => $this->avatar,
            'sex' => $this->sex,
            'sex_format' => $this->sex_format,
            'age' => $this->age,
            'birthday' => $this->birthday,
            'description' => $this->description,
            'phone' => $this->phone,
            'location' => $this->location,
            'address' => $this->address,
            'organization_name' => $this->organization_name,
            'recipient_type' => $this->recipient_type,
            'donor_type' => $this->donor_type,
            'notes' => $this->notes,

            // Extract role names from the roles relationship
            'roles' => $this->role ? [$this->role] : [], // Convert single string to an array


            // Extract permission names using Laravel's helper method
            'permissions' => $this->getAllPermissions()->pluck('name'),
        ];
    }
}
