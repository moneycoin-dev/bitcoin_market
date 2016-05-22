<?php

require __DIR__ . '/../vendor/autoload.php';
    
$configurator = new Nette\Configurator;

//$configurator->setDebugMode('23.75.345.200'); // enable for your remote IP
$configurator->enableDebugger(__DIR__ . '/../log');

$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$container = $configurator->createContainer();

//resolves session issue when we are running from CLI
if(!$container->parameters['consoleMode']){
    $requestFatory = new Nette\Http\RequestFactory;
    $request = $requestFatory->createHttpRequest();
    $response = new Nette\Http\Response;
    $session = new Nette\Http\Session($request, $response);

    captcha\Captcha\CaptchaControl::register($session);
}

return $container;
