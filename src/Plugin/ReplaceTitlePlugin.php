<?php
namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;
use Proxy\Config;

class ReplaceTitlePlugin extends AbstractPlugin
{
    public function onCompleted(ProxyEvent $event)
    {
        $response = $event['response'];
        $content_type = $response->headers->get('content-type');
        if (strpos($content_type, 'text/html') === false) {
            return;
        }

        if (Config::get('replace_title')) {
            $content = $response->getContent();
            $content = preg_replace('/<title[^>]*>(.*?)<\/title>/is', '<title>' . Config::get('replace_title') . '</title>', $content);
            $response->setContent($content);
        }
    }
}
