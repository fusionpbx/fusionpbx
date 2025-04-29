<?php

namespace App\Repositories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;

class UserRepository
{
    protected $model;

    public function __construct(User $user)
    {
        $this->model = $user;
    }

    public function getAll()
    {
        return $this->model->all();
    }

    public function findById($id)
    {
        return $this->model->find($id);
    }

    public function create(array $userData)
    {
        if (App::hasDebugModeEnabled()) {
            Log::debug('[UserRepository][create] $userData: ' . print_r($userData, true));
        }
        
        return $this->model->create($userData);
    }

    public function update(User $user, array $userData)
    {
        return $user->update($userData);
    }


    public function delete(User $user)
    {
        return $user->delete();
    }


    public function syncGroups(User $user, array $groupIds)
    {
        $syncGroups = [];

        if (!empty($groupIds)) {
            $groupsDB = Group::whereIn("group_uuid", $groupIds)->pluck("group_name", "group_uuid");

            foreach ($groupIds as $group) {
                $syncGroups[$group] = [
                    "domain_uuid" => $user->domain_uuid,
                    "group_name" => $groupsDB[$group] ?? null,
                ];
            }
        }

        return $user->groups()->sync($syncGroups);
    }

    public function syncSettings(User $user, array $settings)
    {
        foreach ($settings as $setting_subcategory => $setting_value) {
            if ($setting_value) {
                $setting_name = match ($setting_subcategory) {
                    "language" => "code",
                    "time_zone" => "name",
                    default => "",
                };

                $user->userSettings()->updateOrCreate(
                    [
                        "user_uuid" => $user->user_uuid,
                        "user_setting_subcategory" => $setting_subcategory,
                    ],
                    [
                        "domain_uuid" => $user->domain_uuid,
                        "user_setting_category" => "domain",
                        "user_setting_name" => $setting_name,
                        "user_setting_value" => $setting_value,
                    ]
                );
            }
        }
    }

    public function handlePassword(array &$userData)
    {
        if (empty($userData["password"])) {
            unset($userData["password"]);
        }
    }
}