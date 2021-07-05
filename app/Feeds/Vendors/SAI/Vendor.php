<?php

namespace App\Feeds\Vendors\SAI;

use App\Feeds\Feed\FeedItem;
use App\Feeds\Processor\HttpProcessor;

class Vendor extends HttpProcessor
{
    public const CATEGORY_LINK_CSS_SELECTORS = [ 'div#emthemesModez-verticalCategories-sidebar ul li.navPages-item' ];
    public const PRODUCT_LINK_CSS_SELECTORS = [ 'div.emthemesModez-productsByCategoryTabs-products ul li.product' ];

    protected array $first = [ 'https://www.affinitechstore.com/store' ];

    public function isValidFeedItem( FeedItem $fi ): bool
    {
        return !empty( $fi->getMpn() );
    }
}