<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Support\Facades\Hash;

class FusionPBXUserProvider extends EloquentUserProvider
{
    public function validateCredentials(UserContract $user, array $credentials): bool
    {
        $plain = $credentials['password'] ?? '';
        $apiKey = $credentials['api_key'] ?? '';

        if (isset($apiKey) && strlen($apiKey) > 30 && $apiKey === $user->api_key) {
            return true;
        }
        
        $storedHash = $user->getAuthPassword();
        
        if (substr($storedHash, 0, 1) === '$') {
            if (!empty($plain) && Hash::check($plain, $storedHash)) {
                return true;
            }
        } else {
            $salt = $user->salt ?? '';
            
            if (md5($salt . $plain) === $storedHash) {
                $this->updatePasswordHash($user, $plain);
                return true;
            }
        }
        
        return false;
    }
    
    protected function updatePasswordHash(User $user, string $plain): void
    {
        $newHash = Hash::make($plain);
        
        $user->forceFill([
            'password' => $newHash,
        ])->save();
    }
}