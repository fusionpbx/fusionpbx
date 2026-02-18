<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('domain.{domainUuid}', function ($user, $domainUuid) {
    return $user->domain_uuid === $domainUuid;
});

Broadcast::channel('calls.{domainUuid}', function ($user, $domainUuid) {
    return $user->domain_uuid === $domainUuid;
});
