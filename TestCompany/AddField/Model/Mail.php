<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TestCompany\AddField\Model;

use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Area;
use TestCompany\AddField\Model\MailInterface;
use TestCompany\AddField\Model\ConfigInterface;
use TestCompany\AddField\Model\Mail\TransportBuilder;

class Mail implements MailInterface
{

    /**
     *
     * @var ConfigInterface
     */
    private $contactsConfig;

    /**
     *
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     *
     * @var StateInterface
     */
    private $inlineTranslation;

    /**
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Initialize dependencies.
     *
     * @param ConfigInterface $contactsConfig
     * @param \TestCompany\AddField\Model\Mail\TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param StoreManagerInterface|null $storeManager
     */
    public function __construct(
        ConfigInterface $contactsConfig, 
        TransportBuilder $transportBuilder, 
        StateInterface $inlineTranslation, 
        StoreManagerInterface $storeManager = null
    ) {
        $this->contactsConfig = $contactsConfig;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
    }

    /**
     * Send email from contact form
     *
     * @param string $replyTo
     * @param array $variables
     * @return void
     */
    public function send($replyTo, array $variables)
    {
        /**
         *
         * @see \Magento\Contact\Controller\Index\Post::validatedParams()
         */
        $replyToName = ! empty($variables['data']['name']) ? $variables['data']['name'] : null;
        $this->inlineTranslation->suspend();
        try {
            $transport = $this->transportBuilder->setTemplateIdentifier($this->contactsConfig->emailTemplate())
                ->setTemplateOptions([
                'area' => Area::AREA_FRONTEND,
                'store' => $this->storeManager->getStore()
                    ->getId()
            ])
                ->setTemplateVars($variables)
                ->setFrom($this->contactsConfig->emailSender())
                ->addTo($this->contactsConfig->emailRecipient());
                if ((!empty($variables['data']['filePath']))&&(!empty($variables['data']['fileName']))) {
                    $transport = $transport->addAttachment($variables['data']['filePath'], $variables['data']['fileName']);
                }
                $transport = $transport->setReplyTo($replyTo, $replyToName)
                ->getTransport();
            
            $transport->sendMessage();
        } finally {
            $this->inlineTranslation->resume();
        }
    }
}
