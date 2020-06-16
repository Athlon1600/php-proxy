<?php

namespace Proxy\Plugin;

use Proxy\Config;
use Proxy\Event\ProxyEvent;

class CorsPlugin extends AbstractPlugin
{
    public function onBeforeRequest(ProxyEvent $event){

        $request = $event['request'];

        $urlParts = parse_url( $request->getUri() );

        $url = $urlParts['scheme'] . '://' . $urlParts['host'];

        $request->headers->set( 'Access-Control-Allow-Origin', '*' );
        $request->headers->set( 'Access-Control-Allow-Credentials', 'true' );
        $request->headers->set( 'Access-Control-Allow-Methods', 'GET, POST, OPTIONS' );
        $request->headers->set( 'Access-Control-Allow-Headers', 'DNT,Origin,X-CustomHeader,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type' );

        $request->headers->set( 'Origin', $url, true );

        if ( !Config::get( 'no_referer' ) )
        {
            $request->headers->set( 'Referer', $url, true );
        }
    }
}