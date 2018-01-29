<?php
namespace Proxy\Plugin;

use Proxy\Event\ProxyEvent;

abstract class AbstractPlugin
{
    public function onBeforeRequest(ProxyEvent $event)
    {
        // fired right before a request is being sent to a proxy
    }

    public function onHeadersReceived(ProxyEvent $event)
    {
        // fired right after response headers have been fully received - last chance to modify before sending it back to the user
    }

    public function onCurlWrite(ProxyEvent $event)
    {
        // fired as the data is being written piece by piece
    }

    public function onCompleted(ProxyEvent $event)
    {
        // fired after the full response=headers+body has been read - will only be called on "non-streaming" responses
    }

    final public function subscribe($dispatcher)
    {
        $event_listeners = [
            'request.before_send' => 'onBeforeRequest',
            'request.sent' => 'onHeadersReceived',
            'curl.callback.write' => 'onCurlWrite',
            'request.complete' => 'onCompleted',
        ];

        foreach ($event_listeners as $event => $listener) {
            $dispatcher->addListener($event, [$this, $listener]);
        }
    }
}
