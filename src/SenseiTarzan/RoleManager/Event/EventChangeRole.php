<?php

namespace SenseiTarzan\RoleManager\Event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;
use SenseiTarzan\RoleManager\Class\Role\Role;

    class EventChangeRole extends PlayerEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct( Player $player, private Role $oldRole, private Role $newRole)
    {
        $this->player = $player;
    }

    /**
     * @return ?Role
     */
    public function getOldRole(): ?Role
    {
        return $this->oldRole;
    }

    /**
     * @return Role
     */
    public function getNewRole(): Role
    {
        return $this->newRole;
    }

    /**
     * @param Role $newRole
     */
    public function setNewRole(Role $newRole): void
    {
        $this->newRole = $newRole;
    }



}