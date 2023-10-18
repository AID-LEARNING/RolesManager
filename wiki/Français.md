
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
use SenseiTarzan\RoleManager\Class\Exception\SaveDataException;

class JSONSeparedSave extends IDataSaveRoleManager
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
        return "Json System Separed";
    }

    /**
    * C'est une promesse de chargement le joueur en cache et donne une erreur SaveDataException
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
    * C'est une promesse de creation Data pour les premières fois
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
                $reject(new SaveDataException("Error no existe data {$rolePlayer->getId()}"));
                return ;
            }
            try {
                $infoPlayer = $this->playersConfig[$rolePlayer->getId()];
                $infoPlayer->setAll( $rolePlayer->jsonSerialize());
                $infoPlayer->save();
                $resolve();
            } catch (JsonException) {
                $reject(new SaveDataException("Error save data player {$player->getName()}"));
            }
        });
    }

    /**
    * C'est une promesse de Mise à jour des donnes pour les joueurs déconnectée
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
                $reject(new SaveDataException("Error save data player offline $id"));
            }
        });
    }
    
    /**
    * C'est une promesse de Mise à jour des donnes pour les joueurs connectée
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
                $reject(new SaveDataException("Error no existe data $id"));
                return ;
            }
            try {
                $infoPlayer = $this->playersConfig[$id];
                $infoPlayer->set($type, $data);
                $infoPlayer->save();
                $resolve();
            } catch (JsonException) {
                $reject(new SaveDataException("Error save data player $id"));
            }
        });
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
RolePlayerManager::getInstance()->getPlayer(Player or string)->getRole();
````

# Récupérer le role name ou si vous aves un role custom name avec qui va joueur [En ligne]
````php
use SenseiTarzan\RoleManager\Component\RolePlayerManager;
RolePlayerManager::getInstance()->getPlayer(Player or string)->getRoleName();
````

# Mettre un role à un joueur
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Class\Save\ResultUpdate;
use SOFe\AwaitGenerator\Await;

Await::g2c(RoleManager::getInstance()->setRolePlayer(Player or string, string or Role), function (ResultUpdate $resultUpdate) {
        // L'action que vous voulez quand le role a été mis
    }, 
    [
        SaveDataException::class => function () {},
        CancelEventException::class => function () {},
        InvalidArgumentException::class => function () {}
    ]
);
````

# Mettre un nom de rôle personnalisé à un joueur et pour les rôles qui ont le ``changeName`` activer[En ligne].
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
use SOFe\AwaitGenerator\Await;
Await::g2c(RoleManager::getInstance()->setNameRoleCustom(Player, string), function (string $nameCustom) {
        // L'action que vous voulez quand le roleNameCustom a été mis
    },
    [
        RoleNoNameCustomException::class => function () {},
        CancelEventException::class => function () {},
        RoleFilteredNameCustomException::class =>  function () {},
        SaveDataException::class => function () {}
    ]
);
````

# Mettre un prefix à un joueur [En Ligne]
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
use SOFe\AwaitGenerator\Await;
Await::g2c(RoleManager::getInstance()->setPrefix(Player, string), function (string $prefix) {
        // L'action que vous voulez quand le prefix a été mis
    },
    [
        SaveDataException::class => function () {},
        CancelEventException::class => function () {}
    ]
);
````

# Mettre un suffix à un joueur [En Ligne]
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
use SOFe\AwaitGenerator\Await;
Await::g2c(RoleManager::getInstance()->setSuffix(Player, string), function (string $prefix) use ($sender, $target) {
        // L'action que vous voulez quand le prefix a été mis
    },
    [
        SaveDataException::class => function () {},
        CancelEventException::class => function () {}
    ]
);
````

# Définir un/des permissions un joueur
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
use SOFe\AwaitGenerator\Await;

Await::g2c(RoleManager::getInstance()->setPermissionPlayer(Player or string, array<string> or string), function (ResultUpdate $resultUpdate) use ($sender, $target) {
        // L'action que vous voulez quand le set de permission a été fait
    },
    [
            SaveDataException::class => function () {},
            CancelEventException::class => function () {}
    ]
);
````

# Ajouter un/des permission(s) à un joueur
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
use SOFe\AwaitGenerator\Await;
Await::g2c(RoleManager::getInstance()->addPermissionPlayer(Player or string, array<string> or string), function (ResultUpdate $resultUpdate) use ($sender, $target, $perm) {
        // L'action que vous voulez quand l'ajout de permission a été fait
    },
    [
            SaveDataException::class => function () {},
            CancelEventException::class => function () {}
    ]
);
````

# Enlever un/des permission(s) à un joueur
````php
use SenseiTarzan\RoleManager\Component\RoleManager;
use SOFe\AwaitGenerator\Await;

Await::g2c(RoleManager::getInstance()->removePermissionPlayer(Player or string, array<string> or string), function (ResultUpdate $resultUpdate) use ($sender, $target, $perm) {
        // L'action que vous voulez quand le supprission de permission a été fait
    },
    [
            SaveDataException::class => function () {},
            CancelEventException::class => function () {}
    ]
);
````