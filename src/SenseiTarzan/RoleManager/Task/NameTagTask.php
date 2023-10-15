<?php

namespace SenseiTarzan\RoleManager\Task;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use SenseiTarzan\RoleManager\Component\TextAttributeManager;
use SOFe\AwaitGenerator\Await;

class NameTagTask extends Task
{

    /**
     * @inheritDoc
     */
    public function onRun(): void
    {
        foreach (Server::getInstance()->getOnlinePlayers() as $player){
            if (!$player->isConnected()) continue;
            Await::g2c(TextAttributeManager::getInstance()->formatNameTag($player), function (string $format) use ($player) {
                $player->setNameTag($format);
            }, function () use ($player) {
                $player->setNameTag("Loading...");
            });
        }
    }
}