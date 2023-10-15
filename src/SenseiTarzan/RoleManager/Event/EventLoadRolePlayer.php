<?php

namespace SenseiTarzan\RoleManager\Event;

use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;
use SenseiTarzan\RoleManager\Class\Role\RolePlayer;

class EventLoadRolePlayer extends PlayerEvent
{

    public function __construct(Player $player, protected RolePlayer $rolePlayer)
    {
        $this->player = $player;
    }

    /**
     * @return RolePlayer
     */
    public function getRolePlayer(): RolePlayer
    {
        return $this->rolePlayer;
    }

}