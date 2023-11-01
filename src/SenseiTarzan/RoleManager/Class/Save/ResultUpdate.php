<?php

namespace SenseiTarzan\RoleManager\Class\Save;

use SenseiTarzan\RoleManager\Class\Role\Role;

readonly class ResultUpdate
{

    public function __construct(public bool $online, public array|string|Role $data, public bool $updatePermission = false)
    {
    }

}