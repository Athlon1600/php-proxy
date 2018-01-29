<?php
namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

class ProxifyPlugin extends AbstractPlugin
{
    private const CONTENT_TYPE_BLACKLIST = ['image', 'font', 'application/javascript', 'application/x-javascript', 'text/javascript', 'text/plain'];
    private const LINK_TYPE_BLACKLIST = ['data:', 'magnet:', 'about:', 'javascript:', 'mailto:', 'tel:', 'ios-app:', 'android-app:'];
    private const CONTENT_PARSERS = [
        '@\bcontent=(?<quote>\'|")\d+\s*;\s*url=(?<url>.*?)\k<quote>@is' => 'self::proxifyUrlCallback',           // content="X;url=<url>" (meta-refresh)
        '@\b(?:src|href)\s*=\s*(?<quote>\'|")(?<url>.*?)\k<quote>@is' => 'self::proxifyUrlCallback',              // src="<url>" & href="<url>"
        '@[^a-z]{1}url\s*\((?<delim>\'|"|)(?<url>[^\)]*)\k<delim>\)@im' => 'self::proxifyUrlCallback',            // url(<url>)
        '@\@import\s+(?<quote>\'|")(?<url>.*?)\k<quote>@im' => 'self::proxifyUrlCallback',                        // @import '<url>'
        '@\b(?:srcset)\s*=\s*(?<quote>\'|")(?<value>.*?)\k<quote>@im' => 'self::proxifySrcsetAttributeCallback',  // srcset="<url> xxx, …"
        '@<\s*form[^>]*action=(?<quote>\'|")(?<url>.*?)\k<quote>[^>]*>@im' => 'self::proxifyFormCallback',        // <form action="<url>" …>
    ];

    private $base_url = '';

    public function onCompleted(ProxyEvent $event)
    {
        $response = $event['response'];
        $content_type = $response->headers->get('content-type');
        if (starts_with($content_type, self::CONTENT_TYPE_BLACKLIST)) {
            return;
        }

        // to be used when proxifying all the relative links
        $this->base_url = $event['request']->getUri();
        $proxified_content = preg_replace_callback_array(self::CONTENT_PARSERS, $response->getContent());
        $response->setContent($proxified_content);
    }

    public function onBeforeRequest(ProxyEvent $event)
    {
        $request = $event['request'];
        $this->convertPostToGet($request);
    }

    private function convertPostToGet($request)
    {
        if (!$request->post->has('convertGET')) {
            return;
        }

        $request->get->replace($request->post->all()); // Change POST data to GET data
        $request->post->clear();                       // Remove POST data
        $request->setMethod('GET');                    // This is now a GET request
        $request->prepare();
    }

    private function proxifyFormCallback($matches)
    {
        $full_capture = $this->proxifyUrlCallback($matches);

        // If the form method is not post, inject method="post" and add a hidden input field called "convertGET"
        $full_capture = preg_replace('@(<\s*form\s*)((?:(?!method=(\'|")post\3)[^>])*>)@i', '$1 method="post" $2<input type="hidden" name="convertGET" value="1">', $full_capture);
        return $full_capture;
    }

    private function proxifySrcsetAttributeCallback($matches)
    {
        $attribute = $matches[0];
        $value = $matches['value'];
        $srcset_url_pattern = "@(?:\s*(?<url>[^\s,]*)(?:\s*(?:,|\S*)))@im";
        $proxified_value = preg_replace_callback($srcset_url_pattern, array($this, 'proxifyUrlCallback'), $value);
        return str_replace($value, $proxified_value, $attribute);
    }

    private function proxifyUrlCallback($matches)
    {
        $full_capture = $matches[0];
        if (!($url = $matches['url'] ?? null) || starts_with($url, self::LINK_TYPE_BLACKLIST)) {
            return $full_capture;
        }

        $proxified_url = proxify_url($url, $this->base_url);
        return str_replace($url, $proxified_url, $full_capture);
    }
}
