## English
Configuration of the ``plugin_data/RoleManager/config.yml``

| Key       | Description                                        | valuer attendu                                                                       |
|-----------|----------------------------------------------------|--------------------------------------------------------------------------------------|
| data-type | Allows you to define the player's save data system | ``json`` is default <br/>  ``yaml``  <br/> ``yml``<br/>``custom``  for experimenters |

# Creating a role

| Key           | Description                                                                                                                                                                                                                                                                                | type             | obligatoire |
|---------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------------|-------------|
| name          | The name of the role that will be displayed any initialized colors will be deleted by the plugins passed by the chatformat.                                                                                                                                                                | string           | **yes**     |
| changeName    | allows the player to change the name of his role without changing his role                                                                                                                                                                                                                 | boolean          | **no** default is false    |
| default       | allows to know if the role is the default one for the player for the first connection.                                                                                                                                                                                                     | boolean          | **no** default is false    |
| priority      | Allows to structure the role with priorities.                                                                                                                                                                                                                                              | integer or float | **yes**     |
| chatFormat    | this is the formatting on the role chat.                                                                                                                                                                                                                                                   | string           | **yes**     |
| nameTagFormat | this is the formatting for the role nametag.                                                                                                                                                                                                                                               | string           | **yes**     |
| heritages     | how does it work to recover the permissions of the role and its heritage. <br/> You have to put the id of the role and for that it is all simple you have to put the name in lower case and replace the spaces by underscore ( _ ).  <br/> Example: <br/> Wizard Mage of comes wizard_mage | array            | **yes**     |
| permissions   | this is the party to manage the permissiosn of the role.                                                                                                                                                                                                                                   | array            | **yes**     |

your file must be in ``plugin_data/RoleManager/roles`` because otherwise the roles will not be initialized
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
## Default ChatAttribute

| Key               | Description                      | 
|-------------------|----------------------------------|
| ``{&playerName}`` | give the name player             | 
| ``{&role}``       | give the role player             | 
| ``{&prefix}``     | give the prefix player           |
| ``{&suffix}``     | give the suffix player           |
| ``{&factionName}``| give the faction name player     |
| ``{&factionRank}``| give the rank player in faction  |
| ``{&message}``   | give the  message player          |
| \n                | Allows you to return to the line |

## NameTagAttribute par défaut

| Key               | Description                      | 
|-------------------|----------------------------------|
| ``{&playerName}`` | give the name player             | 
| ``{&role}``       | give the role player             | 
| ``{&prefix}``     | give the prefix player           |
| ``{&suffix}``     | give the suffix player           |
| ``{&factionName}``| give the faction name player     |
| ``{&factionRank}``| give the rank player in faction  |
| \n                | Allows you to return to the line |
## Create a ChatAttribute
#### This allows you to create your own ``{&...}`` for the chatFormat
```php
TextAttributeManager::getInstance()->registerChatAttribute(new ChatAttribute("playerXpLvl", function (Player $player, string $message /*this variable will not be of any use to you*/, string $search, string &$format): void {
            $format = str_replace($search, $player->getXpManager()->getXpLevel(), $format);
})));
```
This will create a ``{&playerXpLvl}`` and you can add it in the chatFormat

## Create a NameTagAttribute
#### Cela permet de créer votre propre ``{&...}`` pour le nameTagFormat
```php
TextAttributeManager::getInstance()->registerNameTagAttribute(new NameTagAttribute("playerXpLvl", function (Player $player, string $search, string &$format): void {
            $format = str_replace($search, $player->getXpManager()->getXpLevel(), $format);
})));
```
This will create a ``{&playerXpLvl}`` and you can add it in the nameTagFormat

# Creation of your own player data backup system
### [⚠️⚠️] This is an example I'm not looking for optimization but shows how to use, and you must experiment to do it
```php
<?php

namespace xxxx/xxxx;

use pocketmine\player\Player;
use pocketmine\utils\Config;
use SenseiTarzan\RoleManager\Class\Role\RolePlayer;
use SenseiTarzan\RoleManager\Component\RoleManager;
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
     * @param string $type 'role' | 'suffix' | 'prefix' | 'permissions'
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
     * @param string $type  'role' |  'addPermission' | 'removePermission' | 'setPermission'
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
            "addPermission" => $this->config->get($search) + (is_string($data) ? [$data] : $data),
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
RoleManager::getInstance()->getRole("id_role")
````

# Recover the role of the player [Online]
````php
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
RolePlayerManager::getInstance()->getPlayer(Player|string)->getRole();
````

# Put a role to a player
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
RoleManager::getInstance()->setRolePlayer(Player|string, Role);
````
# Put a prefix to a player [Online]
````php
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
RolePlayerManager::getInstance()->getPlayer(Player|string)->setPrefix("your prefix");
````

# Put a suffix to a player [Online]
````php
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
RolePlayerManager::getInstance()->getPlayer(Player|string)->setSuffix("your suffix");
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

## Français
Configuration de la ``plugin_data/RoleManager/config.yml``

| Clé       | Description                                                   | valuer attendu                                                                                       |
|-----------|---------------------------------------------------------------|------------------------------------------------------------------------------------------------------|
| data-type | Permet de définir le system de donnée de sauvegarde du joueur | ``json`` is default <br/>  ``yaml``  <br/> ``yml``<br/> ``custom``  pour les personnes experimenters |

# Creation d'un role

| Clé             | Description                                                                                                                                                                                                                                                                                      | type attendu             | obligatoire   |
|-----------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------------------|---------------|
| name            | Le nom du role qui sera afficher toute colors initialiser sera supprime par le plugins passe par le chatformat.                                                                                                                                                                                  | texte                    | **oui**      
| changeName | permet au joueur de changer le nom de son rôle sans changer son rôle | true ou false   |**non** false par défaut      |
| default | permet de savoir si le role et celui mis par défaut au joueur pour le premier connection. | true ou false            | **non** false par défaut      |
| priority        | Permet de structure le role avec des priority en nombre. | nombre entier ou decimal | **oui**       |
| chatFormat      | ceci est le formatage sur le chat du role. | texte                    | **oui**       |
| nameTagFormat   | ceci est le formatage pour le nametag du role. | texte                    | **oui**       |
| heritages       | comment ça fonctionne cela recuperer les permissions du role et de c'est heritage.<br/> il faut mettre l'id du role et pour cela, c'est tout simple vous devais mettre le nom en minuscule et replace les espaces par des underscore ( _ ).<br/> Exemple: <br/>Sorcier Mage de vient sorcier_mage | array                    | **oui**       |
| permissions | ce si est la partie pour gere les permissions du role. | array                    | **oui**       |

Votre fichier doit être dans ``plugin_data/RoleManager/roles`` car sinon les role ne seront pas initialiser
et vous devais faire vos roles en .yml

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
## ChatAttribute par défaut

| Clé               | Description                   | 
|-------------------|-------------------------------|
| ``{&playerName}`` | donne le nom du joueur        | 
| ``{&role}``       | donne le role du joueur       | 
| ``{&prefix}``     | donne le prefix du jouer      |
| ``{&suffix}``     | donne le suffix du jouer      |
| ``{&factionName}``| donne le nom de la faction du joueur     |
| ``{&factionRank}``| donne le rang du joueur dans la faction  |
| ``{&message}``    | donne le message du joueur    |
| \n                | Permet de retourne a la ligne |

## NameTagAttribute par défaut

| Clé               | Description                   | 
|-------------------|-------------------------------|
| ``{&playerName}`` | donne le nom du joueur        | 
| ``{&role}``       | donne le role du joueur       | 
| ``{&prefix}``     | donne le prefix du jouer      |
| ``{&suffix}``     | donne le suffix du jouer      |
| ``{&factionName}``| donne le nom de la faction du joueur     |
| ``{&factionRank}``| donne le rang du joueur dans la faction  |
| \n                | Permet de retourne a la ligne |
## Creation de un ChatAttribut
#### Cela permet de créer votre propre ``{&...}`` pour le chatFormat
```php
TextAttributeManager::getInstance()->registerChatAttribute(new ChatAttribute("playerXpLvl", function (Player $player, string $message /*cette variable ne vous sera d'aucune utilité*/, string $search, string &$format): void {
            $format = str_replace($search, $player->getXpManager()->getXpLevel(), $format);
})));
```
cela va creer un ``{&playerXpLvl}`` est vous pourrez l'ajouter dans le chatFormat

## Creation de un NameTagAttribute
#### Cela permet de créer votre propre ``{&...}`` pour le nameTagFormat
```php
TextAttributeManager::getInstance()->registerNameTagAttribute(new NameTagAttribute("playerXpLvl", function (Player $player, string $search, string &$format): void {
            $format = str_replace($search, $player->getXpManager()->getXpLevel(), $format);
})));
```
Cela va creer un ``{&playerXpLvl}`` est vous pourrez l'ajouter dans le nameTagFormat

# Creation de votre propre system de sauvegarde de données du joueur
### [⚠️⚠️] Ceci est un exemple je ne cherche pas l'optimisation, mais montre comment utiliser et vous devez entre experimenter pour le faire
```php
<?php

namespace SenseiTarzan\RoleManager\Class\Save;

use pocketmine\player\Player;
use pocketmine\utils\Config;
use SenseiTarzan\RoleManager\Class\Role\RolePlayer;
use SenseiTarzan\RoleManager\Component\RoleManager;
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
     * @param string $type 'role' | 'suffix' | 'prefix' | 'permissions'
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
            "addPermission" => $this->config->get($search) + (is_string($data) ? [$data] : $data),
            "removePermission" => array_diff($this->config->get($search), (is_string($data) ? [$data] : $data)),
            "setPermission" => is_string($data) ? [$data] : $data,
            default => $data
        });
        $infoPlayer->save();
        unset($infoPlayer);
    }
}
```


# Récupérer le role par rapport a l'id

````php
use SenseiTarzan\RoleManager\Component\RoleManager;
RoleManager::getInstance()->getRole("id_role")
````

# Récupérer le role du jouer [En ligne]
````php
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
RolePlayerManager::getInstance()->getPlayer(Player|string)->getRole();
````

# Mettre un role à un joueur
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
RoleManager::getInstance()->setRolePlayer(Player|string, Role);
````
# Mettre un prefix à un joueur [En Ligne]
````php
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
RolePlayerManager::getInstance()->getPlayer(Player|string)->setPrefix("your prefix");
````

# Mettre un suffix à un joueur [En Ligne]
````php
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
RolePlayerManager::getInstance()->getPlayer(Player|string)->setSuffix("your suffix");
````

# Définir un/des permissions o un joueur
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
RoleManager::getInstance()->setPermissionPlayer(Player or string, array or string);
````

# Ajouter un/des permission(s) à un joueur
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
RoleManager::getInstance()->addPermissions(Player or string, array or string);
````

# Enlever un/des permission(s) à un joueur
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
RoleManager::getInstance()->removePermissionPlayer(Player or string, array or string);
````


