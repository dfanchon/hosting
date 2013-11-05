<?php
namespace Hosting\Account\Controller;

use Rubedo\Services\Manager;
use Rubedo\Blocks\Controller\AbstractController;
use Rubedo\Mongo\DataAccess;
use Rubedo\Collection\AbstractCollection;
use WebTales\MongoFilters;

class IndexController extends AbstractController
{
       
    function createAction ()
    {

       $newDB = "rubedo999";
       $newHost = "rubedo999.local"; 
       $adminName = "admin";
       $adminEmail = "admin@webtales.fr";
       $adminLogin = "admin";
       $adminPassword = "admin";
       $defaultLocale = "fr";
        
       $dataService = Manager::getService('MongoDataAccess');
       $dataService::setDefaultDb($newDB);
        
       $installService = Manager::getService('AutomatedInstall');
       if (isset( $installService->config['datastream']['mongo']['db'][$newHost])) {
           throw new \Rubedo\Exceptions\Server('Host ' . $newHost . ' already exists in the configuration file');
       }
            
       // Set the default language
       $installService->importLanguages();
       $installService->setDefaultRubedoLanguage($defaultLocale);
       
       // Ensure Indexes
       $process = "Ensure Indexes Error";
       Manager::getService('UrlCache')->drop();
       Manager::getService('Cache')->drop();
       $servicesArray = \Rubedo\Interfaces\config::getCollectionServices();
       $success = true;
       foreach ($servicesArray as $service) {
           if (! Manager::getService($service)->checkIndexes()) {
               $success = $success && Manager::getService($service)->ensureIndexes();
           }
       }
       if (!$success) {
           throw new \Rubedo\Exceptions\Server('Host creation process stopped during process : '.$process);        
       }    
       
       // Initialize contents
       $process = "Initialize contents";
        \Rubedo\Internationalization\Translate::setDefaultLanguage($defaultLocale);
        
        $translateService = Manager::getService('Translate');
        
        $success = true;
        
        $contentPath = APPLICATION_PATH . '/data/default/';
        $contentIterator = new \DirectoryIterator($contentPath);
        foreach ($contentIterator as $directory) {
            if ($directory->isDot() || ! $directory->isDir()) {
                continue;
            }
            if (in_array($directory->getFilename(), array(
                'groups',
                'site'
            ))) {
                continue;
            }
            $collection = ucfirst($directory->getFilename());
            $collectionService = Manager::getService($collection);
            $isLocalizable = $collectionService instanceof Rubedo\Collection\AbstractLocalizableCollection;
            $itemsJson = new \DirectoryIterator($contentPath . '/' . $directory->getFilename());
            foreach ($itemsJson as $file) {
                if ($file->isDot() || $file->isDir()) {
                    continue;
                }
                if ($file->getExtension() == 'json') {
                    $itemJson = file_get_contents($file->getPathname());
                    $itemJson = preg_replace_callback('/###(.*)###/U', array(
                        'Rubedo\\Update\\Install',
                        'replaceWithTranslation'
                    ), $itemJson);               
                    
                    $item = json_decode($itemJson, True);
                    
                    try {
                        if (! $collectionService->findOne(\WebTales\MongoFilters\Filter::factory('Value')->setName('defaultId')
                            ->setValue($item['defaultId']))) {
                            $result = $collectionService->create($item);
                        } else {
                            $result['success'] = true;
                        }
                    } catch (\Rubedo\Exceptions\User $exception) {
                        $result['success'] = true;
                    }
                    
                    $success = $result['success'] && $success;
                }
            }
        }
        if (!$success) {
            throw new \Rubedo\Exceptions\Server('Host creation process stopped during process : '.$process);
        }
              
       // Initialize default groups
        $process = "Initialize default groups";
       \Rubedo\Internationalization\Translate::setDefaultLanguage($defaultLocale);

       try {
            $adminWorkspaceId = Manager::getService('Workspaces')->getAdminWorkspaceId();
            if (! $adminWorkspaceId) {
                Manager::getService('Workspaces')->create(array(
                    'text' => Manager::getService('Translate')->translate("Workspace.admin", 'admin'),
                    'nativeLanguage' => $defaultLocale
                ));
            }
        } catch (Rubedo\Exceptions\User $exception) {
            // dont
            // stop
            // if
            // already
            // exists
        }
        $adminWorkspaceId = Manager::getService('Workspaces')->getAdminWorkspaceId();
        
        $success = true;
        $groupsJsonPath = APPLICATION_PATH . '/data/default/groups';
        $groupsJson = new \DirectoryIterator($groupsJsonPath);
        foreach ($groupsJson as $file) {
            if ($file->isDot() || $file->isDir()) {
                continue;
            }
            if ($file->getExtension() == 'json') {
                $itemJson = file_get_contents($file->getPathname());
                
                $itemJson = preg_replace_callback('/###(.*)###/U', array(
                    'Rubedo\\Update\\Install',
                    'replaceWithTranslation'
                ), $itemJson);
                
                $item = json_decode($itemJson, True);
                
                if ($item['name'] == 'admin') {
                    $item['workspace'] = $adminWorkspaceId;
                    $item['inheritWorkspace'] = false;
                }
                $result = Manager::getService('Groups')->create($item);
                $success = $result['success'] && $success;
            }
        }
        
        // Admin User
        $process = "Set Admin User";
        $params=array(
                "name" => $adminName,
                "login" => $adminLogin,
                "password" => $adminPassword,
                "email" => $adminEmail
        );
        
        $hashService = \Rubedo\Services\Manager::getService('Hash');
               
        $params['salt'] = $hashService->generateRandomString();
        $params['password'] = $hashService->derivatePassword($params['password'], $params['salt']);
        $adminGroup = Manager::getService('Groups')->findByName('admin');
        $params['defaultGroup'] = $adminGroup['id'];
        $filters=\WebTales\MongoFilters\Filter::factory();
        $filters->addFilter(\WebTales\MongoFilters\Filter::factory('Value')->setName('UTType')
                ->setValue("default"));
        $defaultUserType=Manager::getService("UserTypes")->findOne($filters);
        $params['typeId']=$defaultUserType["id"];
        $params['taxonomy']=array();
        $params['fields']=array();
        $wasFiltered = AbstractCollection::disableUserFilter();
        $userService = Manager::getService('Users');
        $response = $userService->create($params);
        $result = $response['success'];
        if (! $result) {
           throw new \Rubedo\Exceptions\Server('Host creation process stopped during process : '.$process);        
        } else {
            $userId = $response['data']['id'];
        
            $groupService = Manager::getService('Groups');
            $adminGroup['members'][] = $userId;
            $groupService->update($adminGroup);
        }       
       
        // Write & save config
        $installService->config['datastream']['mongo']['db'][$newHost]=$newDB;
        $installService->installObject->saveLocalConfig($installService->config);
          
    }
}