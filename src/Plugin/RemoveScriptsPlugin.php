<?php
namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;
use Proxy\Config;

class RemoveScriptsPlugin extends AbstractPlugin
{
    public function onCompleted(ProxyEvent $event)
    {
        $uri = $event['request']->getUri();
        $response = $event['response'];
        $content_type = $response->headers->get('content-type');
        if (strpos($content_type, 'text/html') === false) {
            return;
        }

        $url_host = parse_url($uri, PHP_URL_HOST);

        // remove JS from urls
        $js_remove = (array)Config::get('js_remove');
        foreach ($js_remove as $pattern) {
            if (strpos($url_host, $pattern) !== false) {
                $content = $response->getContent();
                $content = preg_replace('/<\s*script[^>]*>(.*?)<\s*\/\s*script\s*>/is', '', $content);
                $response->setContent($content);
            }
        }
    }
}
