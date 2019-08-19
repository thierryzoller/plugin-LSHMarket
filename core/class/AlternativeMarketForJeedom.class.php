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

require_once __DIR__ . '/../../../../core/php/core.inc.php';
require_once('AmfjMarket.class.php');
require_once('AmfjDownloadManager.class.php');

/**
 * Classe des objets de Jeedom
 */
class LSHMarket2 extends eqLogic
{
    /**
     * Compare deux objets en fonction de la valeur 'order'
     *
     * @param AlternativeMarketForJeedom $obj1 Premier objet à comparer
     * @param AlternativeMarketForJeedom $obj2 Deuxième objet à comparer
     *
     * @return int|null 0 si =, -1 si $obj1 < $obj2, 1 si $obj1 > $obj2
     */
    public static function cmpByOrder($obj1, $obj2)
    {
        $result = null;
        $obj1Order = $obj1['order'];
        $obj2Order = $obj2['order'];
        if ($obj1Order == $obj2Order) {
            $result = 0;
        } else {
            if ($obj1Order < $obj2Order) {
                $result = -1;
            } else {
                $result = 1;
            }
        }
        return $result;
    }

    /**
     * Met à jour la liste tous les jours
     */
    public static function cronDaily()
    {
        LSHDownloadManager::init();

        $plugin = plugin::byId('LSHMarket');
        $eqLogics = eqLogic::byType($plugin->getId(), true);

        foreach ($eqLogics as $eqLogic) {
            $source = [];
            $source['name'] = $eqLogic->getName();
            $source['type'] = $eqLogic->getConfiguration('type');
            $source['data'] = $eqLogic->getConfiguration('data');
            $market = new AmfjMarket($source);
            $market->refresh(true);
            foreach ($market->getItems() as $marketItem) {
                $marketItem->downloadIcon();
            }
        }
    }
}
