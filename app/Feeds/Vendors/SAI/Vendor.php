<?php

namespace App\Feeds\Vendors\SAI;

use App\Feeds\Feed\FeedItem;
use App\Feeds\Processor\HttpProcessor;


class Vendor extends HttpProcessor
{
    public const CATEGORY_LINK_CSS_SELECTORS = [ 'div#emthemesModez-verticalCategories ul li.navPages-item a' ];
    public const PRODUCT_LINK_CSS_SELECTORS = [ 'ul li a' ];

    protected array $first = [ 'https://www.affinitechstore.com' ];

    public function isValidFeedItem( FeedItem $fi ): bool
    {
        return !empty( $fi->getMpn() );
    }

}