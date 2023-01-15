<?php

namespace SenseiTarzan\RoleManager\Event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use pocketmine\player\Player;
use SenseiTarzan\RoleManager\Class\Role\Role;

class EventChangeRole extends Event implements Cancellable
{
    use CancellableTrait;

    public function __construct(private Player|string $player, private Role $oldRole, private Role $newRole,)
    {
    }

    /**
     * @return Role
     */
    public function getOldRole(): Role
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

    /**
     * @return Player|string
     */
    public function getPlayer(): Player|string
    {
        return $this->player;
    }


}