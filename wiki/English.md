## English
Configuration of the ``plugin_data/RoleManager/config.yml``

| Key       | Description                                        | valuer attendu                                                                       |
|-----------|----------------------------------------------------|--------------------------------------------------------------------------------------|
| data-type | Allows you to define the player's save data system | ``json`` is default <br/>  ``yaml``  <br/> ``yml``<br/>``custom``  for experimenters |

# Creating a role

| Key           | Description                                                                                                                 | type             | obligatoire             |
|---------------|-----------------------------------------------------------------------------------------------------------------------------|------------------|-------------------------|
| name          | The name of the role that will be displayed any initialized colors will be deleted by the plugins passed by the chatformat. | string           | **yes**                 |
| changeName    | allows the player to change the name of his role without changing his role                                                  | boolean          | **no** default is false |
| default       | allows to know if the role is the default one for the player for the first connection.                                      | boolean          | **no** default is false |
| priority      | Allows to structure the role with priorities.                                                                               | integer or float | **yes**                 |
| chatFormat    | this is the formatting on the role chat.                                                                                    | string           | **yes**                 |
| nameTagFormat | this is the formatting for the role nametag.                                                                                | string           | **yes**                 |
| heritages     | how does it work to recover the permissions of the role and its heritage.                                                   | string[]         | **yes**                 |
| permissions   | this is the party to manage the permissiosn of the role.                                                                    | string[]         | **yes**                 |

your file must be in ``plugin_data/RoleManager/roles`` because otherwise the roles will not be initialized,
and you must create your roles in the .yml file.

```yaml

name: Exemple 
default: false 
priority: 1 
chatFormat: "§7[§r{&prefix}§7]§r[§6{&role}§r]{&playerName}[{&suffix}] : {&message}" 
nameTagFormat: "[§6{&role}§r]\n{&playerName}"
heritages: []
permissions:
  - pocketmine.command.me
  - pocketmine.command.list
```

# TextAttributeManager
## Create a ChatAttribute
#### This allows you to create your own ``{&...}`` for the chatFormat
```php
use SenseiTarzan\RoleManager\Component\TextAttributeManager;  //import
use SenseiTarzan\RoleManager\Class\Text\ChatAttribute;  //import
TextAttributeManager::getInstance()->registerChatAttribute(new ChatAttribute("playerXpLvl", function (Player $player, string $message /*this variable will not be of any use to you*/, string $search, string &$format): void {
            $format = str_replace($search, $player->getXpManager()->getXpLevel(), $format);
})));
```
This will create a ``{&playerXpLvl}`` and you can add it in the chatFormat

## Create a NameTagAttribute
#### This allows you to create your own ``{&...}`` for the NameTagFormat
```php
use SenseiTarzan\RoleManager\Component\TextAttributeManager; //import
use SenseiTarzan\RoleManager\Class\Text\NameTagAttribute; //import
TextAttributeManager::getInstance()->registerNameTagAttribute(new NameTagAttribute("playerXpLvl", function (Player $player, string $search, string &$format): void {
            $format = str_replace($search, $player->getXpManager()->getXpLevel(), $format);
})));
```
This will create a ``{&playerXpLvl}`` and you can add it in the nameTagFormat

# Creation of your own player data backup system
### [⚠️⚠️] This is an example I'm not looking for optimization but shows how to use, and you must experiment to do it
```php
<?php

namespace xxxx\xxxx;

use pocketmine\player\Player;
use pocketmine\utils\Config;
use SenseiTarzan\RoleManager\Class\Role\RolePlayer;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Class\Save\IDataSave;
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
use Symfony\Component\Filesystem\Path;
use SenseiTarzan\RoleManager\Class\Exception\SaveDataException;

class JSONSeparatedSave extends IDataSaveRoleManager
{

    /** @var Config[] */
    private array $playersConfig = [];

    public function __construct(string $dataFolder)
    {
        $this->dataFolder = $dataFolder;
        @mkdir(Path::join($this->dataFolder, "datas"));
    }

    public function getName(): string
    {
        return "Json System Separated";
    }

    /**
    * This is a promise to load a player into cache and provide a SaveDataException error.
    * @param Player|string $player
    * @return Generator<RolePlayer>
    * @throws SaveDataException
    */
    protected function createPromiseLoadDataPlayer(Player|string $player): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($player) {
            Await::f2c(function () use ($player): Generator {
                if (!file_exists($path = Path::join($this->dataFolder, "datas", strtolower($name = $player->getName())))){
                    $this->playersConfig[$rolePlayer->getId()] = new Config($path, Config::YAML);
                    $rolePlayer = new RolePlayer($name, prefix: "", suffix: "", role: RoleManager::getInstance()->getDefaultRole()->getId());
                    yield from $this->createPromiseSaveDataPlayer($player, $rolePlayer);
                    return $rolePlayer;
                }
                $this->playersConfig[$rolePlayer->getId()] = new Config($path, Config::YAML);
                $infoPlayer = $this->playersConfig[$rolePlayer->getId()];
                return new RolePlayer($name, $infoPlayer->get('prefix', ""), $infoPlayer->get('suffix', ""), $infoPlayer->get('role', RoleManager::getInstance()->getDefaultRole()->getId()), $infoPlayer->get('subRoles', []), $infoPlayer->get('nameRoleCustom'], null), $infoPlayer->get('permissions', ""));
            }, $resolve, function (\Throwable $throwable) use ($reject){
                 unset($this->playersConfig[strtolower($player->getName())]);
                 $reject($throwable);
            });
        });
    }

    /**
    * This is a promise to create data for the first time.
    * @param Player|string $player
    * @param RolePlayer $rolePlayer
    * @return Generator
    * @throws SaveDataException
    */
    protected function createPromiseSaveDataPlayer(Player|string $player, RolePlayer $rolePlayer): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($player, $rolePlayer) {
            if(!isset($this->playersConfig[$rolePlayer->getId()]))
            {
                $reject(new SaveDataException("Error no exists data {$rolePlayer->getId()}"));
                return ;
            }
            try {
                $infoPlayer = $this->playersConfig[$rolePlayer->getId()];
                $infoPlayer->setAll( $rolePlayer->jsonSerialize());
                $infoPlayer->save();
                $resolve();
            } catch (JsonException) {
                $reject(new SaveDataException("Error saving data for player {$player->getName()}"));
            }
        });
    }

    /**
    * This is a promise to update data for offline players.
    * @param string $id
    * @param string $type
    * @param mixed $data
    * @return Generator
    * @throws SaveDataException
    */
    public function createPromiseUpdateOffline(string $id, string $type, mixed $data): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($id, $type, $data) {
           
            try {
                if (!file_exists($path = Path::join($this->dataFolder, "datas", $id))){
                    $config = new Config($path, Config::YAML);
                    $rolePlayer = new RolePlayer($id, prefix: "", suffix: "", role: RoleManager::getInstance()->getDefaultRole()->getId(), subRoles: [], nameRoleCustom: null);
                    $config->setAll($rolePlayer->jsonSerialize());
                    $config->save();
                    unset($rolePlayer, $config);
                    $resolve();
                    return ;
                }
                $infoPlayer = $this->playersConfig[$id];
                $infoPlayer->setNested($search = (match ($type) {
                        "addPermissions", "removePermissions", "setPermissions" => 'permissions',
                        "addSubRoles", "removeSubRoles", "setSubRoles" => 'subRoles',
                        default => $type
                    })), match ($type) {
                    "addPermissions", "addSubRoles" => array_merge($dataInSave = $infoPlayer->get($search, []), array_filter((is_string($data) ? [$data] : $data), fn(string $value) => (($type !== "addSubRoles") || RoleManager::getInstance()->existRole($value)) && !in_array($value, $dataInSave))),
                    "removePermissions", "removeSubRoles" => array_values(array_diff($infoPlayer->get($search, []), (is_string($data) ? [$data] : $data))),
                    "setPermissions", "setSubRoles" => is_string($data) ? [$data] : $data,
                    default => $data
                });
                $infoPlayer->save();
                $resolve();
            } catch (JsonException) {
                $reject(new SaveDataException("Error saving data for offline player {$id}"));
            }
        });
    }
    
    /**
    * This is a promise to update data for online players.
    * @param string $id
    * @param string $type
    * @param mixed $data
    * @return Generator
    * @throws SaveDataException
    */
    public function createPromiseUpdateOnline(string $id, string $type, mixed $data): Generator
    {
    
    
        return Await::promise(function ($resolve, $reject) use ($id, $type, $data) {
            if(!isset($this->playersConfig[$id]))
            {
                $reject(new SaveDataException("Error no exists data $id"));
                return ;
            }
            try {
                $infoPlayer = $this->playersConfig[$id];
                $infoPlayer->set($type, $data);
                $infoPlayer->save();
                $resolve();
            } catch (JsonException) {
                $reject(new SaveDataException("Error saving data for player {$id}"));
            }
        });
    }
}

```


# Retrieve the role in relation to the id

````php
use SenseiTarzan\RoleManager\Component\RoleManager;
RoleManager::getInstance()->getRole("id_role" or "name_role")
````

# Recover the role of the player [Online]
````php
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
RolePlayerManager::getInstance()->getPlayer(Player or string)->getRole();
````
# Get the role name or if you have a custom role name with who will play [Online]
````php
use SenseiTarzanRoleManagerComponentRolePlayerManager;
RolePlayerManager::getInstance()->getPlayer(Player or string)->getRoleName();
````

## Set a role for a player

```php
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Class\Save\ResultUpdate;
use SOFe\AwaitGenerator\Await;

Await::g2c(RoleManager::getInstance()->setRolePlayer(Player or string, string or Role), function (ResultUpdate $resultUpdate) {
    // The action you want to perform when the role has been set
    }, 
    [
        SaveDataException::class => function () {},
        CancelEventException::class => function () {},
        InvalidArgumentException::class => function () {}
    ]
);

RoleManager::getInstance()->setRolePlayer(Player|string, Role|string);
```

## Set a custom role name for a player and for roles with the ``changeName`` feature enabled [Online].

```php
use SenseiTarzan\RoleManager\Component\RoleManager;
use SOFe\AwaitGenerator\Await;
Await::g2c(RoleManager::getInstance()->setNameRoleCustom(Player, string), function (string $nameCustom) {
    // The action you want to perform when the roleNameCustom has been set
    },
    [
        RoleNoNameCustomException::class => function () {},
        CancelEventException::class => function () {},
        RoleFilteredNameCustomException::class =>  function () {},
        SaveDataException::class => function () {}
    ]
);
```

## Set a prefix for a player [Online]

```php
use SenseiTarzan\RoleManager\Component\RoleManager;
use SOFe\AwaitGenerator\Await;
Await::g2c(RoleManager::getInstance()->setPrefix(Player, string), function (string $prefix) {
    // The action you want to perform when the prefix has been set
    }, 
    [
        SaveDataException::class => function () {},
        CancelEventException::class => function () {}
    ]
);
```

## Set a suffix for a player [Online]

```php
use SenseiTarzan\RoleManager\Component\RoleManager;
use SOFe\AwaitGenerator\Await;
Await::g2c(RoleManager::getInstance()->setSuffix(Player, string), function (string $prefix) {
    // The action you want to perform when the suffix has been set
}, 
[
    SaveDataException::class => function () {},
    CancelEventException::class => function () {}
);
```

## Define permission(s) for a player

```php
use SenseiTarzan\RoleManager\Component\RoleManager;
use SOFe\AwaitGenerator\Await;

Await::g2c(RoleManager::getInstance()->setPermissionPlayer(Player or string, array<string> or string), function (ResultUpdate $resultUpdate) use ($sender, $target) {
    // The action you want to perform when the permission set has been completed
    },    
    [
        SaveDataException::class => function () {},
        CancelEventException::class => function () {}
    ]
);
```

## Add permission(s) to a player

```php
use SenseiTarzan\RoleManager\Component\RoleManager;
use SOFe\AwaitGenerator\Await;
Await::g2c(RoleManager::getInstance()->addPermissionPlayer(Player or string, array<string> or string), function (ResultUpdate $resultUpdate) use ($sender, $target, $perm) {
    // The action you want to perform when permission addition has been completed
    },
    [
        SaveDataException::class => function () {},
        CancelEventException::class => function () {}
    ]
);
```

## Remove permission(s) from a player

```php
use SenseiTarzan\RoleManager\Component\RoleManager;
use SOFe\AwaitGenerator\Await;

Await::g2c(RoleManager::getInstance()->removePermissionPlayer(Player or string, array<string> or string), function (ResultUpdate $resultUpdate) use ($sender, $target, $perm) {
    // The action you want to perform when permission removal has been completed
    },
    [
        SaveDataException::class => function () {},
        CancelEventException::class => function () {}
    ]
);
```