<?php

namespace Kunstmaan\MediaBundle\Helper\RemoteSlide;

use Kunstmaan\MediaBundle\Form\RemoteSlide\RemoteSlideType;

use Kunstmaan\MediaBundle\Helper\Media\AbstractMediaHandler;

use Kunstmaan\MediaBundle\Entity\Media;

use Kunstmaan\MediaBundle\Helper\StrategyInterface;

use Kunstmaan\MediaBundle\Entity\Folder;

use Doctrine\ORM\EntityManager;
use Kunstmaan\MediaBundle\Entity\VideoGallery;
use Kunstmaan\MediaBundle\Form\VideoType;
use Kunstmaan\MediaBundle\AdminList\VideoListConfigurator;
use Kunstmaan\MediaBundle\Entity\Video;

/**
 * RemoteSlideStrategy
 */
class RemoteSlideHandler extends AbstractMediaHandler
{

    /**
     * @var string
     */
    const CONTENT_TYPE = "remote/slide";

    const TYPE = 'slide';

    /**
     * @return string
     */
    public function getName()
    {
        return "Remote Slide Handler";
    }

    /**
     * @return string
     */
    public function getType()
    {
        return RemoteSlideHandler::TYPE;
    }

    /**
     * @return \Kunstmaan\MediaBundle\Form\VideoType
     */
    public function getFormType()
    {
        return new RemoteSlideType();
    }

    /**
     * @param Media $media
     *
     * @return bool
     */
    public function canHandle(Media $media)
    {
        if ($media->getContentType() == RemoteSlideHandler::CONTENT_TYPE) {
            return true;
        }

        return false;
    }

    /**
     * @param Media $media
     *
     * @return Video
     */
    public function getFormHelper(Media $media)
    {
        return new RemoteSlideHelper($media);
    }

    /**
     * @param Media $media
     *
     * @throws \RuntimeException when the file does not exist
     */
    public function prepareMedia(Media $media)
    {
        if (null == $media->getUuid()) {
            $uuid = uniqid();
            $media->setUuid($uuid);
        }
        $slide = new RemoteSlideHelper($media);
        $code = $slide->getCode();
        //update thumbnail
        switch ($slide->getType()) {
            case 'slideshare':
                $json = json_decode(file_get_contents('http://www.slideshare.net/api/oembed/2?url='.$code.'&format=json'));
                $slide->setThumbnailUrl('http:'.$json->thumbnail);
                break;
        }
    }

    /**
     * @param Media $media
     */
    public function saveMedia(Media $media)
    {
    }

    /**
     * @param Media $media
     */
    public function removeMedia(Media $media)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function updateMedia(Media $media)
    {

    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function getAddUrlFor(array $params = array())
    {
        return array(
                'slide' => array(
                        'path'   => 'KunstmaanMediaBundle_folder_slidecreate',
                        'params' => array(
                                'folderId' => $params['folderId']
                        )
                )
        );
    }

    /**
     * @param mixed $data
     *
     * @return Media
     */
    public function createNew($data)
    {
       $result = null;
        if (is_string($data)) {
            $parsedUrl = parse_url($data);
            switch($parsedUrl['host']) {
                case 'www.slideshare.net':
                case 'slideshare.net':
                    $result = new Media();
                    $slide = new RemoteSlideHelper($result);
                    $slide->setType('slideshare');
                    $slide->setCode($data);
                    $result = $slide->getMedia();
                    $result->setName('SlideShare ' . $data);
                    break;
            }
        }

        return $result;
    }

    /**
     * @param Media  $media    The media entity
     * @param string $basepath The base path
     * @param int    $width    The prefered width of the thumbnail
     * @param int    $height   The prefered height of the thumbnail
     *
     * @return string
     */
    public function getThumbnailUrl(Media $media, $basepath, $width = -1, $height = -1)
    {
        $helper = new RemoteSlideHelper($media);

        return $helper->getThumbnailUrl();
    }

    /**
     * @return multitype:string
     */
    public function getAddFolderActions()
    {
        return array(
                RemoteSlideHandler::TYPE => array(
                    'type' => RemoteSlideHandler::TYPE,
                    'name' => 'media.slide.add')
                );
    }
}