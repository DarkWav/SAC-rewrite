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

class Main extends PluginBase
{
    #global variables
    public $Colorized;
    public $version = "4.0.7";
    public $config_version = "1.0.0";
    public $logger;
    public $server;
    public $Config;
    public $Analyzers = array();

    public function onEnable(): void
    {
        $this->logger = $this->getServer()->getLogger();
        $this->server = $this->getServer();
        $this->Config = $this->getConfig();
        $this->Colorized = $this->Config->get("Color"); #set color for output messages
        @mkdir($this->getDataFolder()); #create folder for config files, ect
        $this->saveDefaultConfig();
        $this->server->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->logger->info("[SAC] > ShadowAntiCheat enabled");

        #config integrity check

        switch ($this->Config->get("config_version")) #check if the config is up to date.
        {
            case $this->config_version:
                break;
            default:
            {
                $this->logger->warning(TextFormat::YELLOW . "[SAC] > Your configuration file is outdated, please update when possible"); #nofify user about outdated config
                break;
            }
        }
        switch ($this->Config->get("plugin_version")) #check if the config file is compatible with the current version of the plugin.
        {
            case $this->version:
                break;
            default:
            {
                $this->logger->error(TextFormat::RED . "[SAC] > Your configuration file is incompatible with this version of SAC, please delete ./plugin_data/ShadowAntiCheat/config.yml"); #throw error and nofify user about incompatible config
                $this->server->getPluginManager()->disablePlugin($this); #disable the plugin to prevent unpreventable errors
                break;
            }
        }

        #analyzer management
        #connect existing players with an analyzer instance

        foreach ($this->server->getOnlinePlayers() as $player) {
            $hash = spl_object_hash($player);
            $name = $player->getName();
            $oldhash = null;
            $analyzer = null;
            foreach ($this->Analyzers as $key => $ana) {
                if ($ana->PlayerName == $name) {
                    $oldhash = $key;
                    $analyzer = $ana;
                    $analyzer->Player = $player;
                }
            }
            if ($oldhash != null) {
                unset($this->Analyzers[$oldhash]);
                $this->Analyzers[$hash] = $analyzer;
                $this->Analyzers[$hash]->onPlayerRejoin();
            } else {
                $analyzer = new Analyzer($player, $this);
                $this->Analyzers[$hash] = $analyzer;
                $this->Analyzers[$hash]->onPlayerJoin();
            }
        }
    }

    #command handling

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        switch ($command->getName()) #get name of entered command and test for SAC commands
        {
            case "sac":
            case "shadowanticheat":
                $sender->sendMessage(TextFormat::ESCAPE . "$this->Colorized" . "[SAC] > ShadowAntiCheat v" . $this->version . " [Dizzy Devil] by DarkWav");
                return true;
        }
        return false; #do not influence the further processing of the command
    }
}
