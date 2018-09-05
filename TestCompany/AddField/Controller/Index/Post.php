<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TestCompany\AddField\Controller\Index;

use Magento\Contact\Model\ConfigInterface;
use Magento\Contact\Model\MailInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class Post extends \Magento\Contact\Controller\Index
{

    /**
     *
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     *
     * @var Context
     */
    private $context;

    /**
     *
     * @var MailInterface
     */
    private $mail;

    /**
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @var UploaderFactory
     */
    private $uploaderFactory;

    /**
     *
     * @var Filesystem
     */
    private $fileSystem;

    /**
     *
     * @param Context $context
     * @param ConfigInterface $contactsConfig
     * @param MailInterface $mail
     * @param DataPersistorInterface $dataPersistor
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context, 
        ConfigInterface $contactsConfig, 
        MailInterface $mail, 
        DataPersistorInterface $dataPersistor, 
        LoggerInterface $logger = null, 
        Filesystem $fileSystem, 
        UploaderFactory $uploaderFactory
    ) {
        parent::__construct($context, $contactsConfig);
        $this->context = $context;
        $this->mail = $mail;
        $this->dataPersistor = $dataPersistor;
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
        $this->fileSystem = $fileSystem;
        $this->uploaderFactory = $uploaderFactory;
    }

    /**
     * Post user question
     *
     * @return Redirect
     */
    public function execute()
    {
        if (! $this->isPostRequest()) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }
        try {
            $this->sendEmail($this->validatedParams());
            $this->messageManager->addSuccessMessage(__('Thanks for contacting us with your comments and questions. We\'ll respond to you very soon.'));
            $this->dataPersistor->clear('contact_us');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->dataPersistor->set('contact_us', $this->getRequest()
                ->getParams());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage(__('An error occurred while processing your form. Please try again later.'));
            $this->dataPersistor->set('contact_us', $this->getRequest()
                ->getParams());
        }
        return $this->resultRedirectFactory->create()->setPath('contact/index');
    }

    /**
     *
     * @param array $post
     *            Post data from contact form
     * @return void
     */
    private function sendEmail($post)
    {
        $this->mail->send($post['email'], [
            'data' => new DataObject($post)
        ]);
    }

    /**
     *
     * @return bool
     */
    private function isPostRequest()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        return ! empty($request->getPostValue());
    }

    /**
     *
     * @return array
     * @throws \Exception
     */
    private function validatedParams()
    {
        $request = $this->getRequest();
        $attachmentInfo = $this->uploadFile();
        if (trim($request->getParam('name')) === '') {
            throw new LocalizedException(__('Name is missing'));
        }
        if (trim($request->getParam('comment')) === '') {
            throw new LocalizedException(__('Comment is missing'));
        }
        if (false === \strpos($request->getParam('email'), '@')) {
            throw new LocalizedException(__('Invalid email address'));
        }
        
        if (($attachmentInfo['fileSize'])&&($attachmentInfo['fileSize'] / 1024 / 1024 > 3)) {
            throw new LocalizedException(__('Image size is missing'));
        }
        if ((!empty($attachmentInfo['fileType']))&&(!in_array($attachmentInfo['fileType'], array('image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/bmp')))) {
            throw new LocalizedException(__('Image type is missing'));
        }
        if (trim($request->getParam('hideit')) !== '') {
            throw new \Exception();
        }
        return array_merge($request->getParams(), $attachmentInfo);
    }

    /**
     *
     * @return array
     * @throws \Exception
     */
    private function uploadFile()
    {      
        $filesData = $this->getRequest()->getFiles('picture');
        if (!empty($filesData['name'])) {
            $uploader = $this->uploaderFactory->create([
                'fileId' => 'picture'
            ]);
            try {
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(true);
                $uploader->setAllowCreateFolders(true);
                $path = $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath('picture');
                $result = $uploader->save($path);
                $attachmentInfo = array(
                    'filePath' => $result['path'] . $result['file'],
                    'fileName' => $result['name'],
                    'fileSize' => $result['size'],
                    'fileType' => $result['type']
                );
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        } else {
            $attachmentInfo = array(
                'filePath' => '',
                'fileName' => '',
                'fileSize' => 0,
                'fileType' => ''
            );
        }
        return $attachmentInfo;
    }
}
