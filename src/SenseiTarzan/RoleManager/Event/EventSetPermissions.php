<?php

namespace SenseiTarzan\RoleManager\Event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class EventSetPermissions extends PlayerEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(Player $player, private array $permissions)
    {
        $this->player = $player;
    }

    /**
     * @return array
     */
    public function getPermissions(): array
    {
        return array_values($this->permissions);
    }

    /**
     * @param array $permissions
     */
    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

}