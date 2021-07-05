<?php
declare(strict_types=1);
namespace DarkWav\SAC;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2021 DarkWav and others.
 */

# imports

use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\Config;

class Main extends PluginBase
{
  #global variables
  /** @var int */
  public int $Colorized;
  /** @var String */
  public String $version = "4.0.18";
  /** @var String */
  public String $codename = "no codename yet lol"; #TODO: think of a decent codename for v4 ;)
  /** @var String[] */
  public array $supported_config_versions = ["4.0.16", "4.0.17", "4.0.18"];
  /** @var String */
  public String $config_version = "1.0.3";
  /** @var MainLogger */
  public MainLogger $logger;
  /** @var Server */
  public Server $server;
  /** @var Config */
  public Config $Config;
  /** @var Config */
  public Config $advancedConfig;
  /** @var Analyzer[] */
  public array $Analyzers = [];

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

    if($this->Config->get("config_version") != $this->config_version || $this->advancedConfig->get("config_version") != $this->config_version) #check if the config is up to date.
    {
      $this->logger->warning(TextFormat::YELLOW . "[SAC] > Your configuration is outdated, please update when possible"); #nofify user about outdated config
    }
    if(!in_array($this->Config->get("plugin_version"), $this->supported_config_versions) || !in_array($this->advancedConfig->get("plugin_version"), $this->supported_config_versions)) #check if the config file is compatible with the current version of the plugin.
    {
      $this->logger->error(TextFormat::RED . "[SAC] > Your configuration file is incompatible with this version of SAC, please delete ./plugin_data/ShadowAntiCheat/config.yml and ./plugin_data/ShadowAntiCheat/advanced.yml"); #throw error and nofify user about incompatible config
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

  /**
   * @param CommandSender $sender
   * @param Command $command
   * @param string $label
   * @param array $args
   * @return bool
   */
  public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool
  {
    if($command->getName() == "shadowanticheat") #get name of entered command and test for SAC commands
    {
      $sender->sendMessage(TextFormat::ESCAPE."$this->Colorized"."[SAC] > ShadowAntiCheat v".$this->version." [".$this->codename."] by DarkWav");
    }
    return false; #do not influence further processing of the command
  }
}
