<?php
namespace TestCompany\AddField\Model\Mail;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{

    public function __construct(
        FactoryInterface $templateFactory, 
        MessageInterface $message, 
        SenderResolverInterface $senderResolver, 
        ObjectManagerInterface $objectManager, 
        TransportInterfaceFactory $mailTransportFactory
    ) {
        parent::__construct(
            $templateFactory, 
            $message, 
            $senderResolver, 
            $objectManager, 
            $mailTransportFactory
        );
    }

    public function addAttachment($file, $filename)
    {
        $this->message->createAttachment(
            file_get_contents($file), 
            \Zend_Mime::TYPE_OCTETSTREAM, 
            \Zend_Mime::DISPOSITION_ATTACHMENT, 
            \Zend_Mime::ENCODING_BASE64, 
            $filename
         );
        return $this;
    }
}