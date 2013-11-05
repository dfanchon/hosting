<?php
namespace Hosting\Service;
use Rubedo\Update\Install;
use Rubedo\Collection\AbstractCollection;

class AutomatedInstall extends Install
{

    public function __construct ()
    {
              
        \Rubedo\User\CurrentUser::setIsInstallerUser(true);
        AbstractCollection::disableUserFilter();
        
        $this->installObject = new \Rubedo\Update\Install();
        if (! $this->installObject->isConfigWritable()) {
            throw new \Rubedo\Exceptions\User('Local config file %1$s should be writable', "Exception29", $this->localConfigFile);
        }

        $this->installObject->loadLocalConfig();
        $this->config = $this->installObject->getLocalConfig();
        if (! isset($this->config['installed'])) {
            $this->config['installed'] = array();
        }

    }
    
 
}