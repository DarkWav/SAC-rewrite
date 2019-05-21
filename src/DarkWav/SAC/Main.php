<?php
declare(strict_types=1);
namespace DarkWav\SAC;

# imports

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\Config;

class Main extends PluginBase
{
  #global variables
  public $cl; #color of output messages
  public $version = "4.0.0"; #declare current plugin version
  public $logger; #current PluginLogger instance
  public $server; #current Server instance
  public $config; #get the plugins main configuration file
  public function onEnable() : void #inintialisation when plugin is being loaded
  {
    $this->logger = $this->getServer()->getLogger(); #hook into ServerLogger to prevent auto-prefixing and link it to variable logger
    $this->server = $this->getServer(); #link current Server instance to variable server
    @mkdir($this->getDataFolder()); #create folder for config files, ect
    $this->saveDefaultConfig(); #save config.yml to the DataFolder if it doesn't exist yet
    $this->config = $this->getConfig(); #link config.yml to config variable
    $this->cl = "3"; #set color for output messages to dark aqua
    $this->logger->info("[SAC] > ShadowAntiCheat enabled"); #paint startup message, do not color it to prevent confusion of user.
    switch($this->config->get("plugin_version")) #check if the config file is compatible with the current version of the plugin.
    {
      case "4.0.0": 
      {
        break; #do nothing if compatibility check passes
      }
      default:
      {
        $this->logger->error(TextFormat::RED . "[SAC] > Your configuration file is incompatible with this version of SAC, please delete ./plugin_data/ShadowAntiCheat/config.yml"); #throw error and nofify user about incompatible config
        $this->server->getPluginManager()->disablePlugin($this); #disable the plugin to prevent unpretendable errors
        break;
      }
    }
  }
  public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool #listen to command executions
  {
    switch($command->getName()) #get name of entered command and test for SAC commands
    {
      case "sac": #perform actions if command name is "sac"
      {
        $sender->sendMessage(TextFormat::ESCAPE."$this->cl"."[SAC] > ShadowAntiCheat v".$this->version." [Comet] by DarkWav"); #send info message
        break;
      }
      case "shadowanticheat": #perform actions if command name is "shadowanticheat"
      {
        $sender->sendMessage(TextFormat::ESCAPE."$this->cl"."[SAC] > ShadowAntiCheat v".$this->version." [Comet] by DarkWav"); #send info message
        break;
      }
      default:
      {
        break; #do nothing if the command name is different
      }
    }
    return false; #do not influence the further processing of the command
  }
}
