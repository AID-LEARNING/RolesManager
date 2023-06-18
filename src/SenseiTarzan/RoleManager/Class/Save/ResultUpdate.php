<?php

namespace SenseiTarzan\RoleManager\Class\Save;

class ResultUpdate
{

    public function __construct(public readonly bool $online, public readonly mixed $data, public readonly bool $updatePermission = false)
    {
    }

}