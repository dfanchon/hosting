<?php
namespace Hosting\Controller;

use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class IndexController extends AbstractActionController
{
    function indexAction ()
    {
        echo "ok";
    }
}