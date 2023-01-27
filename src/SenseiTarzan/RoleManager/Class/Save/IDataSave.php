<?php

namespace SenseiTarzan\RoleManager\Class\Save;

use pocketmine\player\Player;
use SenseiTarzan\RoleManager\Class\Role\Role;
use SenseiTarzan\RoleManager\Class\Role\RolePlayer;

interface IDataSave
{

     public function getName(): string;
     public function loadDataPlayer(Player $player): void;


    /**
     * @param string $id
     * @param string $type 'role' | 'suffix' | 'prefix' | 'permissions'
     * @param mixed $data
     * @return void
     */
    public function updateOnline(string $id, string $type, mixed $data): void;

    /**
     * @param string $id
     * @param string $type
     * @param mixed $data
     * @return void
     */
    public function updateOffline(string $id, string $type, mixed $data): void;

}