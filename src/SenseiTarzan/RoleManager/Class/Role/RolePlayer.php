<?php

namespace SenseiTarzan\RoleManager\Class\Role;

use Error;
use Generator;
use JsonSerializable;
use pocketmine\permission\PermissionAttachment;
use pocketmine\Server;
use SenseiTarzan\DataBase\Component\DataManager;
use SenseiTarzan\RoleManager\Class\Exception\CancelEventException;
use SenseiTarzan\RoleManager\Class\Exception\RoleNoNameCustomException;
use SenseiTarzan\RoleManager\Component\RoleManager;
use SenseiTarzan\RoleManager\Event\EventChangeNameCustom;
use SenseiTarzan\RoleManager\Event\EventChangePrefix;
use SenseiTarzan\RoleManager\Event\EventChangeRole;
use SenseiTarzan\RoleManager\Event\EventChangeSuffix;
use SOFe\AwaitGenerator\Await;

class RolePlayer implements JsonSerializable
{

    private string $id;
    private ?PermissionAttachment $attachment = null;

    /**
     * RolePlayer constructor.
     * @param string $name
     * @param string $prefix
     * @param string $suffix
     * @param string $role
     * @param string[] $subRoles
     * @param string|null $nameRoleCustom
     * @param array $permissions
     * @throws \JsonException
     */
    public function __construct(private string $name, private string $prefix, private string $suffix, private string $role, private array $subRoles, private string|null $nameRoleCustom, private array $permissions = [])
    {
        $this->id = strtolower($this->name);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix(string $prefix): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($prefix) {
            $event = new EventChangePrefix(Server::getInstance()->getPlayerExact($this->getName()), $this->getPrefix(), $prefix);
            $event->call();
            if ($event->isCancelled()) {
                $reject(new CancelEventException());
                return;
            }
            Await::f2c(function () use ($event): Generator {
                yield from DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "prefix", $prefix = $event->getNewPrefix());
                $this->prefix = $prefix;
                return $prefix;
            }, $resolve, $reject);
        });

    }

    /**
     * @return string
     */
    public function getSuffix(): string
    {
        return $this->suffix;
    }

    /**
     * @param string $suffix
     */
    public function setSuffix(string $suffix): Generator
    {

        return Await::promise(function ($resolve, $reject) use ($suffix) {

            $event = new EventChangeSuffix(Server::getInstance()->getPlayerExact($this->getName()), $this->getSuffix(), $suffix);
            $event->call();
            if ($event->isCancelled()){
                $reject(new CancelEventException());
                return;
            }
            Await::f2c(function () use($event) : Generator{
                yield from DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "suffix", $suffix = $event->getNewSuffix());
                $this->suffix = $suffix;
                return $suffix;
            }, $resolve, $reject);
        });
    }


    /**
     * @return Role
     */
    public function getRole(): Role
    {
        return RoleManager::getInstance()->getRole($this->role);
    }

    /**
     * @return array
     */
    public function getSubRoles(): array
    {
        return $this->subRoles;
    }

    public function hasSubRole(string $role): bool
    {
        return in_array($role, $this->subRoles, true) || $this->role === $role || in_array($role, $this->getRole()->getAllHeritages(), true);
    }

    /**
     * @param array $roles
     * @return void
     */
    public function filterNoHasSubRoles(array $roles): array
    {
        $list = [];
        foreach ($roles as $role) {
            if ($role instanceof Role) {
                if (!$this->hasSubRole($role->getId())){
                    $list[] = $role->getId();
                }
                continue;
            }
            if (!$this->hasSubRole($role)){
                $list[] = $role;
            }

        }
        return $list;
    }

    /**
     * @param array|string|Role $roles
     * @return Generator<string>
     */
    public function addSubRole(array|string|Role $roles): Generator
    {
        return $this->setSubRoles(array_merge($this->subRoles, $this->filterNoHasSubRoles(is_array($roles) ? $roles : [$roles])));
    }

    /**
     * @param array|string|Role $roles
     * @return Generator<string[]>
     */
    public function removeSubRole(array|string|Role $roles): Generator
    {
        return $this->setSubRoles(array_diff($this->subRoles, $this->filterNoHasSubRoles(is_array($roles) ? $roles : [$roles])));
    }

    /**
     * @param array|string|Role $roles
     * @return Generator
     */
    public function setSubRoles(array|string|Role $roles): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($roles) {
            if (is_string($roles)) {
                $roles = [$roles];
            } else if ($roles instanceof Role) {
                $roles = [$roles->getId()];
            }
            $roles = array_values($roles);
            Await::f2c(function () use ($roles): Generator {
                yield from DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "subRoles", $roles);
                $this->subRoles = $roles;
                return $roles;
            }, $resolve, $reject);
        });

    }

    public function clearSubRoles(): void
    {
        $this->setSubRoles([]);
    }

    /**
     * @param string|Role $role
     * @return Generator<Role>
     * @throws CancelEventException
     */
    public function setRole(string|Role $role): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($role) {
            $event = new EventChangeRole(Server::getInstance()->getPlayerExact($this->getName()), $this->getRole(), $role instanceof Role ? $role : RoleManager::getInstance()->getRole($role));
            $event->call();
            if ($event->isCancelled()) {
                $reject(new CancelEventException());
                return;
            }
            Await::f2c(function () use ($event, $role): Generator {

                yield from DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "role", ($id = ($role = $event->getNewRole())->getId()));
                $this->role = $id;
                return $role;
            }, $resolve, $reject);
        });
    }

    /**
     * @return string|null
     */
    public function getNameRoleCustom(): ?string
    {
        return $this->nameRoleCustom;
    }

    /**
     * @param string $role
     * @return Generator<string>
     * @throws CancelEventException
     */
    public function setRoleNameCustom(string $role): Generator
    {

        return Await::promise(function ($resolve, $reject) use ($role) {
            if (!$this->getRole()->isChangeName()) {
                $reject(new RoleNoNameCustomException());
                return;
            }
            $event = new EventChangeNameCustom(Server::getInstance()->getPlayerExact($this->getName()), $this->getNameRoleCustom(), $role);
            $event->call();
            if ($event->isCancelled()) {
                $reject(new CancelEventException());
                return;
            }
            Await::f2c(function () use ($event, $role): Generator {
                yield from DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "nameRoleCustom", $newName = $event->getNewNameCustom());
                $this->nameRoleCustom = $newName;
                return $newName;
            }, $resolve, $reject);
        });
    }

    public function getRoleName(): string
    {
        return ($this->getRole()->isChangeName() ? $this->getNameRoleCustom() : null) ?? $this->getRole()->getName();
    }

    /**
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getPermissionsSubRoles(): array
    {
        if (empty($this->getSubRoles())) return [];
        $permissions = [];
        $roleManager = RoleManager::getInstance();
        foreach ($this->getSubRoles() as $role) {
            if (!$roleManager->existRole($role)) continue;
            $permissions = array_merge($permissions, $roleManager->getRole($role)->getPermissions());
        }
        return $permissions;
    }

    /**
     * @param array|string $permissions
     * @return Generator<string[]>
     */
    public function setPermissions(array|string $permissions): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($permissions) {
            Await::f2c(function () use ($permissions): Generator {
                if (is_string($permissions)) {
                    $permissions = [$permissions];
                }
                $permissions = array_values($permissions);
                yield from DataManager::getInstance()->getDataSystem()->updateOnline($this->getId(), "permissions", $permissions);
                $this->permissions = $permissions;
                return $permissions;
            }, $resolve, $reject);
        });
    }

    /**
     * @param array|string $permissions
     * @return Generator<string[]>
     */
    public function addPermissions(array|string $permissions): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($permissions) {
            Await::g2c($this->setPermissions(array_merge($this->permissions, is_array($permissions) ? $permissions : [$permissions])), $resolve, $reject);
        });
    }

    /**
     * @param array|string $permissions
     * @return Generator<string[]>
     */
    public function removePermissions(array|string $permissions): Generator
    {
        return Await::promise(function ($resolve, $reject) use ($permissions) {
            Await::g2c($this->setPermissions(array_diff($this->permissions, is_array($permissions) ? $permissions : [$permissions])), $resolve, $reject);
        });
    }


    public function jsonSerialize(): array
    {
        return ["prefix" => $this->getPrefix(), "suffix" => $this->getSuffix(), "role" => $this->getRole()->getId(), "subRoles" => $this->getSubRoles(), "nameRoleCustom" => $this->getNameRoleCustom(), "permissions" => $this->getPermissions()];
    }

    public function setAttachment(PermissionAttachment $addAttachment): void
    {
        $this->attachment = $addAttachment;
    }

    public function getAttachment(): PermissionAttachment
    {
        return $this->attachment ?? throw new Error("Attachment not found");
    }
}