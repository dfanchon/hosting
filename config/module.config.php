<?php
return array(

   //routing
    'router' => array(
        'routes' => array(
            'hosting' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/hosting',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Hosting\\Controller',
                        'controller' => 'Index',
                        'action' => 'index'
                    )
                ),
                'may_terminate' => true
            )
        )
    ),
    'controllers' => array(
        'invokables' => array(
            'Hosting\\Controller\\Index' => 'Hosting\\Controller\\IndexController'
        )
    ),        
    'service_manager' => array(
        'invokables' => array(
            'Glups' => 'Hosting\\Service\\Glups'
        )
    )
);
