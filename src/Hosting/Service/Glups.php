<?php
namespace Hosting\Service;
use Rubedo\Update\Install;

class Glups extends Install
{

    protected $installObject;

    public function __construct ()
    {
        
        parent::__construct();
        
        \Rubedo\User\CurrentUser::setIsInstallerUser(true);

        $this->installObject = new \Rubedo\Update\Install();
        if (! $this->installObject->isConfigWritable()) {
            throw new \Rubedo\Exceptions\User('Local config file %1$s should be writable', "Exception29", $this->localConfigFile);
        }

        $this->installObject->loadLocalConfig();
        $this->config = $this->installObject->getLocalConfig();
        if (! isset($this->config['installed'])) {
            $this->config['installed'] = array();
        }

        return $this->config;

    }
}