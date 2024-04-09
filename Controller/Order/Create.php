<?php
/**
 * @author MageRocket
 * @copyright Copyright (c) 2024 MageRocket (https://magerocket.com/)
 * @link https://magerocket.com/
 */

namespace MageRocket\GoCuotas\Controller\Order;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use MageRocket\GoCuotas\Helper\Data;
use MageRocket\GoCuotas\Model\GoCuotas;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class Create implements ActionInterface
{
    /**
     * @var Data $helper
     */
    protected $helper;

    /**
     * @var Session $session
     */
    protected $session;

    /**
     * @var GoCuotas $goCuotas
     */
    protected $goCuotas;

    /**
     * @var JsonFactory $jsonFactory
     */
    protected $jsonFactory;

    /**
     * Init Construct
     *
     * @param Data $helper
     * @param Session $session
     * @param GoCuotas $goCuotas
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Data $helper,
        Session $session,
        GoCuotas $goCuotas,
        JsonFactory $jsonFactory
    ) {
        $this->helper = $helper;
        $this->session = $session;
        $this->goCuotas = $goCuotas;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * Execute
     *
     * @return ResponseInterface|Json|ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $order = $this->session->getLastRealOrder();
        $url = $this->helper->getCallBackUrl();
        $paymentJson = ['error' => true, 'failure_url' => $url];
        try {
            $response = $this->goCuotas->createTransaction($order);
            $this->goCuotas->saveTransaction($order);
            $url = $this->replaceURL($response['url_init']);
            // Generate Token Cancel
            $tokenCancel = $this->helper->generateToken($order, 'cancel');
            $cancelUrl = $this->helper->getCallBackUrl(['token' => $tokenCancel]);
            $paymentJson = ['error' => false, 'init_url' => $url, 'cancel_url' => $cancelUrl];
        } catch (\Exception $e) {
            $this->helper->log('ERROR Create Preference: ' . $e->getMessage());
        } finally {
            $resultJson = $this->jsonFactory->create();
            return $resultJson->setData($paymentJson);
        }
    }

    /**
     * ReplaceURL
     *
     * Replace GoCuotas URL
     *
     * @param string $url
     * @return string
     */
    private function replaceURL(string $url): string
    {
        return preg_replace("/www\./", "api-magento.", $url);
    }
}
