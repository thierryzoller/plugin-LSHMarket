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


require_once('AmfjDownloadManager.class.php');
require_once('AmfjDataStorage.class.php');

/**
 * Gestion des informations liées à GitHub
 */
class AmfjGitManager
{
    /**
     * @var string Utilisateur du dépot
     */
    private $gitId;
    /**
     * @var AmfjDataStorage Gestionnaire de base de données
     */
    private $dataStorage;

    /**
     * Constructeur du gestionnaire Git
     *
     * @param string $gitId Utilisateur du compte Git
     */
    public function __construct($gitId)
    {
        AmfjDownloadManager::init();
        $this->gitId = $gitId;
        $this->dataStorage = new AmfjDataStorage('amfj');
    }

    /**
     * Met à jour la liste des dépôts
     *
     * @return bool True si l'opération a réussie
     *
     * @throws Exception
     */
    public function updateRepositoriesList()
    {
        $result = false;
        $jsonList = $this->downloadRepositoriesList();
        if ($jsonList !== false) {
            $jsonAnswer = \json_decode($jsonList, true);
            $dataToStore = array();
            foreach ($jsonAnswer as $repository) {
                $data = array();
                $data['name'] = $repository['name'];
                $data['full_name'] = $repository['full_name'];
                $data['description'] = $repository['description'];
                $data['html_url'] = $repository['html_url'];
                $data['git_id'] = $this->gitId;
                $data['default_branch'] = $repository['default_branch'];
                \array_push($dataToStore, $data);
            }
            $this->dataStorage->storeRawData('repo_last_update_' . $this->gitId, \time());
            $this->dataStorage->storeJsonData('repo_data_' . $this->gitId, $dataToStore);
            // Efface la liste des dépôts ignorés
            $this->saveIgnoreList([]);
            $result = true;
        }
        return $result;
    }

    /**
     * Télécharge la liste des dépôts au format JSON
     *
     * @return string|bool Données au format JSON ou False en cas d'échec
     * @throws Exception
     */
    protected function downloadRepositoriesList()
    {
        $result = false;
        $content = AmfjDownloadManager::downloadContent('https://api.github.com/orgs/' . $this->gitId . '/repos?per_page=100');
        // Limite de l'API GitHub atteinte
        if (\strstr($content, 'API rate limit exceeded')) {
            $content = AmfjDownloadManager::downloadContent('https://api.github.com/rate_limit');
            $gitHubLimitData = json_decode($content, true);
            $refreshDate = date('H:i', $gitHubLimitData['resources']['core']['reset']);
            throw new \Exception('Limite de l\'API GitHub atteinte. Le rafraichissement sera accessible à ' . $refreshDate);
        } elseif (\strstr($content, 'Bad credentials')) {
            // Le token GitHub n'est pas bon
            throw new \Exception('Problème de Token GitHub');
        } else {
            // Test si c'est un dépôt d'organisation
            if (\strstr($content, '"message":"Not Found"')) {
                // Test d'un téléchargement pour un utilisateur
                $content = AmfjDownloadManager::downloadContent('https://api.github.com/users/' . $this->gitId . '/repos?per_page=100');
                // Test si c'est un dépot d'utilisateur
                if (\strstr($content, '"message":"Not Found"') || strlen($content) < 10) {
                    throw new \Exception('Le dépôt ' . $this->gitId . ' n\'existe pas.');
                } else {
                    $result = $content;
                }
            } else {
                $result = $content;
            }
        }
        return $result;
    }

    /**
     * Mettre à jour les dépôts
     *
     * @param string $sourceName Nom de la source
     * @param array $repositoriesList Liste des dépots
     * @param bool $force Forcer les mises à jour
     */
    public function updateRepositories($sourceName, $repositoriesList, $force)
    {
        $ignoreList = $this->getIgnoreList();
        foreach ($repositoriesList as $repository) {
            $repositoryName = $repository['name'];
            $marketItem = AmfjMarketItem::createFromGit($sourceName, $repository);
            if (($force || $marketItem->isNeedUpdate($repository)) && !\in_array($repositoryName, $ignoreList)) {
                if (!$marketItem->refresh()) {
                    \array_push($ignoreList, $repositoryName);
                }
            }
        }
        $this->saveIgnoreList($ignoreList);
    }

    /**
     * Obtenir la liste des dépots ignorés
     *
     * @return array|mixed
     */
    protected function getIgnoreList()
    {
        $result = array();
        $jsonList = $this->dataStorage->getJsonData('repo_ignore_' . $this->gitId);
        if ($jsonList !== null) {
            $result = $jsonList;
        }
        return $result;
    }

    /**
     * Sauvegarder la liste des dépôts ignorés
     *
     * @param array $ignoreList Liste des dépôts ignorés
     */
    protected function saveIgnoreList($ignoreList)
    {
        $this->dataStorage->storeJsonData('repo_ignore_' . $this->gitId, $ignoreList);
    }

    /**
     * Lire le contenu du fichier contenant la liste des dépôts
     *
     * @return bool|array Tableau associatifs contenant les données ou false en cas d'échec
     */
    public function getRepositoriesList()
    {
        $result = false;
        $jsonStrList = $this->dataStorage->getJsonData('repo_data_' . $this->gitId);
        if ($jsonStrList !== null) {
            $result = $jsonStrList;
        }
        return $result;
    }

    /**
     * Obtenir la liste des plugins
     *
     * @param string $sourceName Nom de la source
     *
     * @return array Liste des plugins
     */
    public function getItems($sourceName)
    {
        $result = array();
        $repositories = $this->getRepositoriesList();
        $ignoreList = $this->getIgnoreList();
        foreach ($repositories as $repository) {
            if (!\in_array($repository['name'], $ignoreList)) {
                $marketItem = AmfjMarketItem::createFromCache($sourceName, $repository['full_name']);
                array_push($result, $marketItem);
            }
        }
        return $result;
    }
}