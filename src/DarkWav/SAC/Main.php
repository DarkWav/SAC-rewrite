<?php
declare(strict_types=1);
namespace DarkWav\SAC;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2020 DarkWav and others.
 */

# imports

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\Config;
use pocketmine\Player;

use DarkWav\SAC\EventListener;
use DarkWav\SAC\Analyzer;
use DarkWav\SAC\KickTask;

class Main extends PluginBase
{
  #global variables
  public $Colorized;
  public $version = "4.0.13";
  public $alternate_versions = ["4.0.9", "4.0.10", "4.0.11", "4.0.12"];
  public $config_version = "1.0.2";
  public $logger;
  public $server;
  public $Config;
  public $advancedConfig;
  public $Analyzers = array();

  public function onEnable() : void
  {
    $this->logger = $this->getServer()->getLogger();
    $this->server = $this->getServer();
    $this->server->getPluginManager()->registerEvents(new EventListener($this), $this);
    @mkdir($this->getDataFolder()); #create folder for config files, ect
    $this->saveDefaultConfig();
    $this->saveResource("advanced.yml", false);
    $this->Config = $this->getConfig();
    $this->advancedConfig = new Config($this->getDataFolder() . "advanced.yml", Config::YAML);
    $this->Colorized = $this->Config->get("Color"); #set color for output messages
    $this->logger->info("[SAC] > ShadowAntiCheat enabled");

    #config integrity check

    switch($this->Config->get("config_version")) #check if the config is up to date.
    {
      case $this->config_version: break;
      default:
      {
        $this->logger->warning(TextFormat::YELLOW . "[SAC] > Your configuration file is outdated, please update when possible"); #nofify user about outdated config
        break;
      }
    }

    #check if the config file is compatible with the current version of the plugin.
    if (!($plugin_version = $this->Config->get("plugin_version")) === $this->version && !in_array($plugin_version, $this->alternate_versions)) {
      $this->logger->error(TextFormat::RED . "[SAC] > Your configuration file is incompatible with this version of SAC, please delete ./plugin_data/ShadowAntiCheat/config.yml"); #throw error and nofify user about incompatible config
      $this->server->getPluginManager()->disablePlugin($this); #disable the plugin to prevent unpretendable errors
    }

    #analyzer management
    #connect existing players with an analyzer instance

    foreach($this->server->getOnlinePlayers() as $player)
    {
      $uuid     = $player->getRawUniqueID();
      $name     = $player->getName();
      $olduuid  = null;
      $analyzer = null;
      foreach ($this->Analyzers as $key=>$ana)
      {
        if ($ana->PlayerName == $name)
        {
          $olduuid          = $key;
          $analyzer         = $ana;
          $analyzer->Player = $player;
        }
      }
      if ($olduuid != null)
      {
        unset($this->Analyzers[$olduuid]);
        $this->Analyzers[$uuid] = $analyzer;
        $this->Analyzers[$uuid]->onPlayerRejoin();
      }
      else
      {
        $analyzer = new Analyzer($player, $this);
        $this->Analyzers[$uuid] = $analyzer;
        $this->Analyzers[$uuid]->onPlayerJoin();
      }
    }
  }

  #command handling

  public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool
  {
    if($command->getName() == "sac" || $command->getName() == "shadowanticheat") #get name of entered command and test for SAC commands
    {
      $sender->sendMessage(TextFormat::ESCAPE."$this->Colorized"."[SAC] > ShadowAntiCheat v".$this->version." [Dizzy Devil] by DarkWav");
    }
    return false; #do not influence further processing of the command
  }
}
