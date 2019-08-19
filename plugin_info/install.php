<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
require_once(dirname(__FILE__) . '/../core/class/AmfjDataStorage.class.php');
require_once(dirname(__FILE__) . '/../core/class/AlternativeMarketForJeedom.class.php');

/**
 * Fonction appelée à l'activation du plugin
 */
function AlternativeMarketForJeedom_install()
{
    $dataStorage = new LSHDataStorage('lsh');
    $dataStorage->createDataTable();

    $markets = [
        ['name' => 'LSH Stable', 'enabled' => 1, 'type' => 'json', 'order' => 1, 'data' => 'https://raw.githubusercontent.com/thierryzoller/LSH-Lists/master/results/lsh-stable.json']
    ];

    foreach ($markets as $market) {
        $dataStorage->storeJsonData('source_'.$market['name'], $market);
    }

    config::save('github::enable', 1);
    config::save('show-disclaimer', true, 'LSHMarket');
}

/**
 * Fonction appelée à la mise à jour du plugin
 */
function AlternativeMarketForJeedom_update()
{
    // Suppression de l'ancienne gestion des sources
    foreach (eqLogic::byType('LSHMarket') as $eqLogic) {
        $eqLogic->remove();
    }
}

/**
 * Fonction appelée à la désactivation du plugin
 */
function AlternativeMarketForJeedom_remove()
{
    // Suppression des sources de la base de données
    $dataStorage = new LSHDataStorage('lsh');
    $dataStorage->dropDataTable();

    // Suppresion des données de configuration
    config::remove('show-disclaimer', 'LSHMarket');
    config::remove('show-duplicates', 'LSHMarket');
    config::remove('show-sources-filters', 'LSHMarket');
    // Suppression du cache depuis le répertoire core/ajax
    exec('rm -fr ../../plugins/LSHMarket/cache/*');
}

