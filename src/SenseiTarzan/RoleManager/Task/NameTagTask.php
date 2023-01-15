<?php

namespace SenseiTarzan\RoleManager\Task;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use SenseiTarzan\RoleManager\Component\TextAttributeManager;

class NameTagTask extends Task
{

    /**
     * @inheritDoc
     */
    public function onRun(): void
    {
        foreach (Server::getInstance()->getOnlinePlayers() as $player){
            if (!$player->isConnected()) continue;
            $format = null;
            TextAttributeManager::getInstance()->formatNameTag($player, $format);
            if ($format === null) continue;
            $player->setNameTag($format);
        }
    }
}