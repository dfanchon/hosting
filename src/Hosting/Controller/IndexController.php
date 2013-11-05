<?php
namespace Hosting\Controller;

use Rubedo\Services\Manager;
use Rubedo\Blocks\Controller\AbstractController;

class IndexController extends AbstractController
{
       
    function indexAction ()
    {
       $config =  Manager::getService('AutomatedInstall');
       print_r($config);
    }
}