<?php

namespace SenseiTarzan\RoleManager\Utils;

use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use SenseiTarzan\RoleManager\Class\Role\Role;

class CustomKnownTranslationFactory
{

    public static function format_permissions_list_string(array $permissions): string
    {
        return "\n- " . implode(",\n- ", $permissions) . "\n";
    }

    public static function get_information_role(string $name): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::GET_INFORMATION_ROLE, ["role" => $name]);
    }

    public static function title_create_role(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_CREATE_ROLE, []);
    }

    public static function title_select_role(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_SELECT_ROLE, []);
    }
    public static function title_select_type(string $name): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_SELECT_TYPE, ["role" => $name]);
    }
    public static function title_modified_general(string $name): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_MODIFIED_GENERAL, ["role" => $name]);
    }
    public static function title_modified_default(string $name): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_MODIFIED_DEFAULT, ["role" => $name]);

    }
    public static function title_modified_remove(string $name): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_MODIFIED_REMOVE, ["role" => $name]);

    }
    public static function title_modified_permissions(string $name): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_MODIFIED_PERMISSIONS, ["role" => $name]);
    }
    public static function title_permissions_add(string $name): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_PERMISSIONS_ADD, ["role" => $name]);
    }
    public static function title_permissions_remove(string $name): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_PERMISSIONS_REMOVE, ["role" => $name]);
    }
    public static function title_modified_heritages(string $name): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_MODIFIED_HERITAGES, ["role" => $name]);
    }
    public static function title_heritages_add(string $name): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_HERITAGES_ADD, ["role" => $name]);
    }
    public static function title_heritages_remove(string $name): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::TITLE_HERITAGES_REMOVE, ["role" => $name]);
    }
    public static function button_modified_general(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTON_MODIFIED_GENERAL, []);

    }
    public static function description_modified_default(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::DESCRIPTION_MODIFIED_DEFAULT, []);

    }
    public static function description_modified_remove(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::DESCRIPTION_MODIFIED_REMOVE, []);

    }
    public static function button_modified_default(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTON_MODIFIED_DEFAULT, []);

    }
    public static function button_modified_permissions(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTON_MODIFIED_PERMISSIONS, []);

    }
    public static function button_permissions_add(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTON_PERMISSIONS_ADD, []);

    }
    public static function button_permissions_remove(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTON_PERMISSIONS_REMOVE, []);

    }
    public static function button_modified_heritages(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTON_MODIFIED_HERITAGES, []);

    }

    public static function button_heritages_add(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTON_HERITAGES_ADD, []);

    }

    public static function button_heritages_remove(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTON_HERITAGES_REMOVE, []);

    }
    public static function button_modified_remove(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTON_MODIFIED_REMOVE, []);

    }
    public static function buttons_accept(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTONS_ACCEPT, []);

    }
    public static function buttons_denied(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::BUTTONS_DENIED, []);

    }


    public static function message_create_role(string $name): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::CREATE_ROLE, ["role" => $name]);
    }

    public static function set_default_role_sender(string $name): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::CREATE_ROLE, ["role" => $name]);
    }

    public static function remove_role(string $name): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::REMOVE_ROLE, ["role" => $name]);
    }
    public static function exemple_image_label(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::EXEMPLE_IMAGE_LABEL, []);
    }
    public static function exemple_priority_label(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::EXEMPLE_PRIORITY_LABEL, []);
    }
    public static function exemple_heritages_label(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::EXEMPLE_HERITAGES_LABEL, []);
    }
    public static function exemple_permissions_label(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::EXEMPLE_PERMISSIONS_LABEL, []);
    }
    public static function error_player_disconnected(string $name): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::ERROR_PLAYER_DISCONNECTED, ["player" => $name]);
    }

    public static  function set_role_sender(Player|string $player, Role $role): Translatable{
        return new Translatable(CustomKnownTranslationKeys::SET_ROLE_SENDER, ['player' => $player instanceof Player ? $player->getName() : $player, 'role' => $role->getName()]);
    }

    public static  function set_role_target(Role $role): Translatable{
        return new Translatable(CustomKnownTranslationKeys::SET_ROLE_TARGET, ['role' => $role->getName()]);
    }
    public static  function add_permissions_sender(Player|string $player, array $permissions): Translatable{
        return  new Translatable(CustomKnownTranslationKeys::ADD_PERMISSIONS_SENDER, ['player' => $player instanceof Player ? $player->getName() : $player, 'permissions' => self::format_permissions_list_string($permissions)]);
    }
    public static  function add_permissions_target( array $permissions): Translatable{
        return new Translatable(CustomKnownTranslationKeys::ADD_PERMISSIONS_TARGET, ['permissions' => self::format_permissions_list_string($permissions)]);
    }
    public static  function remove_permissions_sender(Player|string $player, array $permissions): Translatable{
        return new Translatable(CustomKnownTranslationKeys::REMOVE_PERMISSIONS_SENDER, ['player' => $player instanceof Player ? $player->getName() : $player, 'permissions' => self::format_permissions_list_string($permissions)]);
    }
    public static  function remove_permissions_target( array $permissions): Translatable{
        return new Translatable(CustomKnownTranslationKeys::REMOVE_PERMISSIONS_TARGET,['permissions' => self::format_permissions_list_string($permissions)]);
    }
    public static  function set_permissions_sender(Player|string $player, array $permissions): Translatable{

        return new Translatable(CustomKnownTranslationKeys::SET_PERMISSIONS_SENDER,['player' => $player instanceof Player ? $player->getName() : $player, 'permissions' => self::format_permissions_list_string($permissions)]);
    }
    public static  function set_permissions_target( array $permissions): Translatable{
        return new Translatable(CustomKnownTranslationKeys::SET_PERMISSIONS_TARGET,['permissions' => self::format_permissions_list_string($permissions)]);
    }

    public static function set_suffix_sender(Player $player, string $suffix): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::SET_SUFFIX_SENDER, ['player' => $player->getName() , 'suffix'=> $suffix]);
    }

    public static function set_suffix_target(string $suffix): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::SET_SUFFIX_TARGET, ['suffix'=> $suffix]);
    }
    public static function set_prefix_sender(Player $player, string $prefix): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::SET_PREFIX_SENDER, ['player' => $player->getName(), 'prefix'=> $prefix]);
    }
    public static function set_prefix_target( string $prefix): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::SET_PREFIX_TARGET, ['prefix'=> $prefix]);
    }
    public static function set_name_role_sender(Player $player, string $nameRoleCustom): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::SET_NAME_ROLE_CUSTOM_SENDER, ['player' => $player->getName(), 'nameCustom'=> $nameRoleCustom]);
    }
    public static function set_name_role_custom_target(string $nameRoleCustom): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::SET_NAME_ROLE_CUSTOM_TARGET, ['nameCustom'=> $nameRoleCustom]);
    }

}