<?php

namespace SenseiTarzan\RoleManager\Class\Save;

use Generator;
use JsonException;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use SenseiTarzan\RoleManager\Class\Exception\SaveDataException;
use SenseiTarzan\RoleManager\Class\Role\RolePlayer;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SOFe\AwaitGenerator\Await;
use Symfony\Component\Filesystem\Path;

class JSONSave extends IDataSaveRoleManager
{

    private Config $config;

    public function __construct(string $dataFolder)
    {
        $this->config = new Config(Path::join($dataFolder, "data.json"), Config::JSON);
    }

    public function getName(): string
    {
        return "Json System";
    }


    protected function createPromiseLoadDataPlayer(Player|string $player): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($player) {
            Await::f2c(function () use ($player): Generator {
                if (!$this->config->exists($name = $player->getName(), true)) {
                    $rolePlayer = new RolePlayer($name, prefix: "", suffix: "", role: RoleManager::getInstance()->getDefaultRole()->getId(), subRoles: [], nameRoleCustom: null);
                    yield from $this->createPromiseSaveDataPlayer($player, $rolePlayer);
                    return $rolePlayer;
                }
                $infoPlayer = $this->config->get(strtolower($name));
                return new RolePlayer($name, $infoPlayer['prefix'] ?? "", $infoPlayer['suffix'] ?? "", $infoPlayer['role'] ?? RoleManager::getInstance()->getDefaultRole()->getId(), $infoPlayer['subRoles'] ?? [], $infoPlayer['nameRoleCustom'] ?? null, $infoPlayer['permissions'] ?? []);
            }, $resolve, $reject);
        });
    }

    protected function createPromiseSaveDataPlayer(Player|string $player, RolePlayer $rolePlayer): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($player, $rolePlayer) {
            try {
                $this->config->set($rolePlayer->getId(), $rolePlayer->jsonSerialize());
                $this->config->save();
                $resolve();
            } catch (JsonException) {
                $reject(new SaveDataException("Error save data player {$player->getName()}"));
            }
        });
    }


    public function createPromiseUpdateOffline(string $id, string $type, mixed $data): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($id, $type, $data) {
            try {
                if (!$this->config->exists($id, true)) {
                    $rolePlayer = new RolePlayer($id, prefix: "", suffix: "", role: RoleManager::getInstance()->getDefaultRole()->getId(), subRoles: [], nameRoleCustom: null);
                    $this->config->set(strtolower($id), ($rolePlayer)->jsonSerialize());
                    unset($rolePlayer);
                }
                $this->config->setNested($search = (strtolower($id) . "." . (match ($type) {
                        "addPermissions", "removePermissions", "setPermissions" => 'permissions',
                        "addSubRoles", "removeSubRoles", "setSubRoles" => 'subRoles',
                        default => $type
                    })), match ($type) {
                    "addPermissions", "addSubRoles" => array_merge($dataInSave = $this->config->getNested($search), array_filter((is_string($data) ? [$data] : $data), fn(string $value) => (($type !== "addSubRoles") || RoleManager::getInstance()->existRole($value)) && !in_array($value, $dataInSave))),
                    "removePermissions", "removeSubRoles" => array_values(array_diff($this->config->getNested($search), (is_string($data) ? [$data] : $data))),
                    "setPermissions", "setSubRoles" => is_string($data) ? [$data] : $data,
                    default => $data
                });
                $this->config->save();
                $resolve();
            } catch (JsonException) {
                $reject(new SaveDataException("Error save data player offline {$id}"));
            }
        });
    }

    public function createPromiseUpdateOnline(string $id, string $type, mixed $data): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($id, $type, $data) {
            try {
                $this->config->setNested($id . ".$type", $data);
                $this->config->save();
                $resolve();
            } catch (JsonException) {
                $reject(new SaveDataException("Error save data player {$id}"));
            }
        });
    }
}