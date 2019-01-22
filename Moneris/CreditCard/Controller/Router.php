<?php
namespace Moneris\CreditCard\Controller;

class Router implements \Magento\Framework\App\RouterInterface
{
    /**
     * @var \Magento\Framework\App\ActionFactory
     */
    private $actionFactory;

    /**
     * Response
     *
     * @var \Magento\Framework\App\ResponseInterface
     */
    private $response;

    /**
     * Event manager
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var \Magento\Framework\DataObject
     */
    private $dataObject;

    /**
     * @param \Magento\Framework\App\ActionFactory $actionFactory
     * @param \Magento\Framework\App\ResponseInterface $response
     */
    public function __construct(
        \Magento\Framework\App\ActionFactory $actionFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\Framework\DataObject $dataObject
    ) {
        $this->actionFactory = $actionFactory;
        $this->eventManager = $eventManager;
        $this->response = $response;
        $this->dataObject = $dataObject;
    }

    /**
     * Validate and Match
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return \Magento\Framework\App\ActionInterface|null
     */
    public function match(\Magento\Framework\App\RequestInterface $request)
    {
        $_identifier = trim($request->getPathInfo(), '/');
        $pathInfo = explode('/', $_identifier);
        $identifier = implode('/', $pathInfo);

        $this->dataObject->setData('identifier', $identifier);
        $this->dataObject->setData('continue', true);
        $this->eventManager->dispatch(
            'collinsHarper_moneris_controller_router_match_before',
            ['router' => $this, 'condition' => $this->dataObject]
        );

        if ($this->dataObject->getRedirectUrl()) {
            $this->response->setRedirect($this->dataObject->getRedirectUrl());
            $request->setDispatched(true);
            return $this->actionFactory->create(
                'Magento\Framework\App\Action\Redirect',
                ['request' => $request]
            );
        }
        
        if (!$this->dataObject->getContinue()) {
            return null;
        }
        
        $identifier = $this->dataObject->getIdentifier();
        $info = explode('/', $identifier);
        
        if ($identifier && !empty($info) && count($info)==2 && strpos($identifier, 'interac') !== false) {
            $controller = $info[0];
            $action = $info[1];
            
            $request->setModuleName('moneriscc')->setControllerName($controller)->setActionName($action);
            $request->setAlias(\Magento\Framework\Url::REWRITE_REQUEST_PATH_ALIAS, $identifier);
        } else {
            return null;
        }
        
        return $this->actionFactory->create(
            'Magento\Framework\App\Action\Forward',
            ['request' => $request]
        );
    }
}
