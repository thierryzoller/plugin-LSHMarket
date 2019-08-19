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

/**
 * Gestion des téléchargements
 */
class LSHDownloadManager
{
    /**
     * @var bool Statut de la connexion
     */
    protected static $connectionStatus = false;

    /**
     * @var string Token GitHub
     */
    protected static $gitHubToken;

    /**
     * @var string
     */
    private static $urlForTest = 'www.google.fr';

    /**
     * Constructeur testant le statut de la connexion.
     *
     * @param bool $forceConnectionStatus Booléen pour forcer l'état de la connexion.
     */
    public static function init($forceConnectionStatus = null)
    {
        if ($forceConnectionStatus !== null) {
            self::$connectionStatus = $forceConnectionStatus;
        } else {
            if (!self::isConnected()) {
                self::testConnection();
            }
        }
        self::$gitHubToken = config::byKey('github::token');
        
    }

    /**
     * Test le statut de la connexion.
     */
    protected static function testConnection()
    {
        $sock = \fsockopen(self::$urlForTest, 80);
        if ($sock !== false) {
            self::$connectionStatus = true;
            fclose($sock);
        } else {
            self::$connectionStatus = $sock;
        }
    }

    /**
     * Obtenir le statut de la connexion
     *
     * @return bool True si la connexion fonctionne
     */
    public static function isConnected()
    {
        return self::$connectionStatus;
    }

    /**
     * Télécharge un contenu à partir de son lien
     *
     * @param string $url Lien du contenu à télécharger.
     * @param bool $binary Télécharger un binaire
     *
     * @return string|bool Données téléchargées ou False en cas d'échec
     */
    public static function downloadContent($url, $binary = false)
    {
        if (self::$gitHubToken !== false && self::$gitHubToken != '' && !$binary) {
            $toAdd = 'access_token=' . self::$gitHubToken;
            // Test si un paramètre a déjà été passé
            if (strpos($url, '?') !== false) {
                $url = $url . '&' . $toAdd;
            } else {
                $url = $url . '?' . $toAdd;
            }
        }
        log::add('LSHMarketforJeedom', 'debug', 'Download ' . $url);
        return self::downloadContentWithCurl($url, $binary);
    }

    /**
     * Télécharge un fichier binaire
     *
     * @param string $url Lien du fichier
     * @param string $dest Destination du fichier
     */
    public static function downloadBinary($url, $dest)
    {
        $imgData = self::downloadContent($url, true);
        if (\file_exists($dest)) {
            \unlink($dest);
        }
        $filePointer = \fopen($dest, 'wb');
        \fwrite($filePointer, $imgData);
        \fclose($filePointer);
    }

    /**
     * Télécharge un contenu à partir de son lien avec la méthode cURL
     *
     * @param string $url Lien du contenu à télécharger.
     * @param bool $binary Télécharger un binaire
     *
     * @return string|bool Données téléchargées ou False en cas d'échec
     */
    protected static function downloadContentWithCurl($url, $binary = false)
    {
        $content = false;
        $curlSession = curl_init();
        if ($curlSession !== false) {
            \curl_setopt($curlSession, CURLOPT_URL, $url);
            \curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
            if ($binary) {
                \curl_setopt($curlSession, CURLOPT_BINARYTRANSFER, true);
            }
            \curl_setopt($curlSession, CURLOPT_USERAGENT, 'LSHMarketforJeedom');
            $content = \curl_exec($curlSession);
            \curl_close($curlSession);
        }
        return $content;
    }
}
