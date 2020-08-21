<?php

namespace Api\Users\Events;

use Api\Users\Models\User;
use Infrastructure\Events\Event;

class UserWasDeleted extends Event
{
    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
