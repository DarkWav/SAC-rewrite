<?php
declare(strict_types=1);

namespace DarkWav\SAC;

/*
 *  ShadowAntiCheat by DarkWav.
 *  Distributed under the MIT License.
 *  Copyright (C) 2016-2019 DarkWav
 */

# imports

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\Config;

use DarkWav\SAC\EventListener;
use DarkWav\SAC\Analyzer;

class Main extends PluginBase
{
    #global variables
    public $cl;
    public $version = "4.0.1";
    public $logger;
    public $server;
    public $config;
    public $Analyzers = array();

    public function onEnable() : void
    {
        $this->logger = $this->getServer()->getLogger();
        $this->server = $this->getServer();
        $this->server->getPluginManager()->registerEvents(new EventListener($this), $this);
        @mkdir($this->getDataFolder()); #create folder for config files, ect
        $this->saveDefaultConfig();
        $this->config = $this->getConfig();
        $this->cl = "3"; #set color for output messages to dark aqua
        $this->logger->info("[SAC] > ShadowAntiCheat enabled");

        #config integrity check

        switch ($this->config->get("plugin_version")) #check if the config file is compatible with the current version of the plugin.
        {
            case $this->version:
                break;
            case "4.0.0":
                break;
            default:
                {
                    $this->logger->error(TextFormat::RED . "[SAC] > Your configuration file is incompatible with this version of SAC, please delete ./plugin_data/ShadowAntiCheat/config.yml"); #throw error and nofify user about incompatible config
                    $this->server->getPluginManager()->disablePlugin($this); #disable the plugin to prevent unpretendable errors
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
                $observer = new Analyzer($player, $this);
                $this->Analyzer[$hash] = $analyzer;
                $this->Analyzer[$hash]->onPlayerJoin();
            }
        }
    }

    #command handling

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool
    {
        switch ($command->getName()) #get name of entered command and test for SAC commands
        {
            case "sac":
                {
                    $sender->sendMessage(TextFormat::ESCAPE . "$this->cl" . "[SAC] > ShadowAntiCheat v" . $this->version . " [Comet] by DarkWav");
                    break;
                }
            case "shadowanticheat":
                {
                    $sender->sendMessage(TextFormat::ESCAPE . "$this->cl" . "[SAC] > ShadowAntiCheat v" . $this->version . " [Comet] by DarkWav");
                    break;
                }
            default:
                {
                    break;
                }
        }
        return false; #do not influence the further processing of the command
    }
}
