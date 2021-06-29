<?php

namespace App\Feeds\Vendors\PSA;

use App\Feeds\Feed\FeedItem;
use App\Feeds\Processor\SitemapHttpProcessor;
use App\Feeds\Utils\Link;

class Vendor extends SitemapHttpProcessor
{
    public const CATEGORY_LINK_CSS_SELECTORS = [ '.collection-list.product a'];

    public array $first = [ 'https://myplushusa.com/sitemap.xml' ];

    public function filterProductLinks( Link $link ): bool
    {
        return str_contains( $link->getUrl(), '/collections/retail' );
    }

}
