<?php

namespace SenseiTarzan\RoleManager\Class\Save;

use Exception;
use Generator;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SenseiTarzan\DataBase\Class\IDataSave;
use SenseiTarzan\RoleManager\Class\Role\RolePlayer;
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
use SenseiTarzan\RoleManager\Event\EventLoadRolePlayer;
use SOFe\AwaitGenerator\Await;
use Throwable;

abstract class IDataSaveRoleManager implements IDataSave
{

    abstract protected function createPromiseLoadDataPlayer(Player|string $player): Generator;


    final public function loadDataPlayer(Player|string $player): void
    {
        assert($player instanceof Player);
        Await::g2c($this->createPromiseLoadDataPlayer($player), function (RolePlayer $rolePlayer) use ($player) {
            RolePlayerManager::getInstance()->loadPlayer($player, $rolePlayer);
            $event = new EventLoadRolePlayer($player, $rolePlayer);
            $event->call();
        }, function (Throwable $exception) use ($player) {
            $player->kick(TextFormat::DARK_RED . "Error: " . $exception->getMessage());
        });
    }

    abstract protected function createPromiseSaveDataPlayer(Player|string $player, RolePlayer $rolePlayer): Generator;
    abstract public function createPromiseUpdateOnline(string $id, string $type, mixed $data): Generator;

    abstract public function createPromiseUpdateOffline(string $id, string $type, mixed $data): Generator;

    final public function updateOnline(string $id, string $type, mixed $data): Generator
    {
        return $this->createPromiseUpdateOnline(mb_strtolower($id), $type, $data);
    }

    final public function updateOffline(string $id, string $type, mixed $data): Generator
    {
        return $this->createPromiseUpdateOffline(mb_strtolower($id), $type, $data);
    }
}