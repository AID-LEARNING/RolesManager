# RolesManager

#### Documentation in english [[here](https://github.com/AID-LEARNING/RolesManager/blob/beta/wiki/English.md)] 🇬🇧.
#### Documentation en français [[here](https://github.com/AID-LEARNING/RolesManager/blob/beta/wiki/Français.md)] 🇫🇷.

## ⚠️⚠️**in case of error or crash create a way out and fix it as soon as possible**

### Faction System Support
 - PiggyFactions
 - FactionMaster

## Commands:
 - /role **give you the role you have**
 - /role create **allow you to create roles in games with a form interface**
 - /role modify **allow you to modify in-game roles with a form interface**
 - /role reload **allow you to refresh the role data against their configuration file**
 - /role setrole [``target:Player``] [``name:String``] **you allow putting a role to a player**
 - /role setperm [``target:Player``] [``permission[;permission2;...]:String``] **you allow putting a permission(s) to a player**
 - /role addperm [``target:Player``] [``permission[;permission2;...]:String``] **you allow adding a permission(s) to a player**
 - /role subperm [``target:Player``] [``permission[;permission2;...]:String``] **you allow removing a permission(s) to a player**
 - /role setnamecustom [``target:Player``] [``name:String``] **you allow putting a custom name role to a player***
 - /role setperfix [``target:Player``] [``perfix:String``] **you allow putting a prefix to a player**
 - /role suffix [``target:Player``] [``suffix:String``] **you allow putting a suffix to a player**

## Attributes for ChatFormat 

| Key                | Description                      | 
|--------------------|----------------------------------|
| ``{&playerName}``  | give the name player             | 
| ``{&role}``        | give the role player             | 
| ``{&prefix}``      | give the prefix player           |
| ``{&suffix}``      | give the suffix player           |
| ``{&factionName}`` | give the faction name player     |
| ``{&factionRank}`` | give the rank player in faction  |
| ``{&message}``     | give the  message player         |
| ``\n   ``          | Allows you to return to the line |

## Attributes for NameTagFormat

| Key                | Description                      | 
|--------------------|----------------------------------|
| ``{&playerName}``  | give the name player             | 
| ``{&role}``        | give the role player             | 
| ``{&prefix}``      | give the prefix player           |
| ``{&suffix}``      | give the suffix player           |
| ``{&factionName}`` | give the faction name player     |
| ``{&factionRank}`` | give the rank player in faction  |
| ``\n   ``          | Allows you to return to the line |

