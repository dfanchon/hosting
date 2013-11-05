<?php
return array(

   //routing
    'router' => array(
        'routes' => array(
            'hosting' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/hosting/create',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Hosting\\Account\\Controller',
                        'controller' => 'Index',
                        'action' => 'create'
                    )
                ),
                'may_terminate' => true
            )
        )
    ),
    'controllers' => array(
        'invokables' => array(
            'Hosting\\Account\\Controller\\Index' => 'Hosting\\Account\\Controller\\IndexController'
        )
    ),        
    'service_manager' => array(
        'invokables' => array(
            'AutomatedInstall' => 'Hosting\\Service\\AutomatedInstall'
        )
    )
);
