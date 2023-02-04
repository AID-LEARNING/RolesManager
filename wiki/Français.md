
## Français
Configuration de la ``plugin_data/RoleManager/config.yml``

| Clé       | Description                                                   | valuer attendu                                                                                       |
|-----------|---------------------------------------------------------------|------------------------------------------------------------------------------------------------------|
| data-type | Permet de définir le system de donnée de sauvegarde du joueur | ``json`` is default <br/>  ``yaml``  <br/> ``yml``<br/> ``custom``  pour les personnes experimenters |

# Creation d'un role

| Clé           | Description                                                                                                     | type attendu             | obligatoire              |
|---------------|-----------------------------------------------------------------------------------------------------------------|--------------------------|--------------------------|
| name          | Le nom du role qui sera afficher toute colors initialiser sera supprime par le plugins passe par le chatformat. | texte                    | **oui**                  |
| changeName    | permet au joueur de changer le nom de son rôle sans changer son rôle                                            | true ou false            | **non** false par défaut |
| default       | permet de savoir si le role et celui mis par défaut au joueur pour le premier connection.                       | true ou false            | **non** false par défaut |
| priority      | Permet de structure le role avec des priority en nombre.                                                        | nombre entier ou decimal | **oui**                  |
| chatFormat    | ceci est le formatage sur le chat du role.                                                                      | texte                    | **oui**                  |
| nameTagFormat | ceci est le formatage pour le nametag du role.                                                                  | texte                    | **oui**                  |
| heritages     | comment ça fonctionne cela recuperer les permissions du role et de c'est heritage.                              | texte[]                  | **oui**                  |
| permissions   | ce si est la partie pour gere les permissions du role.                                                          | texte[]                  | **oui**                  |

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
## Creation de un ChatAttribut
#### Cela permet de créer votre propre ``{&...}`` pour le chatFormat
```php
use SenseiTarzan\RoleManager\Component\TextAttributeManager;  //import
use SenseiTarzan\RoleManager\Class\Text\ChatAttribute;  //import
TextAttributeManager::getInstance()->registerChatAttribute(new ChatAttribute("playerXpLvl", function (Player $player, string $message /*cette variable ne vous sera d'aucune utilité*/, string $search, string &$format): void {
            $format = str_replace($search, $player->getXpManager()->getXpLevel(), $format);
})));
```
cela va creer un ``{&playerXpLvl}`` est vous pourrez l'ajouter dans le chatFormat

## Creation de un NameTagAttribute
#### Cela permet de créer votre propre ``{&...}`` pour le nameTagFormat
```php
use SenseiTarzan\RoleManager\Component\TextAttributeManager; //import
use SenseiTarzan\RoleManager\Class\Text\NameTagAttribute; //import
TextAttributeManager::getInstance()->registerNameTagAttribute(new NameTagAttribute("playerXpLvl", function (Player $player, string $search, string &$format): void {
            $format = str_replace($search, $player->getXpManager()->getXpLevel(), $format);
})));
```
Cela va creer un ``{&playerXpLvl}`` est vous pourrez l'ajouter dans le nameTagFormat

# Creation de votre propre system de sauvegarde de données du joueur
### [⚠️⚠️] Ceci est un exemple je ne cherche pas l'optimisation, mais montre comment utiliser et vous devez entre experimenter pour le faire
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


# Récupérer le role par rapport à l'id

````php
use SenseiTarzan\RoleManager\Component\RoleManager;
RoleManager::getInstance()->getRole("id_role" or "name_role")
````

# Récupérer le role du joueur [En ligne]
````php
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
RolePlayerManager::getInstance()->getPlayer(Player|string)->getRole();
````

# Mettre un role à un joueur
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
RoleManager::getInstance()->setRolePlayer(Player|string, Role|string);
````

# Mettre un nom de rôle personnalisé à un joueur et pour les rôles qui ont le ``changeName`` activer [En ligne].
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
RoleManager::getInstance()->setNameRoleCustom(Player, "votre nom personalise");
````

# Mettre un prefix à un joueur [En Ligne]
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
RoleManager::getInstance()->setPrefix(Player, "votre prefix");
````

# Mettre un suffix à un joueur [En Ligne]
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
RoleManager::getInstance()->setSuffix(Player, "votre suffix");
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
