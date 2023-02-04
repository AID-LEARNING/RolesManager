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

class JSONSeparedSave implements IDataSave
{

/**
* @var Config[] 
 */    
    private array $playersConfig = [];

    public function __construct(string $dataFolder)
    {
        $this->dataFolder = $dataFolder;
        @mkdir(Path::join($this->dataFolder, "datas",));
    }

    public function getName(): string
    {
        return "Json System";
    }

    public function loadDataPlayer(Player $player): void
    {
        if (!file_exists($path = Path::join($this->dataFolder, "datas", strtolower($name = $player->getName())))){
            $infoPlayer = new Config($path, Config::YAML);
            RolePlayerManager::getInstance()->loadPlayer($player, $rolePlayer = new RolePlayer($name, prefix: "", suffix: "", role: RoleManager::getInstance()->getDefaultRole()->getId()));
            $infoPlayer->setAll($rolePlayer->jsonSerialize());
            $infoPlayer->save();
            $this->playersConfig[$rolePlayer->getId()] = $infoPlayer;
            return;
        }
        $infoPlayer = new Config($path, Config::YAML);
        RolePlayerManager::getInstance()->loadPlayer($player, new RolePlayer($name, $infoPlayer->get('prefix', ""), $infoPlayer->get('suffix', ""), $infoPlayer->get('role', RoleManager::getInstance()->getDefaultRole()->getId()), $infoPlayer->get('permissions', [])));
        $this->playersConfig[$rolePlayer->getId()] = $infoPlayer;
    }

    /**
     * @param string $id
     * @param string $type 'role' | 'suffix' | 'prefix' | 'permissions' | 'nameRoleCustom'
     * @param mixed $data
     * @return void
     * @throws JsonException
     */
    public function updateOnline(string $id, string $type, mixed $data): void
    {
    
        ($config = $this->playersConfig[$id])->set($type, $data);
        $config->save();
    }


    /**
     * @param string $id
     * @param string $type  'role' | 'suffix' | 'prefix' | 'addPermission' | 'removePermission' | 'setPermission'
     * @param mixed $data
     * @return void
     * @throws JsonException
     */
    public function updateOffline(string $id, string $type, mixed $data): void
    {

        $infoPlayer = new Config($path = Path::join($this->dataFolder, "datas", strtolower($name = $player->getName()))), Config::YAML);

        if (!file_exists($path){
            $rolePlayer = new RolePlayer($id, prefix: "", suffix: "", role: RoleManager::getInstance()->getDefaultRole()->getId());
            $infoPlayer->set($rolePlayer->getId(), $rolePlayer->jsonSerialize());
        }
        $infoPlayer->set($search = match ($type) {
         "addPermission", "removePermission", "setPermission" => 'permissions',
         default => $type
        }, match ($type) {
            "addPermission" => array_merge($this->config->get($search),(is_string($data) ? [$data] : $data)),
            "removePermission" => array_diff($this->config->get($search), (is_string($data) ? [$data] : $data)),
            "setPermission" => is_string($data) ? [$data] : $data,
            default => $data
        });
        $infoPlayer->save();
        unset($infoPlayer);
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
RolePlayerManager::getInstance()->getPlayer(Player|string)->getRole();
````
# Get the role name or if you have a custom role name with who will play [Online]
````php
use SenseiTarzanRoleManagerComponentRolePlayerManager;
RolePlayerManager::getInstance()->getPlayer(Player|string)->getRoleName();
````

# Put a role to a player
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
RoleManager::getInstance()->setRolePlayer(Player|string, Role|string);
````
# Put a prefix to a player [Online]
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
RoleManager::getInstance()->setPrefix(Player, "your prefix");
````

# Put a role name custom to a player and for roles who have ``changeName`` activate [Online]
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
RoleManager::getInstance()->setNameRoleCustom(Player, "your name custom");
````
# Put a suffix to a player [Online]
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
RoleManager::getInstance()->setSuffix(Player, "your suffix");
````

# Define permissions o a player
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
RoleManager::getInstance()->setPermissionPlayer(Player or string, array or string);
````

# Add permission(s) to a player
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
RoleManager::getInstance()->addPermissions(Player or string, array or string);
````

# Remove permission(s) from a player
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
RoleManager::getInstance()->removePermissionPlayer(Player or string, array or string);
````
