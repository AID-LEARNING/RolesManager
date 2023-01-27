<?php

namespace SenseiTarzan\RoleManager\Event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;
use SenseiTarzan\RoleManager\Class\Role\Role;

class EventChangePrefix extends PlayerEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(Player $player, private string $oldPrefix, private string $newPrefix)
    {
        $this->player = $player;
    }

    /**
     * @return string
     */
    public function getOldPrefix(): string
    {
        return $this->oldPrefix;
    }

    /**
     * @return string
     */
    public function getNewPrefix(): string
    {
        return $this->newPrefix;
    }

    /**
     * @param string $newPrefix
     */
    public function setNewPrefix(string $newPrefix): void
    {
        $this->newPrefix = $newPrefix;
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

}